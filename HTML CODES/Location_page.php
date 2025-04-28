<?php
session_start();

// Include database connection
require_once '../database.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// If logged in, set user variables for use in the page
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../Pictures/boy.png';
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PESTCOZAM OFFICE</title>
  <link rel="stylesheet" href="../CSS CODES/Location_page.css"/>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <!-- Add SweetAlert2 CSS and JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
  <style>
    html {
    scroll-behavior: smooth;
  }
  </style>
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
      <span class="brand-name">PESTCOZAM</span>
    </div>
    <nav>
      <ul>
        <li><a href="../index.php">Home</a></li>
        <li><a href="Home_page.php#offer-section">Services</a></li>
        <li><a href="Home_page.php#about-us-section">About Us</a></li>
        <li><a href="Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
        <?php if ($is_logged_in): ?>
          <li class="user-profile">
            <div class="profile-dropdown">
              <img src="../Pictures/boy.png" alt="Profile" class="profile-pic">
              <div class="dropdown-content">
                <a href="Profile.php"><i class='bx bx-user'></i> Profile</a>
                <a href="logout.php"><i class='bx bx-log-out'></i> Logout</a>
              </div>
            </div>
          </li>
        <?php else: ?>
          <li class="auth-buttons">
              <a href="Login.php" class="btn-login"><i class='bx bx-log-in'></i> Login</a>
              <a href="Signup.php" class="btn-signup"><i class='bx bx-user-plus'></i> Sign Up</a>
          </li>
        <?php endif; ?>
      </ul>      
    </nav>
  </header>
</div>


    <!-- HERO SECTION -->
  <section class="hero">
    <img src="../Pictures/Location hero.jpg" alt="Hero Background" class="hero-img" />
    <div class="hero-content">
      <h1>PESTCOZAM OFFICE</h1>
      <p>
        PESTCOZAM serves as our main office, where we specialize in professional pest control solutions.
        Our team is committed to effectively eliminating pests, preventing infestations, and ensuring a cleaner,
        safer, and healthier environment for homes and businesses. With our expertise and advanced methods,
        we strive to provide reliable and long-lasting pest management services tailored to our clients' needs.
      </p>
    </div>
  </section>

  <!-- LOCATION TITLE SECTION -->
  <section class="location-title">
    <h2>Our Location</h2>
  </section>

  <!-- MAP SECTION -->
  <section class="map-section">
    <div class="map-container">
      <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.727595440936!2d122.08213807462018!3d6.923131393076589!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3250417c4a5526d5%3A0xa7965524f6cddecf!2sPestcozam%20Pest%20Control%20Services!5e0!3m2!1sen!2sph!4v1740095132168!5m2!1sen!2sph" 
        width="100%" 
        height="100%" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </section>


  <!-- ADDRESS SECTION -->
  <section class="address-section">
    <div class="address-container">
      <div class="address-image">
        <img src="../Pictures/Pestcozam building.png" alt="Office Location">
      </div>
  
      <div class="details-container">
        <div class="info-wrapper">
          <div class="info-block">
            <i class="pestcozam-map"></i>
            <h3>ADDRESS</h3>
            <p>Estrada St, Zamboanga, 7000<br>Zamboanga del Sur</p>
          </div>
  
          <div class="info-block">
            <i class="pestcozam-info"></i>
            <h3>CONTACT INFORMATION</h3>
            <p>0905 - 177 - 5662<br>pestcozam@yahoo.com</p>
          </div>
        </div>
      </div>

      <div class="button-container">
        <a href="https://www.google.com/maps?q=Estrada+St,+Zamboanga,+7000+Zamboanga+del+Sur" target="_blank" class="direction-btn">
          Get Direction
        </a>
      </div>
    </div>
  </section>
  
  
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
          <a href="https://www.facebook.com/PESTCOZAM" target="_blank"><img src="../Pictures/facebook.png" alt="Facebook" /></a>
          <a href="#"><img src="../Pictures/telegram.png" alt="Telegram" /></a>
          <a href="https://www.instagram.com/pestcozam" target="_blank"><img src="../Pictures/instagram.png" alt="Instagram" /></a>
        </div>
      </div>
    </div>
  </footer>
  
  <script>
    let lastScrollTop = 0;
    const headerWrapper = document.querySelector('.header-wrapper');
    const navbarHeight = headerWrapper.offsetHeight;
    
    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Scroll down
        if (scrollTop > lastScrollTop && scrollTop > navbarHeight) {
            headerWrapper.classList.add('hide-nav-group');
        } 
        // Scroll up
        else {
            headerWrapper.classList.remove('hide-nav-group');
        }
        
        lastScrollTop = scrollTop;
    });

    document.addEventListener("DOMContentLoaded", function () {
        // Handle appointment button
        document.querySelectorAll(".btn-appointment").forEach(button => {
            button.addEventListener("click", function (event) {
                event.preventDefault();
    
                // First check if user is logged in
                fetch("../PHP CODES/check_session.php")
                    .then(response => response.json())
                    .then(data => {
                        if (data.loggedIn) {
                            // If logged in, clear any existing appointment session first
                            fetch("../PHP CODES/clear_appointment_session.php")
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        window.location.href = "Appointment-service.php";
                                    }
                                });
                        } else {
                            sessionStorage.setItem("redirectTo", "Appointment-service.php");
                            Swal.fire({
                                icon: 'warning',
                                title: 'Login Required',
                                text: 'You must log in first to make an appointment.',
                                confirmButtonText: 'Login'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "Login.php";
                                }
                            });
                        }
                    });
            });
        });
    });
    </script>
    <script src="../JS CODES/sessionHandler.js"></script>
</body>
</html>
