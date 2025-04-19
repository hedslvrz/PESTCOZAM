<?php
session_start();
require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Update the appointments query at the top of the file
try {
    // Improved query to get appointments with ALL assigned technicians
    $appointmentsQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.street_address,
        a.barangay,
        a.city,
        a.region,
        a.technician_id,
        a.is_for_self,
        CASE 
            WHEN a.is_for_self = 1 THEN u.firstname
            ELSE a.firstname
        END as client_firstname,
        CASE 
            WHEN a.is_for_self = 1 THEN u.lastname
            ELSE a.lastname
        END as client_lastname,
        s.service_name,
        t.firstname as tech_firstname,
        t.lastname as tech_lastname,
        (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname) SEPARATOR ', ') 
         FROM appointment_technicians at 
         JOIN users u2 ON at.technician_id = u2.id 
         WHERE at.appointment_id = a.id) as all_technicians
    FROM appointments a
    INNER JOIN services s ON a.service_id = s.service_id
    INNER JOIN users u ON a.user_id = u.id
    LEFT JOIN users t ON a.technician_id = t.id
    ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $stmt = $db->prepare($appointmentsQuery);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get available technicians
    $techQuery = "SELECT id, firstname, lastname 
                 FROM users 
                 WHERE role = 'technician' 
                 AND status = 'verified'";
    $techStmt = $db->prepare($techQuery);
    $techStmt->execute();
    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $appointments = [];
    $technicians = [];
}

// Check if user is logged in and has AOS role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

// Get user data from database
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $user = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS CODES/dashboard-aos.css">
    <link rel="stylesheet" href="../CSS CODES/modal.css">
    <style>
        /* Override disabled option styling for technician select */
        #technician-select option[disabled] {
            background-color: transparent;
            color: inherit;
        }
    </style>
    <title>AOS Dashboard</title>
</head>
<body>
    <!-- SIDEBAR SECTION -->
    <section id="sidebar">
        <div class="logo-container">
            <img src="../Pictures/pest_logo.png" alt="Flower Logo" class="flower-logo">
            <span class="brand-name">PESTCOZAM</span>
        </div>
        <ul class="side-menu top">
            <li class="active">
                <a href="#dashboard" onclick="showSection('dashboard')">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#work-orders" onclick="showSection('work-orders')">
                    <i class='bx bx-task'></i>
                    <span class="text">Manage Job Orders</span>
                </a>
            </li>
            <li>
                <a href="#schedule-followup" onclick="showSection('schedule-followup')">
                    <i class='bx bx-calendar-plus'></i>
                    <span class="text">Schedule Follow-up</span>
                </a>
            </li>
            <li>
                <a href="#reports" onclick="showSection('reports')">
                    <i class='bx bxs-file'></i>
                    <span class="text">Manage Technician Reports</span>
                </a>
            </li>
            <li>
                <a href="#profile" onclick="showSection('profile')">
                    <i class='bx bx-user'></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout">
                    <i class='bx bx-log-out'></i>
                    <span>Log out</span>
                </a>
            </li>
        </ul>
    </section>

    <!-- MAIN NAVBAR -->
    <nav id="main-navbar">
        <i class='bx bx-menu'></i>
        <form action="#">
            <div class="form-input">
                <input type="search" placeholder="Search">
                <button type="submit" class="search"><i class='bx bx-search'></i></button>
            </div>
        </form>
        <a href="#" class="notification">
            <i class='bx bxs-bell'></i>
            <span class="num">3</span>
        </a>
        <a href="#" class="profile">
            <img src="../Pictures/tech-profile.jpg" alt="Supervisor Profile">
        </a>
    </nav>

    <!-- Dashboard Section -->
    <section id="dashboard" class="section active">
        <main>
            <div class="form-container">
                <form id="dashboard-form" method="POST" action="process_dashboard.php">
                    <div class="head-title">
                        <div class="left">
                            <h1>Dashboard</h1>
                            <ul class="breadcrumb">
                                <li><a href="#">Dashboard</a></li>
                                <li><i class='bx bx-right-arrow-alt'></i></li>
                                <li><a class="active" href="#">Home</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="box-info">
                        <li>
                            <i class='bx bxs-calendar-check'></i>
                            <span class="text">
                                <h3>5</h3>
                                <p>Pending Jobs</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-group'></i>
                            <span class="text">
                                <h3>8</h3>
                                <p>Active Technicians</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-file'></i>
                            <span class="text">
                                <h3>12</h3>
                                <p>Service Reports</p>
                            </span>
                        </li>
                    </div>
                </form>
            </div>
        </main>
    </section>

    <!-- Work Orders/Job Orders Section -->
    <section id="work-orders" class="section">
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Work Orders Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Work Orders</a></li>
                    </ul>
                </div>
            </div>

            <div class="table-data">
                <div class="order-list">
                    <div class="table-header">
                        <div class="search-filters">
                            <div class="search-box">
                                <i class='bx bx-search'></i>
                                <input type="text" id="searchAppointments" placeholder="Search appointments...">
                            </div>
                            <div class="filter-buttons">
                                <button type="button" class="filter-btn active" data-filter="all">All</button>
                                <button type="button" class="filter-btn" data-filter="pending">Pending</button>
                                <button type="button" class="filter-btn" data-filter="confirmed">Confirmed</button>
                                <button type="button" class="filter-btn" data-filter="completed">Completed</button>
                            </div>
                            <div class="date-filter">
                                <input type="date" id="filterDate">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="work-orders-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Schedule</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Technician</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($appointments)): ?>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr data-status="<?php echo strtolower($appointment['status']); ?>" data-date="<?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?>">
                                            <td>#<?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                            <td>
                                                <div class="schedule-info">
                                                    <i class='bx bx-calendar'></i>
                                                    <div>
                                                        <span class="date"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></span>
                                                        <span class="time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="customer-info">
                                                    <i class='bx bx-user'></i>
                                                    <span><?php echo htmlspecialchars($appointment['client_firstname'] . ' ' . 
                                                        $appointment['client_lastname']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="service-info">
                                                    <i class='bx bx-package'></i>
                                                    <span><?php echo htmlspecialchars($appointment['service_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="tech-info">
                                                    <?php if (!empty($appointment['all_technicians'])): ?>
                                                        <i class='bx bx-user-check'></i>
                                                        <span title="<?php echo htmlspecialchars($appointment['all_technicians']); ?>">
                                                            <?php 
                                                            $technicians = $appointment['all_technicians'];
                                                            $techArray = explode(', ', $technicians);
                                                            echo '<ul class="tech-list">';
                                                            foreach ($techArray as $tech) {
                                                                echo '<li>' . htmlspecialchars($tech) . '</li>';
                                                            }
                                                            echo '</ul>';
                                                            ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="no-tech">Not Assigned</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status <?php echo strtolower($appointment['status']); ?>">
                                                    <?php echo htmlspecialchars($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($appointment['status'] === 'Pending' || $appointment['status'] === 'Confirmed'): ?>
                                                        <a href="job-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="view-btn">
                                                            <i class='bx bx-show'></i> View Details
                                                        </a>
                                                        <button type="button" class="feedback-btn" onclick="showFeedbackModal(<?php echo $appointment['appointment_id']; ?>)">
                                                            <i class='bx bx-message-square-detail'></i> Feedback
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="status-message">Job completed</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="no-records">No appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <!-- Schedule Follow-up Section -->
    <section id="schedule-followup" class="section">
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Schedule Follow-up</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Appointments</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Schedule Follow-up</a></li>
                    </ul>
                </div>
            </div>

            <div class="followup-form-container">
                <div class="followup-label">Schedule Follow-up Visit</div>
                <div class="followup-grid">
                    <!-- Main Schedule Content -->
                    <div class="calendar-container">
                        <!-- FOLLOW-UP FORM -->
                        <div class="settings-card no-hover">
                            <div class="card-header">
                                <i class='bx bx-calendar-edit'></i>
                                <h4>Follow-up Details</h4>
                            </div>
                            <div class="plan-frequency">
                                <!-- Customer Selection - Updated to load completed appointments -->
                                <div class="form-group">
                                    <label>Select Customer's Last Appointment:</label>
                                    <select id="customer-select" required onchange="loadCustomerDetails(this.value)">
                                        <option value="" disabled selected>Select Customer</option>
                                        <?php 
                                        try {
                                            $customerQuery = "SELECT 
                                                a.id as appointment_id, 
                                                CASE 
                                                    WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                                                    ELSE CONCAT(a.firstname, ' ', a.lastname)
                                                END as customer_name,
                                                a.service_id,
                                                s.service_name,
                                                a.appointment_date,
                                                a.technician_id,
                                                CONCAT(t.firstname, ' ', t.lastname) as technician_name,
                                                CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location,
                                                (SELECT GROUP_CONCAT(at.technician_id) 
                                                FROM appointment_technicians at 
                                                WHERE at.appointment_id = a.id) as all_technician_ids,
                                                (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname) SEPARATOR ', ') 
                                                FROM appointment_technicians at 
                                                JOIN users u2 ON at.technician_id = u2.id 
                                                WHERE at.appointment_id = a.id) as all_technician_names
                                            FROM appointments a
                                            JOIN users u ON a.user_id = u.id
                                            JOIN services s ON a.service_id = s.service_id
                                            LEFT JOIN users t ON a.technician_id = t.id
                                            WHERE a.status = 'Completed'
                                            ORDER BY a.appointment_date DESC";
                                            
                                            // Add detailed error logging
                                            error_log("Executing customer query for follow-up: " . $customerQuery);
                                            
                                            $stmt = $db->prepare($customerQuery);
                                            $stmt->execute();
                                            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            error_log("Number of customers with completed appointments found: " . count($customers));
                                            
                                            // Check if any customers were found
                                            if (empty($customers)) {
                                                echo '<option value="">No completed appointments found</option>';
                                            } else {
                                                foreach ($customers as $customer) {
                                                    $displayDate = date('M d, Y', strtotime($customer['appointment_date']));
                                                    echo '<option value="' . $customer['appointment_id'] . '" '
                                                         . 'data-service="' . $customer['service_id'] . '" '
                                                         . 'data-location="' . htmlspecialchars($customer['location']) . '" '
                                                         . 'data-technician="' . $customer['technician_id'] . '" '
                                                         . 'data-technician-name="' . htmlspecialchars($customer['technician_name']) . '" '
                                                         . 'data-all-technicians="' . htmlspecialchars($customer['all_technician_ids']) . '" '
                                                         . 'data-all-technician-names="' . htmlspecialchars($customer['all_technician_names']) . '">'
                                                         . htmlspecialchars($customer['customer_name']) . ' - ' 
                                                         . htmlspecialchars($customer['service_name']) . ' (' . $displayDate . ')'
                                                         . '</option>';
                                                }
                                            }
                                        } catch(PDOException $e) {
                                            // Improved error logging with detailed message
                                            error_log("Error in customer query for follow-up: " . $e->getMessage());
                                            echo '<option value="">Error loading customers: ' . $e->getMessage() . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Service Type -->
                                <div class="form-group">
                                    <label>Service Type:</label>
                                    <select id="service-type" required>
                                        <option value="">Select Service</option>
                                        <?php 
                                        try {
                                            $serviceQuery = "SELECT service_id, service_name FROM services ORDER BY service_name";
                                            $stmt = $db->prepare($serviceQuery);
                                            $stmt->execute();
                                            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($services as $service) {
                                                echo '<option value="' . $service['service_id'] . '">' . 
                                                    htmlspecialchars($service['service_name']) . '</option>';
                                            }
                                        } catch(PDOException $e) {
                                            echo '<option value="">Error loading services</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Customer Location (Read-only display) -->
                                <div class="form-group">
                                    <label>Customer Location:</label>
                                    <input type="text" id="customer-location" readonly>
                                </div>
                                
                                <!-- Technician Selection (Default to last assigned) -->
                                <div class="form-group">
                                    <label>Assign Technician:</label>
                                    <select id="technician-select" required multiple size="6" style="width: 100%; max-width: 300px; min-height: 150px;">
                                        <option value="" disabled selected>Show Technician/s</option>
                                        <?php 
                                        if (!empty($technicians)) {
                                            foreach ($technicians as $tech) {
                                                echo '<option value="' . $tech['id'] . '">' . 
                                                    htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Follow-up Date -->
                                <div class="form-group">
                                    <label>Follow-up Date:</label>
                                    <input type="date" id="followup-date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <!-- Follow-up Time -->
                                <div class="form-group">
                                    <label>Follow-up Time:</label>
                                    <select id="followup-time" required>
                                        <option value="">Select Time</option>
                                        <option value="07:00:00">7:00 AM - 9:00 AM</option>
                                        <option value="09:00:00">9:00 AM - 11:00 AM</option>
                                        <option value="11:00:00">11:00 AM - 1:00 PM</option>
                                        <option value="13:00:00">1:00 PM - 3:00 PM</option>
                                        <option value="15:00:00">3:00 PM - 5:00 PM</option>
                                    </select>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="form-group">
                                    <button type="button" class="btn-submit" id="schedule-followup-btn">
                                        <i class='bx bx-calendar-check'></i> Schedule Follow-up
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Appointments Card -->
                        <div class="settings-card no-hover">
                            <div class="card-header">
                                <i class='bx bx-notepad'></i>
                                <h4>Scheduled Follow-ups</h4>
                            </div>
                            <div class="table-wrapper">
                                <table class="appointments-table">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Client</th>
                                            <th>Service</th>
                                            <th>Technician</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="followups-list">
                                        <?php
                                        try {
                                            $followupsQuery = "SELECT 
                                                a.id as appointment_id,
                                                a.appointment_date,
                                                a.appointment_time,
                                                CASE 
                                                    WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                                                    ELSE CONCAT(a.firstname, ' ', a.lastname)
                                                END as customer_name,
                                                s.service_name,
                                                GROUP_CONCAT(CONCAT(tech.firstname, ' ', tech.lastname) SEPARATOR ', ') as technician_names,
                                                CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location,
                                                a.status
                                            FROM appointments a
                                            JOIN users u ON a.user_id = u.id
                                            JOIN services s ON a.service_id = s.service_id
                                            LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
                                            LEFT JOIN users tech ON at.technician_id = tech.id
                                            WHERE a.status = 'Confirmed' 
                                            AND a.appointment_date >= CURDATE()
                                            GROUP BY a.id
                                            ORDER BY a.appointment_date ASC, a.appointment_time ASC
                                            LIMIT 10";
                                            
                                            $stmt = $db->prepare($followupsQuery);
                                            $stmt->execute();
                                            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (!empty($followups)) {
                                                foreach ($followups as $followup) {
                                                    echo '<tr>';
                                                    echo '<td>' . date('M d, Y', strtotime($followup['appointment_date'])) . ' ' . 
                                                         date('h:i A', strtotime($followup['appointment_time'])) . '</td>';
                                                    echo '<td>' . htmlspecialchars($followup['customer_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($followup['service_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($followup['technician_names'] ?? 'Not Assigned') . '</td>';
                                                    echo '<td>' . htmlspecialchars($followup['location']) . '</td>';
                                                    echo '<td><span class="status ' . strtolower($followup['status']) . '">' . 
                                                         htmlspecialchars($followup['status']) . '</span></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6">No follow-ups scheduled</td></tr>';
                                            }
                                        } catch(PDOException $e) {
                                            echo '<tr><td colspan="6">Error loading follow-ups</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <!-- Reports Section -->
    <section id="reports" class="section">
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Manage Technician Reports</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Reports</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Technician Reports</a></li>
                    </ul>
                </div>
            </div>

            <div class="reports-grid">
                <!-- Sample Report Card -->
                <div class="report-card" data-report-id="1" onclick="openReportModal(1)">
                    <div class="report-header">
                        <div class="report-status pending">Pending Review</div>
                        <div class="report-date">March 15, 2024</div>
                    </div>
                    <div class="report-body">
                        <div class="technician-info">
                            <img src="../Pictures/boy.png" alt="Technician">
                            <div>
                                <h3>John Smith</h3>
                                <span>Senior Technician</span>
                            </div>
                        </div>
                        <div class="report-preview">
                            <p><i class='bx bx-map'></i> Tetuan, Zamboanga City</p>
                            <p><i class='bx bx-user'></i> Client: Maria Garcia</p>
                            <p><i class='bx bx-spray-can'></i> Service: Pest Control</p>
                        </div>
                    </div>
                </div>

                <!-- More Sample Cards -->
                <div class="report-card" data-report-id="2" onclick="openReportModal(2)">
                    <div class="report-header">
                        <div class="report-status approved">Approved</div>
                        <div class="report-date">March 14, 2024</div>
                    </div>
                    <div class="report-body">
                        <div class="technician-info">
                            <img src="../Pictures/boy.png" alt="Technician">
                            <div>
                                <h3>Mike Johnson</h3>
                                <span>Pest Control Specialist</span>
                            </div>
                        </div>
                        <div class="report-preview">
                            <p><i class='bx bx-map'></i> Sta. Maria, Zamboanga City</p>
                            <p><i class='bx bx-user'></i> Client: John Doe</p>
                            <p><i class='bx bx-spray-can'></i> Service: Termite Control</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Report Details Modal -->
        <div id="reportModal" class="modal">
            <div class="report-modal-content">
                <span class="close-modal" onclick="closeReportModal()">&times;</span>
                <form id="reportForm" class="report-form">
                    <h2>Service Report Details</h2>
                    
                    <div class="form-section">
                        <h3>Basic Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Report ID</label>
                                <input type="text" value="REP-2024-001" readonly>
                            </div>
                            <div class="form-group">
                                <label>Date Submitted</label>
                                <input type="text" value="March 15, 2024" readonly>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Technician Name</label>
                                <input type="text" value="John Smith" readonly>
                            </div>
                            <div class="form-group">
                                <label>Client Name</label>
                                <input type="text" value="Maria Garcia" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Service Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Service Type</label>
                                <input type="text" value="Pest Control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" value="Tetuan, Zamboanga City" readonly>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Time In</label>
                                <input type="time" value="09:00" readonly>
                            </div>
                            <div class="form-group">
                                <label>Time Out</label>
                                <input type="time" value="11:30" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Treatment Information</h3>
                        <div class="form-group full-width">
                            <label>Treatment Method</label>
                            <textarea readonly>Spray treatment and bait installation for comprehensive pest control</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Chemicals Used</label>
                                <input type="text" value="PestAway Pro, RoachGuard" readonly>
                            </div>
                            <div class="form-group">
                                <label>Quantity Used</label>
                                <input type="text" value="2L, 500g" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Documentation</h3>
                        <div class="image-gallery">
                            <div class="image-item">
                                <img src="../Pictures/sample-report-1.jpg" alt="Before Treatment">
                                <span>Before Treatment</span>
                            </div>
                            <div class="image-item">
                                <img src="../Pictures/sample-report-2.jpg" alt="After Treatment">
                                <span>After Treatment</span>
                            </div>
                            <div class="image-item">
                                <img src="../Pictures/sample-report-3.jpg" alt="Area Treated">
                                <span>Area Treated</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Additional Notes</h3>
                        <div class="form-group full-width">
                            <textarea readonly>Client requested follow-up treatment in 3 months. Areas treated: kitchen, bathroom, and garden perimeter. Recommended preventive measures explained to client.</textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-approve" onclick="approveReport()">
                            <i class='bx bx-check'></i> Approve Report
                        </button>
                        <button type="button" class="btn-reject" onclick="rejectReport()">
                            <i class='bx bx-x'></i> Reject Report
                        </button>
                        <button type="button" class="btn-print" onclick="printReport()">
                            <i class='bx bx-printer'></i> Print Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Profile Section -->
    <section id="profile" class="section">
        <main>
            <form id="profile-form" method="POST" action="process_profile.php" enctype="multipart/form-data">
                <div class="head-title">
                    <div class="left">
                        <h1>My Profile</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="#">Profile</a>
                            </li>
                            <li><i class='bx bx-right-arrow-alt'></i></li>
                            <li>
                                <a class="active" href="#">Details</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="profile-container">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <input type="file" name="profile_image" id="profile_image" hidden>
                            <label for="profile_image">
                                <img src="images/profile-image.png" alt="Profile Picture">
                            </label>
                        </div>
                        <div class="profile-info">
                            <h3>John Doe</h3>
                            <p>Area Operations Supervisor</p>
                            <p>AOS-001</p>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="info-section">
                        <div class="section-header">
                            <h3>Personal Information</h3>
                            <button class="edit-btn"><i class="bx bx-edit"></i> Edit</button>
                        </div>
                        <div class="info-content">
                            <div class="info-row">
                                <p><strong>First Name</strong></p>
                                <p><strong>Last Name</strong></p>
                                <p><strong>Date of Birth</strong></p>
                            </div>
                            <div class="info-row">
                                <p>John</p>
                                <p>Doe</p>
                                <p>03-05-1990</p>
                            </div>
                            <div class="info-row">
                                <p><strong>Email:</strong></p>
                                <p><strong>Phone Number:</strong></p>
                                <p><strong>User Role:</strong></p>
                            </div>
                            <div class="info-row">
                                <p>john.doe@pestcozam.com</p>
                                <p>0953-654-4541</p>
                                <p>Area Operations Supervisor</p>
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="info-section">
                        <div class="section-header">
                            <h3>Address</h3>
                            <button class="edit-btn"><i class="bx bx-edit"></i> Edit</button>
                        </div>
                        <div class="info-content">
                            <div class="info-row">
                                <p><strong>Country</strong></p>
                                <p><strong>City:</strong></p>
                                <p><strong>City Address</strong></p>
                                <p><strong>Postal Code</strong></p>
                            </div>
                            <div class="info-row">
                                <p>Philippines</p>
                                <p>Zamboanga City</p>
                                <p>Tetuan, Hotdog Drive</p>
                                <p>7000</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </section>

    <div id="customModal" class="custom-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3 id="modalTitle"></h3>
            </div>
            <div class="modal-body" id="modalMessage"></div>
            <div class="modal-buttons">
                <button type="button" class="modal-button primary" id="modalOkBtn">OK</button>
            </div>
        </div>
    </div>
    
    <script src="../JS CODES/modal.js"></script>
    <script src="../JS CODES/dashboard-aos.js"></script>
    <script src="../JS CODES/followup.js"></script>
    <script src="../JS CODES/work-orders.js"></script>
    <script>
        function loadCustomerDetails(selectedValue) {
            if (!selectedValue || selectedValue === "") {
                // Clear form fields if no appointment is selected
                document.getElementById('service-type').value = '';
                document.getElementById('customer-location').value = '';
                // For multi-select, clear all selections
                const techSelect = document.getElementById('technician-select');
                if (techSelect) {
                    for (let i = 0; i < techSelect.options.length; i++) {
                        techSelect.options[i].selected = false;
                    }
                }
                return;
            }
            
            console.log('Loading details for appointment ID:', selectedValue);
            
            // Get the selected option element
            const selectedOption = document.querySelector(`#customer-select option[value="${selectedValue}"]`);
            if (!selectedOption) {
                console.error('Selected option not found for appointment ID:', selectedValue);
                return;
            }
            
            // Extract data from data attributes
            const serviceId = selectedOption.getAttribute('data-service');
            const location = selectedOption.getAttribute('data-location');
            const allTechnicians = selectedOption.getAttribute('data-all-technicians');
            const allTechnicianNames = selectedOption.getAttribute('data-all-technician-names');
            
            console.log('Appointment details found:', { serviceId, location, allTechnicians });
            
            // Set service type and customer location if available
            if (serviceId) document.getElementById('service-type').value = serviceId;
            if (location) document.getElementById('customer-location').value = location;
            
            const technicianSelect = document.getElementById('technician-select');
            if (technicianSelect) {
                // Clear previous selections
                for (let i = 0; i < technicianSelect.options.length; i++) {
                    technicianSelect.options[i].selected = false;
                }
                
                // Process technician IDs from all_technicians attribute
                if (allTechnicians && allTechnicians.trim() !== "" && allTechnicians.toLowerCase() !== "null") {
                    const techIds = allTechnicians.split(',').map(id => id.trim());
                    const techNames = allTechnicianNames ? 
                        allTechnicianNames.split(',').map(name => name.trim()) : 
                        techIds.map(id => `Technician ${id}`);
                    
                    console.log('Setting technicians:', techIds);
                    
                    // Select technicians that exist in the dropdown
                    for (let i = 0; i < technicianSelect.options.length; i++) {
                        if (techIds.includes(technicianSelect.options[i].value)) {
                            technicianSelect.options[i].selected = true;
                        }
                    }
                    
                    // Add any missing technicians to the dropdown
                    techIds.forEach((id, index) => {
                        const exists = Array.from(technicianSelect.options)
                            .some(opt => opt.value === id);
                        
                        if (!exists && id) {
                            const name = techNames[index] || `Technician ${id}`;
                            const opt = document.createElement('option');
                            opt.value = id;
                            opt.text = name;
                            technicianSelect.appendChild(opt);
                            opt.selected = true;
                        }
                    });
                } else {
                    // Fallback to main technician if no multi-technician data
                    const mainTechId = selectedOption.getAttribute('data-technician');
                    if (mainTechId && mainTechId !== 'null') {
                        const mainTechName = selectedOption.getAttribute('data-technician-name');
                        console.log('Setting main technician:', mainTechId, mainTechName);
                        
                        // Check if the technician exists in the dropdown
                        let techExists = false;
                        for (let i = 0; i < technicianSelect.options.length; i++) {
                            if (technicianSelect.options[i].value === mainTechId) {
                                technicianSelect.options[i].selected = true;
                                techExists = true;
                                break;
                            }
                        }
                        
                        // If not found, add it
                        if (!techExists && mainTechId) {
                            const opt = document.createElement('option');
                            opt.value = mainTechId;
                            opt.text = mainTechName || `Technician ${mainTechId}`;
                            technicianSelect.appendChild(opt);
                            opt.selected = true;
                        }
                    }
                }
            }
        }
        
        // When the page loads, attach event handler to the schedule button
        document.addEventListener('DOMContentLoaded', function() {
            const scheduleBtn = document.getElementById('schedule-followup-btn');
            if (scheduleBtn) {
                scheduleBtn.addEventListener('click', scheduleFollowUp);
            }
            
            // Initialize customer select change event if it exists on this page
            const customerSelect = document.getElementById('customer-select');
            if (customerSelect) {
                customerSelect.addEventListener('change', function() {
                    loadCustomerDetails(this.value);
                });
            }
        });
    </script>
</body>
</html>