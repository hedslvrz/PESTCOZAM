<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['visit_id']) || !isset($data['technician_id'])) {
        throw new Exception('Missing required parameters');
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "UPDATE recurrence_visits 
              SET technician_id = ?, updated_at = NOW() 
              WHERE visit_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$data['technician_id'], $data['visit_id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Visit not found or no changes made');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Technician assignment updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
