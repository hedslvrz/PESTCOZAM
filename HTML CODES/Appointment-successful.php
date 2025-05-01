<?php
session_start();
require_once "../database.php";
require_once "../PHP CODES/AppointmentSession.php";

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
            // Get all appointment data from AppointmentSession
            $allData = AppointmentSession::getAllData();
            
            if (!$allData) {
                $_SESSION['error'] = "Missing appointment data. Please start over.";
                header("Location: Appointment-service.php");
                exit();
            }
            
            // Extract data from the session
            $user_id = $allData['user_id'] ?? $_SESSION['user_id'];
            $service_id = $allData['service_id'] ?? null;
            
            // Get original_service_id from session - this ensures we store the correct service
            $original_service_id = $allData['original_service_id'] ?? $service_id;
            
            // Use original_service_id for database storage to ensure correct service info
            $service_id_for_db = $original_service_id;
            
            $is_for_self = $allData['is_for_self'] ?? 1;
            $service_type = $allData['service_type'] ?? null;
            $appointment_date = $allData['appointment_date'] ?? null;
            $appointment_time = $allData['appointment_time'] ?? null;
            $region = $allData['region'] ?? '';
            $province = $allData['province'] ?? '';
            $city = $allData['city'] ?? '';
            $barangay = $allData['barangay'] ?? '';
            $street_address = $allData['street_address'] ?? '';
            $landmark = $allData['landmark'] ?? null;
            $latitude = $allData['latitude'] ?? null;
            $longitude = $allData['longitude'] ?? null;
            $property_type = $allData['property_type'] ?? 'residential';
            $establishment_name = $allData['establishment_name'] ?? null;
            $property_area = $allData['property_area'] ?? null;
            $pest_concern = $allData['pest_concern'] ?? null;
            
            // For appointments not for self, get personal info
            $firstname = null;
            $lastname = null;
            $email = null;
            $mobile_number = null;
            
            if ($is_for_self == 0) {
                $firstname = $allData['firstname'] ?? null;
                $lastname = $allData['lastname'] ?? null;
                $email = $allData['email'] ?? null;
                $mobile_number = $allData['mobile_number'] ?? null;
            }
            
            // Insert the appointment into the database now
            $insertQuery = "INSERT INTO appointments (
                user_id, service_id, is_for_self, status, service_type,
                region, province, city, barangay, street_address, landmark, 
                latitude, longitude, appointment_date, appointment_time,
                firstname, lastname, email, mobile_number,
                property_type, establishment_name, property_area, pest_concern
            ) VALUES (
                :user_id, :service_id, :is_for_self, :status, :service_type,
                :region, :province, :city, :barangay, :street_address, :landmark,
                :latitude, :longitude, :appointment_date, :appointment_time,
                :firstname, :lastname, :email, :mobile_number,
                :property_type, :establishment_name, :property_area, :pest_concern
            )";
            
            $stmt = $db->prepare($insertQuery);
            $result = $stmt->execute([
                ':user_id' => $user_id,
                ':service_id' => $service_id_for_db, // Use the original service ID here
                ':is_for_self' => $is_for_self,
                ':status' => 'pending',
                ':service_type' => $service_type,
                ':region' => $region,
                ':province' => $province,
                ':city' => $city,
                ':barangay' => $barangay,
                ':street_address' => $street_address,
                ':landmark' => $landmark,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':appointment_date' => $appointment_date,
                ':appointment_time' => $appointment_time,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':email' => $email,
                ':mobile_number' => $mobile_number,
                ':property_type' => $property_type,
                ':establishment_name' => $establishment_name,
                ':property_area' => $property_area,
                ':pest_concern' => $pest_concern
            ]);
            
            if ($result) {
                $_SESSION['appointment_confirmed'] = true;
                $_SESSION['appointment_id'] = $db->lastInsertId();
                
                // Send confirmation email
                require_once "../PHP CODES/mailer.php";
                require_once "../EMAIL TEMPLATES/email_functions.php";
                
                // Get user's email - send to the appropriate person based on appointment type
                $userEmail = $is_for_self ? $_SESSION['email'] : $email;
                
                // Get complete service details - use the original service ID
                $serviceQuery = "SELECT service_name, starting_price FROM services WHERE service_id = :service_id";
                $serviceStmt = $db->prepare($serviceQuery);
                $serviceStmt->execute([':service_id' => $service_id_for_db]); // Use original service ID
                $serviceDetails = $serviceStmt->fetch(PDO::FETCH_ASSOC);
                
                // Format the appointment date and time
                $formattedDate = date('F d, Y', strtotime($appointment_date));
                $formattedTime = date('h:i A', strtotime($appointment_time));
                
                // Get user details for self-appointments
                $userQuery = "SELECT * FROM users WHERE id = :user_id";
                $userStmt = $db->prepare($userQuery);
                $userStmt->execute([':user_id' => $user_id]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                // Determine name and contact details based on appointment type
                $displayFirstname = $is_for_self ? $user['firstname'] : $firstname;
                $displayLastname = $is_for_self ? $user['lastname'] : $lastname;
                $displayEmail = $is_for_self ? $user['email'] : $email;
                $displayMobile = $is_for_self ? $user['mobile_number'] : $mobile_number;
                $fullName = htmlspecialchars($displayFirstname . ' ' . $displayLastname);
                
                // Get full address
                $fullAddress = htmlspecialchars(
                    $street_address . ', ' . 
                    $barangay . ', ' . 
                    $city . ', ' . 
                    $province . ', ' . 
                    $region
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
            // User cancelled the appointment - simply clear the session
            AppointmentSession::clearAllData();
            header("Location: Appointment-service.php");
            exit();
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred while cancelling your appointment.";
            header("Location: Appointment-service.php");
            exit();
        }
    } elseif (isset($_POST['done'])) {
        // Clear all appointment related session data
        AppointmentSession::clearAllData();
        
        // Redirect to home page
        header("Location: ../index.php");
        exit();
    }
}

try {
    // Check if appointment was already confirmed
    if (isset($_SESSION['appointment_confirmed']) && $_SESSION['appointment_confirmed'] && isset($_SESSION['appointment_id'])) {
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
                  WHERE a.id = :appointment_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([':appointment_id' => $_SESSION['appointment_id']]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if appointment data was found
        if (!$appointment) {
            throw new Exception("Appointment data not found in database.");
        }
    } else {
        // Get appointment details from session steps
        // Get all data from the session
        $allData = AppointmentSession::getAllData();
        if (!$allData) {
            throw new Exception("No appointment data found in session.");
        }
        
        // Get service details from database - use original_service_id if available, otherwise use service_id
        $service_id_to_query = $allData['original_service_id'] ?? $allData['service_id'];
        $query = "SELECT service_name, starting_price FROM services WHERE service_id = :service_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':service_id' => $service_id_to_query]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            throw new Exception("Service details not found for ID: " . $service_id_to_query);
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
        $isForSelf = $allData['is_for_self'] ?? 1;
        
        // Combine all data to create appointment array
        $appointment = [
            'service_name' => $service['service_name'] ?? 'N/A',
            'starting_price' => $service['starting_price'] ?? 0,
            'appointment_date' => $allData['appointment_date'] ?? date('Y-m-d'),
            'appointment_time' => $allData['appointment_time'] ?? '12:00:00',
            'street_address' => $allData['street_address'] ?? '',
            'landmark' => $allData['landmark'] ?? '',
            'barangay' => $allData['barangay'] ?? '',
            'city' => $allData['city'] ?? 'Zamboanga City',
            'province' => $allData['province'] ?? 'Zamboanga Del Sur',
            'region' => $allData['region'] ?? 'Region IX',
            'display_firstname' => $isForSelf ? ($user['firstname'] ?? '') : ($allData['firstname'] ?? ''),
            'display_lastname' => $isForSelf ? ($user['lastname'] ?? '') : ($allData['lastname'] ?? ''),
            'display_email' => $isForSelf ? ($user['email'] ?? '') : ($allData['email'] ?? ''),
            'display_mobile_number' => $isForSelf ? ($user['mobile_number'] ?? '') : ($allData['mobile_number'] ?? ''),
            'property_type' => $allData['property_type'] ?? 'Residential',
            'establishment_name' => $allData['establishment_name'] ?? '',
            'property_area' => $allData['property_area'] ?? '',
            'pest_concern' => $allData['pest_concern'] ?? '',
            'service_id' => $allData['service_id'],
            'original_service_id' => $allData['original_service_id'] ?? $allData['service_id'],
            'service_type' => $allData['service_type'] ?? (($allData['service_id'] == 17) ? 'Ocular Inspection' : 'Treatment')
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
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
                    <?php
                    // Get the original service name if it's an ocular inspection
                    $original_service_name = $appointment['service_name'];
                    $service_id_for_price = isset($appointment['original_service_id']) ? 
                        $appointment['original_service_id'] : $appointment['service_id'];
                    
                    // If we need to fetch the original service price
                    if (isset($appointment['service_id']) && $appointment['service_id'] == 17 && 
                        isset($appointment['original_service_id']) && $appointment['original_service_id'] != 17) {
                        // Look up the original service name and price
                        try {
                            $origServiceQuery = "SELECT service_name, starting_price FROM services WHERE service_id = :service_id";
                            $origServiceStmt = $db->prepare($origServiceQuery);
                            $origServiceStmt->execute([':service_id' => $appointment['original_service_id']]);
                            $origService = $origServiceStmt->fetch(PDO::FETCH_ASSOC);
                            if ($origService) {
                                $original_service_name = $origService['service_name'];
                                // Update the price in the appointment array
                                $appointment['starting_price'] = $origService['starting_price'];
                            }
                        } catch (Exception $e) {
                            // If there's an error, just use the current service name and price
                        }
                    }
                    ?>
                    <div class="receipt-row">
                        <span class="label">Service Type:</span>
                        <span class="value"><?php echo htmlspecialchars($original_service_name); ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Appointment Type:</span>
                        <span class="value">
                            <?php 
                            // Check if this is an ocular inspection (service ID 17)
                            if ((isset($appointment['service_id']) && $appointment['service_id'] == 17) || 
                                (isset($appointment['service_type']) && strtolower($appointment['service_type']) == 'ocular inspection') ||
                                (isset($allData['service_id']) && $allData['service_id'] == 17) ||
                                (isset($allData['service_type']) && strtolower($allData['service_type']) == 'ocular inspection')
                            ) {
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
                <form method="post" id="doneForm" style="width: 100%; text-align: center;">
                    <button type="button" id="doneBtn" class="done-btn">Done</button>
                    <input type="hidden" name="done" value="1">
                </form>
            <?php else: ?>
                <form method="post" id="actionForm">
                    <button type="button" id="cancelBtn" class="back-btn">Cancel</button>
                    <button type="button" id="confirmBtn" class="next-btn">Confirm<br>Appointment</button>
                    <input type="hidden" name="action" id="formAction" value="">
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

    <script>
        // For the "Done" button when appointment is confirmed
        document.getElementById('doneBtn')?.addEventListener('click', function() {
            Swal.fire({
                title: 'Return to Homepage',
                text: 'You will be redirected to the homepage. Continue?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#144578',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'No, stay here'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('doneForm').submit();
                }
            });
        });

        // For the "Cancel" button before confirmation
        document.getElementById('cancelBtn')?.addEventListener('click', function() {
            Swal.fire({
                title: 'Cancel Appointment?',
                text: 'Are you sure you want to cancel this appointment? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel appointment',
                cancelButtonText: 'No, keep appointment'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formAction').value = 'cancel';
                    document.getElementById('actionForm').innerHTML += '<input type="hidden" name="cancel" value="1">';
                    document.getElementById('actionForm').submit();
                }
            });
        });

        // For the "Confirm Appointment" button
        document.getElementById('confirmBtn')?.addEventListener('click', function() {
            Swal.fire({
                title: 'Confirm Appointment?',
                text: 'Are you sure you want to confirm this appointment? An email confirmation will be sent to you.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#144578',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, confirm appointment',
                cancelButtonText: 'No, not yet'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formAction').value = 'confirm';
                    document.getElementById('actionForm').innerHTML += '<input type="hidden" name="confirm" value="1">';
                    document.getElementById('actionForm').submit();
                }
            });
        });
        
        // Clear appointment session when clicking on navigation links
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('nav a:not(.btn-appointment)').forEach(link => {
                link.addEventListener('click', function(event) {
                    // If appointment is already confirmed, no need to clear or warn
                    <?php if (!isset($_SESSION['appointment_confirmed'])): ?>
                    // Prevent default navigation
                    event.preventDefault();
                    
                    // Clear the session via AJAX and then navigate
                    fetch('../PHP CODES/clear_appointment_session.php', {
                        method: 'POST'
                    })
                    .then(() => {
                        window.location.href = this.href;
                    });
                    <?php endif; ?>
                });
            });
        });
    </script>
</body>
</html>
