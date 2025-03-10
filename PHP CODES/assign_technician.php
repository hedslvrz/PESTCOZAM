<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

// Check if user is authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['appointment_id']) || !isset($data['technician_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    // Update appointment with technician_id and status
    $stmt = $db->prepare(
        "UPDATE appointments 
        SET technician_id = :technician_id, 
            status = 'Confirmed' 
        WHERE id = :appointment_id"
    );

    $success = $stmt->execute([
        ':technician_id' => $data['technician_id'],
        ':appointment_id' => $data['appointment_id']
    ]);

    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Technician assigned successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to assign technician'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}