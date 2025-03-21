<?php
// Include database connection
require_once '../../database.php';

// Create a new instance of Database and get connection
$database = new Database();
$conn = $database->getConnection();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Prepare DELETE statement
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header("Location: ../../HTML CODES/dashboard-admin.php#employees");
            exit();
        } else {
            echo "Failed to delete record.";
        }
    } catch (PDOException $e) {
        echo "<div style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "Invalid request.";
}
?>

