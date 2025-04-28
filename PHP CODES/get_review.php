<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if appointment_id is provided
if (!isset($_GET['appointment_id']) || empty($_GET['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit;
}

$appointmentId = $_GET['appointment_id'];

try {
    // Query to get review data for the specified appointment
    $query = "SELECT r.*, 
              CONCAT(u.firstname, ' ', u.lastname) as customer_name
              FROM reviews r
              LEFT JOIN users u ON r.user_id = u.id
              WHERE r.appointment_id = :appointment_id
              LIMIT 1";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointmentId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format date for display
        if (isset($review['created_at']) && $review['created_at']) {
            $review['formatted_date'] = date('F j, Y', strtotime($review['created_at']));
        } else {
            $review['formatted_date'] = 'N/A';
        }
        
        echo json_encode([
            'success' => true,
            'review' => $review
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No review found for this appointment'
        ]);
    }
} catch (PDOException $e) {
    // Log error but return a generic message to the client
    error_log("Database error in get_review.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred while retrieving the review'
    ]);
}
?>
