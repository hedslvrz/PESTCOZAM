<?php
session_start();
require_once '../database.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<pre>POST data: ";
    print_r($_POST);
    echo "</pre>";
    
    // Check the value being stored in session
    if (isset($_POST['is_for_self'])) {
        echo "<pre>is_for_self value: " . $_POST['is_for_self'] . "</pre>";
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php"); // Redirect if not logged in
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['next'])) {
    $user_id = $_SESSION['user_id'];
    $service_id = $_POST['service_id'];
    $isForSelf = isset($_POST['is_for_self']) ? (int)$_POST['is_for_self'] : 1;
    
    // Debug
    error_log("Value of is_for_self: " . $isForSelf);

    // Store progress in session
    $_SESSION['appointment'] = [
        'user_id' => $user_id,
        'service_id' => $service_id,
        'is_for_self' => $isForSelf
    ];

    // Insert a new appointment
    $insertQuery = "INSERT INTO appointments (user_id, service_id, is_for_self, status) 
                    VALUES (:user_id, :service_id, :is_for_self, :status)";
    $stmt = $db->prepare($insertQuery);
    $stmt->execute([
        ':user_id' => $user_id,
        ':service_id' => $service_id,
        ':is_for_self' => $isForSelf,
        ':status' => 'pending'
    ]);

    
        header("Location: Appointment-loc.php");
    exit();
}

// Initialize database class
$database = new Database();
$db = $database->getConnection();

// Modify the query to fetch all service details, including Ocular Inspection
$query = "SELECT service_id, service_name, description, estimated_time, starting_price, image_path, 
          CASE WHEN service_name = 'Ocular Inspection' THEN 1 ELSE 0 END AS is_ocular 
          FROM services ORDER BY is_ocular DESC, service_id ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="../CSS CODES/Appointment-service.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="../JS CODES/appointment.js"></script>
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

    <!-- Appointment Selection Section -->
    <main>
        <div class="form-container">
            <?php foreach ($services as $service): ?>
                <?php if ($service['service_name'] == 'Ocular Inspection'): ?>
                    <h2 class="Appointment-lbl">Ocular Inspection</h2>
                    <div class="ocular-card">
                        <div class="ocular-content">
                            <div class="ocular-image">
                                <img src="../Pictures/<?= htmlspecialchars($service['image_path']) ?>" alt="Ocular Inspection" />
                            </div>
                            <div class="ocular-info">
                                <h3><?= htmlspecialchars($service['service_name']) ?></h3>
                                <p><?= htmlspecialchars($service['description']) ?></p>
                                <div class="service-details">
                                    <p><i class='bx bx-time'></i> Est. Time: <?= htmlspecialchars($service['estimated_time']) ?></p>
                                    <?php if ($service['starting_price'] == 0): ?>
                                        <p><i class='bx bx-check-circle'></i> Free of Charge</p>
                                    <?php else: ?>
                                        <p><i class='bx bx-money'></i> Starting at ₱<?= number_format($service['starting_price']) ?></p>
                                    <?php endif; ?>
                                    <p><i class='bx bx-info-circle'></i> Required before any treatment service</p>
                                </div>
                                <button class="book-now-btn" id="book-btn-<?= $service['service_id'] ?>" onclick="selectService(<?= $service['service_id'] ?>)">
                                    Book Now
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"></div>
                <?php endif; ?>
            <?php endforeach; ?>

            <h2 class="Appointment-lbl">Select a Service</h2>
            
            <div class="offer-grid">
                <?php foreach ($services as $service): ?>
                <?php if ($service['service_name'] != 'Ocular Inspection'): ?>
                <div class="offer-card">
                    <img src="../Pictures/<?= htmlspecialchars($service['image_path']) ?>" 
                         alt="<?= htmlspecialchars($service['service_name']) ?>" />
                    <div class="offer-text">
                        <h3><?= htmlspecialchars($service['service_name']) ?></h3>
                        <p><?= htmlspecialchars($service['description']) ?></p>
                        <div class="service-details">
                            <p><i class='bx bx-time'></i> Est. Time: <?= htmlspecialchars($service['estimated_time']) ?></p>
                            <p><i class='bx bx-money'></i> Starting at ₱<?= number_format($service['starting_price']) ?></p>
                        </div>
                        <!-- Assign a unique ID to each button so we can highlight the selected one -->
                        <button 
                            class="book-now-btn" 
                            id="book-btn-<?= $service['service_id'] ?>" 
                            onclick="selectService(<?= $service['service_id'] ?>)">
                            Book Now
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="appointment-for">
                <p>Who is this appointment for?</p>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="appointment_for" value="1" checked id="for-myself">For Myself
                    </label>
                    <label>
                        <input type="radio" name="appointment_for" value="0" id="for-someone-else">For Someone Else
                    </label>
                </div>
            </div>

            <!-- Add a hidden field to store the selection -->
            <input type="hidden" id="isForSelf" name="is_for_self" value="1">
            
            <div class="reminder">  
                <p>Friendly Reminder:</p>
                <ol>
                    <li>A professional will perform an ocular inspection of the site to assess the pest problem and recommend the best treatment.</li>
                    <li>The total cost of the service will be determined after the ocular inspection. Our experts will assess the severity of the pest issue and recommend the best treatment plan. Pricing may vary depending on the size of the area (square meters) and the type of treatment required. A detailed quotation will be provided after the inspection.</li>
                </ol>
            </div>
            
            <div class="button-container">
            <a href="../Index.php" class="back-btn">Back to Home</a>
            <form id="serviceForm" action="Appointment-service.php" method="POST">
                <input type="hidden" id="selectedService" name="service_id" value="">
                <!-- Changed ID to avoid duplicate IDs -->
                <input type="hidden" id="isForSelfHidden" name="is_for_self" value="1">
                <input type="hidden" name="next" value="1">
                <button type="submit" disabled id="nextButton">Next</button>
            </form>
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

    <script>
    // Track the previously selected button so we can remove the highlight
    let previousSelectedButton = null;

    function selectService(serviceId) {
        // Enable "Next" button
        document.getElementById('selectedService').value = serviceId;
        document.getElementById('nextButton').disabled = false;
        
        // Remove highlight from any previously selected button
        if (previousSelectedButton) {
            previousSelectedButton.classList.remove('selected-btn');
        }

        // Highlight the newly selected button
        const clickedButton = document.getElementById('book-btn-' + serviceId);
        clickedButton.classList.add('selected-btn');

        // Update the reference
        previousSelectedButton = clickedButton;
    }
    // Add event listeners for radio buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial value based on the checked radio button
            const checkedRadio = document.querySelector('input[name="appointment_for"]:checked');
            if (checkedRadio) {
                document.getElementById('isForSelf').value = checkedRadio.value;
            }

            // Add event listeners for radio buttons
            const radioButtons = document.querySelectorAll('input[name="appointment_for"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.getElementById('isForSelf').value = this.value;
                    console.log("Updated isForSelf value to: " + this.value); // Debug line
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
            // Set up radio button event listeners
            const radioButtons = document.querySelectorAll('input[name="appointment_for"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Update the hidden form field with numeric values
                    document.getElementById('isForSelf').value = this.value; // Will be "1" or "0" as strings
                    console.log('isForSelf value updated to: ' + this.value);
                });
            });
            
            // Rest of your code...
        });
        // Update the event listener code
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial value based on the checked radio button
            const checkedRadio = document.querySelector('input[name="appointment_for"]:checked');
            if (checkedRadio) {
                document.getElementById('isForSelfHidden').value = checkedRadio.value;
            }

            // Add event listeners for radio buttons
            const radioButtons = document.querySelectorAll('input[name="appointment_for"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.getElementById('isForSelfHidden').value = this.value;
                    console.log("Updated isForSelf value to: " + this.value);
                });
            });
        });
    </script>
</body>
</html>
