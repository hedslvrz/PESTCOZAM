<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <link rel="stylesheet" href="../../CSS CODES/functions-css/functions.css">
</head>
<body>
    <div class="container">
        <h1>Add Employee</h1>
        <?php
        include_once "../../database.php"; // Fixed directory path

        // Create database connection
        $database = new Database();
        $dbc = $database->getConnection();

        if (isset($_POST['Submit'])) {
            $firstname = trim($_POST['firstname']);
            $lastname = trim($_POST['lastname']);
            $dob = trim($_POST['dob']);
            $email = trim($_POST['email']);
            $mobile_number = trim($_POST['mobile_number']);
            $role = trim($_POST['role']);
            // Status is now automatically set to active
            $status = 'active';
            
            // Get the optional fields (may be empty)
            $sss_no = isset($_POST['sss_no']) ? trim($_POST['sss_no']) : null;
            $pagibig_no = isset($_POST['pagibig_no']) ? trim($_POST['pagibig_no']) : null;
            $philhealth_no = isset($_POST['philhealth_no']) ? trim($_POST['philhealth_no']) : null;

            // Validate inputs
            $errors = [];
            if (empty($firstname)) $errors[] = "First Name field is empty.";
            if (empty($lastname)) $errors[] = "Last Name field is empty.";
            if (empty($dob)) $errors[] = "Date of Birth field is empty.";
            if (empty($email)) $errors[] = "Email field is empty.";
            if (empty($mobile_number)) $errors[] = "Mobile Number field is empty.";
            if (empty($role)) $errors[] = "Role field is empty.";
            // Removed status validation since it's now hardcoded

            if (!empty($errors)) {
                echo "<div class='error'>" . implode("<br/>", $errors) . "</div>";
                echo "<a href='javascript:self.history.back();' class='back-link'>Go Back</a>";
            } else {
                try {
                    // Generate default password (Lastname+dob)
                    $dobFormatted = str_replace("-", "", $dob);
                    $default_password = $lastname . $dobFormatted; // Example: "Doe1995-06-15"
                    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT); // Hash the password

                    // Check if email already exists
                    $stmt = $dbc->prepare("SELECT id FROM users WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->fetch()) {
                        echo "Email already exists. Please use a different email.";
                        exit();
                    }

                    // Get the next available employee number
                    $stmt = $dbc->prepare("SELECT MAX(CAST(SUBSTRING(employee_no, 5) AS UNSIGNED)) as max_id FROM users WHERE employee_no IS NOT NULL");
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $next_id = ($result['max_id'] ?? 0) + 1;
                    $employee_no = 'EMP-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

                    // Prepare the query with correct placeholders
                    $stmt = $dbc->prepare("INSERT INTO users (firstname, lastname, dob, email, mobile_number, role, status, password, employee_no, sss_no, pagibig_no, philhealth_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$firstname, $lastname, $dob, $email, $mobile_number, $role, $status, $hashed_password, $employee_no, $sss_no, $pagibig_no, $philhealth_no]);

                    echo "<div class='success'>Employee added successfully. Do you want to add again?</div>";
                    echo "<a href='../../HTML CODES/dashboard-admin.php' class='back-link'>View Employees</a>";
                } catch (PDOException $e) {
                    echo "<div class='error'>Failed to add employee: " . $e->getMessage() . "</div>";
                }
            }
        }
        ?>

        <a href="../../HTML CODES/dashboard-admin.php" class="home-link">Home</a>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form1" onsubmit="return validateForm();">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">First Name:</label>
                    <input type="text" name="firstname" id="firstname">
                </div>

                <div class="form-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" name="lastname" id="lastname">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="dob">Date of Birth:</label>
                    <input type="date" name="dob" id="dob">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="mobile_number">Mobile Number:</label>
                    <input type="text" name="mobile_number" id="mobile_number" placeholder="11 digits">
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select name="role" id="role">
                        <option value="">Select Role</option>
                        <option value="technician">Technician</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
            </div>

            <!-- Hidden input to automatically set status as active -->
            <input type="hidden" name="status" value="active">

            <div class="form-row">
                <div class="form-group">
                    <label for="sss_no">SSS Number:</label>
                    <input type="text" name="sss_no" id="sss_no" placeholder="10 digits">
                </div>

                <div class="form-group">
                    <label for="pagibig_no">Pag-IBIG Number:</label>
                    <input type="text" name="pagibig_no" id="pagibig_no" placeholder="10-12 digits">
                </div>

                <div class="form-group">
                    <label for="philhealth_no">PhilHealth Number:</label>
                    <input type="text" name="philhealth_no" id="philhealth_no" placeholder="10-12 digits">
                </div>
            </div>

            <input type="submit" name="Submit" value="Add">
        </form>
    </div>
    
    <script>
        function validateForm() {
            const fields = ["firstname", "lastname", "dob", "email", "mobile_number", "role"];
            for (let field of fields) {
                if (!document.getElementById(field).value) {
                    alert("All fields must be filled out!");
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>
