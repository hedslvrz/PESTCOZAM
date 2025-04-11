<?php
session_start();
require_once 'AppointmentSession.php';

// Clear any existing appointment session data
AppointmentSession::clear();

// Return success status
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
