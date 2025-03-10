<?php
// Include database connection
require_once '../../database.php';

// Create a new instance of Database and get connection
$database = new Database();
$conn = $database->getConnection();

if (isset($_POST['update'])) {
    $id = trim($_POST['id']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $dob = trim($_POST['dob']);
    $email = trim($_POST['email']);
    $mobile_number = trim($_POST['mobile_number']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);

    // Validate inputs
    $errors = [];
    if (empty($firstname)) $errors[] = "First Name field is empty.";
    if (empty($lastname)) $errors[] = "Last Name field is empty.";
    if (empty($dob)) $errors[] = "Date of Birth field is empty.";
    if (empty($email)) $errors[] = "Email field is empty.";
    if (empty($mobile_number)) $errors[] = "Mobile Number field is empty.";
    if (empty($role)) $errors[] = "Role field is empty.";
    if (empty($status)) $errors[] = "Status field is empty.";

    if (!empty($errors)) {
        echo "<div style='color: red;'>" . implode("<br/>", array_map('htmlspecialchars', $errors)) . "</div>";
        echo "<a href='javascript:self.history.back();' class='back-link'>Go Back</a>";
    } else {
        try {
            // Update user details using prepared statement
            $query = "UPDATE users SET firstname = :firstname, lastname = :lastname, dob = :dob, email = :email, mobile_number = :mobile_number, role = :role, status = :status WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mobile_number', $mobile_number);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                header("Location: ../../HTML CODES/dashboard-admin.php#employees");
                exit();
            } else {
                echo "<div style='color: red;'>Failed to update user details.</div>";
            }
        } catch (PDOException $e) {
            echo "<div style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>