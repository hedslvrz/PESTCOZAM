<?php
session_start();
require_once '../database.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log request data to help with debugging
error_log("Schedule Follow-up request received: " . print_r($_POST, true));

// Check if user is authorized
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin')) {
    error_log("Unauthorized access attempt. User ID: " . ($_SESSION['user_id'] ?? 'not set') . ", Role: " . ($_SESSION['role'] ?? 'not set'));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$originalAppointmentId = isset($_POST['appointment_id']) ? $_POST['appointment_id'] : '';
$serviceId = isset($_POST['service_id']) ? $_POST['service_id'] : '';
$technicianIdsStr = isset($_POST['technician_id']) ? $_POST['technician_id'] : '';
$followupDate = isset($_POST['followup_date']) ? $_POST['followup_date'] : '';
$followupTime = isset($_POST['followup_time']) ? $_POST['followup_time'] : '';

// Log received data for debug
error_log("Form data received: " . 
          "appointment_id=$originalAppointmentId, " . 
          "service_id=$serviceId, " . 
          "technician_id=$technicianIdsStr, " . 
          "followup_date=$followupDate, " . 
          "followup_time=$followupTime");

// Validate required fields
if (empty($originalAppointmentId) || empty($serviceId) || empty($technicianIdsStr) || empty($followupDate) || empty($followupTime)) {
    error_log("Validation failed: appointment_id='$originalAppointmentId', service_id='$serviceId', technician_id='$technicianIdsStr', followup_date='$followupDate', followup_time='$followupTime'");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

// Parse technician IDs into an array and get the first as main technician
$technicianIds = array_filter(array_map('trim', explode(',', $technicianIdsStr)));
if (empty($technicianIds)) {
    error_log("No valid technician IDs found in string: '$technicianIdsStr'");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No valid technician selected']);
    exit();
}

$mainTechnicianId = reset($technicianIds); // first technician
error_log("Main technician ID: $mainTechnicianId, All technician IDs: " . implode(", ", $technicianIds));

$database = new Database();
$db = $database->getConnection();

try {
    // Begin transaction
    $db->beginTransaction();
    error_log("Database transaction started");
    
    // Get original appointment details to copy
    $query = "SELECT 
        user_id, region, province, city, barangay, street_address, 
        is_for_self, firstname, lastname, email, mobile_number
    FROM appointments 
    WHERE id = :appointment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $originalAppointmentId);
    $stmt->execute();
    
    $originalAppointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$originalAppointment) {
        error_log("Original appointment not found with ID: $originalAppointmentId");
        $db->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Original appointment not found']);
        exit();
    }
    
    error_log("Original appointment found: " . print_r($originalAppointment, true));
    
    // Insert new follow-up appointment with main technician ID
    $insertQuery = "INSERT INTO appointments (
        user_id, service_id, region, province, city, barangay, street_address,
        appointment_date, appointment_time, status, technician_id, is_for_self,
        firstname, lastname, email, mobile_number
    ) VALUES (
        :user_id, :service_id, :region, :province, :city, :barangay, :street_address,
        :appointment_date, :appointment_time, 'Confirmed', :technician_id, :is_for_self,
        :firstname, :lastname, :email, :mobile_number
    )";
    
    $insertStmt = $db->prepare($insertQuery);
    
    // Bind parameters using $mainTechnicianId instead of $technicianId
    $insertStmt->bindParam(':user_id', $originalAppointment['user_id']);
    $insertStmt->bindParam(':service_id', $serviceId);
    $insertStmt->bindParam(':region', $originalAppointment['region']);
    $insertStmt->bindParam(':province', $originalAppointment['province']);
    $insertStmt->bindParam(':city', $originalAppointment['city']);
    $insertStmt->bindParam(':barangay', $originalAppointment['barangay']);
    $insertStmt->bindParam(':street_address', $originalAppointment['street_address']);
    $insertStmt->bindParam(':appointment_date', $followupDate);
    $insertStmt->bindParam(':appointment_time', $followupTime);
    $insertStmt->bindParam(':technician_id', $mainTechnicianId);
    $insertStmt->bindParam(':is_for_self', $originalAppointment['is_for_self']);
    $insertStmt->bindParam(':firstname', $originalAppointment['firstname']);
    $insertStmt->bindParam(':lastname', $originalAppointment['lastname']);
    $insertStmt->bindParam(':email', $originalAppointment['email']);
    $insertStmt->bindParam(':mobile_number', $originalAppointment['mobile_number']);
    
    $insertResult = $insertStmt->execute();
    if (!$insertResult) {
        error_log("Error executing appointment insert: " . print_r($insertStmt->errorInfo(), true));
        throw new PDOException("Failed to insert appointment");
    }
    
    $newAppointmentId = $db->lastInsertId();
    error_log("New appointment created with ID: $newAppointmentId");
    
    // Loop through each technician ID and insert into appointment_technicians
    $techAssignQuery = "INSERT INTO appointment_technicians (appointment_id, technician_id) 
                       VALUES (:appointment_id, :technician_id)";
    $techAssignStmt = $db->prepare($techAssignQuery);
    
    foreach ($technicianIds as $techId) {
        error_log("Adding technician ID: $techId to appointment ID: $newAppointmentId");
        $techAssignStmt->bindParam(':appointment_id', $newAppointmentId);
        $techAssignStmt->bindParam(':technician_id', $techId);
        $techAssignResult = $techAssignStmt->execute();
        
        if (!$techAssignResult) {
            error_log("Error assigning technician: " . print_r($techAssignStmt->errorInfo(), true));
            throw new PDOException("Failed to assign technician ID: $techId");
        }
    }
    
    // Commit transaction
    $db->commit();
    error_log("Transaction committed successfully");
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Follow-up scheduled successfully',
        'appointment_id' => $newAppointmentId
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $db->rollBack();
    error_log("Database error in schedule_followup.php: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
