<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// If logged in, set user variables for use in the page
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../Pictures/boy.png';
}

// Include database connection for fetching service details if needed
require_once '../database.php';
$database = new Database();
$db = $database->getConnection();

// Get service ID from URL parameter
$service_id = isset($_GET['service_id']) ? $_GET['service_id'] : 0;

// Fetch service details from database if needed
$query = "SELECT * FROM services WHERE service_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$service_id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ocular Inspection Service</title>
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
            <h1>Ocular Inspection Service</h1>
            <p class="description">
                An ocular inspection is the essential first step before implementing any pest control treatment. During this inspection, our expert technicians thoroughly examine your property to identify the type and extent of infestation, locate entry points and harborage areas, and develop a customized treatment plan tailored to your specific needs.
            </p>
            <div class="service-image">
                <img src="../Pictures/<?= htmlspecialchars($service['image_path'] ?? 'ocular-inspection.jpg') ?>" alt="Ocular Inspection Process" class="treatment-image">
            </div>
        </div>

        <div class="service-details">
            <section class="process-section">
                <h2>Inspection Process</h2>
                <ol>
                    <li><strong>Scheduling:</strong> We'll arrange a convenient time to visit your property, typically within 24-48 hours of your request.</li>
                    <li><strong>Initial Consultation:</strong> Our inspector will discuss your concerns and any pest activity you've noticed to better understand your situation.</li>
                    <li><strong>Thorough Examination:</strong> We'll carefully inspect both interior and exterior areas for signs of pest activity, damage, and potential entry points.</li>
                    <li><strong>Documentation:</strong> We document our findings, including photos if necessary, to develop a comprehensive treatment plan.</li>
                    <li><strong>Recommendations:</strong> We'll provide detailed treatment options with transparent pricing based on our inspection findings.</li>
                    <li><strong>Treatment Scheduling:</strong> If you choose to proceed, we can schedule your treatment service right away.</li>
                </ol>
            </section>

            <section class="info-grid">
                <div class="info-card">
                    <h3>What We Identify</h3>
                    <ul>
                        <li>Type and extent of pest infestation</li>
                        <li>Entry points and harborage areas</li>
                        <li>Conducive conditions that may attract pests</li>
                        <li>Structural vulnerabilities</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Benefits</h3>
                    <ul>
                        <li>Accurate assessment of your pest situation</li>
                        <li>Customized treatment recommendations</li>
                        <li>Precise cost estimation based on your specific needs</li>
                        <li>Prevention advice to avoid future infestations</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Estimated Time</h3>
                    <ul>
                        <li>Residential properties: 30-60 minutes</li>
                        <li>Commercial properties: 1-2 hours, depending on size</li>
                        <li>Large facilities may require additional time</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Important Note</h3>
                    <ul>
                        <li>Ocular inspection is required before any treatment service</li>
                        <li>The inspection service is provided free of charge</li>
                        <li>No obligation to proceed with recommended treatments</li>
                        <li>Access to all areas of concern is necessary for thorough inspection</li>
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
