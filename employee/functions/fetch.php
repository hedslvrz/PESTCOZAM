<?php
// Include the database connection file
require_once __DIR__ . '/../../database.php';

// Initialize the employees array
$employees = [];

/**
 * Fetch all employees from the database
 * @return array Array of employees
 */
function fetchEmployees() {
    global $employees;
    
    try {
        // Create a new Database instance inside the function to ensure it's initialized
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            error_log("Database connection is null in fetchEmployees()");
            return [];
        }
        
        // Prepare and execute the query
        $stmt = $db->prepare("SELECT * FROM users WHERE role IN ('technician', 'supervisor')");
        if (!$stmt) {
            error_log("Failed to prepare statement in fetchEmployees()");
            return [];
        }
        
        $stmt->execute();
        
        // Fetch the results
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $employees;
    } catch (PDOException $e) {
        // Log the error and return an empty array
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}

/**
 * Get an employee by ID
 * @param int $id Employee ID
 * @return array|null Employee data or null if not found
 */
function getEmployeeById($id) {
    global $employees;
    
    // If employees array is empty, fetch them
    if (empty($employees)) {
        $employees = fetchEmployees();
    }
    
    // Find the employee with the matching ID
    foreach ($employees as $employee) {
        if ($employee['id'] == $id) {
            return $employee;
        }
    }
    
    return null;
}

// Only fetch employees if none have been fetched yet
if (empty($employees)) {
    $employees = fetchEmployees();
}
?>
