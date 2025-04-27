<?php
// Start session
session_start();

// Set content type - always return JSON
header('Content-Type: application/json');

try {
    // Include database connection
    require_once "../database.php";
    
    // Log that we're starting the login process
    error_log("Login API started");
    
    // Get input from either POST or direct input
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // If not in POST, try getting from raw input
    if (empty($email) || empty($password)) {
        $jsonInput = file_get_contents('php://input');
        if (!empty($jsonInput)) {
            $data = json_decode($jsonInput, true);
            $email = isset($data['email']) ? trim($data['email']) : '';
            $password = isset($data['password']) ? trim($data['password']) : '';
        }
    }
    
    error_log("Received login request for email: " . $email);
    
    // Basic validation
    if (empty($email)) {
        throw new Exception("Email is required");
    }
    
    if (empty($password)) {
        throw new Exception("Password is required");
    }
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if connection was successful
    if (!$db) {
        error_log("Database connection failed");
        throw new Exception("Could not connect to database. Please try again later.");
    }
    
    // Prepare query to check user
    $query = "SELECT id, firstname, lastname, password, role, status FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        error_log("No user found with email: " . $email);
        throw new Exception("Invalid email or password");
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed for: " . $email);
        throw new Exception("Invalid email or password");
    }
    
    // Check status
    if ($user['status'] !== 'active') {
        error_log("Account not active for: " . $email);
        throw new Exception("Your account is not active. Please contact admin.");
    }
    
    // Login successful - set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['firstname'] = $user['firstname'];
    $_SESSION['lastname'] = $user['lastname'];
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $user['role'];
    
    error_log("Login successful for: " . $email . " with role: " . $user['role']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'role' => $user['role'],
        'name' => $user['firstname'] . ' ' . $user['lastname']
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Login error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>