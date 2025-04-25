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
    
    <!-- Main Content -->
    <main class="info-main">
        <div class="info-hero">
            <h1>Understanding Pest Inspection</h1>
            <p class="subtitle">Your Guide to Professional Pest Detection and Prevention</p>
        </div>

        <div class="content-wrapper">
            <!-- Overview Section -->
            <section class="info-section">
                <h2>Pest Inspection Overview</h2>
                <p>Pest inspection is a crucial process in identifying and preventing pest infestations before they cause significant damage. It involves a thorough examination of properties to detect signs of pests, assess risk areas, and recommend appropriate treatments. Regular inspections help homeowners and businesses maintain a safe, pest-free environment by addressing issues early.</p>
            </section>

            <!-- Importance Cards -->
            <section class="importance-section">
                <h2>Importance of Pest Inspections</h2>
                <div class="importance-grid">
                    <div class="importance-card">
                        <i class='bx bx-search-alt'></i>
                        <h3>Early Detection</h3>
                        <p>Identifies pest activity before it leads to severe damage or infestation.</p>
                    </div>
                    <div class="importance-card">
                        <i class='bx bx-building-house'></i>
                        <h3>Property Protection</h3>
                        <p>Prevents structural damage caused by termites, rodents, and other destructive pests.</p>
                    </div>
                    <div class="importance-card">
                        <i class='bx bx-plus-medical'></i>
                        <h3>Health & Safety</h3>
                        <p>Helps mitigate health risks from pests that carry diseases or contaminate food and surfaces.</p>
                    </div>
                    <div class="importance-card">
                        <i class='bx bx-check-shield'></i>
                        <h3>Compliance & Prevention</h3>
                        <p>Ensures businesses meet health and safety regulations while preventing costly exterminations.</p>
                    </div>
                </div>
            </section>

            <!-- Methods Section -->
            <section class="methods-section">
                <h2>Key Areas Covered in Inspections</h2>
                <div class="methods-container">
                    <div class="method-card">
                        <i class='bx bx-building'></i>
                        <h3>Structural Examination</h3>
                        <p>Checking walls, ceilings, floors, and wooden structures for pest damage.</p>
                    </div>
                    <div class="method-card">
                        <i class='bx bx-door-open'></i>
                        <h3>Entry Point Identification</h3>
                        <p>Inspecting doors, windows, vents, and cracks where pests may enter.</p>
                    </div>
                    <div class="method-card">
                        <i class='bx bx-droplet'></i>
                        <h3>Moisture & Food Sources</h3>
                        <p>Identifying conditions that attract pests, such as leaks, waste buildup, and food storage areas.</p>
                    </div>
                    <div class="method-card">
                        <i class='bx bx-landscape'></i>
                        <h3>Outdoor & Surroundings Check</h3>
                        <p>Assessing gardens, drainage systems, and storage areas where pests may thrive.</p>
                    </div>
                </div>
            </section>

            <!-- Professional Services -->
            <section class="professional-section">
                <h2>Why Regular Inspections Matter</h2>
                <div class="professional-content">
                    <div class="text-content">
                        <p>Routine pest inspections help prevent unexpected infestations, saving money on costly treatments and repairs. Professional inspectors use specialized tools, such as moisture meters and thermal imaging, to detect hidden pest activity and provide tailored prevention strategies.</p>
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
