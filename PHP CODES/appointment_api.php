<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
include_once "../database.php";

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed.", "success" => false]);
    exit();
}

$response = ["success" => false, "message" => ""];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        isset($data['user_id'], $data['service_id'], $data['region'], $data['province'],
              $data['city'], $data['barangay'], $data['street_address'], $data['appointment_date'], $data['appointment_time'], $data['is_for_self'])
    ) {
        try {
            $pdo->beginTransaction();

            // Default user details as NULL for self-appointments
            $firstname = isset($data['firstname']) ? $data['firstname'] : NULL;
            $lastname = isset($data['lastname']) ? $data['lastname'] : NULL;
            $email = isset($data['email']) ? $data['email'] : NULL;
            $mobile_number = isset($data['mobile_number']) ? $data['mobile_number'] : NULL;

            // If the appointment is for self, retrieve user details from the users table
            if ($data['is_for_self'] == 1) {
                $stmtUser = $pdo->prepare("SELECT firstname, lastname, email, mobile_number FROM users WHERE id = :user_id");
                $stmtUser->execute([':user_id' => $data['user_id']]);
                $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $firstname = $user['firstname'];
                    $lastname = $user['lastname'];
                    $email = $user['email'];
                    $mobile_number = $user['mobile_number'];
                } else {
                    throw new Exception("User not found.");
                }
            }

            // Insert appointment
            $stmt = $pdo->prepare("INSERT INTO appointments 
                (user_id, service_id, region, province, city, barangay, street_address, appointment_date, appointment_time, is_for_self, firstname, lastname, email, mobile_number) 
                VALUES 
                (:user_id, :service_id, :region, :province, :city, :barangay, :street_address, :appointment_date, :appointment_time, :is_for_self, :firstname, :lastname, :email, :mobile_number)");

            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':service_id' => $data['service_id'],
                ':region' => $data['region'],
                ':province' => $data['province'],
                ':city' => $data['city'],
                ':barangay' => $data['barangay'],
                ':street_address' => $data['street_address'],
                ':appointment_date' => $data['appointment_date'],
                ':appointment_time' => $data['appointment_time'],
                ':is_for_self' => $data['is_for_self'],
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':email' => $email,
                ':mobile_number' => $mobile_number
            ]);

            $pdo->commit();
            $response["success"] = true;
            $response["message"] = "Appointment successfully added.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $response["message"] = "Error: " . $e->getMessage();
        }
    } else {
        $response["message"] = "Missing required fields.";
    }
} else {
    $response["message"] = "Invalid request method.";
}

echo json_encode($response);
?>
