<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once '../database.php';

header('Content-Type: application/json');

// Check if user is authorized
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Debug log
error_log('Received data for technician assignment: ' . print_r($data, true));

// Validate input data
if (!isset($data['appointment_id']) || !isset($data['technician_id'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Begin transaction
    $db->beginTransaction();

    // Check if technician exists and is verified
    $checkStmt = $db->prepare("SELECT id FROM users 
                              WHERE id = ? 
                              AND role = 'technician' 
                              AND status = 'verified'");
                              
    $checkStmt->execute([$data['technician_id']]);

    if ($checkStmt->rowCount() === 0) {
        throw new Exception("Invalid or unverified technician");
    }

    // Update to include status change in the same query
    $updateStmt = $db->prepare("UPDATE appointments SET technician_id = ?, status = 'Confirmed' WHERE id = ?");
    
    $success = $updateStmt->execute([
        $data['technician_id'],
        $data['appointment_id']
    ]);

    if (!$success) {
        throw new Exception("Failed to update appointment");
    }
    
    // Get the technician details to return in the response
    $techStmt = $db->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
    $techStmt->execute([$data['technician_id']]);
    $technician = $techStmt->fetch(PDO::FETCH_ASSOC);

    $db->commit();
    
    // Log success
    error_log("Successfully assigned technician {$data['technician_id']} to appointment {$data['appointment_id']} and updated status to Confirmed");
    
    echo json_encode([
        'success' => true,
        'message' => 'Technician assigned successfully',
        'technician' => $technician,
        'updated_status' => 'Confirmed'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error assigning technician: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
