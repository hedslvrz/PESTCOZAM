<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "pestcozam");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$email = $_POST['email'];
$mobile_number = $_POST['mobile_number'];
$dob = $_POST['dob'];

// Check if email already exists for other users
$check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check_email->bind_param("si", $email, $user_id);
$check_email->execute();
$result = $check_email->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

$sql = "UPDATE users SET 
        firstname = ?, 
        lastname = ?, 
        email = ?, 
        mobile_number = ?, 
        dob = ? 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $firstname, $lastname, $email, $mobile_number, $dob, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$conn->close();
?>
