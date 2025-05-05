<?php
require_once '../database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

if ($action === 'view') {
    try {
        $planId = $_GET['plan_id'] ?? 0;
        
        // Get plan details including visits
        $query = "
            SELECT 
                fv.visit_id,
                fv.visit_date,
                fv.status,
                CONCAT(t.firstname, ' ', t.lastname) as technician_name,
                t.id as technician_id,
                fp.plan_type,
                fp.visit_frequency,
                s.service_name,
                CONCAT(u.firstname, ' ', u.lastname) as customer_name,
                u.email as customer_email,
                u.mobile_number as customer_phone,
                CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location
            FROM followup_visits fv
            JOIN followup_plan fp ON fv.followup_plan_id = fp.id
            JOIN appointments a ON fp.appointment_id = a.id
            JOIN users u ON a.user_id = u.id
            LEFT JOIN users t ON fv.technician_id = t.id
            JOIN services s ON a.service_id = s.service_id
            WHERE fp.id = ?
            ORDER BY fv.visit_date ASC";
            
        $stmt = $db->prepare($query);
        $stmt->execute([$planId]);
        $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get available technicians
        $techQuery = "SELECT id, firstname, lastname FROM users WHERE role = 'technician' AND status = 'active'";
        $techStmt = $db->prepare($techQuery);
        $techStmt->execute();
        $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'visits' => $visits,
            'technicians' => $technicians,
            'plan_details' => $visits[0] ?? null // First row contains all plan details
        ]);
    } catch (PDOException $e) {
        error_log("Error fetching recurrence plan details: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to fetch plan details']);
    }
} elseif ($action === 'fetch') {
    try {
        $query = "
            SELECT 
                fp.id AS id,
                fp.plan_type,
                fp.visit_frequency,
                u.id AS customer_id,
                CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
                u.email AS customer_email,
                u.mobile_number AS customer_phone,
                s.service_name,
                CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) AS location,
                t.id AS technician_id,
                CONCAT(t.firstname, ' ', t.lastname) AS technician_name,
                MIN(fv.visit_date) AS next_schedule
            FROM followup_plan fp
            INNER JOIN appointments a ON fp.appointment_id = a.id
            INNER JOIN users u ON a.user_id = u.id
            INNER JOIN services s ON a.service_id = s.service_id
            LEFT JOIN users t ON a.technician_id = t.id
            LEFT JOIN followup_visits fv ON fv.followup_plan_id = fp.id 
                AND fv.status = 'Scheduled' 
                AND fv.visit_date >= CURRENT_DATE
            GROUP BY 
                fp.id, fp.plan_type, fp.visit_frequency, 
                u.id, u.firstname, u.lastname, u.email, u.mobile_number,
                s.service_name, a.street_address, a.barangay, a.city,
                t.id, t.firstname, t.lastname
            ORDER BY next_schedule ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For debugging
        error_log("Fetched plans: " . print_r($plans, true));
        
        echo json_encode($plans);
    } catch (PDOException $e) {
        error_log("Error fetching recurrence plans: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch recurrence plans']);
    }
} elseif ($action === 'details') {
    $planId = $_GET['plan_id'] ?? null;
    if ($planId) {
        try {
            // Get plan details with customer and service information
            $planQuery = "
                SELECT 
                    fp.*,
                    CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
                    u.contact_number,
                    u.email,
                    s.service_name,
                    CONCAT(t.firstname, ' ', t.lastname) AS technician_name,
                    t.id AS technician_id,
                    a.location,
                    a.id AS appointment_id
                FROM followup_plan fp
                JOIN appointments a ON fp.appointment_id = a.id
                JOIN users u ON a.user_id = u.id
                LEFT JOIN users t ON a.technician_id = t.id
                LEFT JOIN services s ON a.service_id = s.service_id
                WHERE fp.id = ?
            ";
            $planStmt = $db->prepare($planQuery);
            $planStmt->execute([$planId]);
            $planDetails = $planStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get all scheduled visits
            $visitsQuery = "
                SELECT 
                    fv.id,
                    fv.visit_date,
                    fv.visit_time,
                    fv.status,
                    fv.notes,
                    CONCAT(t.firstname, ' ', t.lastname) AS technician_name
                FROM followup_visits fv
                LEFT JOIN users t ON fv.technician_id = t.id
                WHERE fv.followup_plan_id = ?
                ORDER BY fv.visit_date ASC, fv.visit_time ASC
            ";
            $visitsStmt = $db->prepare($visitsQuery);
            $visitsStmt->execute([$planId]);
            $visits = $visitsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get available technicians
            $techQuery = "
                SELECT id, firstname, lastname 
                FROM users 
                WHERE role = 'technician' AND status = 'verified'
                ORDER BY firstname, lastname
            ";
            $techStmt = $db->prepare($techQuery);
            $techStmt->execute();
            $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'plan' => $planDetails,
                'visits' => $visits,
                'technicians' => $technicians
            ]);
        } catch (PDOException $e) {
            error_log("Error fetching recurrence plan details: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to fetch recurrence plan details']);
        }
    } else {
        echo json_encode(['error' => 'No plan ID provided']);
    }
} else {
    echo json_encode([]);
}
?>
