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
                <a href="#service-reports" onclick="showSection('service-reports')">
                    <i class='bx bxs-file'></i>
                    <span class="text">Manage Service Reports</span>
                </a>
            </li>
            <li>
                <a href="#profile" onclick="showSection('profile')">
                    <i class='bx bx-user'></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="#logout" class="logout">
                    <i class='bx bx-log-out'></i>
                    <span class="text" href="../HTML CODES/logout.php">Log out</span>
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

    <!-- Job Orders Section -->
    <section id="work-orders" class="section">
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Manage Job Orders</h1>
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

    <!-- Service Reports Section -->
    <section id="service-reports" class="section">
        <main>
            <div class="form-container">
                <form id="reports-form" method="POST" action="process_reports.php">
                    <div class="head-title">
                        <div class="left">
                            <h1>Service Reports</h1>
                            <ul class="breadcrumb">
                                <li><a href="#">Reports</a></li>
                                <li><i class='bx bx-right-arrow-alt'></i></li>
                                <li><a class="active" href="#">Manage</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="filters">
                            <div class="tabs">
                                <button type="button" class="active">All Reports</button>
                                <button type="button">Pending Review</button>
                                <button type="button">Approved</button>
                                <button type="button">Rejected</button>
                            </div>
                            <input type="date" id="reportFilterDate" name="report_filter_date">
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Job ID</th>
                                    <th>Technician</th>
                                    <th>Service Date</th>
                                    <th>Customer</th>
                                    <th>Service Type</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>SR-2024-001</td>
                                    <td>JO-2024-002</td>
                                    <td>Michael Brown</td>
                                    <td>2024-01-15</td>
                                    <td>Mary Johnson</td>
                                    <td>Termite Treatment</td>
                                    <td><span class="status pending">Pending Review</span></td>
                                    <td><button class="view-btn">View Details</button></td>
                                </tr>
                                <tr>
                                    <td>SR-2024-002</td>
                                    <td>JO-2024-003</td>
                                    <td>David Miller</td>
                                    <td>2024-01-14</td>
                                    <td>Robert Wilson</td>
                                    <td>Rodent Control</td>
                                    <td><span class="status approved">Approved</span></td>
                                    <td><button class="view-btn">View Details</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
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
                                <h3>John Doe</h3>
                                <p>Area Operations Supervisor</p>
                                <p>ID: AOS-001</p>
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