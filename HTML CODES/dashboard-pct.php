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
        CASE 
            WHEN a.is_for_self = 1 THEN u.mobile_number
            ELSE a.mobile_number
        END as client_mobile,
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
    
    // Get only confirmed appointments for the report dropdown
    $confirmedAppointmentsQuery = "SELECT 
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
        CASE 
            WHEN a.is_for_self = 1 THEN u.mobile_number
            ELSE a.mobile_number
        END as client_mobile,
        s.service_name,
        s.service_id
    FROM appointments a
    INNER JOIN services s ON a.service_id = s.service_id
    INNER JOIN users u ON a.user_id = u.id
    WHERE a.technician_id = ? AND LOWER(a.status) = 'confirmed'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    $stmt = $db->prepare($confirmedAppointmentsQuery);
    $stmt->execute([$_SESSION['user_id']]);
    $confirmedAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $technician = null;
    $assignments = [];
    $confirmedAppointments = [];
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
                                        <option value="<?php echo $appointment['appointment_id']; ?>" 
                                                data-client="<?php echo htmlspecialchars($appointment['client_firstname'] . ' ' . $appointment['client_lastname']); ?>"
                                                data-location="<?php echo htmlspecialchars(implode(', ', array_filter([$appointment['street_address'], $appointment['barangay'], $appointment['city']]))); ?>"
                                                data-service="<?php echo htmlspecialchars($appointment['service_name']); ?>"
                                                data-contact="<?php echo htmlspecialchars($appointment['client_mobile'] ?? ''); ?>">
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
                                <div class="field-group">
                                    <label for="treatment_type">Treatment Type</label>
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
                                <div class="field-group">
                                    <label for="pest_count">Pest Count (if applicable)</label>
                                    <input type="text" name="pest_count" id="pest_count" placeholder="e.g., 15 roaches, 3 rats">
                                </div>
                                <div class="field-group">
                                    <label for="frequency_of_visits">Frequency of Visits</label>
                                    <select name="frequency_of_visits" id="frequency_of_visits">
                                        <option value="">Select Recommended Frequency</option>
                                        <option value="One-time">One-time</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Bi-weekly">Bi-weekly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Semi-annually">Semi-annually</option>
                                        <option value="Annually">Annually</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="report-grid-2col">
                                <div class="field-group">
                                    <label for="treatment_method">Treatment Method/Description</label>
                                    <textarea name="treatment_method" id="treatment_method" rows="3" required placeholder="Describe the treatment methods used in detail..."></textarea>
                                </div>
                                <div class="field-group">
                                    <label for="device_installation">Device Installation (if any)</label>
                                    <textarea name="device_installation" id="device_installation" rows="3" placeholder="Describe any devices installed or set..."></textarea>
                                </div>
                                <div class="field-group">
                                    <label for="consumed_chemicals">Consumed Chemicals/Products</label>
                                    <textarea name="consumed_chemicals" id="consumed_chemicals" rows="3" placeholder="List chemicals used with quantities..."></textarea>
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
                                    <small>Upload up to 5 photos (Max 2MB each). Include before/after photos if available.</small>
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
                    
                    // Fill in the form fields
                    document.getElementById('account_name').value = clientName;
                    document.getElementById('location').value = location;
                    document.getElementById('contact_no').value = contactNo;
                    
                    // Handle dropdown for treatment type
                    const treatmentTypeSelect = document.getElementById('treatment_type');
                    
                    // Try to find a matching option
                    let optionFound = false;
                    for (let i = 0; i < treatmentTypeSelect.options.length; i++) {
                        if (treatmentTypeSelect.options[i].value === treatmentType || 
                            treatmentTypeSelect.options[i].text === treatmentType) {
                            treatmentTypeSelect.selectedIndex = i;
                            optionFound = true;
                            break;
                        }
                    }
                    
                    // If no exact match found, look for a partial match
                    if (!optionFound) {
                        for (let i = 0; i < treatmentTypeSelect.options.length; i++) {
                            if (treatmentTypeSelect.options[i].text.includes(treatmentType) || 
                                treatmentType.includes(treatmentTypeSelect.options[i].text)) {
                                treatmentTypeSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                } else {
                    // Clear fields if "Select an appointment" is chosen
                    document.getElementById('account_name').value = '';
                    document.getElementById('location').value = '';
                    document.getElementById('contact_no').value = '';
                    document.getElementById('treatment_type').selectedIndex = 0;
                }
            });
            
            // Handle file uploads and preview
            document.getElementById('photos').addEventListener('change', function(e) {
                const previewContainer = document.getElementById('photo-preview-container');
                previewContainer.innerHTML = ''; // Clear previous previews
                
                if (this.files.length > 5) {
                    alert('You can only upload a maximum of 5 photos.');
                    this.value = ''; // Clear selected files
                    return;
                }
                
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    
                    // Check file size (max 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        alert(`File ${file.name} exceeds 2MB. Please select a smaller file.`);
                        continue;
                    }
                    
                    // Create preview element
                    const preview = document.createElement('div');
                    preview.className = 'preview-item';
                    
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.onload = function() {
                        URL.revokeObjectURL(this.src); // Free memory
                    };
                    
                    const caption = document.createElement('span');
                    caption.className = 'file-name';
                    caption.textContent = file.name;
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'remove-file';
                    removeBtn.innerHTML = '<i class="bx bx-x"></i>';
                    removeBtn.onclick = function() {
                        preview.remove();
                        // Note: This doesn't actually remove the file from the input
                        // You would need a more complex solution to truly remove a single file
                    };
                    
                    preview.appendChild(img);
                    preview.appendChild(caption);
                    preview.appendChild(removeBtn);
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
        </script>
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
                                            AND (a.technician_id = :technician_id OR att.technician_id = :technician_id)
                                            GROUP BY a.id
                                            ORDER BY a.appointment_date DESC";
                                            
                                            $stmt = $db->prepare($customerQuery);
                                            $stmt->bindParam(':technician_id', $_SESSION['user_id'], PDO::PARAM_INT);
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
                                                         . 'data-technician-name="' . htmlspecialchars($customer['technician_name']) . '" '
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
                                                    $techQuery = "SELECT id, firstname, lastname 
                                                                FROM users 
                                                                WHERE role = 'technician' 
                                                                AND status = 'verified'";
                                                    $techStmt = $db->prepare($techQuery);
                                                    $techStmt->execute();
                                                    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    if (!empty($technicians)) {
                                                        foreach ($technicians as $tech) {
                                                            echo '<option value="' . htmlspecialchars($tech['id']) . '">' . 
                                                                htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']) . '</option>';
                                                        }
                                                    } else {
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
                            </div>
                            <div class="schedule-actions-centered">
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
                                <div class="filter-buttons">
                                    <button type="button" class="filter-btn active" data-filter="all">All</button>
                                    <button type="button" class="filter-btn" data-filter="thisweek">This Week</button>
                                    <button type="button" class="filter-btn" data-filter="nextweek">Next Week</button>
                                    <button type="button" class="filter-btn" data-filter="nextmonth">Next Month</button>
                                </div>
                            </div>
                            <div class="table-wrapper scrollable-table">
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
                                            AND (a.technician_id = ? OR at.technician_id = ?)
                                            GROUP BY a.id
                                            ORDER BY a.appointment_date ASC, a.appointment_time ASC
                                            LIMIT 50";
                                            $stmt = $db->prepare($followupsQuery);
                                            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                                            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (!empty($followups)) {
                                                foreach ($followups as $followup) {
                                                    $appointmentDate = strtotime($followup['appointment_date']);
                                                    $dateClass = '';
                                                    
                                                    // Calculate if appointment is this week, next week, or this month
                                                    $today = strtotime('today');
                                                    $weekStart = strtotime('monday this week', $today);
                                                    $weekEnd = strtotime('sunday this week', $today);
                                                    $nextWeekStart = strtotime('monday next week', $today);
                                                    $nextWeekEnd = strtotime('sunday next week', $today);
                                                    $monthStart = strtotime('first day of this month', $today);
                                                    $monthEnd = strtotime('last day of this month', $today);
                                                    
                                                    if ($appointmentDate >= $weekStart && $appointmentDate <= $weekEnd) {
                                                        $dateClass = 'thisweek';
                                                    } elseif ($appointmentDate >= $nextWeekStart && $appointmentDate <= $nextWeekEnd) {
                                                        $dateClass = 'nextweek';
                                                    } elseif ($appointmentDate >= $monthStart && $appointmentDate <= $monthEnd) {
                                                        $dateClass = 'thismonth';
                                                    }
                                                    
                                                    echo '<tr class="followup-row" data-date="'.date('Y-m-d', $appointmentDate).'" data-period="'.$dateClass.'">';
                                                    echo '<td>' . date('M d, Y', $appointmentDate) . ' ' . 
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
                            <div id="followups-pagination" class="pagination-controls">
                                <!-- Pagination will be inserted via JavaScript -->
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
            <form id="profile-form" method="POST" action="process_profile.php" enctype="multipart/form-data">
                <div class="head-title">
                    <div class="left">
                        <h1>My Profile</h1>
                        <ul class="breadcrumb">
                            <li></li>
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
                            <h3>John Smith</h3>
                            <p>Pest Control Technician</p>
                            <p>PCT-001</p>
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
                                <p>Smith</p>
                                <p>03-05-1995</p>
                            </div>
                            <div class="info-row">
                                <p><strong>Email:</strong></p>
                                <p><strong>Phone Number:</strong></p>
                                <p><strong>User Role:</strong></p>
                            </div>
                            <div class="info-row">
                                <p>john.smith@pestcozam.com</p>
                                <p>0953-654-4541</p>
                                <p>Pest Control Technician</p>
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
                                <p>Tetuan, Zamboanga City</p>
                                <p>7000</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </section>

    <script src="../JS CODES/dashboard-pct.js"></script>
    <script>
        // Function to handle filtering of assignment rows
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const assignmentRows = document.querySelectorAll('tr[data-status]');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    // Show/hide rows based on filter
                    assignmentRows.forEach(row => {
                        if (filterValue === 'all' || row.getAttribute('data-status') === filterValue) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
            
            // Completely replace the customer select event handler with a more effective version
            const customerSelect = document.getElementById('customer-select');
            if (customerSelect) {
                customerSelect.addEventListener('change', function() {
                    if (!this.value) return;
                    
                    console.log('Loading customer details for ID:', this.value);
                    
                    // Get the selected option element
                    const selectedOption = this.options[this.selectedIndex];
                    if (!selectedOption) {
                        console.error('Selected option not found');
                        return;
                    }
                    
                    // Extract data from data attributes
                    const serviceId = selectedOption.getAttribute('data-service');
                    const location = selectedOption.getAttribute('data-location');
                    const allTechnicians = selectedOption.getAttribute('data-all-technicians');
                    const allTechnicianNames = selectedOption.getAttribute('data-all-technician-names');
                    
                    console.log('Customer details found:', { 
                        serviceId, 
                        location, 
                        allTechnicians,
                        allTechnicianNames
                    });
                    
                    // Set service type and customer location if available
                    if (serviceId) document.getElementById('service-type').value = serviceId;
                    if (location) document.getElementById('customer-location').value = location;
                    
                    const technicianSelect = document.getElementById('technician-select');
                    if (technicianSelect) {
                        // Clear previous selections first
                        Array.from(technicianSelect.options).forEach(opt => {
                            opt.selected = false;
                        });
                        
                        // Process technician IDs from all_technicians attribute
                        if (allTechnicians && allTechnicians.trim() !== "" && allTechnicians.toLowerCase() !== "null") {
                            const techIds = allTechnicians.split(',').map(id => id.trim());
                            console.log('Setting technicians:', techIds);
                            
                            // Select each matching technician in the dropdown
                            techIds.forEach(id => {
                                for (let i = 0; i < technicianSelect.options.length; i++) {
                                    if (technicianSelect.options[i].value === id) {
                                        console.log(`Selecting technician ID ${id}`);
                                        technicianSelect.options[i].selected = true;
                                        break;
                                    }
                                }
                            });
                        } else {
                            // Fallback to main technician if no multi-technician data
                            const mainTechId = selectedOption.getAttribute('data-technician');
                            if (mainTechId && mainTechId !== 'null') {
                                console.log('Setting main technician:', mainTechId);
                                
                                // Find and select the matching option
                                for (let i = 0; i < technicianSelect.options.length; i++) {
                                    if (technicianSelect.options[i].value === mainTechId) {
                                        technicianSelect.options[i].selected = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Trigger change event if a value is already selected (e.g., on page reload)
                if (customerSelect.value) {
                    console.log('Triggering change event for preselected customer:', customerSelect.value);
                    const event = new Event('change');
                    customerSelect.dispatchEvent(event);
                }
            }
        });
        
        // Add this function to enable manual debugging from console
        function debugTechnicianSelection(appointmentId) {
            const select = document.getElementById('customer-select');
            if (select) {
                select.value = appointmentId;
                const event = new Event('change');
                select.dispatchEvent(event);
            }
        }
    </script>
</body>
</html>