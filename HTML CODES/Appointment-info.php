<?php 
  session_start();
  require_once "../database.php"; // Ensure database connection
  
  if (!isset($_SESSION['appointment'])) {
      header("Location: Appointment-service.php"); // Redirect if no progress
      exit();
  }
  
  // Check if the appointment is for someone else
  $isForSelf = $_SESSION['appointment']['is_for_self'];
  
  // We want this page to show ONLY for "someone_else" (value 0)
  if ($isForSelf != 0) {
    header("Location: Appointment-calendar.php");
    exit();
  }
  
  $database = new Database();
  $db = $database->getConnection();
  $user_id = $_SESSION['appointment']['user_id'];
  $service_id = $_SESSION['appointment']['service_id'];
  
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $data = json_decode(file_get_contents("php://input"), true);
  
      if (isset($data['firstname'], $data['lastname'], $data['email'], $data['mobile_number'])) {
          $query = "UPDATE appointments SET 
                    firstname = :firstname, lastname = :lastname, 
                    email = :email, mobile_number = :mobile_number 
                    WHERE user_id = :user_id AND service_id = :service_id";
  
          $stmt = $db->prepare($query);
          $stmt->execute([
              ':firstname' => $data['firstname'],
              ':lastname' => $data['lastname'],
              ':email' => $data['email'],
              ':mobile_number' => $data['mobile_number'],
              ':user_id' => $user_id,
              ':service_id' => $service_id
          ]);
  
          echo json_encode(["success" => true, "message" => "Personal information saved."]);
          exit();
      } else {
          echo json_encode(["success" => false, "message" => "Missing required fields."]);
          exit();
      }
  }  
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information</title>
    <link rel="stylesheet" href="../CSS CODES/Appointment-info.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            <li><a href="../Index.php">Home</a></li>
            <li><a href="../HTML CODES/About_us.html">About Us</a></li>
            <li><a href="../HTML CODES/Services.html" class="services">Services</a></li>
            <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Appointment</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php 
                    $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../Pictures/boy.png';
                ?>
                <li class="user-profile">
                    <div class="profile-dropdown">
                        <img src="<?php echo $profile_pic; ?>" alt="Profile" class="profile-pic">
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

<!-- APPOINTMENT FILL UP SECTION -->
    <main>
        <div class="appointment-fillup">
            <label>Fill out personal information:</label>
            <input id="firstname" type="text" placeholder="First name">
            <input id="lastname" type="text" placeholder="Last name">
            <input id="email" type="email" placeholder="Email">
            <input id="mobile_number" type="text" placeholder="Mobile number">
            
            <div class="checkbox-container">
                <input type="checkbox" id="agreement">
                <label for="agreement">I agree to the collection and processing of my personal data in accordance with the Data Privacy Act of 2012 and the company's privacy policy.</label>
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
document.getElementById("nextButton").addEventListener("click", function(event) {
    event.preventDefault(); // Prevent immediate redirection

    let formData = {
        firstname: document.getElementById("firstname").value,
        lastname: document.getElementById("lastname").value,
        email: document.getElementById("email").value,
        mobile_number: document.getElementById("mobile_number").value
    };

    fetch("Appointment-info.php", {  
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = "Appointment-calendar.php"; // Move to next step after saving
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => console.error("Error:", error));
});
</script>


</html>
