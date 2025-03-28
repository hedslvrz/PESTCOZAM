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
    <title>Rat Control Service</title>
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
              <li><a href="../Index.php">Home</a></li>
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
            <h1>Rat Control Service</h1>
            <p class="description">
                Rat Control Service is designed to effectively eliminate and prevent rodent infestations in residential, commercial, and industrial areas. Rats and mice are not only destructive but also carriers of diseases such as leptospirosis, hantavirus, and salmonella. A combination of advanced extermination techniques and preventive measures ensures long-term rodent control, protecting properties and health.
            </p>
            <div class="service-image">
                <img src="../Pictures/rat control.jpg" alt="Rat Control Treatment" class="treatment-image">
            </div>
        </div>

        <div class="service-details">
            <section class="process-section">
                <h2>Service Process</h2>
                <ol>
                    <li><strong>Inspection & Assessment</strong> – Identification of rodent activity, nesting areas, entry points, and food sources.</li>
                    <li><strong>Baiting & Trapping</strong>:
                        <ul>
                            <li>Rodenticides: Eco-friendly, non-harmful-to-humans baiting systems placed in strategic locations.</li>
                            <li>Mechanical Traps: Snap traps, glue boards, and live traps used for safe removal.</li>
                        </ul>
                    </li>
                    <li><strong>Exclusion & Proofing</strong> – Sealing entry points, cracks, and holes to prevent reinfestation.</li>
                    <li><strong>Sanitation & Preventive Measures</strong> – Removal of rodent attractants, proper waste disposal, and recommendations to maintain a rodent-free environment.</li>
                </ol>
            </section>

            <section class="info-grid">
                <div class="info-card">
                    <h3>Target Pests</h3>
                    <ul>
                        <li>House rats</li>
                        <li>Roof rats</li>
                        <li>Norway rats</li>
                        <li>Field mice</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Safety Measures</h3>
                    <ul>
                        <li>Child- and pet-safe rodenticides and traps are used.</li>
                        <li>Proper placement of bait stations to avoid accidental contact with humans or pets.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Estimated Time</h3>
                    <ul>
                        <li>Inspection & Initial Treatment: 1–2 hours</li>
                        <li>Baiting & Trap Placement: 1–3 hours</li>
                        <li>Follow-Up & Monitoring: Weekly or as needed until the infestation is controlled</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Customer Preparation</h3>
                    <ul>
                        <li>Remove exposed food sources and secure garbage bins.</li>
                        <li>Seal any open holes, vents, or cracks where rodents may enter.</li>
                        <li>Inform of any rodent sightings and activity patterns for targeted treatment.</li>
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
