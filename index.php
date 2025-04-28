//include a comment here
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

// Fetch approved reviews with user information - remove LIMIT to get ALL approved reviews
$reviews = [];
try {
    $query = "SELECT r.*, u.firstname, u.lastname, 
              IFNULL(u.profile_pic, './Pictures/boy.png') AS profile_pic 
              FROM reviews r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.status = 'approved' 
              ORDER BY r.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If the table doesn't exist or other error, just continue with empty reviews array
    error_log('Reviews query failed: ' . $e->getMessage());
}

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
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
          
          <!-- Mobile Menu Toggle Button -->
          <button class="mobile-menu-btn" id="mobileMenuBtn">
              <span></span>
              <span></span>
              <span></span>
          </button>
          
          <nav>
            <ul id="navMenu">
              <li><a href="index.php">Home</a></li>
              <li><a href="#offer-section">Services</a></li>
              <li><a href="#about-us-section">About Us</a></li>
              <li><a href="./HTML CODES/Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
              <?php if ($is_logged_in): ?>
              <li class="user-profile">
                  <div class="profile-dropdown">
                      <i class='bx bx-menu hamburger-icon'></i>
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
        
        <a href="./HTML CODES/Location_page.php" class="info-box">
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
    
    <!--   WE ARE SECTION -->
    <section class="who-we-are">
      <div class="who-container">
        <div class="who-text">
          <h4>WHO WE ARE</h4>
          <h2>The pest control expert you can trust.</h2>
          <p>
          PESTCOZAM Pest Control Service provides safe and reliable solutions to 
          keep homes and businesses pest-free. We handle termites, cockroaches, 
          rodents, and more using eco-friendly methods.
          </p>
          <p>
          With trained experts and tailored plans, we ensure fast, effective service. 
          PESTCOZAM is committed to protecting your space and peace of mind.
          </p>
        </div>
        <div class="who-video">
          <video class="analytics-video" onclick="this.paused ? this.play() : this.pause()">
            <source src="./Videos/who-we-are.mp4" type="video/mp4" />
            Your browser does not support the video tag.
          </video>
          <div class="testimonial-box">
            <p>
              "As the owner of PESTCOZAM, I started this service to give families 
              and businesses a safer, cleaner environment. Every job we take on is 
              personal—we treat your space like our own."
            </p>
            <span>- Viktor Kim Bue</span>
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
              <img src="./Pictures/Expert pic.png" alt="Pest Control Expert">
          </div>
          <div class="expert-content">
              <h2>The pest control expert<br>you can trust.</h2>
              <div class="expert-cards">
                  <div class="card white-card">
                      <h3>Pest Control</h3>
                      <p>Keeping your home and business safe, clean, and 
                        pest-free—because peace of mind starts with effective 
                        pest control.</p>
                      <div class="button-group">
                          <button class="learn-btn" onclick="window.location.href='./HTML CODES/Pest_Control_Info.php'">Learn More</button>
                      </div>
                  </div>
                  <div class="card blue-card">
                      <h3>Inspections</h3>
                      <p>Thorough inspections are the first step to a pest-free 
                      environment—spotting the problem before it grows, so 
                      you can act with confidence and clarity.</p>
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
      <video class="analytics-video" onclick="this.paused ? this.play() : this.pause()">
        <source src="./Videos/pestcozam-video.mp4" type="video/mp4">
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
        <h3>20</3>
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
            <button class="learn-more-btn" onclick="window.location.href='./HTML CODES/Lrn_more_ocular.php?service_id=<?= $ocularService['service_id'] ?>'">Learn More</button>
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
              <?php
              $learnMorePage = '';
              switch(strtolower($service['service_name'])) {
                case 'soil poisoning':
                  $learnMorePage = 'Lrn_more_sp.php';
                  break;
                case 'mound demolition':
                  $learnMorePage = 'Lrn_more_md.php';
                  break;
                case 'termite control':
                  $learnMorePage = 'Lrn_more_tc.php';
                  break;
                case 'general pest control':
                  $learnMorePage = 'Lrn_more_gpc.php';
                  break;
                case 'mosquito control':
                  $learnMorePage = 'Lrn_more_mc.php';
                  break;
                case 'rat control':
                  $learnMorePage = 'Lrn_more_rat.php';
                  break;
                case 'other flying insects':
                case 'other flying and crawling insects':
                  $learnMorePage = 'Lrn_more_other.php';
                  break;
                case 'extraction':
                  $learnMorePage = 'Lrn_more_extraction.php';
                  break;
                default:
                  // For debugging purposes, you can see which service name doesn't match
                  error_log('Service name not matched in switch: ' . $service['service_name']);
                  $learnMorePage = 'Lrn_more_sp.php'; // Default case
              }
              ?>
              <button class="learn-more-btn" onclick="window.location.href='./HTML CODES/<?= $learnMorePage ?>?service_id=<?= $service['service_id'] ?>'">Learn More</button>
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
        <p>Committed to do our best to provide a high quality standard and customer-oriented pest control services to our clients.</p>
      </div>
      <div class="mission-block">
        <div class="block-title">Mission</div>
        <p>To be recognized as a top pest control services provider in the country by giving a safe and pest-free home and facility using science and technology in the field of pest control management.</p>
      </div>
    </div>
  </div>


<!-- WHY-PESTCOZAM SECTION -->
  <section id="why-pestcozam-section">
    <div class="why-pestcozam-container">
      <div class="why-pestcozam-content">
        <div class="section-title">Why choose PESTCOZAM?</div>
        <div class="main-title">Committed<br>to Your<br>Comfort</div>
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


<!-- REVIEWS SECTION -->
<section id="reviews-section">
  <div class="reviews-container">
    <div class="reviews-header">
      <div class="customer-title">What Our Customers Are Saying</div>
      <p>See what our happy customers are saying about their experiences with us!</p>
    </div>
    <div class="reviews-slider-container">
      <div class="reviews-slider" id="reviewsSlider">
        <?php if (count($reviews) > 0): ?>
          <?php foreach ($reviews as $index => $review): ?>
            <div class="review-slide" data-index="<?= $index ?>">
              <div class="review-card">
                <img src="<?= htmlspecialchars($review['profile_pic']) ?>" 
                     alt="<?= htmlspecialchars($review['firstname'] . ' ' . $review['lastname']) ?>" 
                     class="review-image">
                <div class="review-content">
                  <h3 class="review-name"><?= htmlspecialchars($review['firstname'] . ' ' . $review['lastname']) ?></h3>
                  <div class="review-stars">
                    <?php 
                      $rating = (int)$review['rating'];
                      echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating); 
                    ?>
                  </div>
                  <p><?= htmlspecialchars($review['review_text']) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="review-slide" data-index="0">
            <div class="review-card">
              <div class="review-content">
                <h3 class="review-name">No Reviews Yet</h3>
                <div class="review-stars">☆☆☆☆☆</div>
                <p>Be the first to leave a review after experiencing our service!</p>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
      
      <div class="slider-controls">
        <button class="prev-button" id="prevReview">←</button>
        <button class="next-button" id="nextReview">→</button>
      </div>
    </div>
    <a href="./HTML CODES/Appointment-service.php" class="book-now-button">BOOK AN APPOINTMENT NOW</a>
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
        <p class="follow-us-text">Follow us on:</p>
        <div class="social-icons">
          <a href="https://www.facebook.com/PESTCOZAM" target="_blank"><img src="./Pictures/facebook.png" alt="Facebook" ></a>
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
        // Handle all appointment buttons including .btn-appointment and .book-now-btn
        document.querySelectorAll(".btn-appointment, .book-now-btn").forEach(button => {
            button.addEventListener("click", function (event) {
                event.preventDefault();
    
                // First check if user is logged in
                fetch("./PHP CODES/check_session.php")
                    .then(response => response.json())
                    .then(data => {
                        if (data.loggedIn) {
                            // If logged in, clear any existing appointment session first
                            fetch("./PHP CODES/clear_appointment_session.php")
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        window.location.href = "./HTML CODES/Appointment-service.php";
                                    }
                                });
                        } else {
                            sessionStorage.setItem("redirectTo", "./HTML CODES/Appointment-service.php");
                            Swal.fire({
                                icon: 'info',
                                title: 'Login Required',
                                text: 'You must log in first to make an appointment.',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = "./HTML CODES/Login.php";
                            });
                        }
                    });
            });
        });
    });

    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navMenu = document.getElementById('navMenu');
        
        if (mobileMenuBtn && navMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                mobileMenuBtn.classList.toggle('active');
            });
            
            // Close mobile menu when clicking on nav links
            const navLinks = navMenu.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navMenu.classList.remove('active');
                    mobileMenuBtn.classList.remove('active');
                });
            });
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideMenu = navMenu.contains(event.target);
            const isClickOnMenuBtn = mobileMenuBtn.contains(event.target);
            
            if (!isClickInsideMenu && !isClickOnMenuBtn && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                mobileMenuBtn.classList.remove('active');
            }
        });
    });

    // Handle responsive review slider adjustments
    document.addEventListener('DOMContentLoaded', function() {
        function adjustReviewSlider() {
            const isMobile = window.innerWidth <= 768;
            const reviewSlides = document.querySelectorAll('.review-slide');
            
            if (isMobile) {
                // On mobile, only show the active slide
                reviewSlides.forEach(slide => {
                    if (!slide.classList.contains('active')) {
                        slide.style.display = 'none';
                    }
                });
            } else {
                // On desktop, show prev/next slides
                updateSlidePositions(); // Call your existing function here
            }
        }
        
        // Call on load and resize
        window.addEventListener('resize', adjustReviewSlider);
        setTimeout(adjustReviewSlider, 100); // Initial call with slight delay
    });
  </script> 
  <script src="./JS CODES/sessionHandler.js"></script>
  <script>
    // Enhanced Review Slider with Fixed Positioning
    document.addEventListener('DOMContentLoaded', function() {
      const reviewSlider = document.getElementById('reviewsSlider');
      const prevButton = document.getElementById('prevReview');
      const nextButton = document.getElementById('nextReview');
      const slides = document.querySelectorAll('.review-slide');
      const totalSlides = slides.length;
      let currentIndex = 0;
      let autoScrollInterval;
      let isHovering = false;
      let isAnimating = false;
      
      if(!reviewSlider || totalSlides <= 1) return;
      
      // Set initial positions
      function initializeSlider() {
        slides.forEach((slide, index) => {
          // Initially hide all slides except the first three
          if (index > 2) {
            slide.style.display = 'none';
          }
        });
        
        // Set up the initial three visible slides
        updateSlidePositions();
      }
      
      // Update slide positions based on current index
      function updateSlidePositions() {
        // Hide all slides first
        slides.forEach((slide) => {
          slide.style.display = 'none';
          slide.classList.remove('active', 'prev', 'next');
        });
        
        // Calculate indices for active, prev, and next slides
        const prevIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        const nextIndex = (currentIndex + 1) % totalSlides;
        
        // Show and position the three slides
        slides[prevIndex].style.display = 'block';
        slides[currentIndex].style.display = 'block';
        slides[nextIndex].style.display = 'block';
        
        // Apply appropriate classes
        slides[prevIndex].classList.add('prev');
        slides[currentIndex].classList.add('active');
        slides[nextIndex].classList.add('next');
      }
      
      // Go to a specific slide with proper animation
      function goToSlide(index) {
        if (isAnimating) return;
        isAnimating = true;
        
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;
        
        currentIndex = index;
        updateSlidePositions();
        
        // Re-enable slide transitions after a brief delay
        setTimeout(() => {
          isAnimating = false;
        }, 500); // Match this to CSS transition time
      }
      
      // Start auto-scrolling
      function startAutoScroll() {
        autoScrollInterval = setInterval(() => {
          if (!isHovering && !isAnimating) {
            goToSlide(currentIndex + 1);
          }
        }, 5000); // Change slide every 5 seconds
      }
      
      // Event listeners
      prevButton.addEventListener('click', () => {
        goToSlide(currentIndex - 1);
      });
      
      nextButton.addEventListener('click', () => {
        goToSlide(currentIndex + 1);
      });
      
      // Handle hover states
      reviewSlider.addEventListener('mouseenter', () => {
        isHovering = true;
      });
      
      reviewSlider.addEventListener('mouseleave', () => {
        isHovering = false;
      });
      
      // Allow clicking on the prev/next slides to navigate
      slides.forEach((slide, index) => {
        slide.addEventListener('click', () => {
          const slideClasses = slide.classList;
          if (slideClasses.contains('prev')) {
            goToSlide(currentIndex - 1);
          } else if (slideClasses.contains('next')) {
            goToSlide(currentIndex + 1);
          }
        });
      });
      
      // Initialize the slider
      initializeSlider();
      startAutoScroll();
    });
  </script>
  <style>
  html {
    scroll-behavior: smooth;
  }
  </style>
</body>
</html>
