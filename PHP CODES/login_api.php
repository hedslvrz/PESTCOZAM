<?php 
    session_start();

    require_once '../database.php';
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"),true);

    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email and Password are required."]);
        exit();
    }

    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":email", $data['email']);
    $stmt->execute();

    if($stmt->rowCount() > 0){
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($data['password'], $row['password'])) {
            if (strcasecmp(trim($row['status']), 'Verified') === 0) {  // Case-insensitive check        
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['lastname'] = $row['lastname'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            
            echo json_encode(["message" => "Login successful.", "success" => true, "role" => $row['role']]);
        }else{
            http_response_code(403);
            echo json_encode(["message" => "Account not verified.", "success" => false]);
        }
    } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid credentials.", "success" => false]);
    }
}else{
            http_response_code(404);
            echo json_encode(["message" => "Account not found.", "success" => false]);
}

?>