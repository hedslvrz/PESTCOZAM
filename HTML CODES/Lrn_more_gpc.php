<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// If logged in, set user variables for use in the page
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../Pictures/boy.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Pest Control Service</title>
    <link rel="stylesheet" href="../CSS CODES/Learn-more.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<div class="header-wrapper">
      <!-- HEADER -->
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
              <li><a href="#offer-section">Services</a></li>
              <li><a href="#about-us-section">About Us</a></li>
              <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
              <?php if ($is_logged_in): ?>
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
    </div>


    <!-- SERVICE CONTENT -->
    <main class="service-content">
        <div class="hero-section">
            <h1>General Pest Control Service</h1>
            <p class="description">
                General Pest Control Service provides a comprehensive solution to eliminate and prevent common household and commercial pests. Uncontrolled pest infestations can lead to property damage, health risks, and discomfort. A combination of inspection, treatment, and preventive measures ensures long-term protection against various pests, creating a cleaner and safer environment.
            </p>
            <div class="service-image">
                <img src="../Pictures/gpc pic.jpg" alt="General Pest Control Treatment" class="treatment-image">
            </div>
        </div>

        <div class="service-details">
            <section class="process-section">
                <h2>Service Process</h2>
                <ol>
                    <li><strong>Inspection & Assessment</strong> – Identification of pest types, infestation severity, and entry points.</li>
                    <li><strong>Treatment Application</strong> – Depending on the pest species and infestation level, various treatment methods are used, including:
                        <ul>
                            <li>Residual Spraying: Application of insecticides in infested areas to eliminate crawling and flying insects.</li>
                            <li>Baiting Systems: Use of attractant baits to control rodents, cockroaches, and ants.</li>
                            <li>Fogging & Misting: Fine mist application to reach hidden pest breeding areas.</li>
                            <li>Traps & Monitoring Devices: Placement of non-toxic traps for rodents and crawling insects.</li>
                        </ul>
                    </li>
                    <li><strong>Preventive Measures & Recommendations</strong> – Sealing entry points, sanitation guidelines, and scheduled maintenance plans to prevent reinfestation.</li>
                </ol>
            </section>

            <section class="info-grid">
                <div class="info-card">
                    <h3>Target Pests</h3>
                    <ul>
                        <li>Cockroaches</li>
                        <li>Ants</li>
                        <li>Rodents (rats & mice)</li>
                        <li>Flies</li>
                        <li>Fleas & ticks</li>
                        <li>Spiders</li>
                        <li>Centipedes & millipedes</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Safety Measures</h3>
                    <ul>
                        <li>Use of eco-friendly, low-toxicity pesticides that are safe for humans and pets.</li>
                        <li>Treated areas should be avoided for a few hours after service to allow chemicals to settle.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Estimated Time</h3>
                    <ul>
                        <li>Inspection & Initial Treatment: 1–3 hours (depending on the severity and size of the area)</li>
                        <li>Fogging & Spraying: 30 minutes – 1 hour</li>
                        <li>Follow-Up & Maintenance: Monthly, quarterly, or as needed for long-term protection</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Customer Preparation</h3>
                    <ul>
                        <li>Cover food, utensils, and pet items before treatment.</li>
                        <li>Keep children and pets away from the treated area during and after service.</li>
                        <li>Repair any cracks, leaks, or open entry points to prevent future infestations.</li>
                    </ul>
                </div>
            </section>
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
