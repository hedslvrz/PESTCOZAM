<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="../CSS CODES/Appointment-loc.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <!-- HEADER -->
  <header class="top-header">
    <div class="container">
        <div class="location">
            <span>• <strong>Zamboanga</strong> • <strong>Pagadian</strong> • <strong>Pasay</strong> • <strong>Davao</strong></span>
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
            <li><a href="../HTML CODES/Home_page.html">Home</a></li>
            <li><a href="../HTML CODES/About_us.html">About Us</a></li>
            <li><a href="../HTML CODES/Services.html" class="services">Services</a></li>
            <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Appointment</a></li>
            <li><a href="../HTML CODES/Login.php" class="btn-login"><i class='bx bx-log-in' ></i> Login</a></li>
            <li><a href="../HTML CODES/Signup.php" class="btn-signup"><i class='bx bx-user-plus' ></i> Sign Up</a></li>
        </ul>
    </nav>
    </header>

    <main>
        <div class="appointment-map">
            <label>Location Details:</label>
            <select>
                <option>Select Region</option>
            </select>
            <select>
                <option>Select Province</option>
            </select>
            <select>
                <option>Select City</option>
            </select>
            <select>
                <option>Select Barangay</option>
            </select>
            
            <input type="text" class="specify-addr" placeholder="Please specify Street Name & House/Building No. (Optional if not applicable)">
            
            <div class="map-container">
                <p>Please drag or tap on the map to mark the exact location for your pest treatment.</p>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.727595440955!2d122.08213807493584!3d6.92313139307655!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3250417c4a5526d5%3A0xa7965524f6cddecf!2sPestcozam%20Pest%20Control%20Services!5e0!3m2!1sen!2sph!4v1740376200786!5m2!1sen!2sph" width="500" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            
            <div class="navigation-buttons">
                <button onclick="window.location.href='Appointment-service.html'">Back</button>
                <button onclick="window.location.href='Appointment-info.html'">Next</button>
            </div>
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
