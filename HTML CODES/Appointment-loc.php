<?php 
  session_start();
  require_once "../database.php"; 
  
  if (!isset($_SESSION['appointment'])) {
      header("Location: Appointment-service.php");
      exit();
  }
  
  $database = new Database();
  $db = $database->getConnection();
  $user_id = $_SESSION['appointment']['user_id'];
  $service_id = $_SESSION['appointment']['service_id'];
  
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $data = json_decode(file_get_contents("php://input"), true);
  
      if (isset($data['region'], $data['province'], $data['city'], $data['barangay'])) {
          $query = "UPDATE appointments SET 
                    region = :region, province = :province, city = :city, 
                    barangay = :barangay, street_address = :street_address 
                    WHERE user_id = :user_id AND service_id = :service_id";
  
          $stmt = $db->prepare($query);
          $result = $stmt->execute([
              ':region' => $data['region'],
              ':province' => $data['province'],
              ':city' => $data['city'],
              ':barangay' => $data['barangay'],
              ':street_address' => $data['street_address'],
              ':user_id' => $user_id,
              ':service_id' => $service_id
          ]);
  
          // Remove the header redirects and just send JSON response
          echo json_encode([
              "success" => true,
              "message" => "Location details saved.",
              "is_for_self" => $_SESSION['appointment']['is_for_self'],
              "next_page" => $_SESSION['appointment']['is_for_self'] == 0 ? 
                            "Appointment-info.php" : "Appointment-calendar.php"
          ]);
          exit();
      } else {
          echo json_encode(["success" => false, "message" => "Missing location details."]);
          exit();
      }
  }
  
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="../CSS CODES/Appointment-loc.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <!-- HEADER -->
  <header class="top-header">
    <div class="container">
        <div class="location">
            <span>• <strong>Zamboanga</strong> • <strong>Pagadian</strong> • <strong>Pasay</strong> • <strong>Davao</strong></span>
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
                <li><a href="../HTML CODES/Home_page.php">Home</a></li>
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

    <main>
        <div class="appointment-map">
            <label>Location Details:</label>
            <select id="region" name="region">
                <option value="">Select Region</option>
            </select>
            <select id="province" name="province">
                <option value="">Select Province</option>
            </select>
            <select id="city" name="city">
                <option value="">Select City</option>
            </select>
            <select id="barangay" name="barangay">
                <option value="">Select Barangay</option>
            </select>
            
            <input type="text" id="street_address" class="specify-addr" placeholder="Please specify Street Name & House/Building No. (Optional if not applicable)">
            
            <div class="map-container">
                <p>Please drag or tap on the map to mark the exact location for your pest treatment.</p>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.727595440955!2d122.08213807493584!3d6.92313139307655!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3250417c4a5526d5%3A0xa7965524f6cddecf!2sPestcozam%20Pest%20Control%20Services!5e0!3m2!1sen!2sph!4v1740376200786!5m2!1sen!2sph" width="500" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            
            <div class="navigation-buttons">
                <button onclick="window.location.href='Appointment-service.php'">Back</button>
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
document.getElementById("nextButton").addEventListener("click", function() {
    let formData = {
        region: document.getElementById("region").value,
        province: document.getElementById("province").value,
        city: document.getElementById("city").value,
        barangay: document.getElementById("barangay").value,
        street_address: document.getElementById("street_address").value
    };

    fetch("Appointment-loc.php", {  
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Check session to decide where to go next
            <?php if ($_SESSION['appointment']['is_for_self'] == 0): ?>
                window.location.href = "Appointment-info.php"; // Go to info page if for someone else
            <?php else: ?>
                window.location.href = "Appointment-calendar.php"; // Skip info page if for self
            <?php endif; ?>
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => console.error("Error:", error));
});
</script>
</html>
