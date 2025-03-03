<?php
// Include the database connection
require_once '../database.php'; 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php"); // Redirect to login if not logged in
    exit();
}

// Initialize database class
$database = new Database();
$db = $database->getConnection();

// Fetch services
$query = "SELECT service_name FROM services";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="../CSS CODES/Appointment-service.css">
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
          <li><a href="../HTML CODES/Home_page.html">Home</a></li>
          <li><a href="../HTML CODES/About_us.html">About Us</a></li>
          <li><a href="../HTML CODES/Services.html" class="services">Services</a></li>
          <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Appointment</a></li>
          <li><a href="../HTML CODES/Login.php" class="btn-login"><i class='bx bx-log-in' ></i> Login</a></li>
          <li><a href="../HTML CODES/Signup.php" class="btn-signup"><i class='bx bx-user-plus' ></i> Sign Up</a></li>
        </ul>
    </nav>
  </header>

    <!-- Appointment Selection Section -->
    <main>
        <div class="form-container">
            <h2 class="Appointment-lbl">Book an Appointment</h2>
            <select id="service" name="service">
                  <option>Select Type of Service</option>
                  <?php foreach ($services as $service): ?>
                      <option><?= htmlspecialchars($service['service_name']) ?></option>
                  <?php endforeach; ?>
            </select>
            
            <div class="reminder">  
                <p>Friendly Reminder:</p>
                <ol>
                    <li>A professional will perform an ocular inspection of the site to assess the pest problem and recommend the best treatment.</li>
                    <li>The total cost of the service will be determined after the ocular inspection. Our experts will assess the severity of the pest issue and recommend the best treatment plan. Pricing may vary depending on the size of the area (square meters) and the type of treatment required. A detailed quotation will be provided after the inspection.</li>
                </ol>
            </div>
            <button onclick="window.location.href='Appointment-loc.html'">Next</button>
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
</html>
