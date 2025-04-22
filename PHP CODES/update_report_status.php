<?php
// Start session
session_start();

// Include database connection
require_once '../database.php';

// Check if the request is using POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate the input data
if (!isset($data['report_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$reportId = intval($data['report_id']);
$status = strtolower($data['status']);

// Valid status values
$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Update report status
    $stmt = $db->prepare("UPDATE service_reports SET status = :status WHERE report_id = :report_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':report_id', $reportId);
    
    if ($stmt->execute()) {
        // If the report is approved, update the corresponding appointment status to "Completed"
        if ($status === 'approved') {
            // First, get the appointment_id from the service report
            $getAppointmentStmt = $db->prepare("SELECT appointment_id FROM service_reports WHERE report_id = :report_id");
            $getAppointmentStmt->bindParam(':report_id', $reportId);
            $getAppointmentStmt->execute();
            $result = $getAppointmentStmt->fetch(PDO::FETCH_ASSOC);
            
            // If there's an associated appointment, update its status
            if ($result && !empty($result['appointment_id'])) {
                $appointmentId = $result['appointment_id'];
                
                $updateAppointmentStmt = $db->prepare("UPDATE appointments SET status = 'Completed' WHERE id = :id");
                $updateAppointmentStmt->bindParam(':id', $appointmentId);
                
                if ($updateAppointmentStmt->execute()) {
                    error_log("Updated appointment #$appointmentId status to Completed after report approval");
                } else {
                    error_log("Failed to update appointment #$appointmentId status");
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Report status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update report status']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
