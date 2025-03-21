<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <link rel="stylesheet" href="../../CSS CODES/forms-css/forms.css">
</head>
<body>
    <div class="container">
        <h1>Add Employee</h1>
        <a href="../../employee/functions/add.php" class="home-link">Home</a>
        
        <form action="../functions/add.php" method="post" name="form1" onsubmit="return validateForm();">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstname" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastname" required>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="tel" name="mobile_number" pattern="[0-9]{11}" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="technician">Technician</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
            </div>
            <div class="status-wrapper">
                <div class="form-group status-center">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="">Select Status</option>
                        <option value="verified">Verified</option>
                        <option value="unverified">Unverified</option>
                    </select>
                </div>
            </div>

            <div class="action-buttons">
                <a href="../../HTML CODES/dashboard-admin.php" class="btn btn-cancel">Cancel</a>
                <button type="submit" name="Submit" class="btn btn-update">Add</button>
            </div>
        </form>
    </div>

    <script>
        function validateForm() {
            const form = document.forms["form1"];
            const firstname = form["firstname"].value.trim();
            const lastname = form["lastname"].value.trim();
            const dob = form["dob"].value;
            const email = form["email"].value.trim();
            const mobile = form["mobile_number"].value.trim();
            const role = form["role"].value;
            const status = form["status"].value;

            if (!firstname || !lastname || !dob || !email || !mobile || !role || !status) {
                alert("All fields must be filled out!");
                return false;
            }

            // Validate Email Format
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailPattern.test(email)) {
                alert("Invalid email format!");
                return false;
            }

            // Validate Mobile Number (10 digits)
            const mobilePattern = /^[0-9]{11}$/;
            if (!mobilePattern.test(mobile)) {
                alert("Mobile number must be 11 digits!");
                return false;
            }

            return true;
        }
    </script>

</body>
</html>
