<?php
session_start();
require_once '../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to view appointment details']);
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit();
}

$appointmentId = $_GET['id'];
$userId = $_SESSION['user_id'];

try {
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Query to get appointment details - ensuring user can only access their own appointments
    $query = "SELECT 
        a.*,
        s.service_name,
        s.service_id,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as client_name,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name,
        a.property_type, 
        a.establishment_name,
        a.property_area,
        a.pest_concern,
        a.email,
        a.mobile_number
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users t ON a.technician_id = t.id
    WHERE a.id = :appointment_id AND a.user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointmentId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Appointment not found or you do not have permission to view it']);
        exit();
    }
    
    // Format dates and times for display
    $appointment['appointment_date'] = date('F d, Y', strtotime($appointment['appointment_date']));
    $appointment['appointment_time'] = date('h:i A', strtotime($appointment['appointment_time']));
    
    // Return success with appointment details
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'details' => $appointment
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
