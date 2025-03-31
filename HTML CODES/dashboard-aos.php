<?php
session_start();
require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Update the appointments query at the top of the file
try {
    // Get appointments with technician info and conditional customer name
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
        t.lastname as tech_lastname
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
                    <i class='bx bxs-briefcase'></i>
                    <span class="text">Assign Technician</span>
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

    <!-- Assign technician Section -->
    <section id="work-orders" class="section">
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Assign technicians</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Assign Technician</a></li>
                    </ul>
                </div>
            </div>

            <div class="table-data">
                <div class="order-list">
                    <div class="filters">
                        <div class="tabs">
                            <button type="button" class="filter-btn active" data-filter="all">All</button>
                            <button type="button" class="filter-btn" data-filter="pending">Pending</button>
                            <button type="button" class="filter-btn" data-filter="confirmed">Confirmed</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Schedule</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($appointments)): ?>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr data-status="<?php echo strtolower($appointment['status']); ?>">
                                            <!-- Schedule -->
                                            <td>
                                                <div class="schedule-info">
                                                    <i class='bx bx-calendar'></i>
                                                    <div>
                                                        <span class="date">
                                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                        </span>
                                                        <span class="time">
                                                            <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Customer -->
                                            <td>
                                                <div class="customer-info">
                                                    <i class='bx bx-user'></i>
                                                    <span><?php echo htmlspecialchars($appointment['client_firstname'] . ' ' . 
                                                        $appointment['client_lastname']); ?></span>
                                                </div>
                                            </td>
                                            
                                            <!-- Service -->
                                            <td>
                                                <div class="service-info">
                                                    <i class='bx bx-package'></i>
                                                    <span><?php echo htmlspecialchars($appointment['service_name']); ?></span>
                                                </div>
                                            </td>
                                            
                                            <!-- Location -->
                                            <td>
                                                <div class="location-info">
                                                    <i class='bx bx-map'></i>
                                                    <span><?php 
                                                        $location = array_filter([
                                                            $appointment['street_address'],
                                                            $appointment['barangay'],
                                                            $appointment['city']
                                                        ]);
                                                        echo htmlspecialchars(implode(', ', $location));
                                                    ?></span>
                                                </div>
                                            </td>
                                            
                                            <!-- Status -->
                                            <td>
                                                <span class="status <?php echo strtolower($appointment['status']); ?>">
                                                    <?php echo htmlspecialchars($appointment['status']); ?>
                                                </span>
                                            </td>
                                            
                                            <!-- Assigned Technician -->
                                            <td>
                                                <?php if (!empty($appointment['tech_firstname'])): ?>
                                                    <div class="tech-info">
                                                        <i class='bx bx-user-check'></i>
                                                        <span><?php echo htmlspecialchars($appointment['tech_firstname'] . ' ' . 
                                                            $appointment['tech_lastname']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="no-tech">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- Action -->
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($appointment['status'] === 'Pending' || $appointment['status'] === 'Confirmed'): ?>
                                                        <form class="inline-assign-form" data-appointment-id="<?php echo $appointment['appointment_id']; ?>">
                                                            <select class="tech-select" name="technician_id" required>
                                                                <option value="">-- Select Technician --</option>
                                                                <?php foreach ($technicians as $tech): ?>
                                                                    <option value="<?php echo $tech['id']; ?>" 
                                                                        <?php echo ($appointment['technician_id'] == $tech['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" class="assign-btn">
                                                                <?php if (empty($appointment['technician_id'])): ?>
                                                                    <i class='bx bx-check'></i> Assign
                                                                <?php else: ?>
                                                                    <i class='bx bx-refresh'></i> Update
                                                                <?php endif; ?>
                                                            </button>
                                                        </form>
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
    
    <div id="assignTechModal" class="custom-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3 id="modalTitle">Assign Technician</h3>
            </div>
            <div class="modal-body">
                <form id="assignTechForm">
                    <input type="hidden" id="appointmentId">
                    <div class="form-group">
                        <label for="technicianId">Select Technician:</label>
                        <select id="technicianId" name="technician_id" required>
                            <option value="">-- Select Technician --</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>">
                                    <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-button primary">Confirm</button>
                        <button type="button" class="modal-button secondary btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../JS CODES/modal.js"></script>
    <script src="../JS CODES/dashboard-aos.js"></script>
</body>
</html>