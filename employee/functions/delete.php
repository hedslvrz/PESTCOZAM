<?php
// Set headers for JSON response
header("Content-Type: application/json");

// Include database connection
require_once "../../database.php";

// Validate ID from GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid or missing employee ID."]);
    exit();
}

$id = (int)$_GET['id'];

// Read JSON body
$input = json_decode(file_get_contents("php://input"), true);
$reason = $input['reason'] ?? null;

if (!$reason) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Deletion reason is required."]);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Ensure the employee exists and is a technician or supervisor
    $checkStmt = $conn->prepare("SELECT role FROM users WHERE id = :id AND role IN ('technician', 'supervisor')");
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Employee not found or not allowed to be deleted."]);
        exit();
    }

    // Soft delete and store reason
    $stmt = $conn->prepare("UPDATE users SET deleted = 1, deleted_reason = :reason WHERE id = :id");
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Employee archived successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error.", "error" => $e->getMessage()]);
    exit();
}
?>
<?php
//     <script src="../../JS CODES/dashboard-admin.js"></script>    
// </body>
// </html>
