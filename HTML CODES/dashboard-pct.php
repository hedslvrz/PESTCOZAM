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
                                                $stmtExtra = $db->prepare("SELECT treatment_methods, device_installation, chemical_consumables FROM appointments WHERE id = ?");
                                                $stmtExtra->execute([$appointment['appointment_id']]);
                                                $extra = $stmtExtra->fetch(PDO::FETCH_ASSOC);
                                                // Prepare JSON for JS
                                                $treatments = $extra && $extra['treatment_methods'] ? htmlspecialchars($extra['treatment_methods']) : '';
                                                $devices = $extra && $extra['device_installation'] ? htmlspecialchars($extra['device_installation']) : '';
                                                $chemicals = $extra && $extra['chemical_consumables'] ? htmlspecialchars($extra['chemical_consumables']) : '';
                                                echo "data-treatments='$treatments' data-devices='$devices' data-chemicals='$chemicals'";
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
                            </div>
                            
                            <div class="report-grid-2col">
                                <!-- Treatment Method Multiple Inputs -->
                                <div class="field-group" id="treatment-method-group">
                                    <label for="treatment_method[]">Treatment Method</label>
                                    <div id="treatment-method-list">
                                        <input type="text" name="treatment_method[]" class="treatment-method-input" required placeholder="Enter treatment method">
                                    </div>
                                    <button type="button" class="add-more-btn" onclick="addMoreInput('treatment-method-list','treatment_method[]','treatment-method-input')">
                                        <i class='bx bx-plus'></i> Add More
                                    </button>
                                </div>
                                <!-- Device Installation Multiple Inputs -->
                                <div class="field-group" id="device-installation-group">
                                    <label for="device_installation[]">Device Installation</label>
                                    <div id="device-installation-list">
                                        <input type="text" name="device_installation[]" class="device-installation-input" placeholder="Enter device name">
                                    </div>
                                    <button type="button" class="add-more-btn" onclick="addMoreInput('device-installation-list','device_installation[]','device-installation-input')">
                                        <i class='bx bx-plus'></i> Add More
                                    </button>
                                </div>
                                <!-- Consumed Chemicals Multiple Inputs -->
                                <div class="field-group" id="consumed-chemicals-group">
                                    <label for="consumed_chemicals[]">Consumed Chemicals</label>
                                    <div id="consumed-chemicals-list">
                                        <input type="text" name="consumed_chemicals[]" class="consumed-chemicals-input" placeholder="Enter chemical name">
                                    </div>
                                    <button type="button" class="add-more-btn" onclick="addMoreInput('consumed-chemicals-list','consumed_chemicals[]','consumed-chemicals-input')">
                                        <i class='bx bx-plus'></i> Add More
                                    </button>
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
        /* Add More button style */
        .add-more-btn {
            margin-top: 8px;
            background: #e3f2fd;
            color: #144578;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.2s;
        }
        .add-more-btn:hover {
            background: #c1e2fc;
        }
        /* Remove input button style */
        .remove-input-btn {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1.2rem;
            margin-left: 6px;
            cursor: pointer;
            vertical-align: middle;
        }
        .remove-input-btn:hover {
            color: #b71c1c;
        }
        /* Space between dynamic inputs */
        #treatment-method-list input,
        #device-installation-list input,
        #consumed-chemicals-list input {
            margin-bottom: 6px;
            width: 90%;
            display: inline-block;
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

                    // --- Treatment Method, Device, Chemicals autofill as first input ---
                    // Treatment Method
                    const treatmentMethodData = selectedOption.getAttribute('data-treatments');
                    let method = '';
                    try {
                        const parsed = JSON.parse(treatmentMethodData);
                        if (Array.isArray(parsed) && parsed.length > 0) method = parsed[0];
                        else if (typeof parsed === 'string') method = parsed;
                    } catch { method = treatmentMethodData; }
                    const methodList = document.getElementById('treatment-method-list');
                    if (methodList) {
                        methodList.innerHTML = '';
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = 'treatment_method[]';
                        input.className = 'treatment-method-input';
                        input.required = true;
                        input.value = method || '';
                        input.placeholder = 'Enter treatment method';
                        methodList.appendChild(input);
                    }
                    // Device Installation
                    const deviceData = selectedOption.getAttribute('data-devices');
                    let device = '';
                    try {
                        const parsed = JSON.parse(deviceData);
                        if (Array.isArray(parsed) && parsed.length > 0) device = parsed[0];
                        else if (typeof parsed === 'string') device = parsed;
                    } catch { device = deviceData; }
                    const deviceList = document.getElementById('device-installation-list');
                    if (deviceList) {
                        deviceList.innerHTML = '';
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = 'device_installation[]';
                        input.className = 'device-installation-input';
                        input.value = device || '';
                        input.placeholder = 'Enter device name';
                        deviceList.appendChild(input);
                    }
                    // Consumed Chemicals
                    const chemicalData = selectedOption.getAttribute('data-chemicals');
                    let chemical = '';
                    try {
                        const parsed = JSON.parse(chemicalData);
                        if (Array.isArray(parsed) && parsed.length > 0) chemical = parsed[0];
                        else if (typeof parsed === 'string') chemical = parsed;
                    } catch { chemical = chemicalData; }
                    const chemicalList = document.getElementById('consumed-chemicals-list');
                    if (chemicalList) {
                        chemicalList.innerHTML = '';
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = 'consumed_chemicals[]';
                        input.className = 'consumed-chemicals-input';
                        input.value = chemical || '';
                        input.placeholder = 'Enter chemical name';
                        chemicalList.appendChild(input);
                    }
                } else {
                    // Clear fields if "Select an appointment" is chosen
                    document.getElementById('account_name').value = '';
                    document.getElementById('location').value = '';
                    document.getElementById('contact_no').value = '';
                    document.getElementById('treatment_type').selectedIndex = 0;
                    document.getElementById('time_in').value = '';
                    document.getElementById('time_out').value = '';
                    document.getElementById('treatment-method-list').innerHTML = '<input type="text" name="treatment_method[]" class="treatment-method-input" required placeholder="Enter treatment method">';
                    document.getElementById('device-installation-list').innerHTML = '<input type="text" name="device_installation[]" class="device-installation-input" placeholder="Enter device name">';
                    document.getElementById('consumed-chemicals-list').innerHTML = '<input type="text" name="consumed_chemicals[]" class="consumed-chemicals-input" placeholder="Enter chemical name">';
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

            // Add More Input Functionality
            function addMoreInput(containerId, inputName, inputClass) {
                const container = document.getElementById(containerId);
                const input = document.createElement('div');
                input.style.display = 'flex';
                input.style.alignItems = 'center';
                input.style.gap = '4px';

                const textInput = document.createElement('input');
                textInput.type = 'text';
                textInput.name = inputName;
                textInput.className = inputClass;
                textInput.placeholder = 'Enter value';
                textInput.required = (inputName === 'treatment_method[]'); // Only treatment method is required

                // Remove button
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-input-btn';
                removeBtn.innerHTML = '<i class="bx bx-x"></i>';
                removeBtn.onclick = function() {
                    container.removeChild(input);
                };

                input.appendChild(textInput);
                input.appendChild(removeBtn);
                container.appendChild(input);
            }
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
                
                <!-- Tab Navigation -->
                <div class="tabs-container">
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="one-time-tab">Follow-up Appointment</button>
                        <button class="tab-btn" data-tab="recurrence-tab">Recurrence Plan</button>
                        <button class="tab-btn" data-tab="plan-visits-tab">Plan Visits</button>
                    </div>
                </div>
                
                <div class="followup-grid">
                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- One-Time Visit Tab -->
                        <div id="one-time-tab" class="tab-pane active">
                            <!-- Main Schedule Content -->
                            <div class="calendar-container">
                                <!-- FOLLOW-UP FORM -->
                                <form action="../HTML CODES/schedule_followup-pct.php" method="POST" class="settings-card no-hover">
                                    <input type="hidden" name="is_recurrence" value="0">
                                    <input type="hidden" name="plan_type" value="one-time">
                                    <div class="card-header">
                                        <i class='bx bx-calendar-edit'></i>
                                        <h4>Follow-up Appointment</h4>
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
                                                    <select id="technician-select" name="technician_id[]" required multiple class="enhanced-select">
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
                                        
                                        <!-- Notes Field -->
                                        <div class="form-group">
                                            <label>Notes:</label>
                                            <textarea id="followup-notes" name="notes" rows="3" placeholder="Enter any special instructions or notes for this follow-up..."></textarea>
                                        </div>
                                    </div>
                                    <div class="schedule-actions-centered">
                                        <button type="submit" class="btn-submit">
                                            <i class='bx bx-calendar-check'></i> Schedule One-Time Follow-up
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Recurrence Plan Tab -->
                        <div id="recurrence-tab" class="tab-pane">
                            <div class="calendar-container">
                                <!-- RECURRENCE PLAN FORM -->
                                <form action="../HTML CODES/schedule_followup-pct.php" method="POST" class="settings-card no-hover">
                                    <input type="hidden" name="is_recurrence" value="1">
                                    <div class="card-header">
                                        <i class='bx bx-calendar-check'></i>
                                        <h4>Maintenance Agreement</h4>
                                    </div>
                                    <div class="plan-frequency">
                                        <!-- Customer Selection - Reuse the query from above -->
                                        <div class="form-group">
                                            <label>Select Customer's Last Appointment:</label>
                                            <select id="rec-customer-select" name="appointment_id" required onchange="loadCustomerDetailsForRec(this.value)">
                                                <option value="" disabled selected>Select Customer</option>
                                                <?php 
                                                try {
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
                                                    error_log("Error in customer query for PCT follow-up recurrence: " . $e->getMessage());
                                                    echo '<option value="">Error loading customers: ' . $e->getMessage() . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Service Type -->
                                        <div class="form-group">
                                            <label>Service Type:</label>
                                            <select id="rec-service-type" name="service_id" required>
                                                <option value="">Select Service</option>
                                                <?php 
                                                try {
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
                                            <input type="text" id="rec-customer-location" readonly>
                                        </div>
                                        
                                        <!-- Technician Selection -->
                                        <div class="form-group">
                                            <label>Assign Technician:</label>
                                            <div class="tech-selection-wrapper">
                                                <div class="tech-selection-container">
                                                    <select id="rec-technician-select" name="technician_id[]" required multiple class="enhanced-select">
                                                        <?php
                                                        try {
                                                            // Get current technician
                                                            if ($currentTech) {
                                                                echo '<option value="' . htmlspecialchars($currentTech['id']) . '" selected>' . 
                                                                    htmlspecialchars($currentTech['firstname'] . ' ' . $currentTech['lastname']) . ' (You)</option>';
                                                            }
                                                            
                                                            // Get all other technicians
                                                            if (!empty($technicians)) {
                                                                foreach ($technicians as $tech) {
                                                                    echo '<option value="' . htmlspecialchars($tech['id']) . '">' . 
                                                                        htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']) . '</option>';
                                                                }
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

                                        <!-- Plan Settings -->
                                        <div class="form-group">
                                            <label>Plan Type:</label>
                                            <select id="rec-plan-type" name="plan_type" required onchange="updateFrequencyOptions()">
                                                <option value="">Select Plan Type</option>
                                                <option value="monthly">Monthly Visit</option>
                                                <option value="quarterly">Quarterly Visit</option>
                                                <option value="yearly">Yearly Visit</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Start Date -->
                                        <div class="form-group">
                                            <label>Start Date:</label>
                                            <input type="date" id="rec-start-date" name="followup_date" required min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        
                                        <!-- Follow-up Time -->
                                        <div class="form-group">
                                            <label>Preferred Time for All Visits:</label>
                                            <select id="rec-followup-time" name="followup_time" required>
                                                <option value="">Select Time</option>
                                                <option value="07:00:00">7:00 AM - 9:00 AM</option>
                                                <option value="09:00:00">9:00 AM - 11:00 AM</option>
                                                <option value="11:00:00">11:00 AM - 1:00 PM</option>
                                                <option value="13:00:00">1:00 PM - 3:00 PM</option>
                                                <option value="15:00:00">3:00 PM - 5:00 PM</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Visit Frequency -->
                                        <div class="form-group">
                                            <label>Visit Frequency:</label>
                                            <select id="rec-visit-frequency" name="visit_frequency" required>
                                                <option value="">Select Frequency</option>
                                                <!-- Options will be populated via JavaScript -->
                                            </select>
                                        </div>
                                        
                                        <!-- Contract Duration -->
                                        <div class="form-group">
                                            <label>Contract Duration:</label>
                                            <div class="duration-input-group">
                                                <input type="number" id="rec-contract-duration" name="contract_duration" min="1" value="6" required>
                                                <select id="rec-duration-unit" name="duration_unit">
                                                    <option value="months" selected>Months</option>
                                                    <option value="years">Years</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <!-- Notes Field -->
                                        <div class="form-group">
                                            <label>Notes:</label>
                                            <textarea id="rec-followup-notes" name="notes" rows="3" placeholder="Enter any special instructions or notes for this maintenance plan..."></textarea>
                                        </div>

                                        <!-- Preview Section -->
                                        <div class="form-group">
                                            <div class="preview-visits-container" id="preview-container" style="display:none;">
                                                <h4>Visit Schedule Preview</h4>
                                                <div id="visits-preview" class="visits-preview-list"></div>
                                                <!-- Add hidden input to store all generated visit dates -->
                                                <input type="hidden" name="visit_dates" id="visit-dates-json">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="schedule-actions-centered">
                                        <button type="button" class="btn-preview" onclick="previewVisits()">
                                            <i class='bx bx-calendar-check'></i> Preview Visits
                                        </button>
                                        <button type="submit" class="btn-submit">
                                            <i class='bx bx-calendar-check'></i> Create Maintenance Plan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Plan Visits Tab -->
                        <div id="plan-visits-tab" class="tab-pane">
                            <div class="settings-card no-hover">
                                <div class="card-header">
                                    <i class='bx bx-list-check'></i>
                                    <h4>Maintenance Plans</h4>
                                </div>
                                <div class="search-box">
                                    <i class='bx bx-search'></i>
                                    <input type="text" id="plan-search" placeholder="Search maintenance plans...">
                                </div>
                                <div class="table-wrapper scrollable-table">
                                    <table class="maintenance-plans-table">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Service</th>
                                                <th>Plan Type</th>
                                                <th>Start Date</th>
                                                <th>Duration</th>
                                                <th>Visits Scheduled</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="maintenance-plans-list">
                                            <?php
                                            try {
                                                $plansQuery = "SELECT 
                                                    fp.id as plan_id, 
                                                    fp.appointment_id,
                                                    fp.plan_type,
                                                    fp.visit_frequency,
                                                    fp.contract_duration,
                                                    fp.duration_unit,
                                                    fp.notes,
                                                    fp.created_at,
                                                    a.service_id,
                                                    s.service_name,
                                                    CASE 
                                                        WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                                                        ELSE CONCAT(a.firstname, ' ', a.lastname)
                                                    END as customer_name,
                                                    CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location,
                                                    (SELECT COUNT(*) FROM followup_visits fv WHERE fv.followup_plan_id = fp.id) as visit_count,
                                                    (SELECT MIN(fv2.visit_date) FROM followup_visits fv2 WHERE fv2.followup_plan_id = fp.id) as start_date
                                                FROM followup_plan fp
                                                JOIN appointments a ON fp.appointment_id = a.id
                                                JOIN users u ON a.user_id = u.id
                                                JOIN services s ON a.service_id = s.service_id
                                                LEFT JOIN appointment_technicians att ON a.id = att.appointment_id
                                                WHERE (fp.created_by = :user_id OR a.technician_id = :tech_id OR att.technician_id = :tech_id2)
                                                AND fp.plan_type IN ('monthly', 'quarterly', 'yearly')
                                                GROUP BY fp.id
                                                ORDER BY fp.created_at DESC";
                                                
                                                $stmt = $db->prepare($plansQuery);
                                                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                                $stmt->bindParam(':tech_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                                $stmt->bindParam(':tech_id2', $_SESSION['user_id'], PDO::PARAM_INT);
                                                $stmt->execute();
                                                $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                if (!empty($plans)) {
                                                    foreach ($plans as $plan) {
                                                        $formattedDuration = $plan['contract_duration'] . ' ' . 
                                                            ($plan['contract_duration'] == 1 ? rtrim($plan['duration_unit'], 's') : $plan['duration_unit']);
                                                        
                                                        echo '<tr class="plan-row" data-plan-id="' . $plan['plan_id'] . '">';
                                                        echo '<td>' . htmlspecialchars($plan['customer_name']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($plan['service_name']) . '</td>';
                                                        echo '<td>' . ucfirst(htmlspecialchars($plan['plan_type'])) . '</td>';
                                                        echo '<td>' . ($plan['start_date'] ? date('M d, Y', strtotime($plan['start_date'])) : 'Not set') . '</td>';
                                                        echo '<td>' . htmlspecialchars($formattedDuration) . '</td>';
                                                        echo '<td>' . $plan['visit_count'] . ' visits</td>';
                                                        echo '<td><button class="view-plan-btn" data-plan-id="' . $plan['plan_id'] . '">View Schedule</button></td>';
                                                        echo '</tr>';
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="7">No maintenance plans found</td></tr>';
                                                }
                                            } catch(PDOException $e) {
                                                error_log("Error loading maintenance plans: " . $e->getMessage());
                                                echo '<tr><td colspan="7">Error loading maintenance plans: ' . $e->getMessage() . '</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Appointments Card (moved outside the tabs) -->
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
                            <!-- Existing followups table -->
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
        </main>
        
        <!-- Add CSS for tabs -->
        <style>
            .tabs-container {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .tabs {
                display: flex;
                border-bottom: 2px solid #e0e0e0;
                margin-bottom: 20px;
            }
            
            .tab-btn {
                padding: 10px 20px;
                background: none;
                border: none;
                border-bottom: 2px solid transparent;
                margin-bottom: -2px;
                cursor: pointer;
                font-weight: 600;
                color: #666;
                transition: all 0.3s ease;
            }
            
            .tab-btn:hover {
                color: #144578;
            }
            
            .tab-btn.active {
                color: #144578;
                border-bottom: 2px solid #144578;
            }
            
            .tab-content {
                width: 100%;
            }
            
            .tab-pane {
                display: none;
            }
            
            .tab-pane.active {
                display: block;
            }
            
            .btn-preview {
                background: linear-gradient(135deg, #009688, #4CAF50);
                color: white;
                padding: 15px 30px;
                border-radius: 12px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-size: 16px;
                border: none;
                box-shadow: 0 6px 15px rgba(76, 175, 80, 0.2);
                margin-right: 15px;
            }
            
            .btn-preview:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
            }
            
            .preview-visits-container {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
                margin-top: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .visits-preview-list {
                max-height: 300px;
                overflow-y: auto;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                padding: 10px;
                background-color: white;
            }
            
            .preview-visit-item {
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 8px;
                background-color: #e3f2fd;
                border-left: 4px solid #2196F3;
                font-size: 14px;
            }
            
            .preview-visit-item:last-child {
                margin-bottom: 0;
            }
            
            .view-plan-btn {
                background-color: #144578;
                color: white;
                border: none;
                border-radius: 4px;
                padding: 6px 12px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            
            .view-plan-btn:hover {
                background-color: #0d335d;
            }
            
            .maintenance-plans-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .maintenance-plans-table th,
            .maintenance-plans-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .maintenance-plans-table th {
                background-color: #f8f9fa;
                color: #144578;
                font-weight: 600;
            }
            
            .schedule-actions-centered {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-top: 25px;
            }
        </style>
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
                    <img src="../Pictures/tech-profile.jpg" alt="User Avatar" class="avatar" />
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
                        <p><strong>Date of Birth:</strong> <?php echo $technician['dob'] ? date('m-d-Y', strtotime($technician['dob'])) : 'Not set'; ?></p>
                    </div>
                    <div class="info-row">
                        <p><strong>Email:</strong> <span data-field="email"><?php echo htmlspecialchars($technician['email']); ?></span></p>
                        <p><strong>Phone Number:</strong> <span data-field="mobile_number"><?php echo htmlspecialchars($technician['mobile_number']); ?></span></p>
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
                        <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($technician['role'])); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($technician['status'])); ?></p>
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

    <!-- Add JavaScript for profile functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize profile editor functionality
        initProfileEditor();
    });

    function initProfileEditor() {
        const editProfileBtn = document.getElementById('openProfileModalBtn');
        const profileModal = document.getElementById('profileModal');
        const closeBtn = profileModal ? profileModal.querySelector('.close') : null;
        const cancelBtn = document.getElementById('closeProfileModalBtn');
        const profileForm = document.getElementById('editProfileForm');
        
        if (editProfileBtn && profileModal && profileForm) {
            editProfileBtn.addEventListener('click', function() {
                openProfileModal();
            });
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    closeProfileModal();
                });
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    closeProfileModal();
                });
            }
            window.addEventListener('click', function(event) {
                if (event.target === profileModal) {
                    closeProfileModal();
                }
            });
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && profileModal.classList.contains('show')) {
                    closeProfileModal();
                }
            });
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                updateUserProfile(this);
            });
        }
    }

    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        if (!modal) return;
        modal.style.display = 'flex';
        // Force reflow then add show class for transition
        void modal.offsetWidth;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeProfileModal() {
        const modal = document.getElementById('profileModal');
        if (!modal) return;
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    function updateUserProfile(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        
        fetch('../PHP CODES/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: 'Profile updated successfully!',
                    icon: 'success',
                    confirmButtonColor: '#144578'
                });
                updateProfileDisplay(formData);
                
                // Ensure button is reset before closing modal
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                
                closeProfileModal();
                // Clear POST data by replacing state if needed
                history.replaceState(null, null, location.pathname);
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Update failed',
                    icon: 'error',
                    confirmButtonColor: '#144578'
                });
                // Reset button state on error
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while updating profile',
                icon: 'error',
                confirmButtonColor: '#144578'
            });
            // Reset button state on error
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }

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
    </script>
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
            
            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding pane
                    button.classList.add('active');
                    const targetTabId = button.getAttribute('data-tab');
                    document.getElementById(targetTabId).classList.add('active');
                });
            });
            
            // Function to load customer details for recurrence plan
            window.loadCustomerDetailsForRec = function(appointmentId) {
                if (!appointmentId) {
                    // Clear fields if no appointment selected
                    document.getElementById('rec-customer-location').value = '';
                    document.getElementById('rec-service-type').value = '';
                    
                    // Clear technician selections
                    const techSelect = document.getElementById('rec-technician-select');
                    if (techSelect) {
                        Array.from(techSelect.options).forEach((option, index) => {
                            // Keep the first option (current logged-in tech) selected
                            option.selected = index === 0;
                        });
                    }
                    return;
                }
                
                // Get the selected option
                const selectedOption = document.querySelector(`#rec-customer-select option[value="${appointmentId}"]`);
                
                if (selectedOption) {
                    // Get data from the data attributes
                    const serviceId = selectedOption.getAttribute('data-service');
                    const location = selectedOption.getAttribute('data-location');
                    const technicianId = selectedOption.getAttribute('data-technician');
                    const allTechnicianIds = selectedOption.getAttribute('data-all-technicians');
                    
                    // Set values in form
                    document.getElementById('rec-customer-location').value = location || '';
                    document.getElementById('rec-service-type').value = serviceId || '';
                    
                    // Select technicians in the multi-select dropdown
                    const techSelect = document.getElementById('rec-technician-select');
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
                            option.selected = (index === 0) || techIds.includes(option.value);
                        });
                    }
                }
            };
            
            // Function to update frequency options based on plan type
            window.updateFrequencyOptions = function() {
                const planType = document.getElementById('rec-plan-type').value;
                const frequencySelect = document.getElementById('rec-visit-frequency');
                
                // Clear existing options
                frequencySelect.innerHTML = '<option value="">Select Frequency</option>';
                
                if (planType) {
                    // Add appropriate options based on plan type
                    switch (planType) {
                        case 'monthly':
                            addFrequencyOptions(frequencySelect, [
                                { value: 1, text: 'Once a month' },
                                { value: 2, text: 'Twice a month' }
                            ]);
                            break;
                        case 'quarterly':
                            addFrequencyOptions(frequencySelect, [
                                { value: 1, text: 'Once every three months' }
                            ]);
                            break;
                        case 'yearly':
                            addFrequencyOptions(frequencySelect, [
                                { value: 1, text: 'Once a year' },
                                { value: 2, text: 'Twice a year' }
                            ]);
                            break;
                    }
                }
            };
            
            // Helper function to add frequency options
            function addFrequencyOptions(selectElement, options) {
                options.forEach(option => {
                    const optElement = document.createElement('option');
                    optElement.value = option.value;
                    optElement.textContent = option.text;
                    selectElement.appendChild(optElement);
                });
            }
            
            // Function to preview visits for recurrence plan
            window.previewVisits = function() {
                const planType = document.getElementById('rec-plan-type').value;
                const startDate = document.getElementById('rec-start-date').value;
                const visitFrequency = document.getElementById('rec-visit-frequency').value;
                const contractDuration = document.getElementById('rec-contract-duration').value;
                const durationUnit = document.getElementById('rec-duration-unit').value;
                const followupTime = document.getElementById('rec-followup-time').value;
                
                if (!planType || !startDate || !visitFrequency || !contractDuration || !durationUnit || !followupTime) {
                    alert('Please fill in all required fields to preview visits');
                    return;
                }
                
                // Generate visit dates based on plan settings
                const visits = generateVisitDates(planType, visitFrequency, contractDuration, durationUnit, startDate, followupTime);
                
                // Display the preview
                const previewContainer = document.getElementById('preview-container');
                const visitsPreview = document.getElementById('visits-preview');
                
                visitsPreview.innerHTML = '';
                
                if (visits.length === 0) {
                    visitsPreview.innerHTML = '<div class="preview-visit-item">No visits generated based on current settings.</div>';
                } else {
                    visits.forEach((visit, index) => {
                        const visitDate = new Date(visit.date);
                        const formattedDate = visitDate.toLocaleDateString('en-US', { 
                            weekday: 'long',
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric'
                        });
                        
                        const visitItem = document.createElement('div');
                        visitItem.className = 'preview-visit-item';
                        visitItem.innerHTML = `<strong>Visit ${index + 1}:</strong> ${formattedDate} at ${formatTimeDisplay(visit.time)}`;
                        visitsPreview.appendChild(visitItem);
                    });
                    
                    // Store all visit dates in the hidden input as JSON
                    document.getElementById('visit-dates-json').value = JSON.stringify(visits);
                }
                
                previewContainer.style.display = 'block';
            };
            
            // Function to generate visit dates
            function generateVisitDates(planType, frequency, duration, durationUnit, startDate, time) {
                const visits = [];
                frequency = parseInt(frequency);
                duration = parseInt(duration);
                
                // Calculate the total number of visits
                let totalVisits;
                
                if (planType === 'monthly' && durationUnit === 'months') {
                    // Frequency is visits per month
                    totalVisits = duration * frequency;
                } else if (planType === 'quarterly' && durationUnit === 'months') {
                    // Frequency is visits per quarter (3 months)
                    totalVisits = Math.floor(duration / 3) * frequency;
                } else if (planType === 'yearly' && durationUnit === 'years') {
                    // Frequency is visits per year
                    totalVisits = duration * frequency;
                } else {
                    // Handle mixed units by converting to days
                    const periodLengthInDays = {
                        'monthly': 30,
                        'quarterly': 90,
                        'yearly': 365
                    }[planType];
                    
                    const durationInDays = {
                        'days': duration,
                        'weeks': duration * 7,
                        'months': duration * 30,
                        'years': duration * 365
                    }[durationUnit];
                    
                    const numberOfPeriods = Math.floor(durationInDays / periodLengthInDays);
                    totalVisits = numberOfPeriods * frequency;
                }
                
                // Generate visit dates
                const startDateObj = new Date(startDate);
                
                if (planType === 'monthly') {
                    // For monthly plans, distribute visits evenly across the month
                    let currentMonth = startDateObj.getMonth();
                    let currentYear = startDateObj.getFullYear();
                    const dayOfMonth = startDateObj.getDate();
                    
                    // First visit is always the start date
                    visits.push({
                        date: startDateObj.toISOString().split('T')[0],
                        time: time
                    });
                    
                    // Calculate subsequent visits
                    for (let i = 1; i < totalVisits; i++) {
                        let nextDate;
                        
                        if (i % frequency === 0) {
                            // Move to next month
                            currentMonth++;
                            if (currentMonth > 11) {
                                currentMonth = 0;
                                currentYear++;
                            }
                            nextDate = new Date(currentYear, currentMonth, dayOfMonth);
                        } else {
                            // Calculate days between visits within the same month
                            // For frequency=2, this splits the month in half
                            // For frequency=3, this divides the month into thirds, etc.
                            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                            const dayIncrement = Math.floor(daysInMonth / frequency);
                            
                            if (i % frequency === 1) {
                                // First visit of the month was already set to the original date
                                // For the second visit, add the increment to the day
                                nextDate = new Date(currentYear, currentMonth, dayOfMonth + dayIncrement);
                                
                                // Check if we crossed into next month
                                if (nextDate.getMonth() !== currentMonth) {
                                    nextDate = new Date(currentYear, currentMonth + 1, 1);
                                }
                            } else {
                                // For additional visits in the month, add another increment
                                const previousVisit = new Date(visits[visits.length - 1].date);
                                nextDate = new Date(previousVisit);
                                nextDate.setDate(previousVisit.getDate() + dayIncrement);
                                
                                // Check if we crossed into next month
                                if (nextDate.getMonth() !== previousVisit.getMonth()) {
                                    nextDate = new Date(currentYear, currentMonth + 1, 1);
                                }
                            }
                        }
                        
                        // Skip Sundays
                        if (nextDate.getDay() === 0) {
                            nextDate.setDate(nextDate.getDate() + 1);
                        }
                        
                        visits.push({
                            date: nextDate.toISOString().split('T')[0],
                            time: time
                        });
                    }
                } else {
                    // For other plan types, distribute visits evenly
                    let visitDates = [];
                    
                    // First visit is the start date
                    visitDates.push(new Date(startDateObj));
                    
                    // Calculate period length and interval between visits
                    let periodLength;
                    if (planType === 'quarterly') periodLength = 90;
                    else if (planType === 'yearly') periodLength = 365;
                    
                    const intervalDays = Math.floor(periodLength / frequency);
                    
                    // Generate dates for subsequent visits
                    let currentDate = new Date(startDateObj);
                    for (let i = 1; i < totalVisits; i++) {
                        if (i % frequency === 0) {
                            // Move to next period
                            if (planType === 'quarterly') {
                                currentDate.setMonth(currentDate.getMonth() + 3);
                            } else if (planType === 'yearly') {
                                currentDate.setFullYear(currentDate.getFullYear() + 1);
                            } else {
                                currentDate.setDate(currentDate.getDate() + periodLength);
                            }
                            visitDates.push(new Date(currentDate));
                        } else {
                            // Add interval days
                            let nextDate = new Date(visitDates[visitDates.length - 1]);
                            nextDate.setDate(nextDate.getDate() + intervalDays);
                            
                            // Skip Sundays
                            if (nextDate.getDay() === 0) {
                                nextDate.setDate(nextDate.getDate() + 1);
                            }
                            
                            visitDates.push(nextDate);
                        }
                    }
                    
                    // Convert dates to required format
                    visits.push(...visitDates.map(date => ({
                        date: date.toISOString().split('T')[0],
                        time: time
                    })));
                }
                
                return visits;
            }
            
            // Format time for display
            function formatTimeDisplay(time) {
                if (!time) return '';
                
                const timeOptions = {
                    '07:00:00': '7:00 AM - 9:00 AM',
                    '09:00:00': '9:00 AM - 11:00 AM',
                    '11:00:00': '11:00 AM - 1:00 PM',
                    '13:00:00': '1:00 PM - 3:00 PM',
                    '15:00:00': '3:00 PM - 5:00 PM'
                };
                
                return timeOptions[time] || time;
            }
            
            // Plan search functionality
            const planSearch = document.getElementById('plan-search');
            if (planSearch) {
                planSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const planRows = document.querySelectorAll('#maintenance-plans-list tr');
                    
                    planRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
            
            // Handle view plan button clicks
            const viewPlanButtons = document.querySelectorAll('.view-plan-btn');
            viewPlanButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const planId = this.getAttribute('data-plan-id');
                    fetchPlanDetails(planId);
                });
            });
            
            // Function to fetch plan details and visits
            function fetchPlanDetails(planId) {
                fetch(`../PHP CODES/get_plan_details.php?plan_id=${planId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.error,
                                confirmButtonColor: '#144578'
                            });
                            return;
                        }

                        // Format the visits into an HTML table
                        const visitsHtml = data.visits.map(visit => {
                            // Create status badge with appropriate color
                            let statusClass = 'badge-primary';
                            if (visit.status.toLowerCase() === 'completed') statusClass = 'badge-success';
                            if (visit.status.toLowerCase() === 'cancelled') statusClass = 'badge-danger';
                            
                            return `
                                <tr>
                                    <td><i class='bx bx-calendar'></i> ${visit.visit_date}</td>
                                    <td><i class='bx bx-time'></i> ${visit.visit_time}</td>
                                    <td><i class='bx bx-user'></i> ${visit.technician_name}</td>
                                    <td><span class="status-badge ${statusClass}">${visit.status}</span></td>
                                </tr>
                            `;
                        }).join('');

                        // Format the plan type with appropriate icon
                        let planTypeIcon = 'bx-calendar';
                        if (data.plan.plan_type === 'monthly') planTypeIcon = 'bx-calendar-check';
                        if (data.plan.plan_type === 'quarterly') planTypeIcon = 'bx-calendar-star';
                        if (data.plan.plan_type === 'yearly') planTypeIcon = 'bx-calendar-event';

                        // Show the plan details and visits in a SweetAlert2 modal with improved design
                        Swal.fire({
                            title: '<i class="bx bx-list-check"></i> Maintenance Plan Schedule',
                            html: `
                                <div class="plan-schedule-modal">
                                    <div class="plan-details-card">
                                        <div class="card-header">
                                            <i class='bx bx-info-circle'></i>
                                            <h4>Plan Details</h4>
                                        </div>
                                        <div class="plan-details-content">
                                            <div class="details-row">
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class='bx bx-user'></i> Client:</span>
                                                    <span class="detail-value">${data.plan.customer_name}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class='bx bx-package'></i> Service:</span>
                                                    <span class="detail-value">${data.plan.service_name}</span>
                                                </div>
                                            </div>
                                            <div class="details-row">
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class='bx bx-map'></i> Location:</span>
                                                    <span class="detail-value">${data.plan.location}</span>
                                                </div>
                                            </div>
                                            <div class="details-row">
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class='bx ${planTypeIcon}'></i> Plan Type:</span>
                                                    <span class="detail-value">${data.plan.plan_type.charAt(0).toUpperCase() + data.plan.plan_type.slice(1)}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class='bx bx-time'></i> Frequency:</span>
                                                    <span class="detail-value">${data.plan.visit_frequency} visits</span>
                                                </div>
                                            </div>
                                            <div class="details-row">
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class='bx bx-calendar-alt'></i> Duration:</span>
                                                    <span class="detail-value">${data.plan.contract_duration} ${data.plan.duration_unit}</span>
                                                </div>
                                            </div>
                                            ${data.plan.notes ? `
                                            <div class="details-row notes-row">
                                                <div class="detail-item full-width">
                                                    <span class="detail-label"><i class='bx bx-note'></i> Notes:</span>
                                                    <span class="detail-value notes">${data.plan.notes}</span>
                                                </div>
                                            </div>` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="visits-table-card">
                                        <div class="card-header">
                                            <i class='bx bx-calendar-check'></i>
                                            <h4>Scheduled Visits</h4>
                                        </div>
                                        <div class="table-container">
                                            <table class="visits-table">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Time</th>
                                                        <th>Technician</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${visitsHtml || '<tr><td colspan="4" class="no-data">No visits scheduled</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <style>
                                    .plan-schedule-modal {
                                        font-family: 'Poppins', sans-serif;
                                        color: #333;
                                    }
                                    .plan-details-card, .visits-table-card {
                                        background-color: #fff;
                                        border-radius: 8px;
                                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                        margin-bottom: 20px;
                                        overflow: hidden;
                                    }
                                    .card-header {
                                        background-color: #144578;
                                        color: white;
                                        padding: 12px 16px;
                                        display: flex;
                                        align-items: center;
                                        gap: 10px;
                                    }
                                    .card-header h4 {
                                        margin: 0;
                                        font-size: 16px;
                                        font-weight: 600;
                                    }
                                    .plan-details-content {
                                        padding: 16px;
                                    }
                                    .details-row {
                                        display: flex;
                                        margin-bottom: 12px;
                                        flex-wrap: wrap;
                                        gap: 15px;
                                    }
                                    .detail-item {
                                        flex: 1 1 45%;
                                        min-width: 200px;
                                    }
                                    .detail-item.full-width {
                                        flex: 1 1 100%;
                                    }
                                    .detail-label {
                                        font-weight: 600;
                                        color: #555;
                                        display: flex;
                                        align-items: center;
                                        gap: 6px;
                                        margin-bottom: 4px;
                                    }
                                    .detail-value {
                                        color: #333;
                                    }
                                    .detail-value.notes {
                                        display: block;
                                        background-color: #f9f9f9;
                                        border-left: 3px solid #144578;
                                        padding: 8px 12px;
                                        margin-top: 6px;
                                        font-style: italic;
                                    }
                                    .notes-row {
                                        margin-top: 16px;
                                        border-top: 1px dashed #ddd;
                                        padding-top: 16px;
                                    }
                                    .table-container {
                                        padding: 16px;
                                        max-height: 300px;
                                        overflow-y: auto;
                                    }
                                    .visits-table {
                                        width: 100%;
                                        border-collapse: collapse;
                                    }
                                    .visits-table th, .visits-table td {
                                        padding: 10px;
                                        text-align: left;
                                        border-bottom: 1px solid #eee;
                                    }
                                    .visits-table th {
                                        background-color: #f5f5f5;
                                        font-weight: 600;
                                        color: #144578;
                                    }
                                    .visits-table tr:hover {
                                        background-color: #f9f9f9;
                                    }
                                    .status-badge {
                                        display: inline-block;
                                        padding: 4px 8px;
                                        border-radius: 12px;
                                        font-size: 12px;
                                        font-weight: 500;
                                        text-transform: capitalize;
                                    }
                                    .badge-primary {
                                        background-color: #5c87af;
                                        color: white;
                                    }
                                    .badge-success {
                                        background-color: #4CAF50;
                                        color: white;
                                    }
                                    .badge-danger {
                                        background-color: #f44336;
                                        color: white;
                                    }
                                    .no-data {
                                        text-align: center;
                                        padding: 20px;
                                        color: #777;
                                        font-style: italic;
                                    }
                                    
                                    /* Responsive styling for smaller screens */
                                    @media screen and (max-width: 576px) {
                                        .detail-item {
                                            flex: 1 1 100%;
                                        }
                                    }
                                </style>
                            `,
                            width: '800px',
                            confirmButtonText: 'Close',
                            confirmButtonColor: '#144578',
                            customClass: {
                                container: 'plan-schedule-modal-container',
                                popup: 'plan-schedule-modal-popup',
                                title: 'plan-schedule-modal-title'
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching plan details:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch plan details. Please try again later.',
                            confirmButtonColor: '#144578'
                        });
                    });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Add event listeners to all "View Details" buttons
            const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const clientId = this.getAttribute('data-client-id');
                    const detailsRow = document.getElementById(`details-${clientId}`);
                    
                    if (detailsRow) {
                        // Toggle visibility of the details row
                        const isVisible = detailsRow.style.display === 'table-row';
                        detailsRow.style.display = isVisible ? 'none' : 'table-row';
                        
                        // Update button text
                        this.textContent = isVisible ? 'View Details' : 'Hide Details';
                    }
                });
            });
        });
    </script>
    
    <script>
        // Function to load customer details when customer is selected in follow-up form
        // ...existing code...
    </script>
</body>
</html>