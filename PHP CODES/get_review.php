<?php
session_start();
require_once '../database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get appointment ID from request
$appointmentId = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

if ($appointmentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid appointment ID'
    ]);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Query to get review data for the appointment
    $query = "SELECT r.*, 
                     DATE_FORMAT(r.created_at, '%Y-%m-%d') as formatted_date
              FROM reviews r
              WHERE r.appointment_id = :appointment_id
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointmentId);
    $stmt->execute();
    
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($review) {
        echo json_encode([
            'success' => true,
            'review' => $review
        ]);
    } else {
        // No review found for this appointment
        echo json_encode([
            'success' => false,
            'message' => 'No review found for this appointment'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
