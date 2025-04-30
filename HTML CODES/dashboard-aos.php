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

    // Get service reports - Added for supervisor functionality
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
} catch(PDOException $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $appointments = [];
    $technicians = [];
    $serviceReports = [];
    $monthlyAppointmentCounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $servicePopularity = [];
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
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <style>
        /* Override disabled option styling for technician select */
        #technician-select option[disabled] {
            background-color: transparent;
            color: inherit;
        }
        
        /* Chart Container Styles */
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

                    <ul class="box-info">
                        <li>
                            <i class='bx bxs-calendar-check'></i>
                            <span class="text">
                                <h3><?php echo htmlspecialchars($stats['total_appointments'] ?? '0'); ?></h3>
                                <p>Total Appointments</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-hourglass'></i>
                            <span class="text">
                                <h3><?php echo htmlspecialchars($stats['pending_jobs'] ?? '0'); ?></h3>
                                <p>Pending Job Orders</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-check-circle'></i>
                            <span class="text">
                                <h3><?php echo htmlspecialchars($stats['completed_treatments'] ?? '0'); ?></h3>
                                <p>Completed Treatments</p>
                            </span>
                        </li>
                        <li>
                            <i class='bx bxs-group'></i>
                            <span class="text">
                                <h3><?php echo htmlspecialchars($stats['active_technicians'] ?? '0'); ?></h3>
                                <p>Active Technicians</p>
                            </span>
                        </li>
                    </ul>
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
                    <div class="followups-controls">
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

                    <div class="scrollable-table">
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
                                        <tr data-status="<?php echo strtolower(trim($appointment['status'])); ?>" data-date="<?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?>">
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
                                            <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
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
                                                    <a href="job-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="view-btn">
                                                        <i class='bx bx-show'></i> View
                                                    </a>
                                                    <?php if ($appointment['status'] === 'Completed'): ?>
                                                        <a href="#" class="review-btn" onclick="loadReviewData(<?php echo $appointment['appointment_id']; ?>); return false;">
                                                            <i class='bx bx-message-square-check'></i> Check Review
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="no-records">
                                        <td colspan="7">No appointments found</td>
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
                            </div>
                            <div class="schedule-actions-centered">
                                <button type="button" class="btn-submit" id="schedule-followup-btn">
                                    <i class='bx bx-calendar-check'></i> Schedule Follow-up
                                </button>
                            </div>
                        </div>
                        
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
                                            GROUP BY a.id
                                            ORDER BY a.appointment_date ASC, a.appointment_time ASC
                                            LIMIT 50";
                                            
                                            $stmt = $db->prepare($followupsQuery);
                                            $stmt->execute();
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
                                                    } elseif ($appointmentDate > $monthEnd) {
                                                        $dateClass = 'nextmonth'; // Add class for next month
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
                                                echo '<tr class="no-results"><td colspan="6">No follow-ups scheduled</td></tr>';
                                            }
                                        } catch(PDOException $e) {
                                            echo '<tr class="no-results"><td colspan="6">Error loading follow-ups: ' . $e->getMessage() . '</td></tr>';
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

    <!-- Manage Technician Reports Section -->
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

            <!-- Search and Filter Controls -->
            <div class="report-controls">
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

        <!-- Report Details Modal - Updated to match admin version -->
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
                        <button type="button" class="btn-approve" id="approveBtn">
                            <i class='bx bx-check'></i> Approve Report
                        </button>
                        <button type="button" class="btn-reject" id="rejectBtn">
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
            
            const reportCards = document.querySelectorAll('.report-card');
            
            reportCards.forEach(card => {
                let showCard = true;
                
                // Filter by search text
                if (searchValue) {
                    const techName = card.getAttribute('data-tech-name').toLowerCase();
                    const location = card.getAttribute('data-location').toLowerCase();
                    const account = card.getAttribute('data-account').toLowerCase();
                    const treatment = card.getAttribute('data-treatment').toLowerCase();
                    
                    if (!techName.includes(searchValue) && 
                        !location.includes(searchValue) && 
                        !account.includes(searchValue) && 
                        !treatment.includes(searchValue)) {
                        showCard = false;
                    }
                }
                
                // Filter by status
                if (statusFilter && card.getAttribute('data-status') !== statusFilter) {
                    showCard = false;
                }
                
                // Filter by date
                if (dateFilter) {
                    const reportDate = new Date(card.getAttribute('data-date'));
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    
                    const monthStart = new Date(today);
                    monthStart.setDate(1);
                    
                    if (dateFilter === 'today' && reportDate.toDateString() !== today.toDateString()) {
                        showCard = false;
                    } else if (dateFilter === 'week' && (reportDate < weekStart || reportDate > today)) {
                        showCard = false;
                    } else if (dateFilter === 'month' && (reportDate < monthStart || reportDate > today)) {
                        showCard = false;
                    }
                }
                
                // Show or hide card based on filters
                card.style.display = showCard ? 'flex' : 'none';
            });
        }
        
        // Function to open the report modal with specific report data
        function openReportModal(reportId) {
            console.log('Opening report modal for ID:', reportId);
            
            // Find the report in the global data
            const report = window.reportsData.find(r => r.report_id == reportId);
            
            if (!report) {
                console.error('Report not found with ID:', reportId);
                return;
            }
            
            console.log('Found report:', report);
            
            // Populate the modal fields with report data
            document.getElementById('reportIdField').value = report.report_id;
            document.getElementById('reportIdDisplay').value = report.report_id;
            document.getElementById('reportDateField').value = new Date(report.date_of_treatment).toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            document.getElementById('techNameField').value = report.tech_name;
            document.getElementById('clientNameField').value = report.account_name;
            document.getElementById('contactNoField').value = report.contact_no;
            document.getElementById('locationField').value = report.location;
            document.getElementById('treatmentTypeField').value = report.treatment_type;
            document.getElementById('treatmentMethodField').value = report.treatment_method;
            document.getElementById('timeInField').value = report.time_in;
            document.getElementById('timeOutField').value = report.time_out;
            document.getElementById('pestCountField').value = report.pest_count;
            document.getElementById('deviceInstallationField').value = report.device_installation;
            document.getElementById('chemicalsField').value = report.consumed_chemicals;
            document.getElementById('frequencyField').value = report.frequency_of_visits;
            
            // Handle photos (if available)
            const imageGallery = document.getElementById('imageGallery');
            imageGallery.innerHTML = ''; // Clear existing images
            
            if (report.photos) {
                let photos = [];
                
                // Parse photos if it's a JSON string
                if (typeof report.photos === 'string') {
                    try {
                        photos = JSON.parse(report.photos);
                        if (!Array.isArray(photos)) {
                            photos = [report.photos];
                        }
                    } catch (e) {
                        photos = [report.photos];
                    }
                } else if (Array.isArray(report.photos)) {
                    photos = report.photos;
                }
                
                // Add photos to the gallery
                photos.forEach(photo => {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'image-container';
                    
                    const img = document.createElement('img');
                    img.src = photo.startsWith('http') ? photo : `../uploads/reports/${photo}`;
                    img.alt = 'Service Report Photo';
                    img.onerror = function() {
                        this.src = '../Pictures/image_placeholder.png';
                        this.alt = 'Image not available';
                    };
                    
                    imgContainer.appendChild(img);
                    imageGallery.appendChild(imgContainer);
                });
            } else {
                imageGallery.innerHTML = '<p class="no-images">No images available</p>';
            }
            
            // Update action buttons based on report status
            updateActionButtons(report.status.toLowerCase());
            
            // Show the modal
            const modal = document.getElementById('reportModal');
            modal.style.display = 'flex';
            
            // Add a class to fade in the modal (if using CSS transitions)
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }
        
        // Function to close the report modal
        function closeReportModal() {
            const modal = document.getElementById('reportModal');
            modal.classList.remove('show');
            
            // Wait for the transition to complete before hiding
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        // Function to update the report status (approve/reject)
        function updateReportStatus(status) {
            const reportId = document.getElementById('reportIdField').value;
            
            if (!reportId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Report ID not found',
                });
                return;
            }
            
            // Confirm before changing status
            Swal.fire({
                title: `Are you sure you want to ${status} this report?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Prepare data for AJAX request
                    const formData = new FormData();
                    formData.append('report_id', reportId);
                    formData.append('status', status);
                    formData.append('action', 'update_status');
                    formData.append('role', 'supervisor'); // Identify that a supervisor is making the change
                    
                    // Send AJAX request to update status
                    fetch('../PHP CODES/update_report_status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Status Updated',
                                text: `Report has been ${status} successfully${status === 'approved' ? '. The appointment has been marked as completed.' : ''}`,
                                confirmButtonColor: '#144578'
                            }).then(() => {
                                // Update the UI to reflect the status change
                                const reportCard = document.querySelector(`.report-card[data-report-id="${reportId}"]`);
                                if (reportCard) {
                                    reportCard.setAttribute('data-status', status);
                                    const statusElement = reportCard.querySelector('.report-status');
                                    if (statusElement) {
                                        statusElement.className = `report-status ${status}`;
                                        statusElement.textContent = status === 'approved' ? 'Approved' : 'Rejected';
                                    }
                                }
                                
                                // Update action buttons
                                updateActionButtons(status);
                                
                                // Close the modal after a short delay
                                setTimeout(() => {
                                    closeReportModal();
                                }, 1500);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to update report status',
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error updating report status:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while updating the report status. Please try again.',
                        });
                    });
                }
            });
        }
        
        // Function to update action buttons based on report status
        function updateActionButtons(status) {
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            
            if (status === 'approved') {
                approveBtn.disabled = true;
                approveBtn.classList.add('disabled');
                rejectBtn.disabled = false;
                rejectBtn.classList.remove('disabled');
            } else if (status === 'rejected') {
                approveBtn.disabled = false;
                approveBtn.classList.remove('disabled');
                rejectBtn.disabled = true;
                rejectBtn.classList.add('disabled');
            } else {
                // Status is pending
                approveBtn.disabled = false;
                approveBtn.classList.remove('disabled');
                rejectBtn.disabled = false;
                rejectBtn.classList.remove('disabled');
            }
        }
        
        // Function to download the report as PDF
        function downloadReportPDF() {
            const reportId = document.getElementById('reportIdField').value;
            const techName = document.getElementById('techNameField').value;
            const reportDate = document.getElementById('reportDateField').value;
            
            // Create a filename for the PDF
            const filename = `Report_${reportId}_${techName.replace(/\s/g, '_')}.pdf`;
            
            // Use HTML2Canvas and jsPDF to create and download the PDF
            const { jsPDF } = window.jspdf;
            const reportForm = document.getElementById('reportForm');
            
            // Create a clone of the form for PDF generation (to prevent layout issues)
            const clone = reportForm.cloneNode(true);
            clone.style.background = 'white';
            clone.style.padding = '20px';
            clone.style.position = 'absolute';
            clone.style.top = '-9999px';
            clone.style.left = '-9999px';
            document.body.appendChild(clone);
            
            // Remove buttons from the clone
            const buttons = clone.querySelectorAll('.form-actions');
            buttons.forEach(btn => btn.remove());
            
            // Generate the PDF
            html2canvas(clone, {
                scale: 2,
                useCORS: true,
                logging: false
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                const imgWidth = canvas.width;
                const imgHeight = canvas.height;
                const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
                const imgX = (pdfWidth - imgWidth * ratio) / 2;
                const imgY = 30;
                
                // Add a header
                pdf.setFontSize(18);
                pdf.text('PESTCOZAM Service Report', pdfWidth / 2, 15, { align: 'center' });
                
                // Add the image of the form
                pdf.addImage(imgData, 'PNG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);
                
                // Download the PDF
                pdf.save(filename);
                
                // Remove the clone from the document
                document.body.removeChild(clone);
            });
        }
        
        // Initialize report cards with event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const reportCards = document.querySelectorAll('.report-card');
            console.log(`Found ${reportCards.length} report cards to initialize`);
            
            reportCards.forEach(card => {
                card.addEventListener('click', function() {
                    const reportId = this.getAttribute('data-report-id');
                    openReportModal(reportId);
                });
            });
            
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
                        <h3><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <p><?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
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
                            <p><strong>First Name:</strong> <span data-field="firstname"><?php echo htmlspecialchars($user['firstname']); ?></span></p>
                            <p><strong>Middle Name:</strong> <span data-field="middlename"><?php echo htmlspecialchars($user['middlename'] ?: 'Not set'); ?></span></p>
                        </div>
                        <div class="info-row">
                            <p><strong>Last Name:</strong> <span data-field="lastname"><?php echo htmlspecialchars($user['lastname']); ?></span></p>
                            <p><strong>Date of Birth:</strong> <?php echo $user['dob'] ? date('m-d-Y', strtotime($user['dob'])) : 'Not set'; ?></p>
                        </div>
                        <div class="info-row">
                            <p><strong>Email:</strong> <span data-field="email"><?php echo htmlspecialchars($user['email']); ?></span></p>
                            <p><strong>Phone Number:</strong> <span data-field="mobile_number"><?php echo htmlspecialchars($user['mobile_number']); ?></span></p>
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
                            <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                            <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($user['status'])); ?></p>
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
                            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middlename">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" value="<?php echo htmlspecialchars($user['middlename']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="lastname">Last Name</label>
                            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mobile_number">Mobile Number</label>
                            <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($user['mobile_number']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" value="<?php echo $user['dob']; ?>">
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

    <!-- Add inline script for direct modal handling -->
    <script>
        // Direct modal event handlers
        document.addEventListener('DOMContentLoaded', function() {
            const profileModal = document.getElementById('profileModal');
            const openBtn = document.getElementById('openProfileModalBtn');
            const closeBtn = document.querySelector('#profileModal .close');
            const cancelBtn = document.getElementById('closeProfileModalBtn');
            const profileForm = document.getElementById('editProfileForm');
            
            function openProfileModal() {
                console.log('Opening modal...');
                
                // First set display to flex
                profileModal.style.display = 'flex';
                
                // Force a reflow/repaint before adding the show class
                void profileModal.offsetWidth;
                
                // Add show class to trigger the transition
                profileModal.classList.add('show');
                
                document.body.style.overflow = 'hidden';
                
                // Debugging - check if modal is visible after changing style
                console.log('Modal display style:', profileModal.style.display);
                console.log('Modal visibility:', getComputedStyle(profileModal).visibility);
                console.log('Modal opacity:', getComputedStyle(profileModal).opacity);
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
                    console.log('Edit button clicked');
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
            }

            // Add form submission handler
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    updateUserProfile(this);
                });
            }
        });
    </script>
    
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
    
    <!-- Review Modal with Box Design Layout -->
    <div id="reviewModal" class="modal">
        <div class="review-modal-content">
            <span class="close-modal" onclick="closeReviewModal()">&times;</span>
            <div class="modal-header">
                <h3>Customer Review Details</h3>
            </div>
            <div class="review-content">
                <div class="review-grid">
                    <!-- Left Side - Ratings -->
                    <div class="review-left">
                        <div class="overall-rating">
                            <h4>Overall Rating</h4>
                            <div class="rating-stars">
                                <div class="rating-number">
                                    <span id="overall-rating-value">0</span><span>/5</span>
                                </div>
                                <div id="overall-stars" class="stars-container"></div>
                            </div>
                        </div>
                        <div class="rating-details">
                            <div class="rating-detail">
                                <span>Service Quality:</span>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="stars-container" id="service-stars"></div>
                                    <div class="rating-number">
                                        <span id="service-rating-value">0</span><span>/5</span>
                                    </div>
                                </div>
                            </div>
                            <div class="rating-detail">
                                <span>Technician Performance:</span>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="stars-container" id="technician-stars"></div>
                                    <div class="rating-number">
                                        <span id="technician-rating-value">0</span><span>/5</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="date-info">
                            <p><i class='bx bx-calendar'></i> <strong>Review Date:</strong> <span id="review-date">--/--/----</span></p>
                        </div>
                    </div>
                    
                    <!-- Right Side - Comments -->
                    <div class="review-right">
                        <div class="review-body">
                            <h4><i class='bx bx-message-detail'></i> Customer Comments</h4>
                            <p id="review-text">Loading review information...</p>
                        </div>
                        
                        <div class="review-feedback">
                            <h4><i class='bx bx-comment-check'></i> Service Feedback</h4>
                            <p id="service-feedback">Loading feedback information...</p>
                        </div>
                        
                        <div class="review-issues" id="issues-container">
                            <h4><i class='bx bx-error-circle'></i> Reported Issues</h4>
                            <p id="reported-issues">Loading issues information...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../JS CODES/modal.js"></script>
    <script src="../JS CODES/dashboard-aos.js"></script>
    <script src="../JS CODES/followup.js"></script>
    <script src="../JS CODES/work-orders.js"></script>
    <script>
        // New function to load and display review data
        function loadReviewData(appointmentId) {
            console.log('Loading review data for appointment ID:', appointmentId);
            
            // Show loading state
            document.getElementById('review-text').textContent = 'Loading...';
            document.getElementById('service-feedback').textContent = 'Loading...';
            document.getElementById('reported-issues').textContent = 'Loading...';
            
            // Clear star ratings
            document.getElementById('overall-stars').innerHTML = '';
            document.getElementById('service-stars').innerHTML = '';
            document.getElementById('technician-stars').innerHTML = '';
            
            // Show the modal first for better UX
            const modal = document.getElementById('reviewModal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            
            // Fetch review data with better error handling
            fetch(`../PHP CODES/get_review.php?appointment_id=${appointmentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Review data received:', data);
                    
                    if (data.success && data.review) {
                        const review = data.review;
                        
                        // Update overall rating
                        document.getElementById('overall-rating-value').textContent = review.rating;
                        document.getElementById('overall-stars').innerHTML = generateStars(review.rating);
                        
                        // Update service rating
                        document.getElementById('service-rating-value').textContent = review.service_rating || 'N/A';
                        document.getElementById('service-stars').innerHTML = review.service_rating ? 
                            generateStars(review.service_rating) : '';
                        
                        // Update technician rating
                        document.getElementById('technician-rating-value').textContent = review.technician_rating || 'N/A';
                        document.getElementById('technician-stars').innerHTML = review.technician_rating ? 
                            generateStars(review.technician_rating) : '';
                        
                        // Update review text and feedback
                        document.getElementById('review-text').textContent = review.review_text || 'No review provided';
                        document.getElementById('service-feedback').textContent = review.service_feedback || 'No feedback provided';
                        
                        // Update reported issues (hide container if none)
                        const issuesContainer = document.getElementById('issues-container');
                        if (review.reported_issues && review.reported_issues.trim() !== '') {
                            document.getElementById('reported-issues').textContent = review.reported_issues;
                            issuesContainer.style.display = 'block';
                        } else {
                            issuesContainer.style.display = 'none';
                        }
                        
                        // Update meta information with formatted date
                        document.getElementById('review-date').textContent = review.formatted_date || 
                            new Date(review.created_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                    } else {
                        // No review found
                        document.getElementById('review-text').textContent = 'No review found for this appointment.';
                        document.getElementById('service-feedback').textContent = 'No feedback provided.';
                        document.getElementById('reported-issues').textContent = 'No issues reported.';
                        
                        // Clear ratings
                        document.getElementById('overall-rating-value').textContent = 'N/A';
                        document.getElementById('service-rating-value').textContent = 'N/A';
                        document.getElementById('technician-rating-value').textContent = 'N/A';
                        
                        // Hide issues container
                        document.getElementById('issues-container').style.display = 'none';
                        
                        // Clear meta info
                        document.getElementById('review-date').textContent = 'N/A';
                    }
                })
                .catch(error => {
                    console.error('Error fetching review:', error);
                    document.getElementById('review-text').textContent = 'Error loading review data. Please try again.';
                    document.getElementById('service-feedback').textContent = 'Error loading feedback data.';
                    document.getElementById('reported-issues').textContent = 'Error loading issues data.';
                    
                    // Show error state for ratings
                    document.getElementById('overall-rating-value').textContent = 'Error';
                    document.getElementById('service-rating-value').textContent = 'Error';
                    document.getElementById('technician-rating-value').textContent = 'Error';
                });
        }
        
        // Helper function to generate star icons based on rating
        function generateStars(rating) {
            let stars = '';
            // Convert to number and ensure it's between 1-5
            const numRating = Math.min(Math.max(parseInt(rating) || 0, 0), 5);
            
            // Generate filled stars
            for (let i = 0; i < numRating; i++) {
                stars += '<i class="bx bxs-star filled"></i>';
            }
            
            // Generate empty stars
            for (let i = numRating; i < 5; i++) {
                stars += '<i class="bx bxs-star"></i>';
            }
            
            return stars;
        }
        
        // Function to close review modal
        function closeReviewModal() {
            const modal = document.getElementById('reviewModal');
            modal.classList.remove('show');
            
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        // Add event listener to "Check Review" buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Log when the DOM is loaded
            console.log('DOM loaded, initializing review buttons');
            
            const reviewButtons = document.querySelectorAll('.review-btn');
            console.log(`Found ${reviewButtons.length} review buttons`);
            
            reviewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const appointmentId = this.getAttribute('data-appointment-id') || 
                                         (this.getAttribute('href') ? this.getAttribute('href').split('=')[1] : null);
                    
                    console.log('Review button clicked for appointment ID:', appointmentId);
                    
                    if (appointmentId) {
                        loadReviewData(appointmentId);
                    } else {
                        console.error('Could not determine appointment ID from button');
                    }
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('reviewModal');
                if (event.target === modal) {
                    closeReviewModal();
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeReviewModal();
                }
            });
        });
    </script>
</body>
</html>