<?php
session_start();
require_once '../database.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Not logged in';
    echo json_encode($response);
    exit;
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Sanitize and validate input
    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $middlename = isset($_POST['middlename']) ? trim($_POST['middlename']) : '';
    $lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mobile_number = isset($_POST['mobile_number']) ? trim($_POST['mobile_number']) : '';
    $dob = isset($_POST['dob']) ? trim($_POST['dob']) : null;
    
    // Validation
    if (empty($firstname)) {
        throw new Exception('First name is required');
    }
    
    if (empty($lastname)) {
        throw new Exception('Last name is required');
    }
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // Check if email format is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Check if the email exists for another user
    $checkEmailStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkEmailStmt->execute([$email, $user_id]);
    if ($checkEmailStmt->rowCount() > 0) {
        throw new Exception('Email is already taken by another user');
    }
    
    // Prepare and execute update query
    $sql = "UPDATE users SET 
            firstname = ?, 
            middlename = ?, 
            lastname = ?, 
            email = ?, 
            mobile_number = ?, 
            dob = ? 
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $firstname,
        $middlename,
        $lastname,
        $email,
        $mobile_number,
        $dob,
        $user_id
    ]);
    
    if ($result) {
        // Update session variables if needed
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        $_SESSION['email'] = $email;
        
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully';
    } else {
        $response['message'] = 'Failed to update profile';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    // Log the error for debugging
    error_log('Profile update PDO error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
