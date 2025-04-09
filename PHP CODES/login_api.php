<?php 
session_start();

require_once '../database.php';
$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email and Password are required."]);
    exit();
}

$query = "SELECT * FROM users WHERE email = :email";
$stmt = $conn->prepare($query);
$stmt->bindParam(":email", $data['email']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($data['password'], $row['password'])) {
        if (strcasecmp(trim($row['status']), 'active') === 0) { // Changed from "Verified" to "active"
            // Set all necessary session variables
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['lastname'] = $row['lastname'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['profile_pic'] = $row['profile_pic'] ?? '../Pictures/boy.png';
            $_SESSION['mobile_number'] = $row['mobile_number'];
            $_SESSION['logged_in'] = true;

            // Return success with role information
            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "role" => $row['role'],
                "user" => [
                    "id" => $row['id'],
                    "firstname" => $row['firstname'],
                    "lastname" => $row['lastname'],
                    "email" => $row['email']
                ]
            ]);
        } else {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Account is inactive. Please contact administrator."]); // Updated error message
        }
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials."]);
    }
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Account not found."]);
}
?>