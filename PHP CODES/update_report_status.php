<?php
session_start();
require_once '../database.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => 'An error occurred during processing.'
];

// Check if user is logged in as admin or supervisor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    $response['message'] = 'Unauthorized access. You must be logged in as an admin or supervisor.';
    echo json_encode($response);
    exit;
}

// Check if this is a POST request and action is 'update_status'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Validate inputs
    if (!isset($_POST['report_id']) || !isset($_POST['status'])) {
        $response['message'] = 'Missing required parameters.';
        echo json_encode($response);
        exit;
    }
    
    $reportId = trim($_POST['report_id']);
    $status = strtolower(trim($_POST['status']));
    $role = isset($_POST['role']) ? $_POST['role'] : $_SESSION['role'];
    
    // Validate status value
    if (!in_array($status, ['approved', 'rejected'])) {
        $response['message'] = 'Invalid status value. Must be either "approved" or "rejected".';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Initialize database
        $database = new Database();
        $db = $database->getConnection();
        
        // Update report status
        $stmt = $db->prepare("UPDATE service_reports SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW() WHERE report_id = :report_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':reviewed_by', $_SESSION['user_id']);
        $stmt->bindParam(':report_id', $reportId);
        
        if ($stmt->execute()) {
            // Log the status change
            $logStmt = $db->prepare("INSERT INTO report_logs (report_id, user_id, action, role, created_at) VALUES (:report_id, :user_id, :action, :role, NOW())");
            $action = $status === 'approved' ? 'approved report' : 'rejected report';
            $logStmt->bindParam(':report_id', $reportId);
            $logStmt->bindParam(':user_id', $_SESSION['user_id']);
            $logStmt->bindParam(':action', $action);
            $logStmt->bindParam(':role', $role);
            $logStmt->execute();
            
            $response['success'] = true;
            $response['message'] = 'Report status has been updated successfully.';
        } else {
            $response['message'] = 'Failed to update report status.';
        }
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log('Update report status error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
