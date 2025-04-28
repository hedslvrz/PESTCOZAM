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
    <title>Mosquito Control Service</title>
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
            <h1>Mosquito Control Service</h1>
            <p class="description">
                Mosquito Control Service is designed to reduce and eliminate mosquito populations in residential and commercial areas, ensuring a safer and more comfortable environment. Mosquitoes are not only a nuisance but also carriers of diseases such as dengue, malaria, and chikungunya. A combination of treatment methods targets breeding grounds, adult mosquitoes, and larvae for long-term protection.
            </p>
            <div class="service-image">
                <img src="../Pictures/Mosquito control.jpg" alt="Mosquito Control Treatment" class="treatment-image">
            </div>
        </div>

        <div class="service-details">
            <section class="process-section">
                <h2>Service Process</h2>
                <ol>
                    <li><strong>Site Inspection & Assessment</strong> – Identification of mosquito breeding areas, stagnant water sources, and high-risk zones.</li>
                    <li><strong>Larvicide Treatment</strong> – Application of eco-friendly larvicides to standing water sources, preventing mosquito larvae from developing into adults.</li>
                    <li><strong>Fogging & Spraying</strong> – A specialized misting or fogging treatment eliminates adult mosquitoes, targeting shaded areas where they rest.</li>
                    <li><strong>Preventive Measures & Recommendations</strong> – Expert advice is provided on reducing mosquito breeding grounds, such as proper waste disposal and drainage management.</li>
                </ol>
            </section>

            <section class="info-grid">
                <div class="info-card">
                    <h3>Target Pests</h3>
                    <ul>
                        <li>Aedes mosquitoes (carriers of dengue and Zika virus)</li>
                        <li>Anopheles mosquitoes (carriers of malaria)</li>
                        <li>Culex mosquitoes (carriers of encephalitis and filariasis)</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Safety Measures</h3>
                    <ul>
                        <li>WHO-approved, non-toxic treatments that are safe for humans, pets, and the environment are used.</li>
                        <li>Clients may need to stay indoors for 30 minutes to 1 hour after fogging to allow the treatment to settle.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Estimated Time</h3>
                    <ul>
                        <li>Inspection & Initial Treatment: 1–2 hours</li>
                        <li>Fogging & Spraying: 30 minutes – 1 hour (depending on area size)</li>
                        <li>Follow-Up & Maintenance: Monthly or as needed for best results</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Customer Preparation</h3>
                    <ul>
                        <li>Cover food, drinking water, and pet bowls before treatment.</li>
                        <li>Remove or cover fish tanks and birdcages.</li>
                        <li>Ensure proper drainage to prevent future mosquito breeding.</li>
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
