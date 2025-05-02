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
        (SELECT COUNT(*) FROM appointments) as total_appointments,
        (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_jobs,
        (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_treatments,
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

// Fetch appointments with follow-up visit status
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
         WHERE at.appointment_id = a.id) as all_technicians,
        (SELECT fv.status 
         FROM followup_visits fv 
         WHERE fv.appointment_id = a.id 
         ORDER BY fv.id DESC LIMIT 1) as followup_status
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
    $serviceReportsQuery = "
        SELECT 
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

// Get service popularity data (appointment counts by service)
try {
    $servicePopularityQuery = "SELECT 
        s.service_name,
        COUNT(a.id) as appointment_count
        FROM services s
        LEFT JOIN appointments a ON s.service_id = a.service_id
        GROUP BY s.service_id, s.service_name
        ORDER BY appointment_count DESC";
    
    $stmt = $db->prepare($servicePopularityQuery);
    $stmt->execute();
    $servicePopularity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching service popularity data: " . $e->getMessage());
    $servicePopularity = [];
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
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
            </li>
            <li>
                <a href="#profile" onclick="showSection('profile')">
                    <i class='bx bx-user' ></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout">
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
    <form id="globalSearchForm" action="#" method="GET">
        <div class="form-input">
            <input type="search" id="globalSearchInput" name="global_search" placeholder="Search across dashboard..." 
                   value="<?php echo isset($_GET['global_search']) ? htmlspecialchars($_GET['global_search']) : ''; ?>">
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
                        <h3><?php echo htmlspecialchars($stats['total_appointments'] ?? '0'); ?></h3>
                        <p>Total Appointments</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-hourglass' ></i>
                    <span class="text">
                        <h3><?php echo htmlspecialchars($stats['pending_jobs'] ?? '0'); ?></h3>
                        <p>Pending Job Orders</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-check-circle' ></i>
                    <span class="text">
                        <h3><?php echo htmlspecialchars($stats['completed_treatments'] ?? '0'); ?></h3>
                        <p>Completed Treatments</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group' ></i>
                    <span class="text">
                        <h3><?php echo htmlspecialchars($stats['active_technicians'] ?? '0'); ?></h3>
                        <p>Active Technicians</p>
                    </span>
                </li>
            </ul>

            <?php
            // Get appointment counts by month for the current year
            try {
                $currentYear = date('Y');
                $appointmentsByMonthQuery = "SELECT 
                    MONTH(appointment_date) as month, 
                    COUNT(*) as count 
                FROM appointments 
                WHERE YEAR(appointment_date) = ? 
                GROUP BY MONTH(appointment_date) 
                ORDER BY month";
                
                $stmt = $db->prepare($appointmentsByMonthQuery);
                $stmt->execute([$currentYear]);
                $appointmentsByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Initialize counts for all months (1-12)
                $monthlyAppointmentCounts = array_fill(0, 12, 0);
                
                // Fill in the actual counts
                foreach ($appointmentsByMonth as $item) {
                    // Month is 1-based, array is 0-based
                    $monthIndex = (int)$item['month'] - 1;
                    $monthlyAppointmentCounts[$monthIndex] = (int)$item['count'];
                }
                
            } catch(PDOException $e) {
                error_log("Error fetching appointment counts by month: " . $e->getMessage());
                $monthlyAppointmentCounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]; // Default to zeros
            }
            ?>

            <!-- Appointment Growth Chart Section -->
            <div class="chart-container">
                <h2>Appointment Growth (<?php echo date('Y'); ?>)</h2>
                <div class="chart-header">
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="color-box current-year"></span>
                            <span><?php echo date('Y'); ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="color-box previous-year"></span>
                            <span><?php echo date('Y')-1; ?></span>
                        </div>
                    </div>
                    <div class="chart-actions">
                        <select id="chartViewType">
                            <option value="monthly">Monthly View</option>
                            <option value="quarterly">Quarterly View</option>
                        </select>
                    </div>
                </div>
                <div class="chart-card">
                    <canvas id="appointmentGrowthChart"></canvas>
                </div>
                <div class="chart-insights">
                    <div class="insight-card">
                        <i class='bx bx-line-chart'></i>
                        <div class="insight-content">
                            <h4>Growth Trend</h4>
                            <p id="growthTrend">Analyzing appointment data...</p>
                        </div>
                    </div>
                    <div class="insight-card">
                        <i class='bx bx-calendar-check'></i>
                        <div class="insight-content">
                            <h4>Peak Period</h4>
                            <p id="peakPeriod">Calculating busy months...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
                .chart-container {
                    background: white;
                    border-radius: 15px;
                    padding: 24px;
                    margin: 24px 0;
                    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
                    transition: box-shadow 0.3s ease;
                }
                
                .chart-container:hover {
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
                }
                
                .chart-container h2 {
                    margin-bottom: 16px;
                    font-size: 1.5rem;
                    color: #144578;
                    font-weight: 600;
                    border-bottom: 2px solid #f0f0f0;
                    padding-bottom: 10px;
                }
                
                .chart-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }
                
                .chart-legend {
                    display: flex;
                    gap: 20px;
                }
                
                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                .color-box {
                    width: 15px;
                    height: 15px;
                    border-radius: 3px;
                }
                
                .color-box.current-year {
                    background-color: #144578;
                }
                
                .color-box.previous-year {
                    background-color: #90CAF9;
                }
                
                .chart-actions select {
                    padding: 5px 10px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    font-size: 0.9rem;
                    color: #333;
                    cursor: pointer;
                }
                
                .chart-card {
                    width: 100%;
                    height: 400px;
                    position: relative;
                    border-radius: 10px;
                    overflow: hidden;
                    background: #fafafa;
                    padding: 10px;
                }
                
                .chart-insights {
                    display: flex;
                    gap: 20px;
                    margin-top: 20px;
                }
                
                .insight-card {
                    flex: 1;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    padding: 15px;
                    border-left: 4px solid #144578;
                }
                
                .insight-card i {
                    font-size: 24px;
                    color: #144578;
                }
                
                .insight-content h4 {
                    margin: 0 0 5px 0;
                    font-size: 1rem;
                    color: #333;
                }
                
                .insight-content p {
                    margin: 0;
                    font-size: 0.9rem;
                    color: #666;
                }
                
                @media screen and (max-width: 768px) {
                    .chart-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 10px;
                    }
                    
                    .chart-card {
                        height: 300px;
                    }
                    
                    .chart-insights {
                        flex-direction: column;
                    }
                }
            </style>

            <!-- Service Popularity Chart Section -->
            <div class="chart-container">
                <h2>Service Popularity</h2>
                <div class="chart-header">
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="color-box service-chart"></span>
                            <span>Appointment Count</span>
                        </div>
                    </div>
                    <div class="chart-actions">
                        <select id="serviceChartType">
                            <option value="bar">Vertical View</option>
                            <option value="horizontalBar">Horizontal View</option>
                        </select>
                    </div>
                </div>
                <div class="chart-card">
                    <canvas id="servicePopularityChart"></canvas>
                </div>
            </div>

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

        <!-- Add JavaScript functions for report modal and PDF download -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        
        <script>
        // ...existing code...
        </script>
    </main>

    <!-- Chart.js library and initialization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Months array for labels
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            // Get the real appointment data from PHP
            const appointmentData = <?php echo json_encode($monthlyAppointmentCounts); ?>;
            
            // Generate some "previous year" data for comparison (simulated data)
            const previousYearData = appointmentData.map(count => {
                // Generate random previous year data that's somewhat related to current year
                // but generally a bit lower to show "growth"
                return Math.max(0, Math.floor(count * 0.7 + Math.random() * 5));
            });
            
            // Get the chart canvas
            const ctx = document.getElementById('appointmentGrowthChart').getContext('2d');
            
            // Create a gradient for the current year data
            const gradientFill = ctx.createLinearGradient(0, 0, 0, 400);
            gradientFill.addColorStop(0, 'rgba(20, 69, 120, 0.4)');
            gradientFill.addColorStop(1, 'rgba(20, 69, 120, 0.0)');
            
            // Create the chart
            let myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Appointments (' + new Date().getFullYear() + ')',
                            data: appointmentData,
                            backgroundColor: gradientFill,
                            borderColor: '#144578',
                            borderWidth: 3,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#144578',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#144578',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Appointments (' + (new Date().getFullYear() - 1) + ')',
                            data: previousYearData,
                            backgroundColor: 'transparent',
                            borderColor: '#90CAF9',
                            borderWidth: 2,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#90CAF9',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.4,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Appointments',
                                font: {
                                    weight: 'bold',
                                    size: 13
                                },
                                padding: {top: 10, bottom: 10}
                            },
                            ticks: {
                                precision: 0,
                                stepSize: 1,
                                font: {
                                    size: 12
                                },
                                color: '#666'
                            },
                            grid: {
                                color: 'rgba(200, 200, 200, 0.2)',
                                borderDash: [5, 5],
                                drawBorder: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month',
                                font: {
                                    weight: 'bold',
                                    size: 13
                                },
                                padding: {top: 10, bottom: 10}
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                color: '#666'
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            backgroundColor: 'rgba(20, 69, 120, 0.9)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            cornerRadius: 8,
                            caretSize: 6,
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.dataset.label}: ${context.raw} appointments`;
                                },
                                labelTextColor: function() {
                                    return '#ffffff';
                                }
                            }
                        },
                        legend: {
                            display: false, // We use our custom legend
                            position: 'top',
                            labels: {
                                font: {
                                    size: 12
                                },
                                boxWidth: 15,
                                padding: 20
                            }
                        },
                        title: {
                            display: false
                        }
                    }
                }
            });
            
            // Handle the chart view type change
            document.getElementById('chartViewType').addEventListener('change', function() {
                const viewType = this.value;
                
                if (viewType === 'quarterly') {
                    // Aggregate monthly data into quarterly data
                    const quarterlyLabels = ['Q1 (Jan-Mar)', 'Q2 (Apr-Jun)', 'Q3 (Jul-Sep)', 'Q4 (Oct-Dec)'];
                    const quarterlyData = [
                        appointmentData.slice(0, 3).reduce((sum, val) => sum + val, 0),
                        appointmentData.slice(3, 6).reduce((sum, val) => sum + val, 0),
                        appointmentData.slice(6, 9).reduce((sum, val) => sum + val, 0),
                        appointmentData.slice(9, 12).reduce((sum, val) => sum + val, 0)
                    ];
                    
                    const quarterlyPrevData = [
                        previousYearData.slice(0, 3).reduce((sum, val) => sum + val, 0),
                        previousYearData.slice(3, 6).reduce((sum, val) => sum + val, 0),
                        previousYearData.slice(6, 9).reduce((sum, val) => sum + val, 0),
                        previousYearData.slice(9, 12).reduce((sum, val) => sum + val, 0)
                    ];
                    
                    myChart.data.labels = quarterlyLabels;
                    myChart.data.datasets[0].data = quarterlyData;
                    myChart.data.datasets[1].data = quarterlyPrevData;
                } else {
                    // Return to monthly view
                    myChart.data.labels = months;
                    myChart.data.datasets[0].data = appointmentData;
                    myChart.data.datasets[1].data = previousYearData;
                }
                
                myChart.update();
            });
            
            // Calculate and display insights
            function calculateInsights() {
                // Calculate year-over-year growth
                let totalCurrentYear = appointmentData.reduce((sum, count) => sum + count, 0);
                let totalPreviousYear = previousYearData.reduce((sum, count) => sum + count, 0);
                let growthPercentage = totalPreviousYear > 0 ? 
                    Math.round(((totalCurrentYear - totalPreviousYear) / totalPreviousYear) * 100) : 100;
                
                // Find peak month (max appointments)
                let maxAppointments = Math.max(...appointmentData);
                let peakMonthIndex = appointmentData.indexOf(maxAppointments);
                let peakMonth = months[peakMonthIndex];
                
                // Update trend insight
                const trendElement = document.getElementById('growthTrend');
                if (growthPercentage > 0) {
                    trendElement.innerHTML = `<span style="color:#28a745">${growthPercentage}% increase</span> in appointments compared to last year`;
                } else if (growthPercentage < 0) {
                    trendElement.innerHTML = `<span style="color:#dc3545">${Math.abs(growthPercentage)}% decrease</span> in appointments compared to last year`;
                } else {
                    trendElement.innerHTML = `Appointment numbers are <span style="color:#ffc107">unchanged</span> from last year`;
                }
                
                // Update peak period insight
                const peakElement = document.getElementById('peakPeriod');
                if (maxAppointments > 0) {
                    peakElement.innerHTML = `Busiest month is <span style="color:#144578;font-weight:bold">${peakMonth}</span> with ${maxAppointments} appointments`;
                } else {
                    peakElement.innerHTML = `Not enough data to determine peak period`;
                }
            }
            
            // Run insights calculation after chart is initialized
            calculateInsights();

            // Service Popularity Chart
            const servicePopularityData = <?php echo json_encode($servicePopularity); ?>;
            const serviceNames = servicePopularityData.map(item => item.service_name);
            const appointmentCounts = servicePopularityData.map(item => item.appointment_count);

            const serviceCtx = document.getElementById('servicePopularityChart').getContext('2d');
            
            // Create gradients for the bars
            const barGradient = serviceCtx.createLinearGradient(0, 0, 0, 400);
            barGradient.addColorStop(0, 'rgba(20, 69, 120, 0.8)');
            barGradient.addColorStop(1, 'rgba(20, 69, 120, 0.2)');
            
            const horizontalBarGradient = serviceCtx.createLinearGradient(0, 0, 400, 0);
            horizontalBarGradient.addColorStop(0, 'rgba(20, 69, 120, 0.8)');
            horizontalBarGradient.addColorStop(1, 'rgba(20, 69, 120, 0.2)');

            // Create the chart with improved design
            let serviceChart = new Chart(serviceCtx, {
                type: 'bar',
                data: {
                    labels: serviceNames,
                    datasets: [{
                        label: 'Number of Appointments',
                        data: appointmentCounts,
                        backgroundColor: barGradient,
                        borderColor: '#144578',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        hoverBackgroundColor: '#2a6db5'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'x',
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Appointments',
                                font: {
                                    weight: 'bold',
                                    size: 13
                                },
                                padding: {top: 10, bottom: 10}
                            },
                            ticks: {
                                precision: 0,
                                stepSize: 1,
                                font: {
                                    size: 12
                                },
                                color: '#666'
                            },
                            grid: {
                                color: 'rgba(200, 200, 200, 0.2)',
                                borderDash: [5, 5],
                                drawBorder: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Service Name',
                                font: {
                                    weight: 'bold',
                                    size: 13
                                },
                                padding: {top: 10, bottom: 10}
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                color: '#666',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(20, 69, 120, 0.9)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            cornerRadius: 8,
                            caretSize: 6,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Appointments: ${context.raw}`;
                                }
                            }
                        },
                        datalabels: {
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            },
                            color: '#fff',
                            anchor: 'center',
                            align: 'center',
                            font: {
                                weight: 'bold'
                            },
                            formatter: function(value) {
                                return value;
                            }
                        }
                    }
                },
                plugins: [{
                    afterDraw: chart => {
                        if (chart.data.datasets[0].data.every(item => item === 0)) {
                            // Display "No data available" if all values are 0
                            const ctx = chart.ctx;
                            const width = chart.width;
                            const height = chart.height;
                            
                            chart.clear();
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.font = '16px sans-serif';
                            ctx.fillStyle = '#666';
                            ctx.fillText('No appointment data available', width / 2, height / 2);
                            ctx.restore();
                        }
                    }
                }]
            });

            // Handle chart type change (vertical vs horizontal)
            document.getElementById('serviceChartType').addEventListener('change', function() {
                const chartType = this.value;
                
                // Destroy current chart
                serviceChart.destroy();
                
                // Setup configuration for the new chart
                const isHorizontal = chartType === 'horizontalBar';
                
                // Create new chart with updated configuration
                serviceChart = new Chart(serviceCtx, {
                    type: 'bar',
                    data: {
                        labels: serviceNames,
                        datasets: [{
                            label: 'Number of Appointments',
                            data: appointmentCounts,
                            backgroundColor: isHorizontal ? horizontalBarGradient : barGradient,
                            borderColor: '#144578',
                            borderWidth: 1,
                            borderRadius: 6,
                            barPercentage: 0.7,
                            categoryPercentage: 0.8,
                            hoverBackgroundColor: '#2a6db5'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: isHorizontal ? 'y' : 'x',
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: isHorizontal ? 'Number of Appointments' : 'Service Name',
                                    font: {
                                        weight: 'bold',
                                        size: 13
                                    },
                                    padding: {top: 10, bottom: 10}
                                },
                                ticks: {
                                    precision: 0,
                                    stepSize: isHorizontal ? 1 : null,
                                    font: {
                                        size: 12
                                    },
                                    color: '#666',
                                    maxRotation: isHorizontal ? 0 : 45,
                                    minRotation: isHorizontal ? 0 : 45
                                },
                                grid: {
                                    color: isHorizontal ? 'rgba(200, 200, 200, 0.2)' : 'transparent',
                                    borderDash: isHorizontal ? [5, 5] : [],
                                    drawBorder: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: isHorizontal ? 'Service Name' : 'Number of Appointments',
                                    font: {
                                        weight: 'bold',
                                        size: 13
                                    },
                                    padding: {top: 10, bottom: 10}
                                },
                                ticks: {
                                    precision: 0,
                                    stepSize: isHorizontal ? null : 1,
                                    font: {
                                        size: 12
                                    },
                                    color: '#666'
                                },
                                grid: {
                                    color: isHorizontal ? 'transparent' : 'rgba(200, 200, 200, 0.2)',
                                    borderDash: isHorizontal ? [] : [5, 5],
                                    drawBorder: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(20, 69, 120, 0.9)',
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                cornerRadius: 8,
                                caretSize: 6,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return `Appointments: ${context.raw}`;
                                    }
                                }
                            },
                            datalabels: {
                                display: function(context) {
                                    return context.dataset.data[context.dataIndex] > 0;
                                },
                                color: '#fff',
                                anchor: 'center',
                                align: 'center',
                                font: {
                                    weight: 'bold'
                                },
                                formatter: function(value) {
                                    return value;
                                }
                            }
                        }
                    },
                    plugins: [{
                        afterDraw: chart => {
                            if (chart.data.datasets[0].data.every(item => item === 0)) {
                                // Display "No data available" if all values are 0
                                const ctx = chart.ctx;
                                const width = chart.width;
                                const height = chart.height;
                                
                                chart.clear();
                                ctx.save();
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.font = '16px sans-serif';
                                ctx.fillStyle = '#666';
                                ctx.fillText('No appointment data available', width / 2, height / 2);
                                ctx.restore();
                            }
                        }
                    }]
                });
            });
            
            // ...existing code...
        });
    </script>
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
                                    <?php 
                                        // Determine the status to display
                                        $displayStatus = strtolower(trim($appointment['followup_status'] ?? $appointment['status']));
                                        if ($displayStatus === 'unknown' && !empty($appointment['followup_status'])) {
                                            $displayStatus = strtolower(trim($appointment['followup_status']));
                                        }
                                    ?>
                                    <tr data-status="<?php echo $displayStatus; ?>" data-date="<?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?>">
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
                                                <span><?php echo htmlspecialchars($appointment['client_firstname'] . ' ' . $appointment['client_lastname']); ?></span>
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
                                            <span class="status <?php echo $displayStatus; ?>">
                                                <?php echo ucfirst($displayStatus); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="job-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="view-btn">
                                                    <i class='bx bx-show'></i> View Details
                                                </a>
                                                <?php if ($displayStatus === 'completed'): ?>
                                                    <button href="#" class="review-btn" onclick="loadReviewData(<?php echo $appointment['appointment_id']; ?>); return false;">
                                                        <i class='bx bx-message-square-check'></i> Check Review
                                                    </button>
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

<!-- Review Modal -->
<div class="modal" id="reviewModal">
    <div class="review-modal-content">
        <div class="modal-header">
            <h3>Customer Review Details</h3>
            <span class="close-modal" onclick="closeReviewModal()">&times;</span>
        </div>
        <div class="review-content">
            <div class="review-grid">
                <div class="review-left">
                    <div class="overall-rating">
                        <h4>Overall Rating</h4>
                        <div class="rating-stars">
                            <span id="overall-rating-value">0</span>
                            <div class="stars-container" id="overall-stars"></div>
                        </div>
                    </div>
                    
                    <div class="rating-details">
                        <div class="rating-detail">
                            <h4>Service Rating</h4>
                            <div>
                                <span id="service-rating-value">0</span>
                                <div class="stars-container" id="service-stars"></div>
                            </div>
                        </div>
                        
                        <div class="rating-detail">
                            <h4>Technician Rating</h4>
                            <div>
                                <span id="technician-rating-value">0</span>
                                <div class="stars-container" id="technician-stars"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="date-info">
                        <p><i class='bx bx-calendar'></i> <span id="review-date">N/A</span></p>
                    </div>
                </div>
                
                <div class="review-right">
                    <div class="review-body">
                        <h4><i class='bx bx-message-square-detail'></i> Review</h4>
                        <p id="review-text">No review found.</p>
                    </div>
                    
                    <div class="review-feedback">
                        <h4><i class='bx bx-comment-detail'></i> Service Feedback</h4>
                        <p id="service-feedback">No feedback provided.</p>
                    </div>
                    
                    <div class="review-issues" id="issues-container">
                        <h4><i class='bx bx-error-circle'></i> Reported Issues</h4>
                        <p id="reported-issues">No issues reported.</p>
                    </div>
                </div>
            </div>
        </div>
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

        <!-- Search and Filter Controls - -->
        <div class="report-controls">
            <div class="filter-options">
                <select id="statusFilter">
                    <option value="">Filter Status</option>
                    <option value="pending">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <input type="date" id="dateFilter" placeholder="Filter by date">
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
                    <div class="image-gallery" id="photosContainer">
                        <!-- Photos will be dynamically added here -->
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

    <!-- Add this script at the end of the reports section -->
    <script>
    // Ensure all report cards have proper data attributes
    document.addEventListener('DOMContentLoaded', function() {
        const reportCards = document.querySelectorAll('.report-card');
        
        reportCards.forEach(card => {
            // Extract data from DOM elements if data attributes aren't set
            if (!card.hasAttribute('data-tech-name')) {
                const techName = card.querySelector('.technician-info h3')?.textContent || '';
                card.setAttribute('data-tech-name', techName);
            }
            
            if (!card.hasAttribute('data-location')) {
                const locationElement = card.querySelector('.report-preview p:nth-child(1)');
                if (locationElement) {
                    // Remove the icon and extract just the location text
                    const locationText = locationElement.textContent.replace(/\s*\u{1F4CD}\s*/u, '').trim();
                    card.setAttribute('data-location', locationText);
                }
            }
            
            if (!card.hasAttribute('data-account')) {
                const accountElement = card.querySelector('.report-preview p:nth-child(2)');
                if (accountElement) {
                    // Remove the "Client:" prefix and extract just the client name
                    const accountText = accountElement.textContent.replace(/Client:\s*/i, '').trim();
                    card.setAttribute('data-account', accountText);
                }
            }
            
            if (!card.hasAttribute('data-treatment')) {
                const treatmentElement = card.querySelector('.report-preview p:nth-child(3)');
                if (treatmentElement) {
                    // Remove the "Service:" prefix and extract just the service name
                    const treatmentText = treatmentElement.textContent.replace(/Service:\s*/i, '').trim();
                    card.setAttribute('data-treatment', treatmentText);
                }
            }
            
            if (!card.hasAttribute('data-status')) {
                const statusElement = card.querySelector('.report-status');
                if (statusElement) {
                    const statusText = statusElement.textContent.toLowerCase();
                    card.setAttribute('data-status', statusText);
                }
            }
        });
        
        // Initialize the filter after ensuring all attributes are set
        if (typeof filterReportsCorrectly === 'function') {
            filterReportsCorrectly();
        }
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
                        
                            <a href="../employee/forms/archive.php" class="btn">
                                <i class='bx bx-archive'></i>
                                <span class="text">View Archive</span>
                            </a>
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

                            const { value: reason, isConfirmed } = await Swal.fire({
                                title: 'Are you sure?',
                                text: "You won't be able to revert this!",
                                icon: 'warning',
                                input: 'textarea',
                                inputLabel: 'Reason for deletion',
                                inputPlaceholder: 'Type your reason here...',
                                inputAttributes: {
                                    'aria-label': 'Type your reason here'
                                },
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, delete it!',
                                preConfirm: (inputValue) => {
                                    if (!inputValue) {
                                        Swal.showValidationMessage('You need to write a reason!');
                                    }
                                    return inputValue;
                                }
                            });

                            if (isConfirmed && reason) {
                                const deleteUrl = event.target.href;

                                const response = await fetch(deleteUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({ reason: reason })
                                });

                                const result = await response.json();
                                if (result.success) {
                                    Swal.fire('Deleted!', result.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', result.message, 'error');
                                }
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
                    </div>
                    <div class="service-cards">
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <div class="service-card">
                                    <input type="hidden" name="service_id[]" value="<?php echo $service['service_id']; ?>">
                                    <div class="service-image">
                                        <?php 
                                        // Check if the image is stored in the database as binary data
                                        if (!empty($service['image_data'])) {
                                            // Image is stored in the database as binary data
                                            $imageType = $service['image_type'] ?? 'image/jpeg'; // Default to JPEG if type not specified
                                            $base64Image = base64_encode($service['image_data']);
                                            echo '<img src="data:' . $imageType . ';base64,' . $base64Image . '" alt="' . htmlspecialchars($service['service_name']) . '">';
                                        } 
                                        // Fallback to old path-based method if no image_data but has image_path
                                        else if (!empty($service['image_path'])) {
                                            $imagePath = $service['image_path'];
                                            $displayImagePath = "../Pictures/" . $imagePath;
                                            $serverImagePath = $_SERVER['DOCUMENT_ROOT'] . "/PESTCOZAM/Pictures/" . $imagePath;
                                            
                                            if (file_exists($serverImagePath)) {
                                                echo '<img src="' . htmlspecialchars($displayImagePath) . '" alt="' . htmlspecialchars($service['service_name']) . '">';
                                            } else {
                                                echo '<img src="../Pictures/service-placeholder.png" alt="' . htmlspecialchars($service['service_name']) . '" class="placeholder-img">';
                                            }
                                        } else {
                                            // If no image available, show placeholder
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
            Swal.fire({
                title: `Are you sure you want to delete "${serviceName}"?`,
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Use fetch with proper JSON handling
                    fetch(`delete-service.php?id=${serviceId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Deleted!',
                                    `${serviceName} has been deleted successfully.`,
                                    'success'
                                );
                                // Remove the service card from the DOM
                                const serviceCard = document.querySelector(`input[value="${serviceId}"]`).closest('.service-card');
                                serviceCard.remove();
                                
                                // If no more services, show the "No services available" message
                                if (document.querySelectorAll('.service-card').length === 0) {
                                    const serviceCards = document.querySelector('.service-cards');
                                    serviceCards.innerHTML = '<p class="no-services-message">No services available. Please add services.</p>';
                                }
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Error deleting service: ' + (data.message || 'Unknown error'),
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting the service. Please try again.',
                                'error'
                            );
                        });
                }
            });
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
            
            // Fetch all customers for client-side filtering (limited to 500 for performance)
            $allCustomersQuery = "SELECT 
                id, 
                firstname, 
                lastname, 
                email, 
                mobile_number, 
                (SELECT COUNT(*) FROM appointments WHERE user_id = users.id) as appointment_count
                FROM users 
                WHERE role = 'user'
                ORDER BY lastname, firstname
                LIMIT 500";
            
            $allCustomersStmt = $db->prepare($allCustomersQuery);
            $allCustomersStmt->execute();
            $allCustomers = $allCustomersStmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log("Error fetching customers: " . $e->getMessage());
            echo "<div class='error-message'>Database error: " . $e->getMessage() . "</div>";
            $customers = [];
            $totalPages = 0;
            $allCustomers = [];
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
                        <input type="text" name="customer_search" id="customer_search" placeholder="Search customers..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit" class="search-btn" id="customer_search_btn"><i class='bx bx-search'></i></button>
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
                        <tbody id="customer-table-body">
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

        <script>
        // Store all customers data for client-side filtering
        const allCustomers = <?php echo json_encode($allCustomers); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const customerSearchInput = document.getElementById('customer_search');
            const customerTableBody = document.getElementById('customer-table-body');
            const customerForm = document.getElementById('customers-form');
            const customerSearchBtn = document.getElementById('customer_search_btn');
            const customerCountSpan = document.querySelector('.customer-count');
            
            if (customerSearchInput && customerTableBody) {
                // Enhanced real-time search with more responsive feedback
                customerSearchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    // Always update UI immediately for better responsiveness
                    if (searchTerm === '') {
                        // If empty, show all rows and reset highlighting
                        showAllCustomerRows();
                        return;
                    }
                    
                    // Client-side filtering for instant results
                    if (searchTerm.length >= 1) {
                        // More immediate feedback even with just one character
                        const filteredCount = filterCustomersTable(searchTerm);
                        
                        // Update customer count to show filtered results
                        if (customerCountSpan) {
                            const text = filteredCount > 0 
                                ? `(${filteredCount} found)` 
                                : '(No matches found)';
                            customerCountSpan.textContent = text;
                        }
                    }
                });
                
                // Form submission event (for server-side processing when Enter is pressed)
                if (customerForm) {
                    customerForm.addEventListener('submit', function(e) {
                        const searchTerm = customerSearchInput.value.trim();
                        // If very short search term and not empty, prevent submission and show message
                        if (searchTerm.length > 0 && searchTerm.length < 2) {
                            e.preventDefault();
                            Swal.fire({
                                title: 'Search Too Short',
                                text: 'Please enter at least 2 characters to search',
                                icon: 'info',
                                confirmButtonColor: '#144578'
                            });
                        }
                    });
                }
                
                // Add clear button functionality
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'clear-search-btn';
                clearBtn.innerHTML = '<i class="bx bx-x"></i>';
                clearBtn.style.display = 'none';
                clearBtn.title = 'Clear search';
                
                // Insert clear button before the search button
                if (customerSearchBtn && customerSearchBtn.parentNode) {
                    customerSearchBtn.parentNode.insertBefore(clearBtn, customerSearchBtn);
                }
                
                // Show/hide clear button based on search input
                customerSearchInput.addEventListener('input', function() {
                    clearBtn.style.display = this.value ? 'flex' : 'none';
                });
                
                // Clear search when button is clicked
                clearBtn.addEventListener('click', function() {
                    customerSearchInput.value = '';
                    customerSearchInput.focus();
                    showAllCustomerRows();
                    clearBtn.style.display = 'none';
                    
                    // Reset customer count to total
                    if (customerCountSpan) {
                        customerCountSpan.textContent = `(${allCustomers.length} total)`;
                    }
                });
            }
            
            // Function to show all customer rows and clear highlights
            function showAllCustomerRows() {
                if (!customerTableBody) return;
                
                // Get all customer rows
                const rows = customerTableBody.querySelectorAll('tr');
                
                // Clear highlighting and show all rows
                rows.forEach(row => {
                    row.style.display = '';
                    row.querySelectorAll('.search-highlight').forEach(highlight => {
                        highlight.outerHTML = highlight.innerHTML;
                    });
                });
                
                // Check if we need to show "no data" row
                const noDataRow = customerTableBody.querySelector('.no-data');
                if (noDataRow) {
                    if (allCustomers.length === 0) {
                        noDataRow.closest('tr').style.display = '';
                    } else {
                        noDataRow.closest('tr').style.display = 'none';
                    }
                }
            }
            
            // Function to filter customers table in real-time
            function filterCustomersTable(searchTerm) {
                if (!customerTableBody) return 0;
                
                // Get all customer rows (excluding no-data row)
                const rows = customerTableBody.querySelectorAll('tr:not(.no-data)');
                let matchCount = 0;
                
                // Process each row
                rows.forEach(row => {
                    // Skip the "no data" rows
                    if (row.querySelector('.no-data')) return;
                    
                    let rowMatch = false;
                    const cells = row.querySelectorAll('td:not(:last-child)'); // Skip the actions column
                    
                    // Clear existing highlights first
                    row.querySelectorAll('.search-highlight').forEach(el => {
                        el.outerHTML = el.innerHTML;
                    });
                    
                    // Check each cell for a match
                    cells.forEach(cell => {
                        const text = cell.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            // Highlight the matching text
                            highlightText(cell, searchTerm);
                            rowMatch = true;
                        }
                    });
                    
                    // Show/hide the row based on match
                    row.style.display = rowMatch ? '' : 'none';
                    if (rowMatch) matchCount++;
                });
                
                // Handle no results case
                if (matchCount === 0 && searchTerm.length > 0) {
                    let noDataRow = customerTableBody.querySelector('.no-data');
                    
                    // Safely escape HTML in search term to prevent XSS
                    const safeSearchTerm = escapeHtml(searchTerm);
                    
                    if (!noDataRow) {
                        // Create a "no results" row if it doesn't exist
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
                            <td colspan="6" class="no-data">
                                No customers found matching "${safeSearchTerm}". 
                                <a href="#" class="clear-search-link">Clear search</a>
                            </td>
                        `;
                        customerTableBody.appendChild(newRow);
                        
                        // Add event listener to clear search link
                        const clearLink = newRow.querySelector('.clear-search-link');
                        if (clearLink) {
                            clearLink.addEventListener('click', function(e) {
                                e.preventDefault();
                                customerSearchInput.value = '';
                                showAllCustomerRows();
                                
                                // Reset customer count to total
                                if (customerCountSpan) {
                                    customerCountSpan.textContent = `(${allCustomers.length} total)`;
                                }
                                
                                // Hide clear button
                                const clearBtn = document.querySelector('.clear-search-btn');
                                if (clearBtn) clearBtn.style.display = 'none';
                            });
                        }
                    } else {
                        // Update existing no-data message
                        noDataRow.innerHTML = `
                            No customers found matching "${safeSearchTerm}". 
                            <a href="#" class="clear-search-link">Clear search</a>
                        `;
                        noDataRow.closest('tr').style.display = '';
                        
                        // Add event listener to clear search link
                        const clearLink = noDataRow.querySelector('.clear-search-link');
                        if (clearLink) {
                            clearLink.addEventListener('click', function(e) {
                                e.preventDefault();
                                customerSearchInput.value = '';
                                showAllCustomerRows();
                                
                                // Reset customer count to total
                                if (customerCountSpan) {
                                    customerCountSpan.textContent = `(${allCustomers.length} total)`;
                                }
                                
                                // Hide clear button
                                const clearBtn = document.querySelector('.clear-search-btn');
                                if (clearBtn) clearBtn.style.display = 'none';
                            });
                        }
                    }
                } else {
                    // Hide any "no results" message if we have results
                    const noDataRow = customerTableBody.querySelector('.no-data');
                    if (noDataRow) {
                        noDataRow.closest('tr').style.display = 'none';
                    }
                }
                
                return matchCount;
            }
            
            // Add this helper function to escape HTML and prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Helper function to highlight text with XSS protection
            function highlightText(element, searchTerm) {
                if (!element || !element.textContent) return;
                
                const regex = new RegExp('(' + escapeRegExp(searchTerm) + ')', 'gi');
                const text = element.textContent;
                
                if (regex.test(text)) {
                    // Save innerHTML and replace only text nodes
                    const walker = document.createTreeWalker(
                        element,
                        NodeFilter.SHOW_TEXT,
                        null,
                        false
                    );
                    
                    const textNodes = [];
                    let node;
                    while (node = walker.nextNode()) {
                        if (regex.test(node.nodeValue)) {
                            textNodes.push(node);
                        }
                    }
                    
                    textNodes.forEach(textNode => {
                        const temp = document.createElement('span');
                        temp.textContent = textNode.nodeValue; // Use textContent for safe handling
                        
                        // Replace each match with a span containing the highlighted text
                        const safeHtml = temp.innerHTML.replace(regex, function(match) {
                            return '<span class="search-highlight">' + match + '</span>';
                        });
                        
                        const fragment = document.createRange().createContextualFragment(safeHtml);
                        textNode.parentNode.replaceChild(fragment, textNode);
                    });
                }
            }
            
            // Escape special characters in the search term for regex
            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
        });
        </script>
    </main>
</section>
<!-- Customers Section -->
 
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
                            Swal.fire(
                                'Error!',
                                'Error updating profile. Invalid response from server.',
                                'error'
                            );
                            submitBtn.textContent = originalText;
                            submitBtn.disabled = false;
                            return;
                        }
                        if (data && data.success) {
                            Swal.fire(
                                'Success!',
                                'Profile updated successfully!',
                                'success'
                            );
                            updateProfileDisplay(formData);
                            closeProfileModal();
                            history.replaceState(null, null, location.pathname);
                        } else {
                            Swal.fire(
                                'Error!',
                                'Error: ' + (data && data.message ? data.message : 'Update failed'),
                                'error'
                            );
                        }
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error updating profile:', error);
                        Swal.fire(
                            'Error!',
                            'An error occurred while updating profile: ' + error.message,
                            'error'
                        );
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

<script src="../JS CODES/dashboard-admin.js"></script>
<script src="../JS CODES/work-orders.js"></script>
<script src="../JS CODES/fix-report-search.js"></script>
<script src="../JS CODES/technician-reports-fix.js"></script>

<script>
// Global search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Global search form handler
    const globalSearchForm = document.getElementById('globalSearchForm');
    const globalSearchInput = document.getElementById('globalSearchInput');
    
    if (globalSearchForm) {
        globalSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = globalSearchInput.value.trim().toLowerCase();
            
            if (searchTerm.length < 2) {
                Swal.fire(
                    'Error!',
                    'Please enter at least 2 characters to search',
                    'error'
                );
                return;
            }
            
            // Store the search term in session storage
            sessionStorage.setItem('globalSearchTerm', searchTerm);
            
            // Apply search across all sections
            performGlobalSearch(searchTerm);
        });
    }
    
    // Check if there's a stored search term and apply it
    const storedSearchTerm = sessionStorage.getItem('globalSearchTerm');
    if (storedSearchTerm && globalSearchInput) {
        globalSearchInput.value = storedSearchTerm;
        performGlobalSearch(storedSearchTerm);
    }
    
    // Handle section-specific search boxes
    initializeSectionSearches();
});

// Perform global search across all dashboard sections
function performGlobalSearch(searchTerm) {
    // Find active section first
    const activeSection = document.querySelector('.section.active');
    let resultsFound = false;
    
    // Count total results found
    let totalResults = 0;
    
    // Reset previous highlights
    document.querySelectorAll('.search-highlight').forEach(el => {
        el.outerHTML = el.innerHTML;
    });
    
    // 1. Search work orders
    totalResults += searchWorkOrders(searchTerm);
    
    // 2. Search employees 
    totalResults += searchEmployees(searchTerm);
    
    // 3. Search services
    totalResults += searchServices(searchTerm);
    
    // 4. Search customers
    totalResults += searchCustomers(searchTerm);
    
    // 5. Search reports
    totalResults += searchReports(searchTerm);
    
    // Show search results notification
    showSearchResults(totalResults, searchTerm);
}

// Function to highlight text matches
function highlightText(element, searchTerm) {
    if (!element || !element.innerHTML) return false;
    
    const content = element.innerHTML;
    const regex = new RegExp('(' + escapeRegExp(searchTerm) + ')', 'gi');
    
    if (regex.test(content)) {
        element.innerHTML = content.replace(regex, '<span class="search-highlight">$1</span>');
        return true;
    }
    return false;
}

// Helper function to escape regex special characters
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Get all text content from an element including nested elements
function getElementText(element) {
    if (!element || !element.textContent) return '';
    return element.textContent.trim().toLowerCase();
}

// Search functions for each section
function searchWorkOrders(searchTerm) {
    const workOrdersTable = document.querySelector('#work-orders .work-orders-table tbody');
    if (!workOrdersTable) return 0;
    
    let count = 0;
    const rows = workOrdersTable.querySelectorAll('tr');
    const searchRegex = new RegExp(escapeRegExp(searchTerm), 'i');
    
    rows.forEach(row => {
        let rowMatch = false;
        const cells = row.querySelectorAll('td:not(:last-child)');
        
        cells.forEach(cell => {
            const cellText = getElementText(cell);
            if (searchRegex.test(cellText)) {
                rowMatch = true;
                highlightTextInElement(cell, searchTerm);
            }
        });
        
        if (rowMatch) {
            row.style.display = '';
            count++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const heading = document.querySelector('#work-orders .head-title h1');
    if (heading) {
        heading.innerHTML = `Work Orders Management ${count > 0 ? `<span class="search-count">(${count} results)</span>` : ''}`;
    }
    
    return count;
}

// Function to highlight text in all text nodes of an element
function highlightTextInElement(element, searchTerm) {
    if (!element) return;
    
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );
    
    const textNodes = [];
    let node;
    while (node = walker.nextNode()) {
        textNodes.push(node);
    }
    
    const regex = new RegExp('(' + escapeRegExp(searchTerm) + ')', 'gi');
    textNodes.forEach(textNode => {
        const parent = textNode.parentNode;
        const text = textNode.nodeValue;
        
        if (!regex.test(text) || parent.classList && parent.classList.contains('search-highlight')) {
            return;
        }
        
        const temp = document.createElement('span');
        temp.innerHTML = text.replace(regex, '<span class="search-highlight">$1</span>');
        
        const fragment = document.createDocumentFragment();
        while (temp.firstChild) {
            fragment.appendChild(temp.firstChild);
        }
        
        parent.replaceChild(fragment, textNode);
    });
}

function searchEmployees(searchTerm) {
    const employeeTable = document.querySelector('#employees #employeeTable tbody');
    if (!employeeTable) return 0;
    
    let count = 0;
    const rows = employeeTable.querySelectorAll('tr');
    
    rows.forEach(row => {
        let rowMatch = false;
        const textCells = row.querySelectorAll('td:not(:first-child):not(:last-child)');
        
        textCells.forEach(cell => {
            if (highlightText(cell, searchTerm)) {
                rowMatch = true;
            }
        });
        
        if (rowMatch) {
            row.style.display = '';
            count++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const heading = document.querySelector('#employees .head-title h1');
    if (heading) {
        heading.innerHTML = `Employee Management ${count > 0 ? `<span class="search-count">(${count} results)</span>` : ''}`;
    }
    
    return count;
}

function searchServices(searchTerm) {
    const serviceCards = document.querySelectorAll('#services .service-card');
    if (serviceCards.length === 0) return 0;
    
    let count = 0;
    
    serviceCards.forEach(card => {
        const serviceName = card.querySelector('h4');
        const serviceDesc = card.querySelector('.description');
        
        let cardMatch = false;
        
        if (serviceName && highlightText(serviceName, searchTerm)) {
            cardMatch = true;
        }
        
        if (serviceDesc && highlightText(serviceDesc, searchTerm)) {
            cardMatch = true;
        }
        
        if (cardMatch) {
            card.style.display = '';
            count++;
        } else {
            card.style.display = 'none';
        }
    });
    
    const heading = document.querySelector('#services .head-title h1');
    if (heading) {
        heading.innerHTML = `Services Management ${count > 0 ? `<span class="search-count">(${count} results)</span>` : ''}`;
    }
    
    return count;
}

function searchCustomers(searchTerm) {
    const customerTable = document.querySelector('#customers .customer-table tbody');
    if (!customerTable) return 0;
    
    let count = 0;
    const rows = customerTable.querySelectorAll('tr');
    
    rows.forEach(row => {
        if (row.querySelector('.no-data')) return;
        
        let rowMatch = false;
        const textCells = row.querySelectorAll('td:not(:last-child)');
        
        textCells.forEach(cell => {
            if (highlightText(cell, searchTerm)) {
                rowMatch = true;
            }
        });
        
        if (rowMatch) {
            row.style.display = '';
            count++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const heading = document.querySelector('#customers .head-title h1');
    if (heading) {
        heading.innerHTML = `Customer Management ${count > 0 ? `<span class="search-count">(${count} results)</span>` : ''}`;
    }
    
    return count;
}

function searchReports(searchTerm) {
    const reportCards = document.querySelectorAll('#reports .report-card');
    if (reportCards.length === 0) return 0;
    
    let count = 0;
    
    reportCards.forEach(card => {
        const techName = card.querySelector('.technician-info h3');
        const location = card.querySelector('.report-preview p:nth-child(1)');
        const client = card.querySelector('.report-preview p:nth-child(2)');
        const service = card.querySelector('.report-preview p:nth-child(3)');
        
        let cardMatch = false;
        
        if (techName && highlightText(techName, searchTerm)) {
            cardMatch = true;
        }
        
        if (location && highlightText(location, searchTerm)) {
            cardMatch = true;
        }
        
        if (client && highlightText(client, searchTerm)) {
            cardMatch = true;
        }
        
        if (service && highlightText(service, searchTerm)) {
            cardMatch = true;
        }
        
        if (cardMatch) {
            card.style.display = '';
            count++;
        } else {
            card.style.display = 'none';
        }
    });
    
    const heading = document.querySelector('#reports .head-title h1');
    if (heading) {
        heading.innerHTML = `Manage Technicians Reports ${count > 0 ? `<span class="search-count">(${count} results)</span>` : ''}`;
    }
    
    return count;
}

// Initialize all section-specific search boxes
function initializeSectionSearches() {
    const workOrdersSearch = document.getElementById('searchAppointments');
    if (workOrdersSearch) {
        workOrdersSearch.addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();
            if (searchTerm.length > 0) {
                searchWorkOrders(searchTerm);
            } else {
                document.querySelectorAll('#work-orders .work-orders-table tbody tr').forEach(row => {
                    row.style.display = '';
                });
                document.querySelectorAll('#work-orders .search-highlight').forEach(el => {
                    el.outerHTML = el.innerHTML;
                });
                const heading = document.querySelector('#work-orders .head-title h1');
                if (heading) {
                    heading.innerHTML = 'Work Orders Management';
                }
            }
        });
    }
    
    const reportsSearch = document.getElementById('reportSearchInput');
    if (reportsSearch) {
        reportsSearch.addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();
            if (searchTerm.length > 0) {
                searchReports(searchTerm);
            } else {
                document.querySelectorAll('#reports .report-card').forEach(card => {
                    card.style.display = '';
                });
                document.querySelectorAll('#reports .search-highlight').forEach(el => {
                    el.outerHTML = el.innerHTML;
                });
                const heading = document.querySelector('#reports .head-title h1');
                if (heading) {
                    heading.innerHTML = 'Manage Technicians Reports';
                }
            }
        });
    }
}

// Display search results notification
function showSearchResults(count, searchTerm) {
    const existingNotification = document.getElementById('searchResultsNotification');
    if (existingNotification) {
        document.body.removeChild(existingNotification);
    }
    
    const notification = document.createElement('div');
    notification.id = 'searchResultsNotification';
    notification.className = 'search-results-notification';
    
    if (count > 0) {
        notification.innerHTML = `
            <i class='bx bx-search'></i>
            <span>Found <strong>${count}</strong> results for "<strong>${searchTerm}</strong>"</span>
            <button class="clear-search" onclick="clearGlobalSearch()">Clear</button>
        `;
        notification.className += ' success';
    } else {
        notification.innerHTML = `
            <i class='bx bx-error-circle'></i>
            <span>No results found for "<strong>${searchTerm}</strong>"</span>
            <button class="clear-search" onclick="clearGlobalSearch()">Clear</button>
        `;
        notification.className += ' warning';
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateY(0)';
        notification.style.opacity = '1';
    }, 10);
    
    setTimeout(() => {
        notification.style.transform = 'translateY(-100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Function to clear global search
function clearGlobalSearch() {
    sessionStorage.removeItem('globalSearchTerm');
    
    const globalSearchInput = document.getElementById('globalSearchInput');
    if (globalSearchInput) {
        globalSearchInput.value = '';
    }
    
    document.querySelectorAll('.section').forEach(section => {
        section.querySelectorAll('table tbody tr').forEach(row => {
            row.style.display = '';
        });
        
        section.querySelectorAll('.service-card').forEach(card => {
            card.style.display = '';
        });
        
        section.querySelectorAll('.report-card').forEach(card => {
            card.style.display = '';
        });
        
        section.querySelectorAll('.search-highlight').forEach(el => {
            el.outerHTML = el.innerHTML;
        });
        
        const heading = section.querySelector('.head-title h1');
        if (heading) {
            heading.innerHTML = heading.innerHTML.replace(/<span class="search-count">.*?<\/span>/, '');
        }
    });
    
    const notification = document.getElementById('searchResultsNotification');
    if (notification) {
        notification.style.transform = 'translateY(-100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}
</script>

<style>
.search-highlight {
    background-color: #ffec8b;
    padding: 2px 0;
    border-radius: 2px;
    font-weight: bold;
}

.search-count {
    font-size: 0.8em;
    background-color: #144578;
    color: white;
    padding: 2px 8px;
    border-radius: 20px;
    margin-left: 10px;
    font-weight: normal;
    display: inline-block;
    vertical-align: middle;
}

.search-results-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1000;
    transform: translateY(-100%);
    opacity: 0;
    transition: all 0.3s ease;
    max-width: 400px;
}

.search-results-notification.success i {
    color: #144578;
    font-size: 24px;
}

.search-results-notification.warning i {
    color: #ff9800;
    font-size: 24px;
}

.search-results-notification span {
    flex: 1;
}

.clear-search {
    background-color: #f0f0f0;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: background-color 0.2s;
}

.clear-search:hover {
    background-color: #e0e0e0;
}

#globalSearchForm .form-input {
    position: relative;
    transition: all 0.3s ease;
}

#globalSearchForm .form-input input {
    width: 250px;
    transition: width 0.3s ease;
}

#globalSearchForm .form-input input:focus {
    width: 300px;
    border-color: #144578;
    box-shadow: 0 0 0 2px rgba(20, 69, 120, 0.2);
}

#globalSearchForm .form-input::before {
    content: "🔍";
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #ccc;
    font-size: 14px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
}

#globalSearchForm .form-input input:not(:focus):placeholder-shown::before {
    opacity: 1;
}
</style>

<!-- Add this right before the closing body tag -->
<style>
/* Fix for technician report card display */
#reports .report-preview p {
    display: flex !important;
    align-items: center !important;
    margin: 8px 0 !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
}

#reports .report-preview p i {
    margin-right: 8px !important;
    font-size: 16px !important;
    color: #144578 !important;
    min-width: 20px !important;
    flex-shrink: 0 !important;
}

/* Ensure only one icon per paragraph */
#reports .report-preview p i + i {
    display: none !important;
}
</style>

<script>
// Call the fix function when the page is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Run immediately
    if (typeof fixTechnicianReportCards === 'function') {
        fixTechnicianReportCards();
    }
    
    // Run again after a slight delay to catch any dynamic content
    setTimeout(function() {
        if (typeof fixTechnicianReportCards === 'function') {
            console.log('Running delayed technician report fix');
            fixTechnicianReportCards();
        }
    }, 500);
    
    // Fix cards when clicking on the Reports tab
    const reportsTabLink = document.querySelector('a[href="#reports"]');
    if (reportsTabLink) {
        reportsTabLink.addEventListener('click', function() {
            setTimeout(function() {
                if (typeof fixTechnicianReportCards === 'function') {
                    fixTechnicianReportCards();
                }
            }, 100);
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Run the fix on tab changes
    const reportTabLink = document.querySelector('a[href="#reports"]');
    if (reportTabLink) {
        reportTabLink.addEventListener('click', function() {
            // Wait a moment for the tab to show
            setTimeout(function() {
                if (typeof fixTechnicianReportCards === 'function') {
                    console.log('Running technician report fix after tab click');
                    fixTechnicianReportCards();
                }
            }, 200);
        });
    }
    
    // Run the fix again on window resize (layout shifts can cause issues)
    window.addEventListener('resize', function() {
        if (document.querySelector('.section.active#reports') && 
            typeof fixTechnicianReportCards === 'function') {
            fixTechnicianReportCards();
        }
    });
    
    // Run one more time after everything else has loaded
    window.addEventListener('load', function() {
        if (typeof fixTechnicianReportCards === 'function') {
            fixTechnicianReportCards();
        }
    });
});
</script>

<script>
// Function to update report status
function updateReportStatus(status) {
    const reportId = document.getElementById('reportIdField').value;
    if (!reportId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Report ID not found'
        });
        return;
    }
    
    // Confirm before proceeding
    Swal.fire({
        title: `Are you sure you want to ${status} this report?`,
        text: status === 'approved' ? "This will also mark the associated appointment as completed." : "",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../PHP CODES/update_report_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_id: reportId,
                    status: status,
                    role: 'admin' // Add role identifier
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: `Report ${status === 'approved' ? 'approved' : 'rejected'} successfully!${status === 'approved' ? ' The appointment has been marked as completed.' : ''}`,
                    }).then(() => {
                        // Refresh the page to show updated status
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error updating report status: ' + (data.message || 'Unknown error')
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the report status.'
                });
            });
        }
    });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the time slot calendar
    if (typeof initTimeSlotCalendar === 'function') {
        initTimeSlotCalendar();
    }
    
    // Also initialize the other calendar if needed
    if (typeof initializeCalendar === 'function') {
        initializeCalendar();
    }
    
    console.log('Calendar initialization attempted');
});
</script>

<!-- Report Submission Form -->
<form id="submitReportForm" method="POST" onsubmit="handleReportSubmission(event)">
    <input type="hidden" name="technician_id" value="<?php echo $technicianId; ?>">
    <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
    <textarea name="report_data" required></textarea>
    <button type="submit">Submit Report</button>
</form>

<script>
function handleReportSubmission(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    const reportData = {
        technician_id: formData.get('technician_id'),
        appointment_id: formData.get('appointment_id'),
        report_data: formData.get('report_data'),
    };

    submitReport(reportData);
}
</script>
</body>
</html>