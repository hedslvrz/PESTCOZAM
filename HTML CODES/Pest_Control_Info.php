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
    <title>Pest Inspection Information - PESTCOZAM</title>
    <link rel="stylesheet" href="../CSS CODES/Pest_Control_Info.css">
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
                <li><a href="../index.php#offer-section">Services</a></li>
                <li><a href="../index.php#about-us-section">About Us</a></li>
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

    
    <!-- INFO MAIN -->
    <main class="info-main">
        <div class="info-hero">
            <h1>Understanding Pest Control</h1>
            <p class="subtitle">Professional Solutions for a Pest-Free Environment</p>
        </div>

        <div class="content-wrapper">
            <!-- Overview Section -->
            <section class="info-section">
                <h2>What is Pest Control?</h2>
                <p>Pest control refers to the regulation, management, and elimination of pests that threaten human health, property, and the environment. Pests such as insects, rodents, and other unwanted organisms can cause structural damage, contaminate food, and spread diseases. Effective pest control involves both preventive measures and targeted extermination to ensure a safe and pest-free environment.</p>
            </section>

            <!-- Importance Cards -->
            <section class="importance-section">
                <h2>Importance of Pest Control</h2>
                <div class="importance-grid">
                    <div class="importance-card">
                        <i class='bx bx-plus-medical'></i>
                        <h3>Health Protection</h3>
                        <p>Pests like mosquitoes, cockroaches, and rodents can carry and transmit diseases such as dengue, salmonella, and leptospirosis.</p>
                    </div>
                    <div class="importance-card">
                        <i class='bx bx-home-alt-2'></i>
                        <h3>Property Preservation</h3>
                        <p>Termites, carpenter ants, and other wood-boring pests can weaken buildings and furniture, leading to costly repairs.</p>
                    </div>
                    <div class="importance-card">
                        <i class='bx bx-dish'></i>
                        <h3>Food Safety</h3>
                        <p>Contamination from pests can lead to foodborne illnesses and financial losses, especially in restaurants and food storage areas.</p>
                    </div>
                    <div class="importance-card">
                        <i class='bx bx-leaf'></i>
                        <h3>Environmental Balance</h3>
                        <p>Integrated pest management (IPM) promotes eco-friendly solutions that minimize harm to non-target species and ecosystems.</p>
                    </div>
                </div>
            </section>

            <!-- Methods Section -->
            <section class="methods-section">
                <h2>Pest Control Methods</h2>
                <div class="methods-container">
                    <div class="method-card">
                        <i class='bx bx-shield-quarter'></i>
                        <h3>Preventive Measures</h3>
                        <p>Sealing entry points, proper waste disposal, and maintaining cleanliness reduce the risk of infestations.</p>
                    </div>
                    <div class="method-card">
                        <i class='bx bx-test-tube'></i>
                        <h3>Chemical Control</h3>
                        <p>The use of pesticides and insecticides to eliminate pests while ensuring safety regulations are met.</p>
                    </div>
                    <div class="method-card">
                        <i class='bx bx-bug'></i>
                        <h3>Biological Control</h3>
                        <p>Natural predators or beneficial microorganisms are introduced to control pest populations sustainably.</p>
                    </div>
                    <div class="method-card">
                        <i class='bx bx-target-lock'></i>
                        <h3>Physical Control</h3>
                        <p>Traps, barriers, and temperature treatments are used to remove or repel pests.</p>
                    </div>
                </div>
            </section>

            <!-- Professional Services -->
            <section class="professional-section">
                <h2>Why Professional Pest Control is Essential</h2>
                <div class="professional-content">
                    <div class="text-content">
                        <p>While DIY pest control methods can offer temporary relief, professional services provide long-term solutions through expert assessments, specialized treatments, and preventive strategies. Pest control experts ensure compliance with safety standards, use advanced technology, and customize treatments based on infestation severity.</p>
                    </div>
                    <div class="cta-container">
                        <a href="Appointment-service.php" class="cta-button">Schedule a Service</a>
                    </div>
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
    </script>
</body>
</html>
