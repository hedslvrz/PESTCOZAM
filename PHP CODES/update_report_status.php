<?php
session_start();
require_once '../database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Validate input parameters
if (!isset($_POST['report_id']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$report_id = intval($_POST['report_id']);
$status = $_POST['status'];

// Validate status
if (!in_array($status, ['approved', 'rejected', 'pending'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // First, check if the report exists
    $checkStmt = $db->prepare("SELECT report_id FROM service_reports WHERE report_id = ?");
    $checkStmt->execute([$report_id]);
    
    if ($checkStmt->rowCount() === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit();
    }
    
    // Update report status
    $updateStmt = $db->prepare("UPDATE service_reports SET status = ?, updated_at = NOW() WHERE report_id = ?");
    $result = $updateStmt->execute([$status, $report_id]);
    
    if ($result) {
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $log_message = "Report #$report_id status changed to $status";
        error_log($log_message . " by admin ID: $admin_id");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Report status updated successfully',
            'report_id' => $report_id,
            'new_status' => $status
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update report status']);
    }
} catch (PDOException $e) {
    error_log("Database error in update_report_status.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
