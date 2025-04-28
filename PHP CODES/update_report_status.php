<?php
// Start a session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection
require_once '../database.php';

// Set response header to JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not authorized. Please log in.'
    ]);
    exit;
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get the input data
$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);

// If input is not JSON, check POST data
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Check if we have report_id and status
if (!isset($data['report_id']) || !isset($data['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters.'
    ]);
    exit;
}

// Validate status value
$status = strtolower($data['status']);
if (!in_array($status, ['approved', 'rejected', 'pending'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value.'
    ]);
    exit;
}

// Get role - only allow admin and supervisor to update report status
$allowedRoles = ['admin', 'supervisor'];
$role = $_SESSION['role'] ?? '';

if (!in_array($role, $allowedRoles)) {
    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to update report status.'
    ]);
    exit;
}

// Connect to the database
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Prepare the update query
    $stmt = $db->prepare("UPDATE service_reports SET status = :status WHERE report_id = :report_id");
    
    // Bind parameters
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':report_id', $data['report_id']);
    
    // Execute the query
    if ($stmt->execute()) {
        // If the report is approved, update the appointment status to "Completed"
        if ($status === 'approved') {
            // First, get the appointment_id associated with this report
            $appointmentStmt = $db->prepare("SELECT appointment_id FROM service_reports WHERE report_id = :report_id");
            $appointmentStmt->bindParam(':report_id', $data['report_id']);
            $appointmentStmt->execute();
            
            $appointmentData = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
            
            // If appointment_id exists, update the appointment status
            if ($appointmentData && !empty($appointmentData['appointment_id'])) {
                $updateAppointmentStmt = $db->prepare("UPDATE appointments SET status = 'Completed' WHERE id = :appointment_id");
                $updateAppointmentStmt->bindParam(':appointment_id', $appointmentData['appointment_id']);
                
                if (!$updateAppointmentStmt->execute()) {
                    // Log error but don't fail the transaction
                    error_log("Failed to update appointment status: " . implode(", ", $updateAppointmentStmt->errorInfo()));
                }
            }
        }
        
        // Commit the transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Report status updated successfully.'
        ]);
    } else {
        // Rollback on failure
        $db->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update report status.'
        ]);
    }
} catch (PDOException $e) {
    // Rollback on exception
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log error for server side debugging
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred.'
    ]);
}
?>
