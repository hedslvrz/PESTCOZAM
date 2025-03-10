<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <link rel="stylesheet" href="../../CSS CODES/functions-css/functions.css">
    <style>
        table {
            margin-bottom: 20px;
        }
        .form-group select[name="status"] {
            text-align-last: center;
        }
        .form-group select option {
            text-align: center;
        }
    </style>
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
                echo "<div class='error'>" . implode("<br/>", $errors) . "</div>";
                echo "<a href='javascript:self.history.back();' class='back-link'>Go Back</a>";
            } else {
                try {
                    // Generate default password (Lastname+dob)
                    $dobFormatted = str_replace("-", "", $dob);
                    $default_password = $lastname . $dobFormatted; // Example: "Doe1995-06-15"
                    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT); // Hash the password

                    // Prepare the query with correct placeholders
                    $stmt = $dbc->prepare("INSERT INTO users (firstname, lastname, dob, email, mobile_number, role, status, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$firstname, $lastname, $dob, $email, $mobile_number, $role, $status, $hashed_password]);

                    echo "<div class='success'>Employee added successfully. Do you want to add again?</div>";
                    echo "<a href='../../HTML CODES/dashboard-admin.php' class='back-link'>View Employees</a>";
                } catch (PDOException $e) {
                    echo "<div class='error'>Failed to add employee: " . $e->getMessage() . "</div>";
                }
            }
        }
        ?>

        <a href="../../HTML CODES/dashboard-admin.php" class="home-link">Home</a>
        <form action="addform.php" method="post" name="form1" onsubmit="return validateForm();">
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
                    <input type="text" name="mobile_number" id="mobile_number">
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

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">Select Status</option>
                        <option value="verified">Verified</option>
                        <option value="unverified">Unverified</option>
                    </select>
                </div>
            </div>

            <input type="submit" name="Submit" value="Add">
        </form>
    </div>
    
    <script>
        function validateForm() {
            const fields = ["firstname", "lastname", "dob", "email", "mobile_number", "role", "status"];
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
