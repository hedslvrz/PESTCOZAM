<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
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
        // Email already exists - redirect with error
        header("Location: Profile.php?error=email_exists");
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
        
        // Redirect back to profile page with success message
        header("Location: Profile.php?success=profile_updated");
        exit;
    } else {
        // Database error - redirect with error
        header("Location: Profile.php?error=update_failed");
        exit;
    }
} catch(PDOException $e) {
    error_log("Error updating profile: " . $e->getMessage());
    header("Location: Profile.php?error=database_error");
    exit;
}
?>
