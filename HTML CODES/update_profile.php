<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$firstname = $_POST['firstname'];
$middlename = $_POST['middlename']; // Added middlename
$lastname = $_POST['lastname'];
$email = $_POST['email'];
$mobile_number = $_POST['mobile_number'];
$dob = $_POST['dob'];

// Check if email already exists for other users
try {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bindParam(1, $email);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Update user data in the database
    $query = "UPDATE users SET 
              firstname = ?, 
              middlename = ?,
              lastname = ?, 
              email = ?, 
              mobile_number = ?, 
              dob = ?
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $firstname);
    $stmt->bindParam(2, $middlename);
    $stmt->bindParam(3, $lastname);
    $stmt->bindParam(4, $email);
    $stmt->bindParam(5, $mobile_number);
    $stmt->bindParam(6, $dob);
    $stmt->bindParam(7, $user_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
} catch(PDOException $e) {
    error_log("Error updating profile: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
