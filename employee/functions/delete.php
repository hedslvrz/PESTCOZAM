<?php
// Include database connection
require_once "../../database.php";

// Check if ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Create a new instance of Database and get connection
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Check if the employee exists and is a technician or supervisor
        $checkStmt = $conn->prepare("SELECT role FROM users WHERE id = :id AND role IN ('technician', 'supervisor')");
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if (!$checkStmt->fetch()) {
            echo "Employee not found or cannot be deleted.";
            exit();
        }
        
        // Delete the employee
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header("Location: ../../HTML CODES/dashboard-admin.php#employees");
            exit();
        } else {
            echo "Error deleting employee.";
            exit();
        }
        
    } catch (PDOException $e) {
        // Check if error is related to foreign key constraint
        if ($e->getCode() == 23000) { // Integrity constraint violation
            echo "This employee cannot be deleted because they have associated appointments or records.";
        } else {
            echo "Database Error: " . $e->getMessage();
        }
        exit();
    }
} else {
    echo "Invalid employee ID.";
    exit();
}
?>
