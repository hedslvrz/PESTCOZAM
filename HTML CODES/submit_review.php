<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "u302876046_root", "Pestcozam@2025", "u302876046_pestcozam");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Validate POST data
$required_fields = ['appointment_id', 'service_id', 'rating', 'service_rating', 'technician_rating', 'service_feedback', 'review_text'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$appointment_id = intval($_POST['appointment_id']);
$service_id = intval($_POST['service_id']);
$rating = intval($_POST['rating']);
$service_rating = intval($_POST['service_rating']);
$technician_rating = intval($_POST['technician_rating']);
$service_feedback = $conn->real_escape_string($_POST['service_feedback']);
$reported_issues = isset($_POST['reported_issues']) ? $conn->real_escape_string($_POST['reported_issues']) : null;
$review_text = $conn->real_escape_string($_POST['review_text']);
$user_id = $_SESSION['user_id'];

// Insert feedback into the database
$sql = "INSERT INTO reviews (user_id, service_id, appointment_id, rating, review_text, service_rating, technician_rating, service_feedback, reported_issues, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    exit();
}

$stmt->bind_param("iiiisssss", $user_id, $service_id, $appointment_id, $rating, $review_text, $service_rating, $technician_rating, $service_feedback, $reported_issues);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback.']);
}

$stmt->close();
$conn->close();
?>
