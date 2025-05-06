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
    <title>Privacy Policy - PESTCOZAM</title>
    <link rel="stylesheet" href="../CSS CODES/Pest_Control_Info.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .policy-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .policy-container h1 {
            color: #144578;
            margin-bottom: 30px;
            font-size: 32px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }
        
        .policy-container h1:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: #144578;
        }
        
        .policy-container h2 {
            color: #144578;
            margin: 30px 0 15px;
            font-size: 22px;
        }
        
        .policy-section {
            margin-bottom: 30px;
        }
        
        .policy-section p {
            margin-bottom: 15px;
            line-height: 1.6;
            color: #333;
        }
        
        .policy-section ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .policy-section li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .last-updated {
            font-style: italic;
            color: #666;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>

    <div class="header-wrapper">
        <!-- HEADER -->
        <header class="top-header">
            <div class="container">
                <div class="location">
                    <i class='bx bx-map'></i>
                    <span><strong>Estrada St, Zamboanga City, Zamboanga Del Sur, 7000</strong></span>
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

    <!-- MAIN CONTENT -->
    <div class="policy-container">
        <h1>Privacy Policy</h1>
        
        <div class="policy-section">
            <p>At PESTCOZAM, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, and safeguard your data when you use our website and services.</p>
        </div>
        
        <div class="policy-section">
            <h2>Information We Collect</h2>
            <p>We may collect the following types of information:</p>
            <ul>
                <li><strong>Personal Information:</strong> Name, email address, phone number, home address, and other details you provide when booking appointments or creating an account.</li>
                <li><strong>Property Information:</strong> Details about your property such as type, size, and pest concerns.</li>
                <li><strong>Payment Information:</strong> When you make payments for our services (processed through secure payment processors).</li>
                <li><strong>Usage Data:</strong> Information about how you interact with our website, including IP address, browser type, and pages visited.</li>
            </ul>
        </div>
        
        <div class="policy-section">
            <h2>How We Use Your Information</h2>
            <p>We use the information we collect for:</p>
            <ul>
                <li>Providing and improving our pest control services</li>
                <li>Processing and scheduling appointments</li>
                <li>Communicating with you about your service requests or appointments</li>
                <li>Sending important notices and service updates</li>
                <li>Marketing and promotional purposes (with your consent)</li>
                <li>Analyzing website usage to improve our services</li>
                <li>Ensuring compliance with applicable laws and regulations</li>
            </ul>
        </div>
        
        <div class="policy-section">
            <h2>Data Protection</h2>
            <p>We implement appropriate technical and organizational measures to maintain the security of your personal information. These measures include:</p>
            <ul>
                <li>Secure storage of personal data</li>
                <li>Limiting access to personal information to authorized personnel</li>
                <li>Regular security assessments and updates</li>
                <li>Staff training on data protection practices</li>
            </ul>
        </div>
        
        <div class="policy-section">
            <h2>Sharing Your Information</h2>
            <p>We may share your information with:</p>
            <ul>
                <li>Our technicians and staff who need access to provide services</li>
                <li>Third-party service providers who assist us in operating our website and business</li>
                <li>Legal authorities when required by law</li>
            </ul>
            <p>We do not sell or rent your personal information to third parties for marketing purposes.</p>
        </div>
        
        <div class="policy-section">
            <h2>Your Rights</h2>
            <p>Under the Data Privacy Act of 2012, you have rights regarding your personal information, including:</p>
            <ul>
                <li>The right to access your personal information</li>
                <li>The right to correct inaccurate information</li>
                <li>The right to delete your information (with certain limitations)</li>
                <li>The right to object to the processing of your information</li>
                <li>The right to be informed about how your data is being used</li>
            </ul>
        </div>
        
        <div class="policy-section">
            <h2>Cookies and Similar Technologies</h2>
            <p>Our website may use cookies and similar technologies to enhance your browsing experience. You can manage your cookie preferences through your browser settings.</p>
        </div>
        
        <div class="policy-section">
            <h2>Contact Us</h2>
            <p>If you have any questions or concerns about our Privacy Policy or data practices, please contact us at:</p>
            <p>Email: pestcozam@yahoo.com<br>
            Phone: 0905-177-5662<br>
            Address: Estrada St, Zamboanga City, Zamboanga Del Sur, 7000</p>
        </div>
        
        <p class="last-updated">Last Updated: June 2023</p>
    </div>

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
        
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > navbarHeight) {
                // Scrolling down & past navbar
                headerWrapper.classList.add('hide-nav-group');
            } else {
                // Scrolling up or at top
                headerWrapper.classList.remove('hide-nav-group');
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });
    </script>
</body>
</html>
