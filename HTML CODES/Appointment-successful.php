<?php
session_start();
require_once "../database.php";

// Check if user is logged in and appointment exists
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Update the SQL query to include both appointment and user details
    $query = "SELECT 
                a.*,
                s.service_name,
                s.starting_price,
                CASE 
                    WHEN a.is_for_self = 1 THEN u.firstname
                    ELSE a.firstname
                END as display_firstname,
                CASE 
                    WHEN a.is_for_self = 1 THEN u.lastname
                    ELSE a.lastname
                END as display_lastname,
                CASE 
                    WHEN a.is_for_self = 1 THEN u.email
                    ELSE a.email
                END as display_email,
                CASE 
                    WHEN a.is_for_self = 1 THEN u.mobile_number
                    ELSE a.mobile_number
                END as display_mobile_number
              FROM appointments a 
              JOIN services s ON a.service_id = s.service_id 
              JOIN users u ON a.user_id = u.id
              WHERE a.user_id = :user_id 
              ORDER BY a.created_at DESC 
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        // No appointment found
        $_SESSION['error'] = "No appointment found. Please book an appointment first.";
        header("Location: Appointment-service.php");
        exit();
    }

    // Format the date and time
    $appointmentDate = date('F d, Y', strtotime($appointment['appointment_date']));
    $appointmentTime = date('h:i A', strtotime($appointment['appointment_time']));

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving your appointment.";
    header("Location: Appointment-service.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Successful</title>
    <link rel="stylesheet" href="../CSS CODES/Appointment-successful.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
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
        <span class="brand-name" style="font-size: 2rem;">PESTCOZAM</span>
    </div>
    <nav>
        <ul>
            <li><a href="../Index.php">Home</a></li>
            <li><a href="../HTML CODES/About_us.html">About Us</a></li>
            <li><a href="../HTML CODES/Services.html" class="services">Services</a></li>
            <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Appointment</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php 
                    $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../Pictures/boy.png';
                ?>
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

    
    <!-- Thank_You - SECTION -->
    <main class="thank-you-section">
        <div class="thank-you-box">
            <img src="../Pictures/success-icon.png" alt="Success Icon" class="check-icon">
            <h2>Thank You!</h2>
            <p>Your Appointment Has Been Successfully Booked!</p>
        </div>

        <!-- Appointment Receipt Section -->
        <div class="appointment-receipt">
            <h3>Appointment Details</h3>
            <div class="receipt-content">
                <div class="receipt-row">
                    <span class="label">Service Type:</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['service_name']); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="label">Date & Time:</span>
                    <span class="value"><?php echo $appointmentDate . ' at ' . $appointmentTime; ?></span>
                </div>
                <div class="receipt-row">
                    <span class="label">Location:</span>
                    <span class="value">
                        <?php 
                        echo htmlspecialchars($appointment['street_address']) . ', ' . 
                             htmlspecialchars($appointment['barangay']) . ', ' . 
                             htmlspecialchars($appointment['city']) . ', ' . 
                             htmlspecialchars($appointment['province']) . ', ' . 
                             htmlspecialchars($appointment['region']); 
                        ?>
                    </span>
                </div>
                <div class="receipt-row">
                    <span class="label">Client Name:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($appointment['display_firstname'] . ' ' . $appointment['display_lastname']); ?>
                    </span>
                </div>
                <div class="receipt-row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['display_email']); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['display_mobile_number']); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="label">Starting Price:</span>
                    <span class="value">₱<?php echo number_format($appointment['starting_price'], 2); ?></span>
                </div>
            </div>
            <div class="receipt-note">
                <p>*Final price will be determined after ocular inspection</p>
                <p>*Please keep this receipt for your reference</p>
            </div>
        </div>

        <div class="thank-you-nav">
            <button class="back-btn" onclick="window.location.href='Appointment-calendar.php'">Back</button>
            <button class="next-btn" onclick="window.location.href='../Index.php'">Done</button>
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
