<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Appointment ID not provided']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "u302876046_root", "Pestcozam@2025", "u302876046_pestcozam");

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$appointment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get appointment details and user details
$sql = "SELECT a.*, s.service_name,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as client_name,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name,
        a.is_for_self,
        s.service_id,
        u.email as user_email,
        u.mobile_number as user_mobile
        FROM appointments a 
        JOIN services s ON a.service_id = s.service_id 
        JOIN users u ON a.user_id = u.id
        LEFT JOIN users t ON a.technician_id = t.id
        WHERE a.id = ? AND a.user_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit();
}

$appointment = $result->fetch_assoc();

// Format dates and times for display
$appointment['appointment_date'] = date('F d, Y', strtotime($appointment['appointment_date']));
$appointment['appointment_time'] = date('h:i A', strtotime($appointment['appointment_time']));

// Return the appointment data as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'appointment' => $appointment
]);
exit();
?>
