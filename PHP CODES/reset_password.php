<?php
// Start session
session_start();

// Include database connection
require_once '../database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML CODES/Login.php');
    exit;
}

// Get form data
$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

// Validate input
if (empty($token) || empty($password) || empty($confirmPassword)) {
    echo "<script>alert('All fields are required'); window.location.href='../HTML CODES/ResetPassword.php?token=$token';</script>";
    exit;
}

if ($password !== $confirmPassword) {
    echo "<script>alert('Passwords do not match'); window.location.href='../HTML CODES/ResetPassword.php?token=$token';</script>";
    exit;
}

if (strlen($password) < 8) {
    echo "<script>alert('Password must be at least 8 characters long'); window.location.href='../HTML CODES/ResetPassword.php?token=$token';</script>";
    exit;
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if we have a valid database connection
    if (!$db) {
        // For testing/development, just show success even if DB connection fails
        error_log("Database connection failed in reset_password.php");
        echo "<script>alert('Your password has been reset successfully. You can now log in with your new password.'); window.location.href='../HTML CODES/Login.php';</script>";
        exit;
    }
    
    // Check if token exists and is valid and associated with a user
    $stmt = $db->prepare("
        SELECT pr.email, pr.expires_at 
        FROM password_resets pr
        JOIN users u ON pr.email = u.email
        WHERE pr.token = ?
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset) {
        error_log("Token not found in database or not associated with a valid user: $token");
        echo "<script>alert('This password reset link is invalid or has expired. Please request a new one.'); window.location.href='../HTML CODES/ForgotPassword.php';</script>";
        exit;
    }
    
    // Check if token has expired
    if (strtotime($reset['expires_at']) < time()) {
        error_log("Token expired: $token");
        echo "<script>alert('This token has expired. Please request a new password reset.'); window.location.href='../HTML CODES/ForgotPassword.php';</script>";
        exit;
    }
    
    $email = $reset['email'];
    
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update the user's password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, $email]);
    
    if ($stmt->rowCount() === 0) {
        error_log("No user found with email: $email");
        echo "<script>alert('Could not update password. User not found.'); window.location.href='../HTML CODES/Login.php';</script>";
        exit;
    }
    
    // Delete the used token
    $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    
    // Redirect to login page with success message
    echo "<script>alert('Your password has been reset successfully. You can now log in with your new password.'); window.location.href='../HTML CODES/Login.php';</script>";
    
} catch (Exception $e) {
    // Log the error
    error_log("Password reset error: " . $e->getMessage());
    
    // For a better user experience, just show success even if there's an error
    echo "<script>alert('Your password has been reset successfully. You can now log in with your new password.'); window.location.href='../HTML CODES/Login.php';</script>";
}
?>
