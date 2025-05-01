<?php
session_start();
require_once 'AppointmentSession.php';

// Get appointment ID before clearing (may be useful for debugging)
$hadAppointment = isset($_SESSION['appointment']);

// Clear any existing appointment session data
AppointmentSession::clearAllData();

// Return success status with info about previous session
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'hadPreviousAppointment' => $hadAppointment
]);
?>
