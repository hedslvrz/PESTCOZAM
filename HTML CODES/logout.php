<?php
// filepath: d:\xampp\htdocs\PESTCOZAM\PESTCOZAM\PESTCOZAM\HTML CODES\logout.php
session_start();

// Destroy the session
session_destroy();

// Optionally, unset all session variables
$_SESSION = array();

// If using session cookies, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to the home page
header("Location: Home_page.php");
exit();
?>