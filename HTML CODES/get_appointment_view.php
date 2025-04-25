<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header("Location: Profile.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "u302876046_root", "Pestcozam@2025", "u302876046_pestcozam");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$appointment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get appointment details
$sql = "SELECT a.*, s.service_name,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as client_name,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name,
        a.is_for_self
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
    header("Location: Profile.php");
    exit();
}

$appointment = $result->fetch_assoc();

// Format dates and times for display
$appointment['appointment_date'] = date('F d, Y', strtotime($appointment['appointment_date']));
$appointment['appointment_time'] = date('h:i A', strtotime($appointment['appointment_time']));

// Store the appointment data in a session variable
$_SESSION['current_appointment'] = $appointment;

// Redirect with success parameter to indicate a retrieved appointment
header("Location: Profile.php?appointment_loaded=true");
exit();
?>
