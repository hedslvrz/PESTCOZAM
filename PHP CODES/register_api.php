<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../database.php';
if (!$dbc) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true); // Convert to associative array
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data received."]);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);

// Validate input fields
if (
    empty($data['firstname']) || 
    empty($data['lastname']) || 
    empty($data['email']) || 
    empty($data['mobile_number']) || 
    empty($data['password'])
) {
    http_response_code(400); // Bad request
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit();
}

try {
    // Check if email already exists
    $stmt = $dbc->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);

    if ($stmt->rowCount() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(["success" => false, "message" => "Email already registered."]);
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO users (firstname, lastname, email, mobile_number, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = $dbc->prepare($sql);
    $stmt->execute([
        $data['firstname'], 
        $data['lastname'], 
        $data['email'], 
        $data['mobile_number'], 
        $hashed_password
    ]);

    // Get the inserted user ID
    $_SESSION['user_id'] = $dbc->lastInsertId();
    $_SESSION['email'] = $data['email'];
    $_SESSION['firstname'] = $data['firstname'];

    http_response_code(201); // Created
    echo json_encode(["success" => true, "message" => "Registration successful!"]);

}catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage()); // Logs to server error log
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
