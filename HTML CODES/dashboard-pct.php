<?php
session_start();
require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in and has PCT role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header("Location: login.php");
    exit();
}

// Get technician data from database
try {
    // Get technician data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'technician'");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get technician's assigned appointments with full details
    $appointmentsQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.street_address,
        a.barangay,
        a.city,
        CASE 
            WHEN a.is_for_self = 1 THEN u.firstname
            ELSE a.firstname
        END as client_firstname,
        CASE 
            WHEN a.is_for_self = 1 THEN u.lastname
            ELSE a.lastname
        END as client_lastname,
        s.service_name,
        s.service_id
    FROM appointments a
    INNER JOIN services s ON a.service_id = s.service_id
    INNER JOIN users u ON a.user_id = u.id
    WHERE a.technician_id = ?
    ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $stmt = $db->prepare($appointmentsQuery);
    $stmt->execute([$_SESSION['user_id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $technician = null;
    $assignments = [];
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
    <link rel="stylesheet" href="../CSS CODES/dashboard-pct.css">
    <title>PCT Dashboard</title>
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
                <a href="#assignments" onclick="showSection('assignments')">
                    <i class='bx bxs-briefcase'></i>
                    <span class="text">My Assignments</span>
                </a>
            </li>
            <li>
                <a href="#submit-report" onclick="showSection('submit-report')">
                    <i class='bx bx-file'></i>
                    <span class="text">Submit Report</span>
                </a>
            </li>
            <li>
                <a href="#profile" onclick="showSection('profile')">
                    <i class='bx bx-user'></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="login.php" class="logout">
                    <i class='bx bx-log-out'></i>
                    <span class="text">Log out</span>
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
            <img src="../Pictures/tech-profile.jpg" alt="Technician Profile">
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

    <!-- Assignments Section -->
    <section id="assignments" class="section">
        <main>
            <div class="form-container">
                <div class="head-title">
                    <div class="left">
                        <h1>My Assignments</h1>
                        <ul class="breadcrumb">
                            <li><a href="#">Assignments</a></li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li><a class="active" href="#">List</a></li>
                        </ul>
                    </div>
                </div>

                <div class="table-container">
                    <div class="filters">
                        <div class="tabs">
                            <button type="button" class="filter-btn active" data-filter="all">All</button>
                            <button type="button" class="filter-btn" data-filter="pending">Pending</button>
                            <button type="button" class="filter-btn" data-filter="confirmed">Confirmed</button>
                            <button type="button" class="filter-btn" data-filter="completed">Completed</button>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Schedule</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assignments)): ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr data-status="<?php echo strtolower($assignment['status']); ?>">
                                        <td>
                                            <div class="schedule-info">
                                                <i class='bx bx-calendar'></i>
                                                <div>
                                                    <span class="date">
                                                        <?php echo date('M d, Y', strtotime($assignment['appointment_date'])); ?>
                                                    </span>
                                                    <span class="time">
                                                        <?php echo date('h:i A', strtotime($assignment['appointment_time'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="customer-info">
                                                <i class='bx bx-user'></i>
                                                <span><?php echo htmlspecialchars($assignment['client_firstname'] . ' ' . 
                                                    $assignment['client_lastname']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="service-info">
                                                <i class='bx bx-package'></i>
                                                <span><?php echo htmlspecialchars($assignment['service_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="location-info">
                                                <i class='bx bx-map'></i>
                                                <span><?php 
                                                    $location = array_filter([
                                                        $assignment['street_address'],
                                                        $assignment['barangay'],
                                                        $assignment['city']
                                                    ]);
                                                    echo htmlspecialchars(implode(', ', $location));
                                                ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status <?php echo strtolower($assignment['status']); ?>">
                                                <?php echo htmlspecialchars($assignment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-records">No assignments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>

    <!-- Submit Report Section -->
    <section id="submit-report" class="section">
        <main>
            <div class="form-container">
                <div class="head-title">
                    <div class="left">
                        <h1>Submit Service Report</h1>
                        <ul class="breadcrumb">
                            <li><a href="#">Reports</a></li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li><a class="active" href="#">Submit</a></li>
                        </ul>
                    </div>
                </div>

                <div class="report-form-container">
                    <form class="service-report-form" method="POST" action="submit_report.php" enctype="multipart/form-data">
                        <!-- Top Section -->
                        <div class="form-section top-section">
                            <div class="form-step form-step-active">
                                <div class="step-header">
                                    <h3 class="step-title">
                                        <span class="step-number">1</span>
                                        <i class='bx bx-calendar'></i>
                                        Select Appointment
                                    </h3>
                                </div>
                                <div class="form-group">
                                    <select name="appointment_id" id="appointment" required>
                                        <option value="">Choose an appointment to report</option>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <option value="<?php echo $assignment['appointment_id']; ?>">
                                                <?php 
                                                echo date('M d, Y', strtotime($assignment['appointment_date'])) . ' - ' . 
                                                     $assignment['client_firstname'] . ' ' . $assignment['client_lastname'] . ' - ' . 
                                                     $assignment['service_name']; 
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section content-section">
                            <div class="form-row">
                                <!-- Service Report Container -->
                                <div class="report-container">
                                    <!-- Report Details -->
                                    <div class="form-step report-details">

                                        <div class="step-header">
                                            <h3 class="step-title">
                                                <span class="step-number">2</span>
                                                <i class='bx bx-file'></i>
                                                Service Report Details
                                            </h3>
                                        </div>
                                        <div class="report-content">
                                            <div class="report-text">
                                                <div class="form-group">
                                                    <textarea name="report" style="width: 350px; height: 250px; overflow: hidden;" id="report" required 
                                                        placeholder="Provide detailed information about the service performed..."></textarea>
                                                </div>
                                            </div>
                                            <div class="report-photos">
            
                                                <div id="preview-container" class="preview-container"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="file-upload-container" id="dropZone" style="height: auto;">
                                                    <i class='bx bx-cloud-upload'></i>
                                                    <p>Drag & Drop Photos Here</p>
                                                    <span>or</span>
                                                    <label for="photos" class="upload-btn">Choose Files</label>
                                                    <input type="file" name="photos[]" id="photos" multiple 
                                                        accept="image/*" class="file-input">
                                                    <small>Upload up to 5 photos (Max 2MB each)</small>
                                                </div>
                                </div>

                                <!-- Appointment Details - Full Width -->
                                <div class="form-step appointment-info" style="width: 900px;">
                                    <div class="step-header">
                                        <h3 class="step-title">
                                            <span class="step-number">3</span>
                                            <i class='bx bx-info-circle'></i>
                                            Appointment Details
                                        </h3>
                                    </div>
                                    <div class="appointment-details">
                                        <div class="detail-item">
                                            <div class="form-group">
                                                <label>Client Name</label>
                                                <div class="input-with-icon">
                                                    <i class='bx bx-user'></i>
                                                    <input type="text" id="client_name" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="form-group">
                                                <label>Service Type</label>
                                                <div class="input-with-icon">
                                                    <i class='bx bx-package'></i>
                                                    <input type="text" id="service_type" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="form-group">
                                                <label>Service Location</label>
                                                <div class="input-with-icon">
                                                    <i class='bx bx-map'></i>
                                                    <input type="text" id="location" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Section -->
                        <div class="form-section bottom-section">
                            <div class="form-actions">
                                <button type="reset" class="btn-reset">
                                    <i class='bx bx-reset'></i>
                                    Clear Form
                                </button>
                                <button type="submit" class="btn-submit">
                                    <i class='bx bx-send'></i>
                                    Submit Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </section>

    <!-- Profile Section -->
    <section id="profile" class="section">
        <main>
            <div class="form-container">
                <form id="profile-form" method="POST" action="process_profile.php" enctype="multipart/form-data">
                    <div class="head-title">
                        <div class="left">
                            <h1>Profile</h1>
                            <ul class="breadcrumb">
                                <li><a href="#">Profile</a></li>
                                <li><i class='bx bx-right-arrow-alt'></i></li>
                                <li><a class="active" href="#">My Account</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="profile-container">
                        <div class="profile-card">
                            <div class="profile-avatar">
                                <img src="../Pictures/tech-profile.jpg" alt="Profile Picture">
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($technician['name']); ?></h3>
                                <p><?php echo htmlspecialchars($technician['role']); ?></p>
                                <p>ID: <?php echo htmlspecialchars($technician['id']); ?></p>
                            </div>
                        </div>

                        <div class="info-section">
                            <div class="section-header">
                                <h3>Personal Information</h3>
                                <button class="edit-btn">
                                    <i class='bx bx-edit'></i>
                                    Edit
                                </button>
                            </div>
                            <div class="info-content">
                                <!-- Personal information fields -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </section>

    <script src="../JS CODES/dashboard-aos.js"></script>
</body>
</html>