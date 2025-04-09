<?php
// Include database connection
require_once "../../database.php";

if (isset($_POST['update'])) {
    // Get form data and sanitize
    $id = (int)$_POST['id'];
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $dob = trim($_POST['dob']);
    $email = trim($_POST['email']);
    $mobile_number = trim($_POST['mobile_number']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    
    // Get the optional fields (may be empty)
    $sss_no = isset($_POST['sss_no']) ? trim($_POST['sss_no']) : null;
    $pagibig_no = isset($_POST['pagibig_no']) ? trim($_POST['pagibig_no']) : null;
    $philhealth_no = isset($_POST['philhealth_no']) ? trim($_POST['philhealth_no']) : null;
    
    // Validate required fields
    if (empty($firstname) || empty($lastname) || empty($dob) || empty($email) || empty($mobile_number) || empty($role) || empty($status)) {
        echo "Please fill in all required fields.";
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }

    // Create a new instance of Database and get connection
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo "Email already in use by another employee. Please use a different email.";
            exit();
        }
        
        // Update the employee
        $query = "UPDATE users 
                  SET firstname = :firstname, 
                      lastname = :lastname, 
                      dob = :dob, 
                      email = :email, 
                      mobile_number = :mobile_number, 
                      role = :role, 
                      status = :status, 
                      sss_no = :sss_no,
                      pagibig_no = :pagibig_no,
                      philhealth_no = :philhealth_no
                  WHERE id = :id";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':firstname', $firstname);
        $stmt->bindParam(':lastname', $lastname);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':mobile_number', $mobile_number);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':sss_no', $sss_no);
        $stmt->bindParam(':pagibig_no', $pagibig_no);
        $stmt->bindParam(':philhealth_no', $philhealth_no);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header("Location: ../../HTML CODES/dashboard-admin.php#employees");
            exit();
        } else {
            echo "Error updating employee.";
            exit();
        }
        
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
        exit();
    }
} else {
    // If not submitted, redirect to the form
    header("Location: ../../HTML CODES/dashboard-admin.php#employees");
    exit();
}
?>
