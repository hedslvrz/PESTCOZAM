<?php
session_start();

// Include database connection
require_once 'database.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// If logged in, set user variables for use in the page
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : './Pictures/boy.png';
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Fetch services from database
$query = "SELECT service_id, service_name, description, estimated_time, starting_price, image_path, 
          CASE WHEN service_name = 'Ocular Inspection' THEN 1 ELSE 0 END AS is_ocular 
          FROM services ORDER BY is_ocular DESC, service_id ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate ocular inspection from other services
$ocularService = null;
$regularServices = [];

foreach ($services as $service) {
    if ($service['service_name'] == 'Ocular Inspection') {
        $ocularService = $service;
    } else {
        $regularServices[] = $service;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="./CSS CODES/Home_page.css">
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
                  <img src="./Pictures/phone.png" alt="Phone Icon" class="icon">
                  <span>0905 - 177 - 5662</span>
                  <span class="divider"></span>
                  <img src="./Pictures/email.png" alt="Email Icon" class="icon">
                  <span>pestcozam@yahoo.com</span>
              </div>
          </div>
      </header>

      <!-- NAVBAR -->
      <header class="navbar">
          <div class="logo-container">
              <img src="./Pictures/pest_logo.png" alt="Flower Logo" class="flower-logo">
              <span class="brand-name" style="font-size: 2rem;">PESTCOZAM</span>
          </div>
          <nav>
            <ul>
              <li><a href="Index.php">Home</a></li>
              <li><a href="#offer-section">Services</a></li>
              <li><a href="#about-us-section">About Us</a></li>
              <li><a href="./HTML CODES/Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
              <?php if ($is_logged_in): ?>
              <li class="user-profile">
                  <div class="profile-dropdown">
                      <img src="./Pictures/boy.png" alt="Profile" class="profile-pic">
                      <div class="dropdown-content">
                          <a href="./HTML CODES/Profile.php"><i class='bx bx-user'></i> Profile</a>
                          <a href="./HTML CODES/logout.php"><i class='bx bx-log-out'></i> Logout</a>
                      </div>
                  </div>
              </li>
          <?php else: ?>
              <li class="auth-buttons">
                  <a href="./HTML CODES/Login.php" class="btn-login"><i class='bx bx-log-in'></i> Login</a>
                  <a href="./HTML CODES/Signup.php" class="btn-signup"><i class='bx bx-user-plus'></i> Sign Up</a>
              </li>
          <?php endif; ?>
            </ul>      
          </nav>
      </header>
    </div>

    <!-- HERO SECTION -->
    <section class="hero">
      <div class="hero-content">
        <h1>ZERO PEST.<br>ZERO STRESS.</h1>
        <p>
          A company that provides Integrated Pest Management using a<br>
          variety of methods in controlling pests.
        </p>
      </div>
      <div class="hero-image">
        <img src="./Pictures/hero image.jpg" alt="Hero Image">
      </div>
    </section>        

    <!-- INFO SECTION -->
    <section class="info-section">
        <div class="info-box">
            <div class="info-icon">
                <img src="./Pictures/phone_2.png" alt="Phone Icon">
            </div>
            <div class="info-text">
                <h3>Call Us Now</h3>
                <p>0905 - 177 - 5662</p>
            </div>
        </div>
        
        <a href="./HTML CODES/Location_page.html" class="info-box">
            <div class="info-icon">
                <img src="./Pictures/location.png" alt="Location Icon">
            </div>
            <div class="info-text">
                <h3>Coverage Area</h3>
                <p>Find Our Location</p>
            </div>
        </a>
        
        <a href="./HTML CODES/Appointment-service.php" class="info-box">
          <div class="info-icon">
              <img src="./Pictures/Appointment.png" alt="Appointment Icon">
          </div>
          <div class="info-text">
              <h3>Appointment</h3>
              <p>Set An Appointment</p>
          </div>
        </a>
    </section>
    
    <!-- WHO WE ARE SECTION -->
    <section class="who-we-are">
      <div class="who-container">
        <div class="who-text">
          <h4>WHO WE ARE</h4>
          <h2>The pest control expert you can trust.</h2>
          <p>
            Nec consectetur lacinia in enim dapibus ullamcorper. Mi donec maecenas
            ad potenti elementum dignissim lectus. Ridiculus parturient sed aenean
            potenti ex amet adipiscing imperdiet sem.
          </p>
          <p>
            Nec consectetur lacinia in enim dapibus ullamcorper. Mi donec maecenas
            ad potenti elementum dignissim lectus. Ridiculus parturient sed aenean
            potenti ex amet adipiscing imperdiet sem.
          </p>
        </div>
        <div class="who-video">
          <video class="analytics-video" autoplay muted loop onclick="this.paused ? this.play() : this.pause()">
            <source src="./Videos/who-we-are.mp4" type="video/mp4" />
            Your browser does not support the video tag.
          </video>
          <div class="testimonial-box">
            <p>
              "Eget mi ridiculus dictum urna sed sodales venenatis eleifend mollis
              fermentum sapien euismod sit quisque."
            </p>
            <span>-Hedrian Dunn Alvarez</span>
          </div>
        </div>
      </div>
    </section>
    
    <!-- PEST CONTROL SECTION -->
    <section class="pest-control-section">
        <div class="pest-control-container">
            <div class="pest-content">
                <h2>Say Goodbye<br>To Pests for Good!</h2>
                <p>We specialize in keeping your spaces safe, clean, and pest-free! With our expert pest control solutions, you can enjoy peace of mind knowing your home and business is protected.</p>
                <button class="find-services-btn" onclick="document.getElementById('offer-section').scrollIntoView({behavior: 'smooth'})">Find Services</button>
            </div>
            
            <div class="pest-image">
                <div class="pestcozam-mascot">
                    <img src="./Pictures/Pestcozam-Mascot.png" class="image-front">
                </div>
            </div>
        </div>
    </section>

    <!-- EXPERT SECTION -->
    <section class="expert-section">
      <div class="expert-container">
          <div class="expert-image">
              <img src="./Pictures/Expert pic.jpg" alt="Pest Control Expert">
          </div>
          <div class="expert-content">
              <h2>The pest control expert<br>you can trust.</h2>
              <div class="expert-cards">
                  <div class="card white-card">
                      <h3>Pest Control</h3>
                      <p>Les convails felis habitasse libra<br>
                      Lec convails felis habitasse libra<br>
                      torquent augue.Natoque possiere libra</p>
                      <div class="button-group">
                          <button class="learn-btn" onclick="window.location.href='./HTML CODES/Pest_Control_Info.php'">Learn More</button>
                      </div>
                  </div>
                  <div class="card blue-card">
                      <h3>Inspections</h3>
                      <p>elementum non sociosqu.Vulputate enim<br>
                      plates non convails aenean laculis.</p>
                      <div class="button-group">
                          <button class="learn-btn" onclick="window.location.href='./HTML CODES/Inspection_Info.php'">Learn More</button>
                      </div>
                  </div>
              </div>
          </div>
      </div>
    </section>
  
  <!-- Analytics Section -->
  <section class="analytics-section">
  <div class="analytics-container">
    <div class="analytics-video-container">
      <video class="analytics-video" autoplay muted loop onclick="this.paused ? this.play() : this.pause()">
        <source src="./Videos/analytics-vid.mp4" type="video/mp4">
      </video>
    </div>

    <!-- Stats Row -->
    <div class="analytics-stats">
      <div class="stat-box">
        <h3>1k+</h3>
        <p>Happy Customers</p>
      </div>

      <div class="stat-box">
        <h3>500</h3>
        <p>Company Support</p>
      </div>

      <div class="stat-box">
        <h3>70</3>
        <p>Professional Expert</p>
      </div>

      <div class="stat-box">
        <h3>5+</h3>
        <p>Years Experience</p>
      </div>
    </div>
  </div>
</section>

  <!-- WHAT WE OFFER SECTION -->
  <section class="offer-section" id="offer-section">
    <div class="offer-container">

      <p class="section-subtitle">WHAT WE OFFER</p>
      <h2 class="pest-title">Our Pest Solutions</h2>

      <?php if ($ocularService): ?>
      <!-- Ocular Inspection Card -->
      <div class="offer-card">
        <img src="./Pictures/<?= htmlspecialchars($ocularService['image_path']) ?>" alt="<?= htmlspecialchars($ocularService['service_name']) ?>" />
        <div class="offer-text">
          <h3><?= htmlspecialchars($ocularService['service_name']) ?></h3>
          <p><?= htmlspecialchars($ocularService['description']) ?></p>
          <div class="service-details">
            <p><i class='bx bx-time'></i> Est. Time: <?= htmlspecialchars($ocularService['estimated_time']) ?></p>
            <?php if ($ocularService['starting_price'] == 0): ?>
              <p><i class='bx bx-check-circle'></i> Free of Charge</p>
            <?php else: ?>
              <p><i class='bx bx-money'></i> Starting at ₱<?= number_format($ocularService['starting_price']) ?></p>
            <?php endif; ?>
            <p><i class='bx bx-info-circle'></i> Required before any treatment service</p>
          </div>
          <div class="button-group">
            <button class="book-now-btn" onclick="window.location.href='./HTML CODES/Appointment-service.php'">Book Now</button>
            <button class="learn-more-btn" onclick="window.location.href='./HTML CODES/Lrn_more_sp.php?service_id=<?= $ocularService['service_id'] ?>'">Learn More</button>
          </div>
        </div>
      </div>

      <div class="section-divider"></div>
      <?php endif; ?>

      <div class="offer-grid">
        <?php foreach ($regularServices as $service): ?>
        <!-- Service Card -->
        <div class="offer-card">
          <img src="./Pictures/<?= htmlspecialchars($service['image_path']) ?>" alt="<?= htmlspecialchars($service['service_name']) ?>" />
          <div class="offer-text">
            <h3><?= htmlspecialchars($service['service_name']) ?></h3>
            <p><?= htmlspecialchars($service['description']) ?></p>
            <div class="service-details">
              <p><i class='bx bx-time'></i> Est. Time: <?= htmlspecialchars($service['estimated_time']) ?></p>
              <p><i class='bx bx-money'></i> Starting at ₱<?= number_format($service['starting_price']) ?></p>
            </div>
            <div class="button-group">
              <button class="book-now-btn" onclick="window.location.href='./HTML CODES/Appointment-service.php'">Book Now</button>
              <button class="learn-more-btn" onclick="window.location.href='./HTML CODES/Lrn_more_sp.php?service_id=<?= $service['service_id'] ?>'">Learn More</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="pricing-notice">
        <p><i class='bx bx-info-circle'></i> Note: Final pricing may vary based on inspection and property size.</p>
      </div>
    </div>
  </section>

<!-- ABOUT US SECTION -->
  <div class="image-container" id="about-us-section">
    <img src="./Pictures/about us.jpg" alt="Pest Control Worker">
    <div class="overlay">
      <h1>ABOUT US</h1>
    </div>
  </div>


<!-- VISION AND MISSION SECTION -->
  <div class="vision-mission-container">
    <div class="vision-mission-content">
      <div class="title">Guaranteed pest removal with no hassle for you</div>
      <p>A Pest Control service provider registered with the Department of Trade & Industry that offers a high quality of service to protect your properties from unwanted/disastrous pests.</p>
    </div>
    <div class="vision-mission-blocks">
      <div class="vision-block">
        <div class="block-title">Vision</div>
        <p>A Pest Control service provider registered with the Department of Trade & Industry that offers a high quality of service to protect your properties from unwanted/disastrous pests.</p>
      </div>
      <div class="mission-block">
        <div class="block-title">Mission</div>
        <p>"We promote the safe and responsible use of pesticides to protect human health, preserve the environment, and support sustainable agriculture. Through education and innovation, we minimize risks and advocate for eco-friendly pest control solutions."</p>
      </div>
    </div>
  </div>


<!-- WHY-PESTCOZAM SECTION -->
  <section id="why-pestcozam-section">
    <div class="why-pestcozam-container">
      <div class="why-pestcozam-content">
        <div class="section-title">Why choose PESTCOZAM?</div>
        <div class="main-title">Committed<br>to Your<br>Comfort</div>
        <a href="#" class="discover-button">Discover more</a>
      </div>
      <div class="why-pestcozam-grid">
        <div class="grid-item" style="background-image: url('./Pictures/Exp-Prof.jpg');">
          <div class="item-title">Experienced<br>Professionals</div>
        </div>
        <div class="grid-item" style="background-image: url('./Pictures/Cust-Treatment.jpg');">
          <div class="item-title">Customized<br>Treatments</div>
        </div>
        <div class="grid-item" style="background-image: url('./Pictures/eco-friendly.jpg');">
          <div class="item-title">Eco-Friendly<br>Solutions</div>
        </div>
        <div class="grid-item" style="background-image: url('./Pictures/transparent-pricing.jpg');">
          <div class="item-title">Transparent<br>Pricing</div>
        </div>
        <div class="grid-item" style="background-image: url('./Pictures/guaranteed-results.jpg');">
          <div class="item-title">Guaranteed<br>Results</div>
        </div>
        <div class="grid-item" style="background-image: url('./Pictures/fast-and-responsive.jpg');">
          <div class="item-title">Fast and<br>Responsive</div>
        </div>
      </div>
    </div>
  </section>


<!-- REVIEWS SECTION - HARDCODED MUNA -->
<section id="reviews-section">
  <div class="reviews-container">
    <div class="reviews-header">
      <div class="customer-title">What Our Customer are saying</div>
      <p>See what our happy customers are saying about their experiences with us!</p>
    </div>
    <div class="reviews-slider">
      <div class="review-slide">
        <div class="review-card">
          <img src="./Pictures/woman.png" alt="Evangeline Tecson Bue" class="review-image">
          <div class="review-content">
            <h3 class="review-name">Evangeline Tecson Bue</h3>
            <div class="review-stars">★★★★★</div>
            <p>"I can't believe the no of caught rats, cockroaches and all. Thank you PESTCOZAM Highly recommended effective and affordable cost"</p>
          </div>
        </div>
      </div>
      <div class="review-slide">
        <div class="review-card">
          <img src="./Pictures/woman.png" alt="Loriel Keme" class="review-image">
          <div class="review-content">
            <h3 class="review-name">Loriel Keme</h3>
            <div class="review-stars">★★★★★</div>
            <p>"Outstanding pest control service. Prompt response, friendly staff and through inspection. The technician was knowledgeable, used eco-friendly products. Highly recommended this professional and effective service"</p>
          </div>
        </div>
      </div>
      <div class="review-slide">
        <div class="review-card">
          <img src="./Pictures/boy.png" alt="Martin Go" class="review-image">
          <div class="review-content">
            <h3 class="review-name">Martin Go</h3>
            <div class="review-stars">★★★★★</div>
            <p>Great service and very detail oriented, would definitely recommend!</p>
          </div>
        </div>
      </div>
      </div>
    <div class="slider-controls">
      <button class="prev-button">←</button>
      <button class="next-button">→</button>
    </div>
    <a href="./HTML CDOES/Appointment-service.php" class="book-now-button">BOOK NOW!</a>
  </div>
</section>

  <!-- FOOTER SECTION -->
  <footer class="footer-section">
    <div class="footer-container">
      <div class="footer-left">
        <div class="footer-brand">
          <img src="./Pictures/pest_logo.png" alt="Flower icon" class="flower-icon" />
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
          <a href="https://www.facebook.com/PESTCOZAM" target="_blank"><img src="./Pictures/facebook.png" alt="Facebook" ></a>
          <a href=""><img src="../Pictures/telegram.png" alt="Telegram" /></a>
          <a href="https://www.instagram.com/pestcozam" target="_blank"><img src="./Pictures/instagram.png" alt="Instagram" /></a>
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
        document.querySelectorAll(".appointment-btn").forEach(button => {
            button.addEventListener("click", function (event) {
                event.preventDefault();
    
                fetch("check_session.php") // Check if user is logged in
                    .then(response => response.json())
                    .then(data => {
                        if (data.loggedIn) {
                            window.location.href = "appointment-service.php";
                        } else {
                            sessionStorage.setItem("redirectTo", "appointment-service.php"); // Store for redirection
                            alert("You must log in first to make an appointment.");
                            window.location.href = "login.php";
                        }
                    });
            });
        });
    });
    </script> 
    <script src="./JS CODES/sessionHandler.js"></script>
    <style>
  html {
    scroll-behavior: smooth;
  }
</style>
</body>
</html>
