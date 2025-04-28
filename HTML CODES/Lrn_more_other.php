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
    <title>Flying and Crawling Insects Control</title>
    <link rel="stylesheet" href="../CSS CODES/Learn-more.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
              <li><a href="../index.php#offer-section">Services</a></li>
              <li><a href="../index.php#about-us-section">About Us</a></li>
              <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
              <?php if ($is_logged_in): ?>
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
    </div>

    <!-- SERVICE CONTENT -->
    <main class="service-content">
        <div class="hero-section">
            <h1>Flying and Crawling Insects Control</h1>
            <p class="description">
                Our comprehensive flying and crawling insects control service targets a wide variety of household pests including cockroaches, ants, flies, spiders, and other common insects. Using targeted treatments and preventive measures, we help maintain a pest-free environment in your home or business premises.
            </p>
            <div class="service-image">
                <img src="../Pictures/Other-flying-insects.jpg" alt="Flying and Crawling Insects Control" class="treatment-image">
            </div>
        </div>

        <div class="service-details">
            <section class="process-section">
                <h2>Service Process</h2>
                <ol>
                    <li><strong>Inspection:</strong> Thorough assessment of infestation areas and entry points</li>
                    <li><strong>Treatment Plan:</strong> Customized solution based on pest type and infestation level</li>
                    <li><strong>Application:</strong> Strategic placement of baits and targeted spraying of problem areas</li>
                    <li><strong>Barrier Protection:</strong> Treatment of entry points and potential pest pathways</li>
                    <li><strong>Follow-up:</strong> Monitoring and maintenance recommendations</li>
                </ol>
            </section>

            <section class="info-grid">
                <div class="info-card">
                    <h3>Target Pests</h3>
                    <ul>
                        <li>Cockroaches</li>
                        <li>Ants</li>
                        <li>House flies</li>
                        <li>Spiders</li>
                        <li>Other common household insects</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Safety Measures</h3>
                    <ul>
                        <li>EPA-approved insecticides</li>
                        <li>Child and pet-friendly applications</li>
                        <li>Targeted treatment methods</li>
                        <li>Proper ventilation procedures</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Estimated Time</h3>
                    <ul>
                        <li>Initial Treatment: 1-3 hours</li>
                        <li>Follow-up visits: 30-60 minutes</li>
                        <li>Full treatment cycle: 2-3 visits</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Customer Preparation</h3>
                    <ul>
                        <li>Clear access to treatment areas</li>
                        <li>Secure food items and utensils</li>
                        <li>Temporary evacuation during treatment</li>
                        <li>Follow post-treatment instructions</li>
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
                                icon: 'info',
                                title: 'Login Required',
                                text: 'You must log in first to make an appointment.',
                                confirmButtonText: 'Log In Now',
                                confirmButtonColor: '#144578',
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown'
                                },
                                hideClass: {
                                    popup: 'animate__animated animate__fadeOutUp'
                                }
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
</body>
</html>
