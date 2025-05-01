<?php
session_start();
require_once '../database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Make sure transaction is not already active
        if ($db->inTransaction()) {
            $db->commit(); // Close any existing transaction first
        }
        
        // Start a fresh transaction
        $db->beginTransaction();
        
        // Get form data
        $appointmentId = $_POST['appointment_id'] ?? null;
        $serviceId = $_POST['service_id'] ?? null;
        $technicianIds = isset($_POST['technician_id']) ? (is_array($_POST['technician_id']) ? $_POST['technician_id'] : [$_POST['technician_id']]) : [];
        $followupDate = $_POST['followup_date'] ?? null;
        $followupTime = $_POST['followup_time'] ?? null;
        $planType = $_POST['plan_type'] ?? null;
        $visitFrequency = $_POST['visit_frequency'] ?? 1;
        $contractDuration = $_POST['contract_duration'] ?? 1;
        $durationUnit = $_POST['duration_unit'] ?? 'months';
        $notes = $_POST['notes'] ?? '';
        
        // Validate required fields
        if (!$serviceId || !$followupDate || !$followupTime || empty($technicianIds) || !$planType) {
            $_SESSION['followup_error'] = "Please fill in all required fields.";
            header("Location: dashboard-pct.php#schedule-followup");
            exit();
        }
        
        // Get the original appointment details to copy customer information
        $customerInfo = [];
        if ($appointmentId) {
            $stmt = $db->prepare("SELECT user_id, is_for_self, firstname, lastname, email, mobile_number, 
                                 street_address, landmark, barangay, city, province, region, latitude, longitude 
                                 FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            $customerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customerInfo) {
                $_SESSION['followup_error'] = "Original appointment not found.";
                header("Location: dashboard-pct.php#schedule-followup");
                exit();
            }
        } else {
            $_SESSION['followup_error'] = "Original appointment reference is required.";
            header("Location: dashboard-pct.php#schedule-followup");
            exit();
        }
        
        // Calculate follow-up dates based on plan type and frequency
        $followupDates = [];
        $startDate = new DateTime($followupDate);
        
        // Add the initial follow-up date
        $followupDates[] = $startDate->format('Y-m-d');
        
        // Calculate the interval based on plan type
        switch ($planType) {
            case 'weekly':
                $intervalUnit = 'week';
                break;
            case 'monthly':
                $intervalUnit = 'month';
                break;
            case 'quarterly':
                $intervalUnit = 'month';
                $visitFrequency = min($visitFrequency, 4); // Max 4 visits per year for quarterly
                $intervalValue = 3; // Every 3 months
                break;
            case 'yearly':
                $intervalUnit = 'year';
                break;
            default:
                $intervalUnit = 'week';
                break;
        }
        
        // Calculate the end date based on contract duration
        $endDate = clone $startDate;
        switch ($durationUnit) {
            case 'days':
                $endDate->modify("+$contractDuration days");
                break;
            case 'weeks':
                $endDate->modify("+$contractDuration weeks");
                break;
            case 'months':
                $endDate->modify("+$contractDuration months");
                break;
            case 'years':
                $endDate->modify("+$contractDuration years");
                break;
        }
        
        // Generate follow-up dates
        $currentDate = clone $startDate;
        $intervalValue = isset($intervalValue) ? $intervalValue : 1;
        
        for ($i = 1; $i < $visitFrequency; $i++) {
            $currentDate->modify("+$intervalValue $intervalUnit");
            
            // Only add date if it's before or equal to the end date
            if ($currentDate <= $endDate) {
                $followupDates[] = $currentDate->format('Y-m-d');
            } else {
                break; // Stop if we've passed the end date
            }
        }
        
        // First, create the follow-up plan record
        // Ensure tables exist before attempting to insert
        $createFollowupPlanTable = "CREATE TABLE IF NOT EXISTS `followup_plan` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `appointment_id` int(11) NOT NULL,
            `plan_type` enum('weekly','monthly','quarterly','yearly') NOT NULL,
            `visit_frequency` int(11) NOT NULL DEFAULT 1,
            `contract_duration` int(11) NOT NULL DEFAULT 1,
            `duration_unit` enum('days','weeks','months','years') NOT NULL DEFAULT 'months',
            `notes` text DEFAULT NULL,
            `created_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $db->exec($createFollowupPlanTable);
        
        $createFollowupVisitsTable = "CREATE TABLE IF NOT EXISTS `followup_visits` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `followup_plan_id` int(11) NOT NULL,
            `appointment_id` int(11) NOT NULL,
            `visit_date` date NOT NULL,
            `visit_time` time NOT NULL,
            `status` enum('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $db->exec($createFollowupVisitsTable);
        
        // Insert the plan
        $planStmt = $db->prepare("INSERT INTO followup_plan 
                                  (appointment_id, plan_type, visit_frequency, 
                                   contract_duration, duration_unit, notes, created_by, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $planStmt->execute([
            $appointmentId,
            $planType,
            $visitFrequency,
            $contractDuration,
            $durationUnit,
            $notes,
            $_SESSION['user_id']
        ]);
        $planId = $db->lastInsertId();
        
        // Now create the individual appointments for each follow-up date
        $appointmentIds = [];
        foreach ($followupDates as $date) {
            // Get the name of the service
            $serviceStmt = $db->prepare("SELECT service_name FROM services WHERE service_id = ?");
            $serviceStmt->execute([$serviceId]);
            $serviceName = $serviceStmt->fetchColumn();
            
            // Get the primary technician (first in the array)
            $primaryTechnicianId = $technicianIds[0];
            
            // Insert the appointment
            $stmt = $db->prepare("INSERT INTO appointments 
                                 (user_id, service_id, service_type, region, province, city, barangay, 
                                  street_address, landmark, appointment_date, appointment_time, status, 
                                  created_at, is_for_self, firstname, lastname, email, mobile_number, 
                                  technician_id, latitude, longitude, pest_concern) 
                                 VALUES 
                                 (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled', NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 'Follow-up Visit')");
            
            $stmt->execute([
                $customerInfo['user_id'],
                $serviceId,
                $serviceName,
                $customerInfo['region'] ?? null,
                $customerInfo['province'] ?? null,
                $customerInfo['city'] ?? null,
                $customerInfo['barangay'] ?? null,
                $customerInfo['street_address'] ?? null,
                $customerInfo['landmark'] ?? null,
                $date,
                $followupTime,
                $customerInfo['is_for_self'] ?? 1,
                $customerInfo['firstname'] ?? null,
                $customerInfo['lastname'] ?? null,
                $customerInfo['email'] ?? null,
                $customerInfo['mobile_number'] ?? null,
                $primaryTechnicianId,
                $customerInfo['latitude'] ?? null,
                $customerInfo['longitude'] ?? null
            ]);
            
            $newAppointmentId = $db->lastInsertId();
            $appointmentIds[] = $newAppointmentId;
            
            // Add additional technicians if there are more than one
            if (count($technicianIds) > 1) {
                foreach (array_slice($technicianIds, 1) as $techId) {
                    $techStmt = $db->prepare("INSERT INTO appointment_technicians 
                                             (appointment_id, technician_id, created_at) 
                                             VALUES (?, ?, NOW())");
                    $techStmt->execute([$newAppointmentId, $techId]);
                }
            }
            
            // Create a link in followup_visits table
            $visitStmt = $db->prepare("INSERT INTO followup_visits 
                                       (followup_plan_id, appointment_id, visit_date, visit_time, status, created_at) 
                                       VALUES (?, ?, ?, ?, 'Scheduled', NOW())");
            $visitStmt->execute([$planId, $newAppointmentId, $date, $followupTime]);
        }
        
        // Commit transaction
        if ($db->inTransaction()) {
            $db->commit();
        }
        
        $_SESSION['followup_success'] = "Follow-up plan created successfully with " . count($followupDates) . " scheduled visits.";
        header("Location: dashboard-pct.php#schedule-followup");
        exit();
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        $_SESSION['followup_error'] = "Error creating follow-up plan: " . $e->getMessage();
        error_log("Follow-up scheduling error: " . $e->getMessage());
        header("Location: dashboard-pct.php#schedule-followup");
        exit();
    }
} else {
    // Not a POST request
    header("Location: dashboard-pct.php#schedule-followup");
    exit();
}
?>
