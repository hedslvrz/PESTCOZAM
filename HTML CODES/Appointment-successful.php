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
            // Get appointment data from AppointmentSession
            $serviceData = AppointmentSession::getData('service', []);
            $calendarData = AppointmentSession::getData('calendar', []);
            $locationData = AppointmentSession::getData('location', []);
            $personalInfo = AppointmentSession::getData('personal_info', []);
            
            // Debug log to check what data is available
            error_log("Service data: " . print_r($serviceData, true));
            error_log("Calendar data: " . print_r($calendarData, true));
            
            // Check if required data exists
            if (empty($calendarData['appointment_date']) || empty($calendarData['appointment_time']) || empty($serviceData['service_id'])) {
                error_log("Missing appointment data: Date=" . ($calendarData['appointment_date'] ?? 'missing') . 
                          ", Time=" . ($calendarData['appointment_time'] ?? 'missing') . 
                          ", Service ID=" . ($serviceData['service_id'] ?? 'missing'));
                $_SESSION['error'] = "Missing appointment information. Please start over.";
                header("Location: Appointment-service.php");
                exit();
            }
            
            // Update the appointment in the database
            $query = "UPDATE appointments SET 
                    appointment_date = :appointment_date,
                    appointment_time = :appointment_time,
                    service_id = :service_id,
                    status = 'pending'
                    WHERE user_id = :user_id 
                    ORDER BY created_at DESC
                    LIMIT 1";
                    
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':appointment_date' => $calendarData['appointment_date'],
                ':appointment_time' => $calendarData['appointment_time'],
                ':service_id' => $serviceData['service_id'],
                ':user_id' => $_SESSION['user_id']
            ]);
            
            if ($result) {
                $_SESSION['appointment_confirmed'] = true;
                
                // Send confirmation email
                require_once "../PHP CODES/mailer.php";
                require_once "../EMAIL TEMPLATES/email_functions.php";
                
                // Get user's email - send to the appropriate person based on appointment type
                $isForSelf = $serviceData['is_for_self'] ?? 1;
                $userEmail = $isForSelf ? $_SESSION['email'] : $personalInfo['email'];
                
                // Get complete service details
                $serviceQuery = "SELECT service_name, starting_price FROM services WHERE service_id = :service_id";
                $serviceStmt = $db->prepare($serviceQuery);
                $serviceStmt->execute([':service_id' => $serviceData['service_id']]);
                $serviceDetails = $serviceStmt->fetch(PDO::FETCH_ASSOC);
                
                // Format the appointment date and time
                $formattedDate = date('F d, Y', strtotime($calendarData['appointment_date']));
                $formattedTime = date('h:i A', strtotime($calendarData['appointment_time']));
                
                // Get user details for self-appointments
                $query = "SELECT * FROM users WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':user_id' => $_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Determine name and contact details based on appointment type
                $displayFirstname = $isForSelf ? $user['firstname'] : $personalInfo['firstname'];
                $displayLastname = $isForSelf ? $user['lastname'] : $personalInfo['lastname'];
                $displayEmail = $isForSelf ? $user['email'] : $personalInfo['email'];
                $displayMobile = $isForSelf ? $user['mobile_number'] : $personalInfo['mobile_number'];
                $fullName = htmlspecialchars($displayFirstname . ' ' . $displayLastname);
                
                // Get full address
                $fullAddress = htmlspecialchars(
                    $locationData['street_address'] . ', ' . 
                    $locationData['barangay'] . ', ' . 
                    $locationData['city'] . ', ' . 
                    $locationData['province'] . ', ' . 
                    $locationData['region']
                );
                
                // Prepare email data
                $emailData = [
                    'title' => 'Appointment Confirmation',
                    'clientName' => $displayFirstname,
                    'serviceName' => $serviceDetails['service_name'],
                    'appointmentDate' => $formattedDate,
                    'appointmentTime' => $formattedTime,
                    'location' => $fullAddress,
                    'email' => $displayEmail,
                    'phone' => $displayMobile,
                    'price' => $serviceDetails['starting_price']
                ];
                
                // Generate the email body using the template
                $emailSubject = "PESTCOZAM - Appointment Confirmation";
                $emailBody = getAppointmentEmailTemplate($emailData);
                
                // Send the email
                $emailResult = sendEmail($userEmail, $emailSubject, $emailBody);
                
                // Log email status but don't show errors to the user
                if (!$emailResult['success']) {
                    error_log("Failed to send confirmation email: " . $emailResult['message']);
                }
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
    } elseif (isset($_POST['done'])) {
        // Clear all appointment related session data
        unset($_SESSION['appointment']);
        unset($_SESSION['appointment_confirmed']);
        
        // Clear all step data from AppointmentSession
        AppointmentSession::clearAllData();
        
        // Redirect to home page
        header("Location: ../index.php");
        exit();
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
        
        // Check if appointment data was found
        if (!$appointment) {
            throw new Exception("Appointment data not found in database.");
        }
    } else {
        // Get appointment details from session steps
        // Get service data
        $serviceData = AppointmentSession::getData('service', []);
        if (!$serviceData) {
            throw new Exception("Service data not found in session.");
        }
        
        $service_id = $serviceData['service_id'] ?? null;
        if (!$service_id) {
            throw new Exception("Service ID not found in session data.");
        }
        
        // Get service details from database
        $query = "SELECT service_name, starting_price FROM services WHERE service_id = :service_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':service_id' => $service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            throw new Exception("Service details not found for ID: " . $service_id);
        }
        
        // Get location data
        $locationData = AppointmentSession::getData('location', []);
        if (!$locationData) {
            throw new Exception("Location data not found in session.");
        }
        
        // Get calendar data (appointment date and time)
        $calendarData = AppointmentSession::getData('calendar', []);
        if (!$calendarData) {
            throw new Exception("Calendar data not found in session.");
        }
        
        // Get personal info data if appointment is not for self
        $personalInfo = [];
        if (isset($serviceData['is_for_self']) && $serviceData['is_for_self'] == 0) {
            $personalInfo = AppointmentSession::getData('personal_info', []);
            if (!$personalInfo) {
                throw new Exception("Personal info data not found in session for someone else appointment.");
            }
        }
        
        // Get user details for self-appointments
        $query = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("User details not found for ID: " . $_SESSION['user_id']);
        }
        
        // Check if appointment is for self or someone else
        $isForSelf = $serviceData['is_for_self'] ?? 1;
        
        // Combine all data to create appointment array
        $appointment = [
            'service_name' => $service['service_name'] ?? 'N/A',
            'starting_price' => $service['starting_price'] ?? 0,
            'appointment_date' => $calendarData['appointment_date'] ?? date('Y-m-d'),
            'appointment_time' => $calendarData['appointment_time'] ?? '12:00:00',
            'street_address' => $locationData['street_address'] ?? '',
            'landmark' => $locationData['landmark'] ?? '', // Add landmark
            'barangay' => $locationData['barangay'] ?? '',
            'city' => $locationData['city'] ?? 'Zamboanga City',
            'province' => $locationData['province'] ?? 'Zamboanga Del Sur',
            'region' => $locationData['region'] ?? 'Region IX',
            'display_firstname' => $isForSelf ? ($user['firstname'] ?? '') : ($personalInfo['firstname'] ?? ''),
            'display_lastname' => $isForSelf ? ($user['lastname'] ?? '') : ($personalInfo['lastname'] ?? ''),
            'display_email' => $isForSelf ? ($user['email'] ?? '') : ($personalInfo['email'] ?? ''),
            'display_mobile_number' => $isForSelf ? ($user['mobile_number'] ?? '') : ($personalInfo['mobile_number'] ?? ''),
            'property_type' => $locationData['property_type'] ?? 'Residential',
            'establishment_name' => $locationData['establishment_name'] ?? '',
            'property_area' => $locationData['property_area'] ?? '',
            'pest_concern' => $locationData['pest_concern'] ?? '',
            'service_id' => $service_id
        ];
    }

    // Format the date and time
    $appointmentDate = date('F d, Y', strtotime($appointment['appointment_date']));
    $appointmentTime = date('h:i A', strtotime($appointment['appointment_time']));

} catch (Exception $e) {
    error_log("Error retrieving appointment details: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving appointment details: " . $e->getMessage();
    // Set a default empty appointment array to prevent errors in the template
    $appointment = [
        'service_name' => 'Not available',
        'starting_price' => 0,
        'appointment_date' => date('Y-m-d'),
        'appointment_time' => '12:00:00',
        'street_address' => '',
        'landmark' => '',
        'barangay' => '',
        'city' => 'Zamboanga City',
        'province' => 'Zamboanga Del Sur',
        'region' => 'Region IX',
        'display_firstname' => '',
        'display_lastname' => '',
        'display_email' => '',
        'display_mobile_number' => '',
        'property_type' => 'Residential',
        'establishment_name' => '',
        'property_area' => '',
        'pest_concern' => '',
        'service_id' => 0
    ];
    $appointmentDate = date('F d, Y');
    $appointmentTime = '12:00 PM';
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
            <li><a href="../index.php">Home</a></li>
            <li><a href="../index.php#offer-section">Services</a></li>
            <li><a href="../index.php#about-us-section">About Us</a></li>
            <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
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
            <p>Please review the details below to confirm your appointment with PESTCOZAM.</p>
        </div>
        <?php endif; ?>

        <!-- Appointment Receipt Section -->
        <div class="appointment-receipt">
            <div class="receipt-header">
                <div class="receipt-logo-container">
                    <img src="../Pictures/pest_logo.png" alt="Flower Logo" class="receipt-logo">
                    <span class="receipt-brand-name">PESTCOZAM</span>
                </div>
                <h3>Appointment Details</h3>
            </div>
            <div class="receipt-content">
                <?php if (isset($_SESSION['error']) && strpos($_SESSION['error'], 'retrieving appointment details') !== false): ?>
                    <div style="text-align: center; padding: 20px; color: #721c24; background-color: #f8d7da; border-radius: 5px;">
                        <p>Could not load appointment details. Please go back to the appointment page.</p>
                    </div>
                <?php else: ?>
                    <div class="receipt-row">
                        <span class="label">Service Type:</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['service_name']); ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Appointment Type:</span>
                        <span class="value">
                            <?php 
                            // Check if this is an ocular inspection (service ID 17)
                            if (isset($appointment['service_id']) && $appointment['service_id'] == 17) {
                                echo '<span class="service-badge ocular">Ocular Inspection</span>';
                            } else {
                                echo '<span class="service-badge treatment">Treatment</span>';
                            }
                            ?>
                        </span>
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
                    
                    <!-- Display landmark if available -->
                    <?php if(!empty($appointment['landmark'])): ?>
                    <div class="receipt-row">
                        <span class="label">Nearest Landmark:</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['landmark']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- New fields -->
                    <div class="receipt-row">
                        <span class="label">Property Type:</span>
                        <span class="value">
                            <?php echo ucfirst(htmlspecialchars($appointment['property_type'] ?? 'Residential')); ?>
                            <?php if(isset($appointment['establishment_name']) && !empty($appointment['establishment_name'])): ?>
                                (<?php echo htmlspecialchars($appointment['establishment_name']); ?>)
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if(isset($appointment['property_area']) && !empty($appointment['property_area'])): ?>
                    <div class="receipt-row">
                        <span class="label">Property Area:</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['property_area']); ?> sq.m</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($appointment['pest_concern']) && !empty($appointment['pest_concern'])): ?>
                    <div class="receipt-row">
                        <span class="label">Pest Concern:</span>
                        <span class="value"><?php echo nl2br(htmlspecialchars($appointment['pest_concern'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
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
                <?php endif; ?>
            </div>
            <div class="receipt-note">
                <p>*Final price will be determined after ocular inspection</p>
                <p>*Please keep this receipt for your reference</p>
            </div>
        </div>

        <div class="thank-you-nav">
            <?php if (isset($_SESSION['appointment_confirmed']) && $_SESSION['appointment_confirmed']): ?>
                <form method="post" style="width: 100%; text-align: center;">
                    <button type="submit" name="done" class="done-btn">Done</button>
                </form>
            <?php else: ?>
                <form method="post">
                    <button type="submit" name="cancel" class="back-btn">Cancel</button>
                    <button type="submit" name="confirm" class="next-btn">Confirm<br>Appointment</button>
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
