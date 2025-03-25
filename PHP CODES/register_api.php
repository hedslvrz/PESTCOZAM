<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
include_once "../database.php";
include_once "User.php";

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed.", "success" => false]);
    exit();
}

$user = new User($db);
$data = json_decode(file_get_contents("php://input"), true);

$user->firstname = htmlspecialchars(strip_tags(trim($data['firstname'])));
$user->middlename = htmlspecialchars(strip_tags(trim($data['middlename'] ?? null))); // Optional field
$user->lastname = htmlspecialchars(strip_tags(trim($data['lastname'])));
$user->email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$user->mobile_number = preg_replace('/\D/', '', trim($data['mobile_number']));
$user->password = trim($data['password']);
$user->role = "user";
$user->status = "Verified";

// Validate email format
if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid email format.", "success" => false]);
    exit();
}

// Validate mobile number length
if (strlen($user->mobile_number) < 10 || strlen($user->mobile_number) > 15) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid mobile number.", "success" => false]);
    exit();
}

// Check if password is empty
if (empty($user->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Password is required.", "success" => false]);
    exit();
}

// Hash the password
$user->password = password_hash($user->password, PASSWORD_DEFAULT);

// Check if email already exists
if ($user->emailExists()) {
    http_response_code(409);
    echo json_encode(["message" => "Email already exists.", "success" => false]);
    exit();
}

// Create the user
if ($user->create()) {
    http_response_code(201);
    echo json_encode(["message" => "User registered successfully. Please Login.", "success" => true]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Error registering user.", "success" => false]);
}
?>