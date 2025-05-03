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

    // Updated appointments query with optimized join to followup_visits
    $appointmentsQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        CASE 
            WHEN fv.id IS NOT NULL THEN 'followup'
            ELSE a.status
        END as status,
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
        CASE 
            WHEN a.is_for_self = 1 THEN u.mobile_number
            ELSE a.mobile_number
        END as client_mobile,
        s.service_name,
        s.service_id,
        fv.id IS NOT NULL as is_followup
    FROM appointments a
    INNER JOIN services s ON a.service_id = s.service_id
    INNER JOIN users u ON a.user_id = u.id
    LEFT JOIN followup_visits fv ON a.id = fv.appointment_id
    LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
    WHERE a.technician_id = ? OR at.technician_id = ?
    ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $stmt = $db->prepare($appointmentsQuery);
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get appointments eligible for reports - modified to include confirmed, pending, and follow-up visits
    $confirmedAppointmentsQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        CASE 
            WHEN fv.id IS NOT NULL THEN 'followup'
            ELSE a.status
        END as status,
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
        CASE 
            WHEN a.is_for_self = 1 THEN u.mobile_number
            ELSE a.mobile_number
        END as client_mobile,
        s.service_name,
        s.service_id,
        fv.id IS NOT NULL as is_followup
    FROM appointments a
    INNER JOIN services s ON a.service_id = s.service_id
    INNER JOIN users u ON a.user_id = u.id
    LEFT JOIN followup_visits fv ON a.id = fv.appointment_id
    LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
    WHERE (a.technician_id = ? OR at.technician_id = ?)
    AND (LOWER(a.status) IN ('pending', 'completed', 'confirmed') OR fv.id IS NOT NULL)
    ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    $stmt = $db->prepare($confirmedAppointmentsQuery);
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $confirmedAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics for dashboard
    // 1. Count pending jobs (appointments with status 'pending' or 'confirmed')
    $pendingJobsQuery = "SELECT COUNT(*) as count FROM appointments a 
                        LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
                        WHERE (a.status = 'pending' OR a.status = 'confirmed') 
                        AND (a.technician_id = ? OR at.technician_id = ?)";
    $stmt = $db->prepare($pendingJobsQuery);
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $pendingJobs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 2. Count active technicians
    $activeTechsQuery = "SELECT COUNT(*) as count FROM users 
                         WHERE role = 'technician' AND status = 'verified'";
    $stmt = $db->prepare($activeTechsQuery);
    $stmt->execute();
    $activeTechs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 3. Count service reports submitted by this technician
    $reportsQuery = "SELECT COUNT(*) as count FROM service_reports 
                     WHERE technician_id = ?";
    $stmt = $db->prepare($reportsQuery);
    $stmt->execute([$_SESSION['user_id']]);
    $serviceReports = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 4. Count scheduled follow-ups
    $followupsQuery = "SELECT COUNT(*) as count FROM appointments a
                      LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
                      WHERE a.status = 'Confirmed' 
                      AND a.appointment_date >= CURDATE()
                      AND (a.technician_id = ? OR at.technician_id = ?)";
    $stmt = $db->prepare($followupsQuery);
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $scheduledFollowups = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Fetch treatment methods, devices, and chemicals for dropdowns
    $treatmentMethods = [];
    $devices = [];
    $chemicals = [];
    try {
        // Treatment Methods
        $stmt = $db->prepare("SELECT id, name FROM treatment_methods ORDER BY name");
        $stmt->execute();
        $treatmentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Devices - Make sure to use the correct columns from the devices table
        $stmt = $db->prepare("SELECT id, name FROM devices ORDER BY name");
        $stmt->execute();
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug info
        error_log("Found " . count($devices) . " devices");

        // Chemicals - Correctly select name column from treatment_chemicals
        $stmt = $db->prepare("SELECT id, name FROM treatment_chemicals ORDER BY name");
        $stmt->execute();
        $chemicals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug info
        error_log("Found " . count($chemicals) . " chemicals");
        
    } catch(PDOException $e) {
        error_log("Error fetching dropdown data: " . $e->getMessage());
    }
    
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $technician = null;
    $assignments = [];
    $confirmedAppointments = [];
    $pendingJobs = 0;
    $activeTechs = 0;
    $serviceReports = 0;
    $scheduledFollowups = 0;
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
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
                    <span class="text">Submit Service Report</span>
                </a>
            </li>
            <li>
                <a href="#schedule-followup" onclick="showSection('schedule-followup')">
                    <i class='bx bx-calendar-plus'></i>
                    <span class="text">Schedule Follow-up</span>
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
                                <h3><?php echo $pendingJobs; ?></h3>
                                <p>Pending Jobs</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-group'></i>
                            <span class="text">
                                <h3><?php echo $activeTechs; ?></h3>
                                <p>Active Technicians</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-file'></i>
                            <span class="text">
                                <h3><?php echo $serviceReports; ?></h3>
                                <p>Service Reports</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-calendar-plus'></i>
                            <span class="text">
                                <h3><?php echo $scheduledFollowups; ?></h3>
                                <p>Scheduled Follow-ups</p>
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
                        <div class="search-wrapper">
                            <i class='bx bx-search'></i>
                            <input type="text" id="assignment-search" placeholder="Search assignments...">
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Schedule</th>
                                <th>Customer</th>
                                <th>Contact</th>
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
                                            <div class="contact-info">
                                                <i class='bx bx-phone'></i>
                                                <span><?php echo htmlspecialchars($assignment['client_mobile']); ?></span>
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
                                            <div class="status-view-container">
                                                <span class="status <?php echo strtolower($assignment['status']); ?>">
                                                    <?php 
                                                    if ($assignment['is_followup']) {
                                                        echo 'Follow-up Visit';
                                                    } else {
                                                        echo htmlspecialchars($assignment['status']);
                                                    }
                                                    ?>
                                                </span>
                                                <a href="view_job_details-pct.php?id=<?php echo $assignment['appointment_id']; ?>" class="view-btn">
                                                    <i class='bx bx-show'></i> View Details
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-records">No assignments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </section>

    <!-- Submit Service Report Section -->
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
                    <div class="right">
                        <a href="view_submitted_reports-pct.php" class="btn-submit" style="margin-top: 10px; text-decoration: none;">
                            <i class='bx bx-show'></i> View Submitted Reports
                        </a>
                    </div>
                </div>

                <div class="report-form-container wide">
                    <?php if(isset($_SESSION['report_success'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['report_success']; unset($_SESSION['report_success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['report_error'])): ?>
                        <div class="alert alert-error">
                            <?php echo $_SESSION['report_error']; unset($_SESSION['report_error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="service-report-form" method="POST" action="../PHP CODES/submit_report.php" enctype="multipart/form-data">
                        <!-- Hidden field for technician ID -->
                        <input type="hidden" name="technician_id" value="<?php echo $_SESSION['user_id']; ?>">
                        
                        <!-- Appointment Selection -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class='bx bx-calendar'></i>
                                <h3>Select Appointment</h3>
                            </div>
                            <div class="field-group">
                                <label for="appointment">Choose an appointment to report (optional)</label>
                                <select name="appointment_id" id="appointment">
                                    <option value="">Select an appointment (or leave blank for non-appointment service)</option>
                                    <?php foreach ($confirmedAppointments as $appointment): ?>
                                        <option
                                            value="<?php echo $appointment['appointment_id']; ?>"
                                            data-client="<?php echo htmlspecialchars($appointment['client_firstname'] . ' ' . $appointment['client_lastname']); ?>"
                                            data-location="<?php echo htmlspecialchars(implode(', ', array_filter([$appointment['street_address'], $appointment['barangay'], $appointment['city']]))); ?>"
                                            data-service="<?php echo htmlspecialchars($appointment['service_name']); ?>"
                                            data-contact="<?php echo htmlspecialchars($appointment['client_mobile'] ?? ''); ?>"
                                            data-time-in="<?php echo htmlspecialchars(substr($appointment['appointment_time'], 0, 5)); ?>"
                                            <?php
                                                // Fetch extra fields for this appointment
                                                $stmtExtra = $db->prepare("SELECT treatment_methods, device_installation, chemical_consumables, chemical_quantities FROM appointments WHERE id = ?");
                                                $stmtExtra->execute([$appointment['appointment_id']]);
                                                $extra = $stmtExtra->fetch(PDO::FETCH_ASSOC);
                                                // Prepare JSON for JS
                                                $treatments = $extra && $extra['treatment_methods'] ? htmlspecialchars($extra['treatment_methods']) : '';
                                                $devices = $extra && $extra['device_installation'] ? htmlspecialchars($extra['device_installation']) : '';
                                                $chemicals = $extra && $extra['chemical_consumables'] ? htmlspecialchars($extra['chemical_consumables']) : '';
                                                $chemQty = $extra && $extra['chemical_quantities'] ? htmlspecialchars($extra['chemical_quantities']) : '';
                                                echo "data-treatments='$treatments' data-devices='$devices' data-chemicals='$chemicals' data-chemical-qty='$chemQty'";
                                            ?>
                                        >
                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])) . ' - ' .
                                                $appointment['client_firstname'] . ' ' . $appointment['client_lastname'] . ' - ' .
                                                $appointment['service_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Report Details -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class='bx bx-file'></i>
                                <h3>Service Report Details</h3>
                            </div>
                            <div class="report-grid-3col">
                                <div class="field-group">
                                    <label for="account_name">Account/Client Name</label>
                                    <input type="text" name="account_name" id="account_name" required>
                                </div>
                                <div class="field-group">
                                    <label for="location">Location/Address</label>
                                    <input type="text" name="location" id="location" required>
                                </div>
                                <div class="field-group">
                                    <label for="contact_no">Contact No</label>
                                    <input type="text" name="contact_no" id="contact_no" required pattern="[0-9\-\+\s]+" title="Enter a valid phone number">
                                </div>
                                <div class="field-group">
                                    <label for="date_of_treatment">Date of Treatment</label>
                                    <input type="date" name="date_of_treatment" id="date_of_treatment" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="field-group">
                                    <label for="time_in">Time In</label>
                                    <input type="time" name="time_in" id="time_in" required>
                                </div>
                                <div class="field-group">
                                    <label for="time_out">Time Out</label>
                                    <input type="time" name="time_out" id="time_out" required>
                                </div>
                                <div class="field-group dropdown-down">
                                    <label for="treatment_type">Treatment Type</label>
                                    <div class="custom-dropdown">
                                        <select name="treatment_type" id="treatment_type" required>
                                            <option value="">Select Treatment Type</option>
                                            <option value="Soil Poisoning">Soil Poisoning</option>
                                            <option value="Mound Demolition">Mound Demolition</option>
                                            <option value="Termite Control">Termite Control</option>
                                            <option value="General Pest Control">General Pest Control</option>
                                            <option value="Mosquito Control">Mosquito Control</option>
                                            <option value="Rat Control">Rat Control</option>
                                            <option value="Flying & Crawling Insect Control">Flying & Crawling Insect Control</option>
                                            <option value="Extraction">Extraction</option>
                                            <option value="Ocular Inspection">Ocular Inspection</option>
                                            <option value="Other">Other (specify in treatment method)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="field-group">
                                    <label for="pest_count">Pest Count (if applicable)</label>
                                    <input type="text" name="pest_count" id="pest_count" placeholder="e.g., 15 roaches, 3 rats">
                                </div>
                                <div class="field-group dropdown-down">
                                    <label for="plan_type">Plan Type</label>
                                    <div class="custom-dropdown">
                                        <select name="plan_type" id="plan_type" required>
                                            <option value="">Select Plan Type</option>
                                            <option value="weekly">Weekly Visit</option>
                                            <option value="monthly">Monthly Visit</option>
                                            <option value="quarterly">Quarterly Visit</option>
                                            <option value="yearly">Yearly Visit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="report-grid-2col">
                                <!-- Treatment Method Dropdown -->
                                <div class="field-group dropdown-down">
                                    <label for="treatment_method">Treatment Method</label>
                                    <div style="display: flex; gap: 8px;">
                                        <div class="custom-dropdown" style="flex:1;">
                                            <select name="treatment_method" id="treatment_method" required>
                                                <option value="">Select Treatment Method</option>
                                                <?php foreach ($treatmentMethods as $method): ?>
                                                    <option value="<?php echo htmlspecialchars($method['name']); ?>">
                                                        <?php echo htmlspecialchars($method['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <input type="text" name="treatment_method_qty" id="treatment_method_qty" placeholder="Qty" style="width:60px;" autocomplete="off">
                                    </div>
                                </div>
                                <!-- Device Installation Dropdown -->
                                <div class="field-group dropdown-down">
                                    <label for="device_installation">Select device</label>
                                    <div style="display: flex; gap: 8px;">
                                        <div class="custom-dropdown" style="flex:1;">
                                            <select name="device_installation" id="device_installation">
                                                <option value="">Select Device</option>
                                                <?php if (!empty($devices)): ?>
                                                    <?php foreach ($devices as $device): ?>
                                                        <option value="<?php echo htmlspecialchars($device['name']); ?>">
                                                            <?php echo htmlspecialchars($device['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="">No devices available</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <input type="text" name="device_installation_qty" id="device_installation_qty" placeholder="Qty" style="width:60px;" autocomplete="off">
                                    </div>
                                </div>
                                <!-- Consumed Chemicals Dropdown -->
                                <div class="field-group dropdown-down">
                                    <label for="consumed_chemicals">Consumed Chemicals</label>
                                    <div style="display: flex; gap: 8px;">
                                        <div class="custom-dropdown" style="flex:1;">
                                            <select name="consumed_chemicals" id="consumed_chemicals">
                                                <option value="">Select Chemical/Product</option>
                                                <?php if (!empty($chemicals)): ?>
                                                    <?php foreach ($chemicals as $chemical): ?>
                                                        <option value="<?php echo htmlspecialchars($chemical['name']); ?>">
                                                            <?php echo htmlspecialchars($chemical['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="">No chemicals available</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <input type="text" name="consumed_chemicals_qty" id="consumed_chemicals_qty" placeholder="Qty" style="width:60px;" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Photo Upload -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class='bx bx-image'></i>
                                <h3>Upload Photos</h3>
                            </div>
                            <div class="upload-container">
                                <div class="upload-area">
                                    <i class='bx bx-cloud-upload'></i>
                                    <p>Drag & Drop Photos Here</p>
                                    <span>or</span>
                                    <label for="photos" class="upload-btn">Choose Files</label>
                                    <input type="file" name="photos[]" id="photos" multiple accept="image/*">
                                    <small>Upload up to 5 photos. Include before/after photos if available.</small>
                                </div>
                                <div class="photo-preview" id="photo-preview-container">
                                    <!-- Preview images will appear here -->
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="reset" class="btn-reset">
                                <i class='bx bx-reset'></i> Clear
                            </button>
                            <button type="submit" class="btn-submit">
                                <i class='bx bx-send'></i> Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <style>
        /* Force dropdowns to open downward */
        .field-group.dropdown-down {
            position: relative;
            overflow: visible;
        }
        .field-group.dropdown-down select[dropdown-direction="down"] {
            position: relative;
            overflow: visible;
            max-height: 44px;
        }
        .field-group.dropdown-down select[dropdown-direction="down"]:focus {
            z-index: 9999;
        }
        </style>
        
        <script>
            // Auto-fill form when appointment is selected
            document.getElementById('appointment').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (this.value) {
                    // Get data from the selected option's data attributes
                    const clientName = selectedOption.getAttribute('data-client');
                    const location = selectedOption.getAttribute('data-location');
                    const treatmentType = selectedOption.getAttribute('data-service');
                    const contactNo = selectedOption.getAttribute('data-contact');
                    const appointmentTime = selectedOption.getAttribute('data-time-in'); // Use appointment time as base
                    
                    // Split appointment time into time_in and calculate time_out (default duration: 2 hours)
                    const timeIn = appointmentTime;
                    const timeOut = calculateTimeOut(appointmentTime, 2); // Add 2 hours by default
                    
                    // Fill in the form fields
                    document.getElementById('account_name').value = clientName;
                    document.getElementById('location').value = location;
                    document.getElementById('contact_no').value = contactNo;
                    document.getElementById('time_in').value = timeIn || '';
                    document.getElementById('time_out').value = timeOut || '';
                    
                    // Handle dropdown for treatment type
                    const treatmentTypeSelect = document.getElementById('treatment_type');
                    let optionFound = false;
                    Array.from(treatmentTypeSelect.options).forEach(option => {
                        if (option.text.trim().toLowerCase() === treatmentType.trim().toLowerCase()) {
                            option.selected = true;
                            optionFound = true;
                        }
                    });

                    // If no exact match found, reset to default
                    if (!optionFound) {
                        treatmentTypeSelect.selectedIndex = 0;
                    }

                    // --- Treatment Method, Device, Chemicals autofill with qty ---
                    // Treatment Method (assume single select, JSON array or string)
                    const treatmentMethodData = selectedOption.getAttribute('data-treatments');
                    let method = '';
                    try {
                        const parsed = JSON.parse(treatmentMethodData);
                        if (Array.isArray(parsed) && parsed.length > 0) method = parsed[0];
                        else if (typeof parsed === 'string') method = parsed;
                    } catch { method = treatmentMethodData; }
                    const methodSelect = document.getElementById('treatment_method');
                    if (methodSelect) {
                        let found = false;
                        Array.from(methodSelect.options).forEach(option => {
                            if (
                                method &&
                                option.value.trim().toLowerCase() === method.trim().toLowerCase()
                            ) {
                                option.selected = true;
                                found = true;
                            } else {
                                option.selected = false;
                            }
                        });
                        // If not found, select the first option (the "Select..." message)
                        if (!found) methodSelect.selectedIndex = 0;
                    }
                    let methodQty = '';
                    const chemQtyData = selectedOption.getAttribute('data-chemical-qty');
                    if (chemQtyData) {
                        try {
                            const parsedQty = JSON.parse(chemQtyData);
                            if (Array.isArray(parsedQty) && parsedQty.length > 0) methodQty = parsedQty[0];
                            else if (typeof parsedQty === 'string') methodQty = parsedQty;
                        } catch { methodQty = chemQtyData; }
                    }
                    if (document.getElementById('treatment_method_qty')) {
                        document.getElementById('treatment_method_qty').value = methodQty;
                    }

                    // Device Installation (assume string or JSON array)
                    const deviceData = selectedOption.getAttribute('data-devices');
                    let device = '';
                    try {
                        const parsed = JSON.parse(deviceData);
                        if (Array.isArray(parsed) && parsed.length > 0) device = parsed[0];
                        else if (typeof parsed === 'string') device = parsed;
                    } catch { device = deviceData; }
                    const deviceSelect = document.getElementById('device_installation');
                    if (deviceSelect) {
                        let found = false;
                        Array.from(deviceSelect.options).forEach(option => {
                            if (
                                device &&
                                option.value.trim().toLowerCase() === device.trim().toLowerCase()
                            ) {
                                option.selected = true;
                                found = true;
                            } else {
                                option.selected = false;
                            }
                        });
                        if (!found) deviceSelect.selectedIndex = 0;
                    }
                    let deviceQty = '';
                    if (chemQtyData) {
                        try {
                            const parsedQty = JSON.parse(chemQtyData);
                            if (Array.isArray(parsedQty) && parsedQty.length > 1) deviceQty = parsedQty[1];
                            else if (typeof parsedQty === 'string') deviceQty = parsedQty;
                        } catch { deviceQty = chemQtyData; }
                    }
                    if (document.getElementById('device_installation_qty')) {
                        document.getElementById('device_installation_qty').value = deviceQty;
                    }

                    // Consumed Chemicals (assume string or JSON array)
                    const chemicalData = selectedOption.getAttribute('data-chemicals');
                    let chemical = '';
                    try {
                        const parsed = JSON.parse(chemicalData);
                        if (Array.isArray(parsed) && parsed.length > 0) chemical = parsed[0];
                        else if (typeof parsed === 'string') chemical = parsed;
                    } catch { chemical = chemicalData; }
                    const chemicalSelect = document.getElementById('consumed_chemicals');
                    if (chemicalSelect) {
                        let found = false;
                        Array.from(chemicalSelect.options).forEach(option => {
                            if (
                                chemical &&
                                option.value.trim().toLowerCase() === chemical.trim().toLowerCase()
                            ) {
                                option.selected = true;
                                found = true;
                            } else {
                                option.selected = false;
                            }
                        });
                        if (!found) chemicalSelect.selectedIndex = 0;
                    }
                    // Fix: Consumed Chemicals Qty should always use the first value if only one, or the correct index if multiple
                    let chemQty = '';
                    if (chemQtyData) {
                        try {
                            const parsedQty = JSON.parse(chemQtyData);
                            // If only one value, use [0], if multiple, use [2] (for chemicals)
                            if (Array.isArray(parsedQty)) {
                                if (parsedQty.length === 1) {
                                    chemQty = parsedQty[0];
                                } else if (parsedQty.length > 2) {
                                    chemQty = parsedQty[2];
                                } else if (parsedQty.length > 0) {
                                    chemQty = parsedQty[parsedQty.length - 1];
                                }
                            } else if (typeof parsedQty === 'string') {
                                chemQty = parsedQty;
                            }
                        } catch { chemQty = chemQtyData; }
                    }
                    if (document.getElementById('consumed_chemicals_qty')) {
                        document.getElementById('consumed_chemicals_qty').value = chemQty;
                    }
                } else {
                    // Clear fields if "Select an appointment" is chosen
                    document.getElementById('account_name').value = '';
                    document.getElementById('location').value = '';
                    document.getElementById('contact_no').value = '';
                    document.getElementById('treatment_type').selectedIndex = 0;
                    document.getElementById('time_in').value = '';
                    document.getElementById('time_out').value = '';
                    if (document.getElementById('treatment_method_qty')) document.getElementById('treatment_method_qty').value = '';
                    if (document.getElementById('device_installation_qty')) document.getElementById('device_installation_qty').value = '';
                    if (document.getElementById('consumed_chemicals_qty')) document.getElementById('consumed_chemicals_qty').value = '';
                }
            });

            // Helper function to calculate time_out based on time_in and duration in hours
            function calculateTimeOut(timeIn, durationHours) {
                if (!timeIn) return '';
                const [hours, minutes] = timeIn.split(':').map(Number);
                const date = new Date();
                date.setHours(hours, minutes);
                date.setHours(date.getHours() + durationHours); // Add duration
                return date.toTimeString().slice(0, 5); // Return in HH:MM format
            }

            // Handle file uploads and preview
            document.getElementById('photos').addEventListener('change', function(e) {
                const previewContainer = document.getElementById('photo-preview-container');
                previewContainer.innerHTML = ''; // Clear previous previews
                
                if (this.files.length > 5) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Too many files',
                        text: 'You can only upload a maximum of 5 photos.',
                        confirmButtonColor: '#144578'
                    });
                    this.value = ''; // Clear selected files
                    return;
                }
                
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    
                    // Create preview element
                    const preview = document.createElement('div');
                    preview.className = 'photo-item';
                    
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    img.style.display = 'block';
                    img.onload = function() {
                        URL.revokeObjectURL(this.src); // Free memory
                    };
                    
                    preview.appendChild(img);
                    previewContainer.appendChild(preview);
                }
            });
            
            // Set default time values based on current time
            document.addEventListener('DOMContentLoaded', function() {
                const now = new Date();
                const timeIn = document.getElementById('time_in');
                const timeOut = document.getElementById('time_out');
                
                // Format current time for time inputs (HH:MM)
                const formatTime = (date) => {
                    return date.toTimeString().slice(0, 5);
                };
                
                // Set time_in to current time if not already set
                if (!timeIn.value) {
                    timeIn.value = formatTime(now);
                }
                
                // Set time_out to 2 hours later if not already set
                if (!timeOut.value) {
                    const later = new Date(now.getTime() + 2 * 60 * 60 * 1000); // 2 hours later
                    timeOut.value = formatTime(later);
                }
            });

            document.getElementById('appointment').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const treatmentType = selectedOption.getAttribute('data-service');
                const treatmentTypeSelect = document.getElementById('treatment_type');

                if (treatmentType) {
                    Array.from(treatmentTypeSelect.options).forEach(option => {
                        option.selected = option.text === treatmentType;
                    });
                } else {
                    treatmentTypeSelect.selectedIndex = 0; // Reset to default
                }
            });

            // Optional: Show selected items as tags for multi-selects
            function enhanceMultiSelect(selectId) {
                const select = document.getElementById(selectId);
                if (!select) return;
                select.addEventListener('change', function() {
                    // No-op: browser default multi-select is used, but you can enhance here if needed
                });
            }
            enhanceMultiSelect('treatment_method');
            enhanceMultiSelect('device_installation');
            enhanceMultiSelect('consumed_chemicals');
        </script>
    </section>
    <!--SUBMIT SERVICE REPORT SECTION-->

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
                
                <!-- Add success/error messages -->
                <?php if(isset($_SESSION['followup_success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['followup_success']; unset($_SESSION['followup_success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['followup_error'])): ?>
                    <div class="alert alert-error">
                        <?php echo $_SESSION['followup_error']; unset($_SESSION['followup_error']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="followup-grid">
                    <!-- Main Schedule Content -->
                    <div class="calendar-container">
                        <!-- FOLLOW-UP FORM -->
                        <form action="../HTML CODES/schedule_followup-pct.php" method="POST" class="settings-card no-hover">
                            <div class="card-header">
                                <i class='bx bx-calendar-edit'></i>
                                <h4>Follow-up Details</h4>
                            </div>
                            <div class="plan-frequency">
                                <!-- Customer Selection -->
                                <div class="form-group">
                                    <label>Select Customer's Last Appointment:</label>
                                    <select id="customer-select" name="appointment_id" required onchange="loadCustomerDetails(this.value)">
                                        <option value="" disabled selected>Select Customer</option>
                                        <?php 
                                        try {
                                            // Improved query with explicit JOIN for appointment_technicians
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
                                            LEFT JOIN appointment_technicians att ON a.id = att.appointment_id
                                            WHERE a.status = 'Completed'
                                            AND (a.technician_id = :technician_id1 OR att.technician_id = :technician_id2)
                                            GROUP BY a.id
                                            ORDER BY a.appointment_date DESC";
                                            
                                            $stmt = $db->prepare($customerQuery);
                                            $stmt->bindParam(':technician_id1', $_SESSION['user_id'], PDO::PARAM_INT);
                                            $stmt->bindParam(':technician_id2', $_SESSION['user_id'], PDO::PARAM_INT);
                                            $stmt->execute();
                                            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (empty($customers)) {
                                                echo '<option value="">No completed appointments found for your assignments</option>';
                                            } else {
                                                foreach ($customers as $customer) {
                                                    $displayDate = date('M d, Y', strtotime($customer['appointment_date']));
                                                    echo '<option value="' . $customer['appointment_id'] . '" '
                                                         . 'data-service="' . $customer['service_id'] . '" '
                                                         . 'data-location="' . htmlspecialchars($customer['location']) . '" '
                                                         . 'data-technician="' . $customer['technician_id'] . '" '
                                                         . 'data-all-technicians="' . htmlspecialchars($customer['all_technician_ids']) . '" '
                                                         . 'data-all-technician-names="' . htmlspecialchars($customer['all_technician_names']) . '">'
                                                         . htmlspecialchars($customer['customer_name']) . ' - ' 
                                                         . htmlspecialchars($customer['service_name']) . ' (' . $displayDate . ')'
                                                         . '</option>';
                                                }
                                            }
                                        } catch(PDOException $e) {
                                            error_log("Error in customer query for PCT follow-up: " . $e->getMessage());
                                            echo '<option value="">Error loading customers: ' . $e->getMessage() . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Service Type -->
                                <div class="form-group">
                                    <label>Service Type:</label>
                                    <select id="service-type" name="service_id" required>
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

                                <!-- Technician Selection -->
                                <div class="form-group">
                                    <label>Assign Technician:</label>
                                    <div class="tech-selection-wrapper">
                                        <div class="tech-selection-container">
                                            <select id="technician-select" name="technician_id" required multiple class="enhanced-select">
                                                <?php 
                                                try {
                                                    // Always include the current logged in technician first
                                                    $currentTechId = $_SESSION['user_id'];
                                                    $currentTechQuery = "SELECT id, firstname, lastname 
                                                                        FROM users 
                                                                        WHERE id = ?";
                                                    $currentTechStmt = $db->prepare($currentTechQuery);
                                                    $currentTechStmt->execute([$currentTechId]);
                                                    $currentTech = $currentTechStmt->fetch(PDO::FETCH_ASSOC);
                                                    
                                                    if ($currentTech) {
                                                        echo '<option value="' . htmlspecialchars($currentTech['id']) . '" selected>' . 
                                                            htmlspecialchars($currentTech['firstname'] . ' ' . $currentTech['lastname']) . ' (You)</option>';
                                                    }
                                                    
                                                    // Get all other technicians
                                                    $techQuery = "SELECT id, firstname, lastname 
                                                                FROM users 
                                                                WHERE role = 'technician' 
                                                                AND status = 'verified'
                                                                AND id != ?";
                                                    $techStmt = $db->prepare($techQuery);
                                                    $techStmt->execute([$currentTechId]);
                                                    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    if (!empty($technicians)) {
                                                        foreach ($technicians as $tech) {
                                                            echo '<option value="' . htmlspecialchars($tech['id']) . '">' . 
                                                                htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']) . '</option>';
                                                        }
                                                    } 
                                                    
                                                    // If no techs found (other than current one)
                                                    if (empty($technicians) && !$currentTech) {
                                                        echo '<option value="" disabled>No technicians available</option>';
                                                    }
                                                } catch(PDOException $e) {
                                                    error_log("Error loading technicians: " . $e->getMessage());
                                                    echo '<option value="" disabled>Error loading technicians</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Follow-up Date -->
                                <div class="form-group">
                                    <label>Follow-up Date:</label>
                                    <input type="date" id="followup-date" name="followup_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <!-- Follow-up Time -->
                                <div class="form-group">
                                    <label>Follow-up Time:</label>
                                    <select id="followup-time" name="followup_time" required>
                                        <option value="">Select Time</option>
                                        <option value="07:00:00">7:00 AM - 9:00 AM</option>
                                        <option value="09:00:00">9:00 AM - 11:00 AM</option>
                                        <option value="11:00:00">11:00 AM - 1:00 PM</option>
                                        <option value="13:00:00">1:00 PM - 3:00 PM</option>
                                        <option value="15:00:00">3:00 PM - 5:00 PM</option>
                                    </select>
                                </div>
                                
                                <!-- Plan Type Selection -->
                                <div class="form-group">
                                    <label>Plan Type:</label>
                                    <select id="plan-type" name="plan_type" required>
                                        <option value="">Select Plan Type</option>
                                        <option value="weekly">Weekly Visit</option>
                                        <option value="monthly">Monthly Visit</option>
                                        <option value="quarterly">Quarterly Visit</option>
                                        <option value="yearly">Yearly Visit</option>
                                    </select>
                                </div>
                                
                                <!-- Frequency Selection -->
                                <div class="form-group">
                                    <label>Visit Frequency:</label>
                                    <select id="visit-frequency" name="visit_frequency">
                                        <option value="">Select Frequency</option>
                                        <option value="1">Once</option>
                                        <option value="2">Twice</option>
                                        <option value="3">Three times</option>
                                        <option value="4">Four times</option>
                                        <option value="6">Six times</option>
                                        <option value="12">Twelve times</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                                
                                <!-- Contract Duration -->
                                <div class="form-group">
                                    <label>Contract Duration:</label>
                                    <div class="duration-input-group">
                                        <input type="number" id="contract-duration" name="contract_duration" min="1" placeholder="Duration">
                                        <select id="duration-unit" name="duration_unit">
                                            <option value="days">Days</option>
                                            <option value="weeks">Weeks</option>
                                            <option value="months" selected>Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Notes Field -->
                                <div class="form-group">
                                    <label>Notes:</label>
                                    <textarea id="followup-notes" name="notes" rows="3" placeholder="Enter any special instructions or notes for this follow-up..."></textarea>
                                </div>
                            </div>
                            <div class="schedule-actions-centered"></div>
                                <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #144578, #2a6db5); color: white; padding: 15px 30px; border-radius: 12px; font-weight: 600; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.3s ease; font-size: 16px; border: none; box-shadow: 0 6px 15px rgba(20, 69, 120, 0.2);">
                                    <i class='bx bx-calendar-check'></i> Schedule Follow-up
                                </button>
                            </div>
                        </form>

                        <!-- Current Appointments Card -->
                        <div class="settings-card no-hover">
                            <div class="card-header">
                                <i class='bx bx-notepad'></i>
                                <h4>Scheduled Follow-ups</h4>
                            </div>
                            <div class="followups-controls">
                                <div class="search-box">
                                    <i class='bx bx-search'></i>
                                    <input type="text" id="followup-search" placeholder="Search follow-ups...">
                                </div>
                            </div>
                            <div class="table-wrapper scrollable-table">
                                <table class="appointments-table">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Service</th>
                                            <th>Location</th>
                                            <th>Follow-ups</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="followups-list">
                                        <?php
                                        try {
                                            // Modified query to group by customer
                                            $followupsQuery = "SELECT 
                                                CASE 
                                                    WHEN a.is_for_self = 1 THEN u.id
                                                    ELSE CONCAT('guest_', a.id)
                                                END as client_id,
                                                CASE 
                                                    WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                                                    ELSE CONCAT(a.firstname, ' ', a.lastname)
                                                END as customer_name,
                                                s.service_name,
                                                CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location,
                                                COUNT(DISTINCT a.id) as appointment_count
                                            FROM appointments a
                                            JOIN users u ON a.user_id = u.id
                                            JOIN services s ON a.service_id = s.service_id
                                            LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
                                            LEFT JOIN users tech ON (at.technician_id = tech.id OR a.technician_id = tech.id)
                                            LEFT JOIN followup_visits fv ON a.id = fv.appointment_id
                                            LEFT JOIN followup_plan fp ON fv.followup_plan_id = fp.id
                                            WHERE (a.status IN ('Confirmed', 'Scheduled') OR fv.id IS NOT NULL)
                                            AND a.appointment_date >= CURDATE()
                                            AND (a.technician_id = ? OR at.technician_id = ?)
                                            GROUP BY client_id
                                            ORDER BY customer_name ASC
                                            LIMIT 50";
                                            $stmt = $db->prepare($followupsQuery);
                                            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                                            $groupedFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            // Now get the detailed appointments for each customer
                                            $detailedFollowupsQuery = "SELECT 
                                                a.id as appointment_id,
                                                a.appointment_date,
                                                a.appointment_time,
                                                CASE 
                                                    WHEN a.is_for_self = 1 THEN u.id
                                                    ELSE CONCAT('guest_', a.id)
                                                END as client_id,
                                                CASE 
                                                    WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                                                    ELSE CONCAT(a.firstname, ' ', a.lastname)
                                                END as customer_name,
                                                s.service_name,
                                                s.service_id,
                                                GROUP_CONCAT(DISTINCT CONCAT(tech.firstname, ' ', tech.lastname) SEPARATOR ', ') as technician_names,
                                                CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location,
                                                IF(fv.id IS NOT NULL, 'Scheduled Follow-up', a.status) as status,
                                                fp.id as plan_id,
                                                fv.id as visit_id
                                            FROM appointments a
                                            JOIN users u ON a.user_id = u.id
                                            JOIN services s ON a.service_id = s.service_id
                                            LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
                                            LEFT JOIN users tech ON (at.technician_id = tech.id OR a.technician_id = tech.id)
                                            LEFT JOIN followup_visits fv ON a.id = fv.appointment_id
                                            LEFT JOIN followup_plan fp ON fv.followup_plan_id = fp.id
                                            WHERE (a.status IN ('Confirmed', 'Scheduled') OR fv.id IS NOT NULL)
                                            AND a.appointment_date >= CURDATE()
                                            AND (a.technician_id = ? OR at.technician_id = ?)
                                            GROUP BY a.id
                                            ORDER BY a.appointment_date ASC, a.appointment_time ASC";
                                            $stmt = $db->prepare($detailedFollowupsQuery);
                                            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                                            $detailedFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            // Create a lookup array for the detailed appointments
                                            $clientAppointments = [];
                                            foreach ($detailedFollowups as $appointment) {
                                                $clientId = $appointment['client_id'];
                                                if (!isset($clientAppointments[$clientId])) {
                                                    $clientAppointments[$clientId] = [];
                                                }
                                                $clientAppointments[$clientId][] = $appointment;
                                            }
                                            
                                            // Debug: Count the follow-ups
                                            echo '<!-- Found ' . count($groupedFollowups) . ' unique clients with follow-ups -->';
                                            
                                            // Check if we have any follow-ups
                                            if (!empty($groupedFollowups)) {
                                                foreach ($groupedFollowups as $client) {
                                                    $clientId = $client['client_id'];
                                                    $appointments = $clientAppointments[$clientId] ?? [];
                                                    
                                                    echo '<tr class="followup-row client-row" data-client-id="' . $clientId . '">';
                                                    echo '<td>' . htmlspecialchars($client['customer_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($client['service_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($client['location']) . '</td>';
                                                    echo '<td>' . count($appointments) . ' scheduled</td>';
                                                    echo '<td><button class="view-details-btn" data-client-id="' . $clientId . '">View Details</button></td>';
                                                    echo '</tr>';
                                                    
                                                    // Add a hidden row for the appointments
                                                    echo '<tr class="appointment-details-row" id="details-' . $clientId . '" style="display: none;">';
                                                    echo '<td colspan="5">';
                                                    echo '<div class="appointment-details">';
                                                    echo '<h4>Scheduled Follow-ups</h4>';
                                                    echo '<table class="nested-appointments-table">';
                                                    echo '<thead><tr><th>Date & Time</th><th>Service</th><th>Technician</th><th>Status</th></tr></thead>';
                                                    echo '<tbody>';
                                                    
                                                    foreach ($appointments as $appointment) {
                                                        $appointmentDate = strtotime($appointment['appointment_date']);
                                                        
                                                        // Determine time period for filtering
                                                        $today = strtotime('today');
                                                        $weekStart = strtotime('monday this week', $today);
                                                        $weekEnd = strtotime('sunday this week', $today);
                                                        $nextWeekStart = strtotime('monday next week', $today);
                                                        $nextWeekEnd = strtotime('sunday next week', $today);
                                                        $nextMonthStart = strtotime('first day of next month', $today);
                                                        $nextMonthEnd = strtotime('last day of next month', $today);
                                                        
                                                        // Set period class
                                                        if ($appointmentDate >= $weekStart && $appointmentDate <= $weekEnd) {
                                                            $dateClass = 'thisweek';
                                                        } elseif ($appointmentDate >= $nextWeekStart && $appointmentDate <= $nextWeekEnd) {
                                                            $dateClass = 'nextweek';
                                                        } elseif ($appointmentDate >= $nextMonthStart && $appointmentDate <= $nextMonthEnd) {
                                                            $dateClass = 'nextmonth';
                                                        } else {
                                                            $dateClass = 'future';
                                                        }
                                                        
                                                        echo '<tr class="nested-followup-row" data-period="' . $dateClass . '">';
                                                        echo '<td>' . date('M d, Y', $appointmentDate) . ' ' . 
                                                             date('h:i A', strtotime($appointment['appointment_time'])) . '</td>';
                                                        echo '<td>' . htmlspecialchars($appointment['service_name']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($appointment['technician_names'] ?? 'Not Assigned') . '</td>';
                                                        echo '<td><span class="status scheduled">' . 
                                                             htmlspecialchars($appointment['status']) . '</span></td>';
                                                        echo '</tr>';
                                                    }
                                                    
                                                    echo '</tbody></table></div></td></tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5">No follow-ups scheduled</td></tr>';
                                            }
                                        } catch(PDOException $e) {
                                            echo '<tr><td colspan="5">Error loading follow-ups: ' . $e->getMessage() . '</td></tr>';
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
            
            <!-- Updated profile container with exact AOS styling -->
            <div class="profile-container">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <img src="../Pictures/boy.png" alt="User Avatar" class="avatar" />
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?></h3>
                        <p><?php echo htmlspecialchars($technician['email']); ?></p>
                        <p><?php echo ucfirst(htmlspecialchars($technician['role'])); ?></p>
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
                            <p><strong>First Name:</strong> <span data-field="firstname"><?php echo htmlspecialchars($technician['firstname']); ?></span></p>
                            <p><strong>Middle Name:</strong> <span data-field="middlename"><?php echo htmlspecialchars($technician['middlename'] ?: 'Not set'); ?></span></p>
                        </div>
                        <div class="info-row">
                            <p><strong>Last Name:</strong> <span data-field="lastname"><?php echo htmlspecialchars($technician['lastname']); ?></span></p>
                            <p><strong>Date of Birth:</strong> <span><?php echo $technician['dob'] ? date('m-d-Y', strtotime($technician['dob'])) : 'Not set'; ?></span></p>
                        </div>
                        <div class="info-row">
                            <p><strong>Email:</strong> <span data-field="email"><?php echo htmlspecialchars($technician['email']); ?></span></p>
                            <p><strong>Phone Number:</strong> <span data-field="mobile_number"><?php echo htmlspecialchars($technician['mobile_number'] ?? 'Not set'); ?></span></p>
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
                            <p><strong>Role:</strong> <span><?php echo ucfirst(htmlspecialchars($technician['role'])); ?></span></p>
                            <p><strong>Status:</strong> <span><?php echo ucfirst(htmlspecialchars($technician['status'])); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Modal - Updated to exactly match AOS implementation -->
            <div id="profileModal" class="modal">
                <div class="modal-content profile-modal-content">
                    <div class="modal-header">
                        <h2>Edit Profile</h2>
                        <span class="close" title="Close">&times;</span>
                    </div>
                    <form id="editProfileForm" method="POST" novalidate>
                        <div class="form-group">
                            <label for="firstname">First Name</label>
                            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($technician['firstname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middlename">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" value="<?php echo htmlspecialchars($technician['middlename']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="lastname">Last Name</label>
                            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($technician['lastname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($technician['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mobile_number">Mobile Number</label>
                            <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($technician['mobile_number']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" value="<?php echo $technician['dob']; ?>">
                        </div>
                        <div class="form-buttons">
                            <button type="button" class="cancel-btn" id="closeProfileModalBtn">Cancel</button>
                            <button type="submit" class="save-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </section>

    <script src="../JS CODES/dashboard-pct.js"></script>
    <script>
        // Function to handle filtering of assignment rows
        document.addEventListener('DOMContentLoaded', function() {
            const assignmentRows = document.querySelectorAll('tr[data-status]');
            
            // Check if there's an appointment_id in the URL fragment
            function checkForAppointmentId() {
                // Get the URL fragment
                const hash = window.location.hash;
                
                // Check if we're in the submit-report section and have a parameter
                if (hash.includes('#submit-report')) {
                    // Show the submit-report section
                    showSection('submit-report');
                    
                    // Extract appointment_id from URL if present
                    const urlParams = new URLSearchParams(hash.split('?')[1]);
                    const appointmentId = urlParams.get('appointment_id');
                    
                    if (appointmentId) {
                        // Find and select the appointment in the dropdown
                        const appointmentSelect = document.getElementById('appointment');
                        if (appointmentSelect) {
                            // Check if the option exists
                            const option = Array.from(appointmentSelect.options).find(
                                opt => opt.value === appointmentId
                            );
                            
                            if (option) {
                                appointmentSelect.value = appointmentId;
                                // Trigger the change event to populate the form
                                const event = new Event('change');
                                appointmentSelect.dispatchEvent(event);
                            }
                        }
                    }
                }
            }
            
            // Run on page load
            checkForAppointmentId();
            
            // Also check when hash changes
            window.addEventListener('hashchange', checkForAppointmentId);
        });
    </script>
    
    <script>
        // Function to load customer details when customer is selected in follow-up form
     function loadCustomerDetails(appointmentId) {
            if (!appointmentId) {
                // Clear fields if no appointment selected
                document.getElementById('customer-location').value = '';
                document.getElementById('service-type').value = '';
                
                // Clear technician selections
                const techSelect = document.getElementById('technician-select');
                if (techSelect) {
                    Array.from(techSelect.options).forEach((option, index) => {
                        // Keep the first option (current logged-in tech) selected
                        option.selected = index === 0;
                    });
                }
                return;
            }
            
            // Get the selected option
            const selectedOption = document.querySelector(`#customer-select option[value="${appointmentId}"]`);
            
            if (selectedOption) {
                // Get data from the data attributes
                const serviceId = selectedOption.getAttribute('data-service');
                const location = selectedOption.getAttribute('data-location');
                const technicianId = selectedOption.getAttribute('data-technician');
                const allTechnicianIds = selectedOption.getAttribute('data-all-technicians');
                
                // Set values in form
                document.getElementById('customer-location').value = location || '';
                document.getElementById('service-type').value = serviceId || '';
                
                // Select technicians in the multi-select dropdown
                const techSelect = document.getElementById('technician-select');
                if (techSelect) {
                    // Parse technician IDs (they might be in format "1,2,3")
                    let techIds = [];
                    
                    if (allTechnicianIds && allTechnicianIds.trim()) {
                        techIds = allTechnicianIds.split(',').map(id => id.trim());
                    } else if (technicianId) {
                        techIds = [technicianId];
                    }
                    
                    // First reset all selections except the first option (current user)
                    Array.from(techSelect.options).forEach((option, index) => {
                        // If it's the first option or if its value is in techIds
                        option.selected = (index === 0) || techIds.includes(option.value);
                    });
                }
            }
        }
        
        // Setup the follow-up search functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Create a modal for displaying appointment details
            const modalHTML = `
                <div id="followup-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Scheduled Follow-ups</h3>
                            <span class="close" id="close-followup-modal">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="client-info-summary">
                                <div><strong>Client:</strong> <span id="modal-client-name"></span></div>
                                <div><strong>Service:</strong> <span id="modal-service-name"></span></div>
                                <div><strong>Location:</strong> <span id="modal-location"></span></div>
                            </div>
                            <h4>Follow-up Appointments</h4>
                            <div class="followup-list-container">
                                <table class="nested-appointments-table">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Service</th>
                                            <th>Technician</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modal-followups-list">
                                        <!-- Follow-up details will be inserted here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Add styles for the modal
            const styleElement = document.createElement('style');
            styleElement.textContent = `
                #followup-modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                    z-index: 1000;
                    overflow: auto;
                }
                #followup-modal .modal-content {
                    background-color: #fff;
                    margin: 10% auto;
                    padding: 0;
                    width: 80%;
                    max-width: 800px;
                    border-radius: 8px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                }
                #followup-modal .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px 20px;
                    background: linear-gradient(135deg, #144578, #2a6db5);
                    color: white;
                    border-radius: 8px 8px 0 0;
                }
                #followup-modal .modal-header h3 {
                    margin: 0;
                    font-weight: 600;
                    color: white;
                }
                #followup-modal .close {
                    color: white;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                }
                #followup-modal .modal-body {
                    padding: 20px;
                }
                #followup-modal .client-info-summary {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 10px;
                }
                #followup-modal h4 {
                    margin-top: 0;
                    margin-bottom: 15px;
                    color: #144578;
                }
                #followup-modal .nested-appointments-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                #followup-modal .nested-appointments-table th,
                #followup-modal .nested-appointments-table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #e0e0e0;
                }
                #followup-modal .nested-appointments-table th {
                    background-color: #f0f2f5;
                    color: #333;
                    font-weight: 600;
                }
                #followup-modal .nested-appointments-table tr:hover {
                    background-color: #f9f9f9;
                }
                #followup-modal .status {
                    padding: 5px 10px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    display: inline-block;
                    text-align: center;
                    min-width: 80px;
                }
                #followup-modal .status.scheduled {
                    background-color: #e3f2fd;
                    color: #0d47a1;
                }
            `;
            document.head.appendChild(styleElement);
            
            // Handle view details buttons for follow-up appointments
            const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
            const followupModal = document.getElementById('followup-modal');
            const closeModal = document.getElementById('close-followup-modal');
            
            // Close modal when clicking the X
            if (closeModal) {
                closeModal.onclick = function() {
                    followupModal.style.display = "none";
                }
            }
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == followupModal) {
                    followupModal.style.display = "none";
                }
            }
            
            // Setup view details buttons
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const clientId = this.getAttribute('data-client-id');
                    const clientRow = document.querySelector(`.client-row[data-client-id="${clientId}"]`);
                    const detailsRow = document.getElementById('details-' + clientId);
                    
                    if (clientRow && detailsRow && followupModal) {
                        // Get client information
                        const clientName = clientRow.cells[0].textContent.trim();
                        const serviceName = clientRow.cells[1].textContent.trim();
                        const location = clientRow.cells[2].textContent.trim();
                        
                        // Set modal info
                        document.getElementById('modal-client-name').textContent = clientName;
                        document.getElementById('modal-service-name').textContent = serviceName;
                        document.getElementById('modal-location').textContent = location;
                        
                        // Get and sort follow-ups
                        const modalFollowupsList = document.getElementById('modal-followups-list');
                        modalFollowupsList.innerHTML = '';
                        
                        // Get all follow-up rows
                        const followupRows = detailsRow.querySelectorAll('.nested-followup-row');
                        
                        // Convert to array and sort by date (earliest first)
                        const sortedFollowups = Array.from(followupRows).sort((a, b) => {
                            const dateA = new Date(a.cells[0].textContent.trim());
                            const dateB = new Date(b.cells[0].textContent.trim());
                            return dateA - dateB;
                        });
                        
                        // Add to modal
                        sortedFollowups.forEach(row => {
                            modalFollowupsList.appendChild(row.cloneNode(true));
                        });
                        
                        // Show modal
                        followupModal.style.display = "block";
                    }
                });
            });
            
            // Handle follow-up search
            const followupSearch = document.getElementById('followup-search');
            if (followupSearch) {
                followupSearch.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.client-row');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>