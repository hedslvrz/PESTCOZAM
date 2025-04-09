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
        <a href="../../HTML CODES/dashboard-admin.php" class="home-link">Home</a>
        
        <form action="../functions/add.php" method="post" name="form1" onsubmit="return validateForm();">
            <div class="form-columns">
                <!-- Left column - Personal Information -->
                <div class="form-column">
                    <h2 class="column-title">Personal Information</h2>
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
                        <input type="tel" name="mobile_number" pattern="[0-9]{11}" placeholder="11 digits" required>
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
                
                <!-- Right column - Government IDs -->
                <div class="form-column">
                    <h2 class="column-title">Government IDs</h2>
                    <div class="form-group">
                        <label>SSS No.</label>
                        <input type="text" name="sss_no" minlength="10" maxlength="10" pattern="[0-9]{10}" placeholder="10 digits">
                    </div>
                    <div class="form-group">
                        <label>Pag-ibig No.</label>
                        <input type="text" name="pagibig_no" minlength="10" maxlength="12" pattern="[0-9]{10,12}" placeholder="10-12 digits">
                    </div>
                    <div class="form-group">
                        <label>Phil Health No.</label>
                        <input type="text" name="philhealth_no" minlength="10" maxlength="12" pattern="[0-9]{10,12}" placeholder="10-12 digits">
                    </div>
                </div>
            </div>
            
            <!-- Hidden input to automatically set status as active -->
            <input type="hidden" name="status" value="active">

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

            if (!firstname || !lastname || !dob || !email || !mobile || !role) {
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
