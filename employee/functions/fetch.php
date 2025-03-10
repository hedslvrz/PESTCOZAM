<?php
require_once(__DIR__ . '/../../database.php');

function fetchEmployees() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT id, firstname, lastname, dob, email, mobile_number, role, status 
                 FROM users 
                 WHERE role IN ('technician', 'supervisor')";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}

// Get employees without printing
$employees = fetchEmployees();

// Remove any debug prints like var_dump or print_r
// var_dump($employees); <- Remove this if it exists
?>
