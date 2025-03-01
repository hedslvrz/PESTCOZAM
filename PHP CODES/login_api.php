<?php
session_start();
require '../database.php';

// Get JSON request body
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    try {
        $stmt = $dbc->prepare("SELECT id, firstname, password, verified FROM users WHERE email = ?");
        $stmt->execute([$data->email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (!password_verify($data->password, $user['password'])) {
                echo json_encode(["success" => false, "message" => "Incorrect password!"]);
                exit();
            }

            if ($user['verified'] == 0) {
                echo json_encode(["success" => false, "message" => "Account not verified. Please verify your email."]);
                exit();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $data->email;
            $_SESSION['firstname'] = $user['firstname'];

            echo json_encode(["success" => true, "message" => "Login successful!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Email not found!"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>
