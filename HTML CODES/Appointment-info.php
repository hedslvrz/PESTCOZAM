<?php 
  session_start();
  require_once "../database.php"; // Ensure database connection
  require_once "../PHP CODES/AppointmentSession.php";
  
  if (!isset($_SESSION['user_id'])) {
      header("Location: Login.php"); // Redirect if not logged in
      exit();
  }
  
  // Verify access to this step
  if (!isset($_SESSION['appointment']) || !AppointmentSession::canAccessStep('personal_info')) {
      header("Location: Appointment-service.php");
      exit();
  }
  
  // Get service data from session
  $serviceData = AppointmentSession::getData('service');
  if (!$serviceData) {
      header("Location: Appointment-service.php");
      exit();
  }
  
  $is_for_self = $serviceData['is_for_self'];
  
  // We want this page to show ONLY for "someone_else" (value 0)
  if ($is_for_self != 0) {
    header("Location: Appointment-calendar.php");
    exit();
  }
  
  $database = new Database();
  $db = $database->getConnection();
  $user_id = $_SESSION['user_id'];
  $service_id = $serviceData['service_id'];
  
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $data = json_decode(file_get_contents("php://input"), true);
  
      if (isset($data['firstname'], $data['lastname'], $data['email'], $data['mobile_number'])) {
          // Save to session only (no database updates here)
          AppointmentSession::saveStep('personal_info', [
              'firstname' => $data['firstname'],
              'lastname' => $data['lastname'],
              'email' => $data['email'],
              'mobile_number' => $data['mobile_number']
          ]);
          
          echo json_encode(["success" => true, "message" => "Personal information saved."]);
          exit();
      } else {
          echo json_encode(["success" => false, "message" => "Missing required fields."]);
          exit();
      }
  }
  
  // Pre-populate form fields if personal_info data exists
  $personalInfo = AppointmentSession::getData('personal_info', []);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information</title>
    <link rel="stylesheet" href="../CSS CODES/Appointment-info.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>

  <!-- HEADER -->
 <div class="header-wrapper">
  <header class="top-header">
    <div class="container">
      <div class="location">
        <i class='bx bx-map'></i>
        <span> <strong>Estrada St, Zamboanga City, Zamboanga Del Sur, 7000<strong></span>
      </div>
      <div class="contact-info">
        <img src="../Pictures/phone.png" alt="Phone Icon" class="icon">
        <span>0905 - 177 - 5662</span>
        <span class="divider"></span>
        <img src="../Pictures/email.png" alt="Email Icon" class="icon">
        <span>pestcozam@yahoo.com</span>
      </div>
    </div>
  </header>

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="logo-container">
        <img src="../Pictures/pest_logo.png" alt="Flower Logo" class="flower-logo">
        <span class="brand-name" style="font-size: 2rem;">PESTCOZAM</span>
    </div>
    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../index.php#offer-section">Services</a></li>
            <li><a href="../index.php#about-us-section">About Us</a></li>
            <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="user-profile">
                    <div class="profile-dropdown">
                        <i class='bx bx-menu hamburger-icon'></i>
                        <div class="dropdown-content">
                            <a href="../HTML CODES/Profile.php"><i class='bx bx-user'></i> Profile</a>
                            <a href="../HTML CODES/logout.php"><i class='bx bx-log-out'></i> Logout</a>
                        </div>
                    </div>
                </li>
            <?php else: ?>
                <li class="auth-buttons">
                    <a href="../HTML CODES/Login.php" class="btn-login"><i class='bx bx-log-in'></i> Login</a>
                    <a href="../HTML CODES/Signup.php" class="btn-signup"><i class='bx bx-user-plus'></i> Sign Up</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<!-- Progress Bar -->
<div class="progress-bar">
    <div class="progress-step completed">
        <div class="circle">1</div>
        <div class="label">Select Service</div>
    </div>
    <div class="progress-line completed"></div>
    <div class="progress-step completed">
        <div class="circle">2</div>
        <div class="label">Location</div>
    </div>
    <div class="progress-line completed"></div>
    <div class="progress-step active">
        <div class="circle">3</div>
        <div class="label">Personal Info</div>
    </div>
    <div class="progress-line"></div>
    <div class="progress-step">
        <div class="circle">4</div>
        <div class="label">Schedule</div>
    </div>
</div>

<!-- APPOINTMENT FILL UP SECTION -->
    <main>
        <div class="appointment-fillup">
            <div class="form-header">
                <div class="form-header-logo">
                    <img src="../Pictures/pest_logo.png" alt="Logo">
                    <span>PESTCOZAM</span>
                </div>
                <h3>Personal Information</h3>
            </div>
            
            <div class="form-title">Recipient Details</div>
            <div class="form-description">Please provide information about the person receiving the service</div>
            
            <label>First Name</label>
            <input id="firstname" type="text" placeholder="Enter first name">
            
            <label>Last Name</label>
            <input id="lastname" type="text" placeholder="Enter last name">
            
            <label>Email Address</label>
            <input id="email" type="email" placeholder="Enter email address">
            
            <label>Mobile Number</label>
            <input id="mobile_number" type="text" placeholder="Enter mobile number">
            
            <div class="checkbox-container">
                <input type="checkbox" id="agreement">
                <label for="agreement">I agree to the collection and processing of my personal data in accordance with the <a href="https://www.privacy.gov.ph/data-privacy-act/" target="_blank">Data Privacy Act of 2012</a> and the <a href="../HTML CODES/Privacy-Policy.php" target="_blank">PESTCOZAM Privacy Policy</a>.</label>
            </div>
            
            <div class="navigation-buttons">
                <button onclick="window.location.href='Appointment-loc.php'">Back</button>
                <button id="nextButton">Next</button>
            </div>
        </div>
    </main>

<!-- FOOTER SECTION -->
  <footer class="footer-section">
    <div class="footer-container">
      <div class="footer-left">
        <div class="footer-brand">
          <img src="../Pictures/pest_logo.png" alt="Flower icon" class="flower-icon" />
          <h3 class="brand-name">PESTCOZAM</h3>
        </div>
        <p class="footer-copyright">
          © 2025 Pestcozam. All rights reserved. 
          Designed by FHASK Solutions
        </p>
      </div>
      <div class="footer-right">
        <p class="follow-us-text">Follow us</p>
        <div class="social-icons">
          <a href="#"><img src="../Pictures/facebook.png" alt="Facebook" /></a>
          <a href="#"><img src="../Pictures/telegram.png" alt="Telegram" /></a>
          <a href="#"><img src="../Pictures/instagram.png" alt="Instagram" /></a>
        </div>
      </div>
    </div>
  </footer>
</body>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Navigation warning
    window.onbeforeunload = function() {
        // Check if user has entered any data
        if (document.getElementById("firstname").value || 
            document.getElementById("lastname").value || 
            document.getElementById("email").value || 
            document.getElementById("mobile_number").value) {
            return "You haven't finished booking your appointment. Are you sure you want to leave?";
        }
    };

    // Pre-populate form fields if data exists
    <?php if (!empty($personalInfo)): ?>
    document.getElementById("firstname").value = "<?php echo addslashes($personalInfo['firstname'] ?? ''); ?>";
    document.getElementById("lastname").value = "<?php echo addslashes($personalInfo['lastname'] ?? ''); ?>";
    document.getElementById("email").value = "<?php echo addslashes($personalInfo['email'] ?? ''); ?>";
    document.getElementById("mobile_number").value = "<?php echo addslashes($personalInfo['mobile_number'] ?? ''); ?>";
    <?php endif; ?>
    
    // Clear appointment session when clicking on navigation links
    document.querySelectorAll('nav a:not(.btn-appointment)').forEach(link => {
        link.addEventListener('click', function(event) {
            // Don't show the native browser confirmation
            window.onbeforeunload = null;
            
            // Show our custom confirmation
            event.preventDefault();
            if (confirm("You haven't finished booking your appointment. Are you sure you want to leave?")) {
                // Clear the session via AJAX and then navigate
                fetch('../PHP CODES/clear_appointment_session.php', {
                    method: 'POST'
                })
                .then(() => {
                    window.location.href = this.href;
                });
            }
        });
    });
});

document.getElementById("nextButton").addEventListener("click", function(event) {
    // Disable navigation warning when proceeding to next step
    window.onbeforeunload = null;
    
    event.preventDefault(); // Prevent immediate redirection

    // Simple form validation
    const firstname = document.getElementById("firstname").value;
    const lastname = document.getElementById("lastname").value;
    const email = document.getElementById("email").value;
    const mobile_number = document.getElementById("mobile_number").value;
    const agreement = document.getElementById("agreement").checked;
    
    if (!firstname || !lastname || !email || !mobile_number) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please fill in all required fields',
        });
        return;
    }
    
    if (!agreement) {
        Swal.fire({
            icon: 'warning',
            title: 'Attention',
            text: 'Please agree to the data privacy policy to continue',
        });
        return;
    }

    let formData = {
        firstname: firstname,
        lastname: lastname,
        email: email,
        mobile_number: mobile_number
    };

    fetch("Appointment-info.php", {  
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Personal information saved successfully.',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = "Appointment-calendar.php"; // Move to next step after saving
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error: ' + data.message,
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred.',
        });
        console.error("Error:", error);
    });
});
</script>

</html>
