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
error_log('Received data: ' . print_r($data, true));

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

    // Update appointment
    $updateStmt = $db->prepare("UPDATE appointments 
                               SET technician_id = ?, 
                                   status = 'Confirmed' 
                               WHERE id = ?");

    $success = $updateStmt->execute([
        $data['technician_id'],
        $data['appointment_id']
    ]);

    if (!$success) {
        throw new Exception("Failed to update appointment");
    }

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Technician assigned successfully'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error assigning technician: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
