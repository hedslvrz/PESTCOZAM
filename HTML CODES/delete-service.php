<?php
session_start();
require_once '../database.php';

// Check if user is logged in and has admin role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if(isset($_GET['id'])) {
    $serviceId = $_GET['id'];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Delete the service
        $stmt = $db->prepare("DELETE FROM services WHERE service_id = ?");
        $result = $stmt->execute([$serviceId]);
        
        // Return JSON response
        header('Content-Type: application/json');
        if($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete service']);
        }
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No service ID provided']);
}
