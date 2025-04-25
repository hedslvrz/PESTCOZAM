<?php
// Prevent any output before headers
ob_start();

session_start();
require_once '../database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $required_fields = ['firstname', 'lastname', 'email', 'mobile_number'];
        $errors = [];

        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst($field) . ' is required';
            }
        }

        // Validate email format
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Check if email already exists (excluding current user)
        if (!empty($_POST['email'])) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$_POST['email'], $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Email address is already in use';
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        // Prepare data for update
        $data = [
            'firstname' => trim($_POST['firstname']),
            'lastname' => trim($_POST['lastname']),
            'email' => trim($_POST['email']),
            'mobile_number' => trim($_POST['mobile_number']),
            'id' => $_SESSION['user_id']
        ];

        // Add optional fields if provided
        if (!empty($_POST['middlename'])) {
            $data['middlename'] = trim($_POST['middlename']);
        }

        if (!empty($_POST['dob'])) {
            $data['dob'] = $_POST['dob'];
        }

        // Build update query dynamically
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if ($field !== 'id') {
                $fields[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        // Add the ID as the last parameter
        $values[] = $data['id'];
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        // Update user profile
        $stmt = $db->prepare($sql);
        $success = $stmt->execute($values);

        if ($success) {
            // Update session data
            $_SESSION['firstname'] = $data['firstname'];
            $_SESSION['lastname'] = $data['lastname'];
            $_SESSION['email'] = $data['email'];
            
            // Clear any previous output
            ob_clean();
            
            // Ensure proper content type and encode data
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            throw new Exception('Failed to update profile');
        }
    } catch (Exception $e) {
        // Clear any previous output
        ob_clean();
        
        // Ensure proper content type and encode error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // Not a POST request
    // Clear any previous output
    ob_clean();
    
    // Ensure proper content type and encode error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// End output buffering
ob_end_flush();
