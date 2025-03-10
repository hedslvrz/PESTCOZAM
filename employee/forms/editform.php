<?php
// Include database connection
require_once "../../database.php";

// Validate and sanitize input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}
$id = (int) $_GET['id'];

// Create a new instance of Database and get connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Fetch employee details
    $query = "SELECT firstname, lastname, dob, email, mobile_number, role, status FROM users WHERE id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        $firstname = $employee['firstname'];
        $lastname = $employee['lastname'];
        $dob = $employee['dob'];
        $email = $employee['email'];
        $mobile_number = $employee['mobile_number'];
        $role = $employee['role'];
        $status = $employee['status'];
    } else {
        die("Employee not found. ID: " . htmlspecialchars($id));
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Query error.", "error" => $e->getMessage()]);
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link rel="stylesheet" href="../../CSS CODES/forms-css/forms.css">
</head>
<body>
    <div class="container">
        <h1>Edit Employee</h1>
        <a href="../../HTML CODES/dashboard-admin.php" class="home-link">Home</a>
        
        <form name="form1" method="post" action="../../employee/functions/edit.php">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile_number" value="<?php echo htmlspecialchars($mobile_number); ?>" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="technician" <?php echo ($role == 'technician') ? 'selected' : ''; ?>>Technician</option>
                        <option value="supervisor" <?php echo ($role == 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="verified" <?php echo ($status == 'verified') ? 'selected' : ''; ?>>Verified</option>
                        <option value="unverified" <?php echo ($status == 'unverified') ? 'selected' : ''; ?>>Unverified</option>
                    </select>
                </div>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                <div class="action-buttons">
                    <button type="submit" name="update" class="btn btn-update">Update</button>
                    <a href="../../HTML CODES/dashboard-admin.php" class="btn btn-cancel">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
