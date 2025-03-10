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
    <title>Termite Control Service</title>
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
              <li><a href="../HTML CODES/Home_page.php">Home</a></li>
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
            <h1>Termite Control Service</h1>
            <p class="description">
                Termite Control Service is designed to eliminate and prevent termite infestations that can cause severe structural damage to homes and buildings. Advanced termite detection methods, targeted treatment solutions, and preventive measures ensure long-term protection. Whether dealing with an active infestation or looking for preventive treatment, this service provides safe and effective solutions.
            </p>
            <div class="service-image">
                <img src="../Pictures/termite control.jpg" alt="Termite Control Treatment" class="treatment-image">
            </div>
        </div>

        <div class="service-details">
            <section class="process-section">
                <h2>Service Process</h2>
                <ol>
                    <li><strong>Inspection & Assessment</strong> – Specialists conduct a thorough inspection using advanced tools to identify termite activity, entry points, and colony locations.</li>
                    <li><strong>Treatment Application</strong> – Depending on the infestation level, one or more of the following methods are applied:
                        <ul>
                            <li>Liquid Termiticides: Applied to soil and entry points to create a protective barrier.</li>
                            <li>Baiting Systems: Non-toxic bait stations attract and eliminate termite colonies.</li>
                            <li>Wood Treatment: Direct treatment on affected wooden structures to kill termites and prevent further damage.</li>
                        </ul>
                    </li>
                    <li><strong>Post-Treatment Monitoring</strong> – Follow-up inspections ensure effectiveness and provide maintenance recommendations.</li>
                </ol>
            </section>

            <section class="info-grid">
                <div class="info-card">
                    <h3>Target Pests</h3>
                    <ul>
                        <li>Subterranean termites</li>
                        <li>Drywood termites</li>
                        <li>Dampwood termites</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Safety Measures</h3>
                    <ul>
                        <li>Eco-friendly, low-toxicity solutions that are safe for humans and pets are used.</li>
                        <li>Clients may need to vacate the treated area for a few hours, depending on the treatment method used.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Estimated Time</h3>
                    <ul>
                        <li>Inspection & Initial Treatment: 2–4 hours</li>
                        <li>Baiting System Installation: 3–5 hours</li>
                        <li>Full-Termite Eradication: May take weeks for complete colony elimination, depending on the severity.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Customer Preparation</h3>
                    <ul>
                        <li>Remove furniture and valuables from affected areas.</li>
                        <li>Clear any obstructions near walls and wooden structures for easy inspection.</li>
                        <li>Inform of any prior termite treatments or home renovations that might affect treatment plans.</li>
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
