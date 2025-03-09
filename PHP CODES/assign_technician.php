<?php
session_start();
require_once '../database.php';

// Check if user is authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No JSON data received"]);
    exit;
}

// Debugging: Output received data
echo json_encode(["success" => true, "received_data" => $data]);
exit;

if (isset($data['appointment_id'], $data['technician_id'])) {
    $db = (new Database())->getConnection();
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Update appointment with technician and change status
        $stmt = $db->prepare("UPDATE appointments 
                             SET technician_id = :tech_id,
                                 status = 'Confirmed',
                                 updated_at = NOW()
                             WHERE appointment_id = :app_id");
        
        $stmt->execute([
            ':tech_id' => $data['technician_id'],
            ':app_id' => $data['appointment_id']
        ]);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Technician assigned successfully'
        ]);
    } catch (PDOException $e) {
        // Rollback on error
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
}