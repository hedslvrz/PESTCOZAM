<?php
// Include database connection if not already included
if (!class_exists('Database')) {
    require_once dirname(__FILE__) . "/../../database.php";
}

// Function to fetch all employees (technicians and supervisors)
function fetchEmployees() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT id, firstname, lastname, dob, email, mobile_number, role, status, 
                        employee_no, sss_no, pagibig_no, philhealth_no 
                 FROM users 
                 WHERE role IN ('technician', 'supervisor')
                 ORDER BY lastname, firstname";
                 
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $employees;
        
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}

// Get the employees data
$employees = fetchEmployees();
?>
