<?php
session_start();
require_once '../database.php';
include_once("../employee/functions/fetch.php");

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in and has admin role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get admin data from database
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $admin = null;
}

// Get dashboard statistics
try {
    $stats = $db->query("SELECT 
        (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_jobs,
        (SELECT COUNT(*) FROM users WHERE role = 'technician' AND status = 'active') as active_technicians,
        (SELECT COUNT(*) FROM service_reports) as total_reports"
    )->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $stats = null;
}

// Get employee statistics
try {
    $stmt = $db->query("SELECT 
        COUNT(*) AS total, 
        SUM(role = 'technician') AS technicians,
        SUM(role = 'supervisor') AS supervisors,
        SUM(role = 'technician' AND status = 'active') AS active_technicians,
        SUM(role = 'supervisor' AND status = 'active') AS active_supervisors,
        SUM(role = 'technician' AND status = 'inactive') AS inactive_technicians,
        SUM(role = 'supervisor' AND status = 'inactive') AS inactive_supervisors
    FROM users
    WHERE role IN ('technician', 'supervisor')");

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure $data is not null
    if (!$data) {
        $data = [
            'total' => 0, 
            'technicians' => 0, 
            'supervisors' => 0, 
            'active_technicians' => 0, 
            'active_supervisors' => 0, 
            'inactive_technicians' => 0, 
            'inactive_supervisors' => 0
        ];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $data = [
        'total' => 0, 
        'technicians' => 0, 
        'supervisors' => 0, 
        'active_technicians' => 0, 
        'active_supervisors' => 0, 
        'inactive_technicians' => 0, 
        'inactive_supervisors' => 0
    ];
}

// Replace the existing appointments query with this updated version that includes multiple technicians
try {
    $appointmentsQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.street_address,
        a.barangay,
        a.city,
        a.technician_id,
        a.is_for_self,
        s.service_name,
        CASE 
            WHEN a.is_for_self = 1 THEN u.firstname
            ELSE a.firstname
        END as client_firstname,
        CASE 
            WHEN a.is_for_self = 1 THEN u.lastname
            ELSE a.lastname
        END as client_lastname,
        t.firstname as tech_firstname,
        t.lastname as tech_lastname,
        (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname) SEPARATOR ', ') 
         FROM appointment_technicians at 
         JOIN users u2 ON at.technician_id = u2.id 
         WHERE at.appointment_id = a.id) as all_technicians
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users t ON a.technician_id = t.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $db->prepare($appointmentsQuery);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $appointments = [];
}

// Get available technicians
try {
    $techQuery = "SELECT id, firstname, lastname 
                 FROM users 
                 WHERE role = 'technician' 
                 AND status = 'verified'";
    $techStmt = $db->prepare($techQuery);
    $techStmt->execute();
    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching technicians: " . $e->getMessage());
    $technicians = [];
}

// Get service reports
try {
    $serviceReportsQuery = "SELECT 
        sr.report_id,
        sr.date_of_treatment,
        sr.time_in,
        sr.time_out,
        sr.treatment_type,
        sr.treatment_method,
        sr.pest_count,
        sr.device_installation,
        sr.consumed_chemicals,
        sr.frequency_of_visits,
        sr.photos,
        sr.location,
        sr.account_name,
        sr.contact_no,
        sr.status,
        CONCAT(u.firstname, ' ', u.lastname) AS tech_name
    FROM service_reports sr
    JOIN users u ON sr.technician_id = u.id
    ORDER BY sr.date_of_treatment DESC";

    $stmt = $db->prepare($serviceReportsQuery);
    $stmt->execute();
    $serviceReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching service reports: " . $e->getMessage());
    $serviceReports = [];
}

// Fetch services data from database including starting prices
try {
    $servicesQuery = "SELECT * FROM services ORDER BY service_id";
    $servicesStmt = $db->prepare($servicesQuery);
    $servicesStmt->execute();
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
    $services = [];
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS CODES/dashboard-admin.css">
    <link rel="stylesheet" href="../CSS CODES/timeslot.css">
    <title>Pestcozam Dashboard</title>
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
                <a href="#dashboard" onclick="showSection('content')">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#work-orders" onclick="showSection('work-orders')">
                    <i class='bx bxs-briefcase'></i>
                    <span class="text">Manage Job Orders</span>
                </a>
            </li>
            <li>
                <a href="#employees" onclick="showSection('employees')">
                    <i class='bx bx-child'></i>
                    <span class="text">Manage Employees</span>
                </a>
            </li>
            <li>
                <a href="#services" onclick="showSection('services')">
                    <i class='bx bx-dish' ></i>
                    <span class="text">Manage Services</span>
                </a>
            </li>
            <li>
                <a href="#customers" onclick="showSection('customers')">
                    <i class='bx bx-run'></i>
                    <span class="text">Manage Customers</span>
                </a>
            </li>
            <li>
                <a href="#reports" onclick="showSection('reports')">
                    <i class='bx bxs-report' ></i>
                    <span class="text">Manage Technician Reports</span>
                </a>
            </li><li>
                <a href="#billing" onclick="showSection('billing')">
                    <i class='bx bx-money-withdraw' ></i>
                    <span class="text">Manage Billing</span>
                </a>
            </li>
            
            <li>
                <a href="#profile" onclick="showSection('profile')">
                    <i class='bx bx-user' ></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="#settings" onclick="showSection('settings')">
                    <i class='bx bx-cog' ></i>
                    <span class="text">Settings</span>
                </a>
            </li>
            <li>
                <a href="Login.php" class="logout">
                    <i class='bx bx-log-out' ></i>
                    <span class="text">Log out</span>
                </a>
            </li>
        </ul>
    </section>
<!-- SIDEBAR SECTION -->

<!-- MAIN NAVBAR -->
<nav id="main-navbar" class="standard-nav">
    <i class='bx bx-menu'></i>
    <a href="#" class="nav-link">Categories</a>
    <form action="#">
        <div class="form-input">
            <input type="search" placeholder="Search">
            <button type="submit" class="search"><i class='bx bx-search'></i></button>
        </div>
    </form>
    <a href="#" class="notification">
        <i class='bx bxs-bell'></i>
        <span class="num">8</span>
    </a>
    <a href="#" class="profile">
        <img src="images/heds.png">
    </a>
</nav>

<!-- Profile Modal - Add this if it doesn't exist -->
<div id="profileModal" class="modal">
    <div class="profile-modal-content">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <span class="close">&times;</span>
        </div>
        <form id="editProfileForm" method="POST" action="../PHP CODES/update_admin_profile.php" novalidate>
            <div class="form-group">
                <label for="firstname">First Name</label>
                <input type="text" id="firstname" name="firstname" value="<?php echo $admin['firstname'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="middlename">Middle Name (Optional)</label>
                <input type="text" id="middlename" name="middlename" value="<?php echo $admin['middlename'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo $admin['lastname'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo $admin['email'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="mobile_number">Mobile Number</label>
                <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo $admin['mobile_number'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth (Optional)</label>
                <input type="date" id="dob" name="dob" value="<?php echo $admin['dob'] ?? ''; ?>">
            </div>
            <div class="form-buttons">
                <button type="button" id="closeProfileModalBtn" class="cancel-btn">Cancel</button>
                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!--DASHBOARD CONTENT -->
<section id="content" class="section active">
    <main>
        <!-- Start dashboard form - closing before time slot section -->
        <form id="dashboard-form" method="POST" action="process_dashboard.php">
            <div class="head-title">
                <div class="left">
                    <h1>Dashboard</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Dashboard</a>
                        </li>
                        <li><i class='bx bx-right-arrow-alt' ></i></li>
                        <li>
                            <a class="active" href="#">Home</a>
                        </li>
                    </ul>
                </div>
            </div>  

            <ul class="box-info">
                <li>
                    <i class='bx bxs-calendar-check' ></i>
                    <span class="text">
                        <h3><?php echo htmlspecialchars($stats['pending_jobs'] ?? '0'); ?></h3>
                        <p>Appointments</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group' ></i>
                    <span class="text">
                        <h3>6</h3>
                        <p>Available</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-dollar-circle' ></i>
                    <span class="text">
                        <h3>₱2025</h3>
                        <p>Total Sales</p>
                        <p>Appointments</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group' ></i>
                    <span class="text">
                        <h3>6</h3>
                        <p>Available</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-dollar-circle' ></i>
                    <span class="text">
                        <h3>₱2025</h3>
                        <p>Total Sales</p>
                    </span>
                </li>
            </ul>
        </form><!-- End dashboard form BEFORE time slot management section -->
        
        <!-- Time Slot Management Section - Now outside the dashboard form -->
        <div class="time-slot-management">
            <h2>Time Slot Management</h2>
            
            <!-- Notification Messages -->
            <?php if (isset($_SESSION['timeslot_success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['timeslot_success']); ?>
                <?php unset($_SESSION['timeslot_success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['timeslot_error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['timeslot_error']); ?>
                <?php unset($_SESSION['timeslot_error']); ?>
            </div>
            <?php endif; ?>
            
            <form id="timeSlotForm" method="POST" action="../PHP CODES/process_timeslot.php">
                <div class="calendar-container">
                    <!-- Calendar Section - Left Side -->
                    <div class="calendar">
                        <div class="calendar-header">
                            <select id="monthSelect">
                                <option value="0">January</option>
                                <option value="1">February</option>
                                <option value="2">March</option>
                                <option value="3">April</option>
                                <option value="4">May</option>
                                <option value="5">June</option>
                                <option value="6">July</option>
                                <option value="7">August</option>
                                <option value="8">September</option>
                                <option value="9">October</option>
                                <option value="10">November</option>
                                <option value="11">December</option>
                            </select>
                            <select id="yearSelect">
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        <div class="calendar-grid">
                            <div class="day-name">Sun</div>
                            <div class="day-name">Mon</div>
                            <div class="day-name">Tue</div>
                            <div class="day-name">Wed</div>
                            <div class="day-name">Thu</div>
                            <div class="day-name">Fri</div>
                            <div class="day-name">Sat</div>
                            <div id="calendar-days" class="calendar-days"></div>
                        </div>
                        
                        <!-- Selected dates display -->
                        <div id="selectedDatesContainer" class="selected-dates">
                            <h4>Selected Dates</h4>
                            <div id="selectedDatesList"></div>
                            <!-- Hidden input to store selected dates -->
                            <input type="hidden" name="selected_dates" id="selectedDatesInput">
                        </div>
                    </div>
                    
                    <!-- Time Slots Section - Right Side -->
                    <div class="time-slots-container">
                        <div class="time-slots">
                            <!-- Morning Slots -->
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Morning Slot (7:00 AM - 9:00 AM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="time_slots[morning_slot_1]" min="1" max="10" value="3" class="limit-input">
                                    <input type="hidden" name="time_ranges[morning_slot_1]" value="07:00 AM - 09:00 AM">
                                </div>
                            </div>
                            
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Morning Slot (9:00 AM - 11:00 AM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="time_slots[morning_slot_2]" min="1" max="10" value="3" class="limit-input">
                                    <input type="hidden" name="time_ranges[morning_slot_2]" value="09:00 AM - 11:00 AM">
                                </div>
                            </div>
                            
                            <!-- Afternoon Slots -->
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Afternoon Slot (11:00 AM - 1:00 PM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="time_slots[afternoon_slot_1]" min="1" max="10" value="3" class="limit-input">
                                    <input type="hidden" name="time_ranges[afternoon_slot_1]" value="11:00 AM - 01:00 PM">
                                </div>
                            </div>
                            
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Afternoon Slot (1:00 PM - 3:00 PM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="time_slots[afternoon_slot_2]" min="1" max="10" value="3" class="limit-input">
                                    <input type="hidden" name="time_ranges[afternoon_slot_2]" value="01:00 PM - 03:00 PM">
                                </div>
                            </div>
                            
                            <!-- Evening Slot -->
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Evening Slot (3:00 PM - 5:00 PM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="time_slots[evening_slot]" min="1" max="10" value="3" class="limit-input">
                                    <input type="hidden" name="time_ranges[evening_slot]" value="03:00 PM - 05:00 PM">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="save-btn">
                                <i class='bx bx-save'></i>
                                Save Time Slots
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- End Time Slot Management Section -->

        <!-- Start new form for the table data section -->
        <form id="table-form" method="POST" action="process_dashboard.php">
            <div class="table-data">
                <div class="recent-appointments">
                    <div class="head">
                        <h3>Recent Appointments</h3>
                        <input type="text" name="search" placeholder="Search appointments...">
                        <button type="submit" name="filter_appointments"><i class="bx bx-filter"></i></button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Date Order</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="todo">
                    <div class="head">
                        <h3>Recent Appointments</h3>
                        <input type="text" name="todo_search" placeholder="Search todos...">
                        <button type="submit" name="filter_todos"><i class="bx bx-filter"></i></button>
                    </div>
                    <ul class="todo-list">
                        <li>
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="not-completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="not-completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </main>
</section>
<!--DASHBOARD CONTENT -->

<!-- Job Orders Section -->
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
                                    <tr>
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

<!-- Feedback Modal - Updated with proper form fields -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeFeedbackModal()">&times;</span>
        <h2>Job Feedback / Follow-up Report</h2>
        <form id="feedbackForm" method="POST">
            <input type="hidden" name="appointment_id" id="feedback_appointment_id">
            
            <div class="form-section">
                <h3>Report Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Report Type:</label>
                        <select name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="follow-up">Follow-up Required</option>
                            <option value="complaint">Customer Complaint</option>
                            <option value="feedback">General Feedback</option>
                            <option value="issue">Service Issue</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority:</label>
                        <select name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Report:</label>
                        <input type="date" name="report_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Follow-up Date (if needed):</label>
                        <input type="date" name="followup_date">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Issue Details</h3>
                <div class="form-group">
                    <label>Description of Issue/Feedback:</label>
                    <textarea name="description" rows="4" required placeholder="Provide a detailed description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Customer Comments:</label>
                    <textarea name="customer_comments" rows="3" placeholder="Enter any comments from the customer..."></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Action Plan</h3>
                <div class="form-group">
                    <label>Recommended Actions:</label>
                    <textarea name="recommended_actions" rows="3" required placeholder="What needs to be done to address this issue?"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Assign To:</label>
                        <select name="assigned_to">
                            <option value="">Select Technician/Staff</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="submit-btn">Submit Report</button>
                <button type="button" class="cancel-btn" onclick="closeFeedbackModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Technician Report Section -->
<section id="reports" class="section">
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Manage Technicians Reports</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Reports</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Technician Reports</a></li>
                </ul>
            </div>
        </div>

        <!-- Search and Filter Controls -->
        <div class="report-controls">
            <div class="search-box">
                <input type="text" id="reportSearchInput" placeholder="Search by technician, location or client...">
                <i class='bx bx-search'></i>
            </div>
            <div class="filter-options">
                <select id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="dateFilter">
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
        </div>

        <div class="reports-grid">
            <?php if (!empty($serviceReports)): ?>
                <?php foreach ($serviceReports as $report): ?>
                    <?php
                    // Determine status class
                    $statusClass = '';
                    switch (strtolower($report['status'])) {
                        case 'pending':
                            $statusClass = 'pending';
                            $statusText = 'Pending Review';
                            break;
                        case 'approved':
                            $statusClass = 'approved';
                            $statusText = 'Approved';
                            break;
                        case 'rejected':
                            $statusClass = 'rejected';
                            $statusText = 'Rejected';
                            break;
                        default:
                            $statusClass = 'pending';
                            $statusText = 'Pending Review';
                    }
                    
                    // Format date
                    $formattedDate = date('F d, Y', strtotime($report['date_of_treatment']));
                    
                    // Handle photos (convert from JSON if needed)
                    $photos = [];
                    if (!empty($report['photos'])) {
                        if (is_string($report['photos'])) {
                            try {
                                $photos = json_decode($report['photos'], true);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    $photos = [$report['photos']]; // Not JSON, treat as single photo
                                }
                            } catch (Exception $e) {
                                $photos = [$report['photos']]; // Error decoding, treat as single photo
                            }
                        } else if (is_array($report['photos'])) {
                            $photos = $report['photos'];
                        }
                    }
                    ?>
                    <div class="report-card" data-report-id="<?php echo $report['report_id']; ?>" 
                         data-status="<?php echo strtolower($report['status']); ?>"
                         data-date="<?php echo $report['date_of_treatment']; ?>"
                         data-tech-name="<?php echo htmlspecialchars($report['tech_name']); ?>"
                         data-location="<?php echo htmlspecialchars($report['location']); ?>"
                         data-account="<?php echo htmlspecialchars($report['account_name']); ?>"
                         data-treatment="<?php echo htmlspecialchars($report['treatment_type']); ?>">
                        <div class="report-header">
                            <div class="report-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                            <div class="report-date"><?php echo $formattedDate; ?></div>
                        </div>
                        <div class="report-body">
                            <div class="technician-info">
                                <img src="../Pictures/boy.png" alt="Technician">
                                <div>
                                    <h3><?php echo htmlspecialchars($report['tech_name']); ?></h3>
                                    <span>Technician</span>
                                </div>
                            </div>
                            <div class="report-preview">
                                <p><i class='bx bx-map'></i> <?php echo htmlspecialchars($report['location']); ?></p>
                                <p><i class='bx bx-user'></i> Client: <?php echo htmlspecialchars($report['account_name']); ?></p>
                                <p><i class='bx bx-spray-can'></i> Service: <?php echo htmlspecialchars($report['treatment_type']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reports">
                    <i class='bx bx-file-blank'></i>
                    <p>No service reports found</p>
                    <span>When technicians submit reports, they will appear here</span>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Report Details Modal -->
    <div id="reportModal" class="modal">
        <div class="report-modal-content">
            <span class="close-modal" onclick="closeReportModal()">&times;</span>
            <form id="reportForm" class="report-form">
                <h2>Service Report Details</h2>
                <input type="hidden" id="reportIdField" value="">
                
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Report ID</label>
                            <input type="text" id="reportIdDisplay" readonly>
                        </div>
                        <div class="form-group">
                            <label>Date of Treatment</label>
                            <input type="text" id="reportDateField" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Technician Name</label>
                            <input type="text" id="techNameField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Client Name</label>
                            <input type="text" id="clientNameField" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact No.</label>
                            <input type="text" id="contactNoField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" id="locationField" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Service Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Treatment Type</label>
                            <input type="text" id="treatmentTypeField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Treatment Method</label>
                            <input type="text" id="treatmentMethodField" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Time In</label>
                            <input type="text" id="timeInField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Time Out</label>
                            <input type="text" id="timeOutField" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Treatment Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pest Count</label>
                            <input type="text" id="pestCountField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Device Installation</label>
                            <input type="text" id="deviceInstallationField" readonly>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Chemicals Consumed</label>
                        <textarea id="chemicalsField" readonly></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Frequency of Visits</label>
                            <input type="text" id="frequencyField" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section" id="photosSection">
                    <h3>Documentation</h3>
                    <div class="image-gallery" id="imageGallery">
                        <!-- Images will be loaded dynamically via JavaScript -->
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-approve" id="approveBtn" onclick="updateReportStatus('approved')">
                        <i class='bx bx-check'></i> Approve Report
                    </button>
                    <button type="button" class="btn-reject" id="rejectBtn" onclick="updateReportStatus('rejected')">
                        <i class='bx bx-x'></i> Reject Report
                    </button>
                    <button type="button" class="btn-download" onclick="downloadReportPDF()">
                        <i class='bx bx-download'></i> Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add JavaScript functions for report modal and PDF download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
    // Make the reports data globally available through the window object
    window.reportsData = <?php echo json_encode($serviceReports ?? []); ?>;

    // Debugging: Log the complete service reports data to check its structure
    console.log('Complete service reports data:', window.reportsData);
    
    // Filter reports based on search input and filter selections
    document.getElementById('reportSearchInput').addEventListener('input', filterReports);
    document.getElementById('statusFilter').addEventListener('change', filterReports);
    document.getElementById('dateFilter').addEventListener('change', filterReports);
    
    // Define the filterReports function
    function filterReports() {
        const searchValue = document.getElementById('reportSearchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
        const dateFilter = document.getElementById('dateFilter').value;
        
        // ...existing filtering code...
    }
    
    // Initialize report cards with event listeners
    function initializeReportCards() {
        console.log('Initializing report cards from inline script');
        const reportCards = document.querySelectorAll('.report-card');
        console.log(`Found ${reportCards.length} report cards in inline script`);
        
        reportCards.forEach(card => {
            const reportId = card.getAttribute('data-report-id');
            console.log(`Setting up click handler for report #${reportId}`);
            
            // Remove existing click handlers
            const newCard = card.cloneNode(true);
            card.parentNode.replaceChild(newCard, card);
            
            // Add direct event listener
            newCard.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Use the external JS function directly
                openReportModal(reportId);
            });
        });
    }
    
    // Call the initialization function when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Don't initialize here, let the external JS handle it
        // This prevents duplicate initialization
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('reportModal');
            if (event.target === modal) {
                closeReportModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeReportModal();
            }
        });
    });
    </script>
</section>

<!-- Employee Section -->
<section id="employees" class="section">
    <main>
        <form id="employees-form" method="POST" action="../employee/forms/addform.php">
            <div class="head-title">
                <div class="left">
                    <h1>Employee Management</h1>
                </div>
                <a href="../employee/forms/addform.php" class="btn-add-employees">
                    <i class='bx bx-plus'></i>
                    <span class="text">Add New Employee</span>
                </a>
            </div>
            <div class="table-data">

                <div class="employee-list">
                    <div class="order-stats">
                        <div class="stat-card">
                            <i class='bx bxs-group'></i>
                            <div class="stat-details">
                                <h3>Total Employees</h3>
                                <p><?php echo $data['technicians'] + $data['supervisors']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class='bx bxs-user-check'></i>
                            <div class="stat-details">
                                <h3>Active</h3>
                                <p><?php echo $data['active_technicians'] + $data['active_supervisors']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class='bx bxs-user-x'></i>
                            <div class="stat-details">
                                <h3>Inactive</h3>
                                <p><?php echo $data['inactive_technicians'] + $data['inactive_supervisors']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="table-header">
                        <div class="search-bar">
                            <i class='bx bx-search'></i>
                            <input type="text" id="searchInput" placeholder="Search employee...">
                        </div>
                        <div class="filter-options">
                            <select id="sortBy">
                                <option value="">Sort by</option>
                                <option value="asc">A - Z</option>
                                <option value="desc">Z - A</option>
                            </select>
                            <select id="filterRole">
                                <option value="">Roles</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="technician">Technician</option>
                            </select>
                            <select id="filterStatus">
                                <option value="">Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="employeeTable">
                            <thead>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <th>Employee No.</th>
                                    <th>Name</th>
                                    <th>Date of Birth</th>
                                    <th>Email</th>
                                    <th>Mobile Number</th>
                                    <th>SSS No.</th>
                                    <th>Pag-ibig No.</th>
                                    <th>Phil Health No.</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($employees)): ?>
                                    <?php foreach ($employees as $res): ?>
                                        <?php if ($res['role'] === 'technician' || $res['role'] === 'supervisor'): ?>
                                        <tr data-role="<?php echo htmlspecialchars($res['role']); ?>" data-status="<?php echo htmlspecialchars($res['status']); ?>">
                                            <td><input type="checkbox"></td>
                                            <td><?php echo htmlspecialchars($res['employee_no'] ?? 'EMP-'.str_pad(($res['id'] ?? 0), 4, '0', STR_PAD_LEFT)); ?></td>
                                            <td><?php echo htmlspecialchars(($res['firstname'] ?? '') . ' ' . ($res['lastname'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($res['dob'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['mobile_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['sss_no'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['pagibig_no'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['philhealth_no'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['role'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['status'] ?? 'unverified'); ?></td>
                                            <td class="action-buttons">
                                                <a href="../employee/forms/editform.php?id=<?php echo urlencode($res['id'] ?? ''); ?>">Edit</a>
                                                <a href="../employee/functions/delete.php?id=<?php echo urlencode($res['id'] ?? ''); ?>" 
                                                   onclick="return confirmDelete(event);">Delete</a>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12">No Technicians or Supervisors available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script>
                        $(document).ready(function () {
                            function filterTable() {
                                let searchValue = $("#searchInput").val().toLowerCase().trim();
                                let selectedRole = $("#filterRole").val();
                                let selectedStatus = $("#filterStatus").val();

                                $("#employeeTable tbody tr").each(function () {
                                    let empNo = $(this).find("td:nth-child(2)").text().toLowerCase();
                                    let name = $(this).find("td:nth-child(3)").text().toLowerCase();
                                    let email = $(this).find("td:nth-child(5)").text().toLowerCase();
                                    let mobile = $(this).find("td:nth-child(6)").text().toLowerCase();
                                    let sssNo = $(this).find("td:nth-child(7)").text().toLowerCase();
                                    let pagibigNo = $(this).find("td:nth-child(8)").text().toLowerCase();
                                    let philhealthNo = $(this).find("td:nth-child(9)").text().toLowerCase();
                                    let role = $(this).attr("data-role");
                                    let status = $(this).attr("data-status");

                                    let searchMatch = searchValue === "" || 
                                        name.includes(searchValue) || 
                                        email.includes(searchValue) || 
                                        mobile.includes(searchValue) || 
                                        empNo.includes(searchValue) ||
                                        sssNo.includes(searchValue) ||
                                        pagibigNo.includes(searchValue) ||
                                        philhealthNo.includes(searchValue);
                                    
                                    let roleMatch = selectedRole === "" || role === selectedRole;
                                    let statusMatch = selectedStatus === "" || status === selectedStatus;

                                    $(this).toggle(searchMatch && roleMatch && statusMatch);
                                });
                            }

                            function sortTable() {
                                let rows = $("#employeeTable tbody tr").get();
                                let sortOrder = $("#sortBy").val();

                                if (!sortOrder) return; // No sorting if default option is selected

                                rows.sort(function (a, b) {
                                    let nameA = $(a).find("td:nth-child(3)").text().trim().toLowerCase();
                                    let nameB = $(b).find("td:nth-child(3)").text().trim().toLowerCase();

                                    return sortOrder === "asc" ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
                                });

                                $.each(rows, function (index, row) {
                                    $("#employeeTable tbody").append(row);
                                });
                            }

                            $("#searchInput, #filterRole, #filterStatus").on("input change", filterTable);
                            $("#sortBy").on("change", function () {
                                sortTable();
                                filterTable();
                            });
                        });

                        async function confirmDelete(event) {
                            event.preventDefault();
                            const confirmed = await confirm('Are you sure you want to delete this employee?');
                            if (confirmed) {
                                window.location.href = event.target.href;
                            }
                            return false;
                        }
                    </script>
                </div>
            </div>
        </form>
    </main>
</section>

<!-- Services Section -->
<section id="services" class="section">
    <main>
        <form id="services-form" method="POST" action="process_services.php">
            <div class="head-title">
                <div class="left">
                    <h1>Services Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Services</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">Available Services</a></li>
                    </ul>
                </div>
                <a href="add-service.php" class="btn-add">
                    <i class='bx bx-plus'></i>
                    <span class="text">Add New Service</span>
                </a>
            </div>

            <div class="table-data">
                <div class="services-list">
                    <div class="head">
                        <h3>Available Services</h3>
                        <div class="actions">
                            <i class="bx bx-search"></i>
                            <i class="bx bx-filter"></i>
                        </div>
                    </div>
                    <div class="service-cards">
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <div class="service-card">
                                    <input type="hidden" name="service_id[]" value="<?php echo $service['service_id']; ?>">
                                    <div class="service-image">
                                        <?php 
                                        // Define the image path
                                        $imagePath = $service['image_path'];
                                        
                                        // For web display, use relative URL path
                                        $displayImagePath = "../Pictures/" . $imagePath;
                                        
                                        // For file_exists check, use server file system path
                                        $serverImagePath = $_SERVER['DOCUMENT_ROOT'] . "/PESTCOZAM/Pictures/" . $imagePath;
                                        
                                        // Check if image exists and path is not empty
                                        if (!empty($imagePath) && file_exists($serverImagePath)) {
                                            echo '<img src="' . htmlspecialchars($displayImagePath) . '" alt="' . htmlspecialchars($service['service_name']) . '">';
                                        } else {
                                            // If image doesn't exist or path is empty, show placeholder
                                            echo '<img src="../Pictures/service-placeholder.png" alt="' . htmlspecialchars($service['service_name']) . '" class="placeholder-img">';
                                        }
                                        ?>
                                    </div>
                                    <div class="service-details">
                                        <input type="text" name="service_name[]" value="<?php echo htmlspecialchars($service['service_name']); ?>" hidden>
                                        <h4><?php echo htmlspecialchars($service['service_name']); ?></h4>
                                        <textarea name="description[]" hidden><?php echo htmlspecialchars($service['description']); ?></textarea>
                                        <p class="description"><?php echo htmlspecialchars($service['description']); ?></p>
                                        <div class="estimated-time">
                                            <i class='bx bx-time-five'></i>
                                            <span>Estimated time: <?php echo htmlspecialchars($service['estimated_time']); ?></span>
                                        </div>
                                        <div class="price-tag">
                                            <i class='bx bx-money'></i>
                                            <span>Starting at: ₱<?php echo number_format($service['starting_price'], 2); ?></span>
                                        </div>
                                        <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                        <div class="service-actions">
                                            <button type="button" onclick="editService(<?php echo $service['service_id']; ?>)" class="btn-edit">
                                                <i class='bx bx-edit'></i> Edit
                                            </button>
                                            <button type="button" class="delete-btn" onclick="deleteService(<?php echo $service['service_id']; ?>, '<?php echo addslashes($service['service_name']); ?>')">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-services-message">No services available. Please add services.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>

        <script>
        function editService(serviceId) {
            window.location.href = `edit-service.php?id=${serviceId}`;
        }
        
        function deleteService(serviceId, serviceName) {
            if (confirm(`Are you sure you want to delete "${serviceName}"? This action cannot be undone.`)) {
                // Use fetch with proper JSON handling
                fetch(`delete-service.php?id=${serviceId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`${serviceName} has been deleted successfully.`);
                            // Remove the service card from the DOM
                            const serviceCard = document.querySelector(`input[value="${serviceId}"]`).closest('.service-card');
                            serviceCard.remove();
                            
                            // If no more services, show the "No services available" message
                            if (document.querySelectorAll('.service-card').length === 0) {
                                const serviceCards = document.querySelector('.service-cards');
                                serviceCards.innerHTML = '<p class="no-services-message">No services available. Please add services.</p>';
                            }
                        } else {
                            alert('Error deleting service: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the service. Please try again.');
                    });
            }
        }
        </script>
    </main>
</section>
<!-- SERVICES SECTION -->

<!-- Customers Section -->
<section id="customers" class="section">
    <main>
        <?php
        // Fetch customers data from the database (users with 'user' role only)
        try {
            // Set default pagination values
            $customersPerPage = 10;
            $page = isset($_GET['customer_page']) ? (int)$_GET['customer_page'] : 1;
            $offset = ($page - 1) * $customersPerPage;
            
            // Handle search functionality
            $searchTerm = isset($_GET['customer_search']) ? $_GET['customer_search'] : '';
            
            // Basic query - filter only for users with role='user'
            $whereClause = "WHERE role = 'user'";
            $params = [];
            
            if (!empty($searchTerm)) {
                $whereClause .= " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR mobile_number LIKE ?)";
                $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
            }
            
            // Count total customers for pagination
            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM users $whereClause");
            if (!empty($params)) {
                $countStmt->execute($params);
            } else {
                $countStmt->execute();
            }
            $totalCustomers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalCustomers / $customersPerPage);
            
            // Get customer data with appointment count - removed address field
            $customersQuery = "SELECT 
                id, 
                firstname, 
                middlename, 
                lastname, 
                email, 
                mobile_number, 
                (SELECT COUNT(*) FROM appointments WHERE user_id = users.id) as appointment_count
                FROM users 
                $whereClause 
                ORDER BY lastname, firstname";
                
            if ($page > 0) {
                $customersQuery .= " LIMIT $offset, $customersPerPage";
            }
            
            $customersStmt = $db->prepare($customersQuery);
            if (!empty($params)) {
                $customersStmt->execute($params);
            } else {
                $customersStmt->execute();
            }
            $customers = $customersStmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log("Error fetching customers: " . $e->getMessage());
            echo "<div class='error-message'>Database error: " . $e->getMessage() . "</div>";
            $customers = [];
            $totalPages = 0;
        }
        ?>
        <form id="customers-form" method="GET" action="#customers">
            <div class="head-title">
                <div class="left">
                    <h1>Customer Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">Customer List</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="customer-container">
                <div class="customer-header">
                    <h2>Customer Directory <span class="customer-count">(<?php echo $totalCustomers; ?> total)</span></h2>
                    <div class="search-container">
                        <input type="text" name="customer_search" placeholder="Search customers..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="customer-table">
                        <thead>
                            <tr>
                                <th>First name</th>
                                <th>Last name</th>
                                <th>Email</th>
                                <th>Mobile number</th>
                                <th>No. of Appointments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($customers)): ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['firstname']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['mobile_number']); ?></td>
                                        <td>
                                            <span class="appointment-count">
                                                <?php echo $customer['appointment_count']; ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a href="view-customer.php?id=<?php echo $customer['id']; ?>" class="view-btn" title="View Details">
                                                <i class='bx bx-show'></i>
                                            </a>
                                            <a href="customer-appointments.php?id=<?php echo $customer['id']; ?>" class="history-btn" title="View Appointment History">
                                                <i class='bx bx-history'></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-data">
                                        <?php if (!empty($searchTerm)): ?>
                                            No customers found matching "<?php echo htmlspecialchars($searchTerm); ?>". 
                                            <a href="?">Clear search</a>
                                        <?php else: ?>
                                            No customers registered in the system.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?customer_page=1&customer_search=<?php echo urlencode($searchTerm); ?>" class="<?php echo $page == 1 ? 'disabled' : ''; ?>">&laquo;</a>
                    
                    <?php
                    // Determine page range to show
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<a href="?customer_page=1&customer_search='.urlencode($searchTerm).'">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="ellipsis">...</span>';
                        }
                    }
                    
                    // Show page links
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<a href="?customer_page='.$i.'&customer_search='.urlencode($searchTerm).'" 
                              class="'.($page == $i ? 'active' : '').'">'.$i.'</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="ellipsis">...</span>';
                        }
                        echo '<a href="?customer_page='.$totalPages.'&customer_search='.urlencode($searchTerm).'">'.$totalPages.'</a>';
                    }
                    ?>
                    
                    <a href="?customer_page=<?php echo $page < $totalPages ? $page + 1 : $totalPages; ?>&customer_search=<?php echo urlencode($searchTerm); ?>" 
                       class="<?php echo $page == $totalPages ? 'disabled' : ''; ?>">&raquo;</a>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </main>
</section>

<!-- Manage Billing Section -->
<section id="billing" class="section">
    <main>
        <form id="billing-form" method="POST" action="process_billing.php">
            <div class="head-title">
                <div class="left">
                    <h1>Billing Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Billing</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">Overview</a></li>
                    </ul>
                </div>
            </div>
            <div class="main-wrapper">
                <section class="billing-section">
                    <h2>Billing History</h2>
                    <div class="filters">
                        <button type="submit" name="filter" value="all" class="filter-btn active">All</button>
                        <button type="submit" name="filter" value="paid" class="filter-btn">Paid</button>
                        <button type="submit" name="filter" value="unpaid" class="filter-btn">Unpaid</button>
                        <select name="date_range" class="date-range">
                            <option value="6">Last 6 Months</option>
                            <option value="3">Last 3 Months</option>
                            <option value="12">Last Year</option>
                        </select>
                    </div>
                    <table class="billing-table">
                        <thead>
                            <tr>
                                <th>Issue Date</th>
                                <th>Billing #</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>August 1, 2024</td>
                                <td>4528642854</td>
                                <td>Cash</td>
                                <td>Unpaid</td>
                            </tr>
                            <tr>
                                <td>August 2, 2024</td>
                                <td>4528642855</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 3, 2024</td>
                                <td>4528642856</td>
                                <td>Cash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 4, 2024</td>
                                <td>4528642857</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 5, 2024</td>
                                <td>4528642858</td>
                                <td>Cash</td>
                                <td>Unpaid</td>
                            </tr>
                            <tr>
                                <td>August 6, 2024</td>
                                <td>4528642859</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 7, 2024</td>
                                <td>4528642860</td>
                                <td>Cash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 8, 2024</td>
                                <td>4528642861</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 9, 2024</td>
                                <td>4528642862</td>
                                <td>Cash</td>
                                <td>Unpaid</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <form class="pagination-container">
                        <div class="pagination">
                            <button type="submit" name="page" value="prev" class="page-btn">&laquo;</button>
                            <button type="submit" name="page" value="1" class="page-btn active">1</button>
                            <button type="submit" name="page" value="2" class="page-btn">2</button>
                            <button type="submit" name="page" value="3" class="page-btn">3</button>
                            <button type="submit" name="page" value="next" class="page-btn">&raquo;</button>
                        </div>
                    </form>
                </section>
                <aside class="payment-section">
                    <div class="payment-info">
                        <h3>Payment Info</h3>
                        <p class="payment-note">
                            Cash or GCash does not show your Credit Card Info.
                        </p>
                    </div>
                    
                    <div class="billing-details">
                        <div class="header-row">
                            <h3>Billing Details</h3>
                            <a href="#" class="edit-link">Edit</a>
                        </div>
                        <p><strong>Service Summary:</strong> Pestcontrol, Thermal Control</p>
                        <p><strong>Billing Name:</strong> John Doe</p>
                        <p><strong>Billing Address:</strong> Lorem Address</p>
                    </div>
                    
                    <div class="billing-notification">
                        <h4>Billing Notification Sent To</h4>
                        <p>Email Address: <strong>name@pestco2am.com</strong></p>
                        <p>Phone Number: <strong>+63 123 456 789</strong></p>
                    </div>
                </aside>
            </div>
        </form>
    </main>
</section>

<!-- Profile Section -->
<section id="profile" class="section">
    <main>
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
        
        <!-- Updated profile container to match admin style -->
        <div class="profile-container">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <img src="../Pictures/boy.png" alt="User Avatar" class="avatar" />
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?></h3>
                    <p><?php echo htmlspecialchars($admin['email']); ?></p>
                    <p><?php echo ucfirst(htmlspecialchars($admin['role'])); ?></p>
                </div>
                <button type="button" class="edit-btn" id="openProfileModalBtn">
                    <i class='bx bx-edit'></i> Edit Profile
                </button>
            </div>

            <!-- Personal Information -->
            <div class="info-section">
                <div class="section-header">
                    <h3>Personal Information</h3>
                </div>
                <div class="info-content">
                    <div class="info-row">
                        <p><strong>First Name:</strong> <span data-field="firstname"><?php echo htmlspecialchars($admin['firstname']); ?></span></p>
                        <p><strong>Middle Name:</strong> <span data-field="middlename"><?php echo htmlspecialchars($admin['middlename'] ?: 'Not set'); ?></span></p>
                    </div>
                    <div class="info-row">
                        <p><strong>Last Name:</strong> <span data-field="lastname"><?php echo htmlspecialchars($admin['lastname']); ?></span></p>
                        <p><strong>Date of Birth:</strong> <?php echo $admin['dob'] ? date('m-d-Y', strtotime($admin['dob'])) : 'Not set'; ?></p>
                    </div>
                    <div class="info-row">
                        <p><strong>Email:</strong> <span data-field="email"><?php echo htmlspecialchars($admin['email']); ?></span></p>
                        <p><strong>Phone Number:</strong> <span data-field="mobile_number"><?php echo htmlspecialchars($admin['mobile_number']); ?></span></p>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="info-section">
                <div class="section-header">
                    <h3>Account Information</h3>
                </div>
                <div class="info-content">
                    <div class="info-row">
                        <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($admin['role'])); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($admin['status'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Edit Modal -->
        <div id="profileModal" class="modal">
            <div class="modal-content profile-modal-content">
                <div class="modal-header">
                    <h2>Edit Profile</h2>
                    <span class="close" title="Close">&times;</span>
                </div>
                <form id="editProfileForm" method="POST" action="update_profile.php" novalidate>
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($admin['firstname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="middlename">Middle Name</label>
                        <input type="text" id="middlename" name="middlename" value="<?php echo htmlspecialchars($admin['middlename']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($admin['lastname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number</label>
                        <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($admin['mobile_number']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo $admin['dob']; ?>">
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="cancel-btn" id="closeProfileModalBtn">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add inline script for profile modal functionality to match dashboard-aos.php -->
        <script>
            // Direct modal event handlers
            document.addEventListener('DOMContentLoaded', function() {
                const profileModal = document.getElementById('profileModal');
                const openBtn = document.getElementById('openProfileModalBtn');
                const closeBtn = document.querySelector('#profileModal .close');
                const cancelBtn = document.getElementById('closeProfileModalBtn');
                const profileForm = document.getElementById('editProfileForm');
                
                function openProfileModal() {
                    // First set display to flex
                    profileModal.style.display = 'flex';
                    
                    // Force a reflow/repaint before adding the show class
                    void profileModal.offsetWidth;
                    
                    // Add show class to trigger the transition
                    profileModal.classList.add('show');
                    
                    document.body.style.overflow = 'hidden';
                }
                
                function closeProfileModal() {
                    // First remove the show class to trigger the transition
                    profileModal.classList.remove('show');
                    
                    // Wait for the transition to complete before hiding
                    setTimeout(() => {
                        profileModal.style.display = "none";
                        document.body.style.overflow = '';
                    }, 300); // Match the transition duration (0.3s)
                }
                
                if (openBtn && profileModal) {
                    // Open modal when clicking the edit button
                    openBtn.addEventListener('click', function() {
                        openProfileModal();
                    });
                    
                    // Close modal when clicking the X button
                    if (closeBtn) {
                        closeBtn.addEventListener('click', closeProfileModal);
                    }
                    
                    // Close modal when clicking the Cancel button
                    if (cancelBtn) {
                        cancelBtn.addEventListener('click', closeProfileModal);
                    }
                    
                    // Close modal when clicking outside the modal content
                    window.addEventListener('click', function(event) {
                        if (event.target === profileModal) {
                            closeProfileModal();
                        }
                    });
                    
                    // Close modal with Escape key
                    document.addEventListener('keydown', function(event) {
                        if (event.key === 'Escape' && profileModal.classList.contains('show')) {
                            closeProfileModal();
                        }
                    });
                    
                    // Handle form submission via AJAX
                    if (profileForm) {
                        profileForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            updateUserProfile(this);
                        });
                    }
                }
                
                // Function to handle profile form submission
                function updateUserProfile(form) {
                    const submitBtn = form.querySelector('[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Saving...';
                    submitBtn.disabled = true;

                    const formData = new FormData(form);

                    fetch('update_profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(text => {
                        console.log('Raw server response:', text);
                        let data = null;
                        try {
                            // Try to parse as JSON only if it looks like JSON
                            if (text.trim().startsWith('{') || text.trim().startsWith('[')) {
                                data = JSON.parse(text);
                            } else if (text.toLowerCase().includes('success')) {
                                data = { success: true, message: 'Profile updated successfully' };
                            } else {
                                throw new Error('Response is not valid JSON');
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e, 'Response text:', text);
                            alert('Error updating profile. Invalid response from server.');
                            submitBtn.textContent = originalText;
                            submitBtn.disabled = false;
                            return;
                        }
                        if (data && data.success) {
                            alert('Profile updated successfully!');
                            updateProfileDisplay(formData);
                            closeProfileModal();
                            history.replaceState(null, null, location.pathname);
                        } else {
                            alert('Error: ' + (data && data.message ? data.message : 'Update failed'));
                        }
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error updating profile:', error);
                        alert('An error occurred while updating profile: ' + error.message);
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
                }
                
                // Function to update the profile display after successful update
                function updateProfileDisplay(formData) {
                    const firstname = formData.get('firstname');
                    const lastname = formData.get('lastname');
                    const email = formData.get('email');
                    
                    // Update Profile Card (using .profile-info)
                    const profileName = document.querySelector('.profile-info h3');
                    const profileEmail = document.querySelector('.profile-info p');
                    if (profileName) profileName.textContent = `${firstname} ${lastname}`;
                    if (profileEmail) profileEmail.textContent = email;
                    
                    // Update details in personal information section
                    const fnameDetail = document.querySelector('[data-field="firstname"]');
                    const middlenameDetail = document.querySelector('[data-field="middlename"]');
                    const lnameDetail = document.querySelector('[data-field="lastname"]');
                    const emailDetail = document.querySelector('[data-field="email"]');
                    const phoneDetail = document.querySelector('[data-field="mobile_number"]');
                    if (fnameDetail) fnameDetail.textContent = firstname;
                    if (middlenameDetail) middlenameDetail.textContent = formData.get('middlename') || 'Not set';
                    if (lnameDetail) lnameDetail.textContent = lastname;
                    if (emailDetail) emailDetail.textContent = email;
                    if (phoneDetail) phoneDetail.textContent = formData.get('mobile_number');
                }
            });
        </script>
    </main>
</section>

<!-- Settings Section -->
<section id="settings" class="section">
    <main>
        <form id="settings-form" method="POST" action="process_settings.php">
            <div class="head-title">
                <div class="left">
                    <h1>Settings Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Settings</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">System Settings</a></li>
                    </ul>
                </div>
            </div>
            <div class="table-data">
                <div class="settings-list">
                    <!-- Settings specific content will be added here -->
                </div>
            </div>
        </form>
    </main>
</section>

<!-- Logout Confirmation Modal - Fixed structure -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <h2>Confirm Logout</h2>
        <p>Are you sure you want to logout?</p>
        <div class="modal-buttons">
            <button type="button" id="cancelLogout" class="cancel-btn">Cancel</button>
            <button type="button" id="confirmLogout" class="logout-btn">Logout</button>
        </div>
    </div>
</div>

<script src="../JS CODES/dashboard-admin.js"></script>
<script src="../JS CODES/work-orders.js"></script>
</body>
</html>