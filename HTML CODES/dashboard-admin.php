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
        SUM(role = 'technician' AND status = 'verified') AS verified_technicians,
        SUM(role = 'supervisor' AND status = 'verified') AS verified_supervisors,
        SUM(role = 'technician' AND status = 'unverified') AS unverified_technicians,
        SUM(role = 'supervisor' AND status = 'unverified') AS unverified_supervisors
    FROM users
    WHERE role IN ('technician', 'supervisor')");

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure $data is not null
    if (!$data) {
        $data = [
            'total' => 0, 
            'technicians' => 0, 
            'supervisors' => 0, 
            'verified_technicians' => 0, 
            'verified_supervisors' => 0, 
            'unverified_technicians' => 0, 
            'unverified_supervisors' => 0
        ];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $data = [
        'total' => 0, 
        'technicians' => 0, 
        'supervisors' => 0, 
        'verified_technicians' => 0, 
        'verified_supervisors' => 0, 
        'unverified_technicians' => 0, 
        'unverified_supervisors' => 0
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

<!--DASHBOARD CONTENT -->
<section id="content" class="section active">
    <main>
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

            <!-- Time Slot Management Section -->
            <div class="time-slot-management">
                <h2>Time Slot Management</h2>
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
                    </div>
                    
                    <!-- Time Slots Section - Right Side -->
                    <div class="time-slots-container">
                        <div class="time-slots">
                            <!-- Morning Slots -->
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Morning Slot (7:00 AM - 9:00 AM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="morning_slot_1_limit" min="1" max="10" value="3" class="limit-input">
                                </div>
                            </div>
                            
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Morning Slot (9:00 AM - 11:00 AM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="morning_slot_2_limit" min="1" max="10" value="3" class="limit-input">
                                </div>
                            </div>
                            
                            <!-- Afternoon Slots -->
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Afternoon Slot (11:00 AM - 1:00 PM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="afternoon_slot_1_limit" min="1" max="10" value="3" class="limit-input">
                                </div>
                            </div>
                            
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Afternoon Slot (1:00 PM - 3:00 PM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="afternoon_slot_2_limit" min="1" max="10" value="3" class="limit-input">
                                </div>
                            </div>
                            
                            <!-- Evening Slot -->
                            <div class="time-slot">
                                <label><i class='bx bx-time'></i>Afternoon Slot (3:00 PM - 5:00 PM)</label>
                                <div class="slot-limit">
                                    <label class="small-label">Slot Limit:</label>
                                    <input type="number" name="evening_slot_limit" min="1" max="10" value="3" class="limit-input">
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
            </div>
            <!-- End Time Slot Management Section -->

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
                                echo (strlen($technicians) > 20) ? 
                                    htmlspecialchars(substr($technicians, 0, 20) . '...') : 
                                    htmlspecialchars($technicians); 
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

<script>
// Updated feedback modal functions to fix the display issue
function showFeedbackModal(appointmentId) {
    document.getElementById('feedback_appointment_id').value = appointmentId;
    const modal = document.getElementById('feedbackModal');
    modal.classList.add('show'); // Changed from style.display = 'flex' to adding 'show' class
    document.body.style.overflow = 'hidden';
    
    // Reset form fields
    document.getElementById('feedbackForm').reset();
    // Set the current date as default
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="report_date"]').value = today;
    
    // Set a default follow-up date (7 days from now)
    const followupDate = new Date();
    followupDate.setDate(followupDate.getDate() + 7);
    document.querySelector('input[name="followup_date"]').value = followupDate.toISOString().split('T')[0];
    
    console.log('Modal should be displayed now with class "show"');
}

function closeFeedbackModal() {
    const modal = document.getElementById('feedbackModal');
    modal.classList.remove('show'); // Changed from style.display = 'none' to removing 'show' class
    document.body.style.overflow = '';
}

// Handle form submission
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show submission in progress
    const submitBtn = document.querySelector('.submit-btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    // Collect form data
    const formData = new FormData(this);
    
    // Simulate form submission (replace with actual AJAX submission)
    setTimeout(() => {
        alert('Report submitted successfully!');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        closeFeedbackModal();
    }, 1000);
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('feedbackModal');
    if (event.target === modal) {
        closeFeedbackModal();
    }
});
</script>

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
                                <h3>Verified</h3>
                                <p><?php echo $data['verified_technicians'] + $data['verified_supervisors']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class='bx bxs-user-x'></i>
                            <div class="stat-details">
                                <h3>Unverified</h3>
                                <p><?php echo $data['unverified_technicians'] + $data['unverified_supervisors']; ?></p>
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
                                <option value="verified">Verified</option>
                                <option value="unverified">Unverified</option>
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
                                        <img src="../Pictures/<?php echo htmlspecialchars($service['image_path']); ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>">
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
                <div class="report-body"></div>
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
                        <h3>Daniel Patilla</h3>
                        <p>Admin</p>
                        <p>Tetuan, Zamboanga City</p>
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
                            <p>Daniel</p>
                            <p>Patilla</p>
                            <p>03-05-2003</p>
                        </div>
                        <div class="info-row">
                            <p><strong>Email:</strong></p>
                            <p><strong>Phone Number:</strong></p>
                            <p><strong>User Role:</strong></p>
                        </div>
                        <div class="info-row">
                            <p>Daniel.Patilla@gmail.com</p>
                            <p>0953-654-4541</p>
                            <p>Admin</p>
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
                    <!-- Settings specific content -->
                </div>
            </div>
        </form>
    </main>
</section>

<!-- MODAL FOR LOGOUT -->
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
    </div>
    <script src="../JS CODES/dashboard-admin.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const calendarDays = document.getElementById('calendar-days');
    
    // Initialize year select with dynamic range
    const currentYear = new Date().getFullYear();
    const yearRange = 20; // Number of years to show in the future
    
    for (let year = currentYear; year <= currentYear + yearRange; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    }
    
    // Set current month and year
    const currentDate = new Date();
    monthSelect.value = currentDate.getMonth();
    yearSelect.value = currentDate.getFullYear();
    
    function renderCalendar() {
        const month = parseInt(monthSelect.value);
        const year = parseInt(yearSelect.value);
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        
        calendarDays.innerHTML = '';
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay.getDay(); i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'day';
            calendarDays.appendChild(emptyDay);
        }
        
        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'day';
            dayElement.textContent = day;
            
            // Check if this is today's date
            const currentDate = new Date();
            if (currentDate.getDate() === day && 
                currentDate.getMonth() === month && 
                currentDate.getFullYear() === year) {
                dayElement.classList.add('today');
            }
            
            dayElement.addEventListener('click', function() {
                document.querySelectorAll('.day').forEach(d => d.classList.remove('selected'));
                this.classList.add('selected');
            });
            
            calendarDays.appendChild(dayElement);
        }
    }
    
    monthSelect.addEventListener('change', renderCalendar);
    yearSelect.addEventListener('change', renderCalendar);
    
    // Initial render
    renderCalendar();
});
    </script>
</body>
</html>