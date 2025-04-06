<?php
session_start();
require_once "../database.php";
require_once "../PHP CODES/AppointmentSession.php"; // Add this line to include AppointmentSession class

// Check if user is logged in and appointment exists in session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['appointment'])) {
    header("Location: Login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Process the appointment confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirm'])) {
        try {
            // Get all appointment data from session
            $appointmentData = $_SESSION['appointment'];
            
            // Update the appointment in the database
            $query = "UPDATE appointments SET 
                    appointment_date = :appointment_date,
                    appointment_time = :appointment_time,
                    service_id = :service_id,
                    status = 'confirmed'
                    WHERE user_id = :user_id 
                    ORDER BY created_at DESC
                    LIMIT 1";
                    
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':appointment_date' => $appointmentData['appointment_date'],
                ':appointment_time' => $appointmentData['appointment_time'],
                ':service_id' => $appointmentData['service_id'],
                ':user_id' => $_SESSION['user_id']
            ]);
            
            if ($result) {
                $_SESSION['appointment_confirmed'] = true;
            } else {
                $_SESSION['error'] = "Failed to confirm appointment. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred while saving your appointment.";
        }
    } elseif (isset($_POST['cancel'])) {
        try {
            // Delete the appointment from the database
            $query = "DELETE FROM appointments 
                     WHERE user_id = :user_id 
                     ORDER BY created_at DESC
                     LIMIT 1";
            
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':user_id' => $_SESSION['user_id']
            ]);
            
            if (!$result) {
                $_SESSION['error'] = "Failed to cancel appointment. Please try again.";
            }
            
            // User cancelled the appointment
            unset($_SESSION['appointment']);
            header("Location: Appointment-service.php");
            exit();
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred while cancelling your appointment.";
            header("Location: Appointment-service.php");
            exit();
        }
    }
}

try {
    // Check if appointment was already confirmed
    if (isset($_SESSION['appointment_confirmed']) && $_SESSION['appointment_confirmed']) {
        // Get the confirmed appointment from the database
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
    } else {
        // Get appointment details from session steps
        // Get service data
        $serviceData = AppointmentSession::getData('service', []);
        $service_id = $serviceData['service_id'] ?? null;
        
        // Get service details from database
        $query = "SELECT service_name, starting_price FROM services WHERE service_id = :service_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':service_id' => $service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get location data
        $locationData = AppointmentSession::getData('location', []);
        
        // Get calendar data (appointment date and time)
        $calendarData = AppointmentSession::getData('calendar', []);
        
        // Get personal info data if appointment is not for self
        $personalInfo = AppointmentSession::getData('personal_info', []);
        
        // Get user details for self-appointments
        $query = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if appointment is for self or someone else
        $isForSelf = $serviceData['is_for_self'] ?? 1;
        
        // Combine all data to create appointment array
        $appointment = [
            'service_name' => $service['service_name'] ?? 'N/A',
            'starting_price' => $service['starting_price'] ?? 0,
            'appointment_date' => $calendarData['appointment_date'] ?? date('Y-m-d'),
            'appointment_time' => $calendarData['appointment_time'] ?? '12:00:00',
            'street_address' => $locationData['street_address'] ?? '',
            'barangay' => $locationData['barangay'] ?? '',
            'city' => $locationData['city'] ?? 'Zamboanga City',
            'province' => $locationData['province'] ?? 'Zamboanga Del Sur',
            'region' => $locationData['region'] ?? 'Region IX',
            'display_firstname' => $isForSelf ? ($user['firstname'] ?? '') : ($personalInfo['firstname'] ?? ''),
            'display_lastname' => $isForSelf ? ($user['lastname'] ?? '') : ($personalInfo['lastname'] ?? ''),
            'display_email' => $isForSelf ? ($user['email'] ?? '') : ($personalInfo['email'] ?? ''),
            'display_mobile_number' => $isForSelf ? ($user['mobile_number'] ?? '') : ($personalInfo['mobile_number'] ?? ''),
        ];
    }

    // Format the date and time
    $appointmentDate = date('F d, Y', strtotime($appointment['appointment_date']));
    $appointmentTime = $appointment['appointment_time'];

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving appointment details.";
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
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['appointment_confirmed']) && $_SESSION['appointment_confirmed']): ?>
        <div class="thank-you-box">
            <img src="../Pictures/success-icon.png" alt="Success Icon" class="check-icon">
            <h2>Thank You!</h2>
            <p>Your Appointment Has Been Successfully Booked!</p>
        </div>
        <?php else: ?>
        <div class="review-box">
            <h2>Review Your Appointment</h2>
            <p>Please confirm your appointment details below:</p>
        </div>
        <?php endif; ?>

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
            <?php if (isset($_SESSION['appointment_confirmed']) && $_SESSION['appointment_confirmed']): ?>
                <button class="next-btn" onclick="window.location.href='../Index.php'">Done</button>
            <?php else: ?>
                <form method="post" style="display: flex; gap: 15px; width: 100%; justify-content: space-between;">
                    <button type="submit" name="cancel" class="back-btn">Cancel</button>
                    <button type="submit" name="confirm" class="next-btn">Confirm Appointment</button>
                </form>
            <?php endif; ?>
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
