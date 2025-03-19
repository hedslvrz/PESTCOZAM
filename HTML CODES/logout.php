<?php
session_start();

// Store user role before destroying session
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Destroy the session
session_destroy();

// Unset all session variables
$_SESSION = array();

// If using session cookies, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect based on role
if ($userRole == 'admin' || $userRole == 'technician' || $userRole == 'supervisor') {
    header("Location: login.php");
} else {
    header("Location: ../Index.php");
}
exit();
?>