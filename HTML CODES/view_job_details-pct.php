<?php
session_start();
require_once '../database.php';

// Check if user is logged in as technician
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header("Location: login.php");
    exit();
}

// Check if appointment ID is provided
$appointmentId = $_GET['id'] ?? null;
if (!$appointmentId) {
    header('Location: dashboard-pct.php#assignments');
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Validate technician is assigned to this appointment
try {
    // Check if technician is assigned to this appointment (check both main technician and appointment_technicians table)
    $accessCheckStmt = $db->prepare("
        SELECT COUNT(*) as assigned 
        FROM (
            SELECT technician_id FROM appointments WHERE id = ? AND technician_id = ?
            UNION
            SELECT technician_id FROM appointment_technicians WHERE appointment_id = ? AND technician_id = ?
        ) as assignments
    ");
    $accessCheckStmt->execute([$appointmentId, $_SESSION['user_id'], $appointmentId, $_SESSION['user_id']]);
    $accessCheck = $accessCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$accessCheck['assigned']) {
        // Technician is not assigned to this appointment
        $_SESSION['error_message'] = "You don't have permission to view this appointment.";
        header('Location: dashboard-pct.php#assignments');
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['error_message'] = "Error checking permissions: " . $e->getMessage();
    header('Location: dashboard-pct.php#assignments');
    exit;
}

// Fetch appointment details with assigned technicians, including follow-up status
try {
    $stmt = $db->prepare("SELECT 
        a.*,
        s.service_name,
        CONCAT(u.firstname, ' ', u.lastname) as customer_name,
        u.email as customer_email,
        u.mobile_number as customer_phone,
        t.firstname as tech_firstname,
        t.lastname as tech_lastname,
        fv.id IS NOT NULL as is_followup
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users t ON a.technician_id = t.id
    LEFT JOIN followup_visits fv ON a.id = fv.appointment_id
    WHERE a.id = ?");
    
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch all technicians assigned to this appointment from appointment_technicians table
    $techStmt = $db->prepare("SELECT technician_id FROM appointment_technicians WHERE appointment_id = ?");
    $techStmt->execute([$appointmentId]);
    $assignedTechs = $techStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Parse saved treatment methods and chemicals if they exist
    $savedMethods = [];
    $savedChemicals = [];
    $savedQuantities = [];
    
    if (!empty($appointment['treatment_methods'])) {
        $savedMethods = json_decode($appointment['treatment_methods'], true) ?? [];
    }
    
    if (!empty($appointment['chemicals'])) {
        $savedChemicals = json_decode($appointment['chemicals'], true) ?? [];
    }
    
    if (!empty($appointment['chemical_quantities'])) {
        $savedQuantities = json_decode($appointment['chemical_quantities'], true) ?? [];
    }
    
    // Create an associative array of chemical => quantity for easier access
    $chemicalQuantities = [];
    foreach ($savedChemicals as $index => $chemical) {
        $chemicalQuantities[$chemical] = $savedQuantities[$index] ?? 0;
    }
    
    // Parse devices if they exist
    $savedDevices = [];
    if (!empty($appointment['devices'])) {
        $savedDevices = json_decode($appointment['devices'], true) ?? [];
    }
    
} catch(PDOException $e) {
    error_log("Error fetching appointment data: " . $e->getMessage());
    $appointment = null;
    $assignedTechs = [];
    $savedMethods = [];
    $chemicalQuantities = [];
    $savedDevices = [];
}

// Fetch available technicians
try {
    $techStmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE role = 'technician'");
    $techStmt->execute();
    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching technicians: " . $e->getMessage());
    $technicians = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - PESTCOZAM</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../CSS CODES/dashboard-admin.css">
    <link rel="stylesheet" href="../CSS CODES/job-details-pct.css">
    <link rel="stylesheet" href="../CSS CODES/dashboard-pct.css">
</head>
<body>
    <div class="job-details-container">
        <div class="head-title">
            <div class="left">
                <h1>Job Order Details #<?php echo htmlspecialchars($appointmentId); ?></h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard-pct.php#assignments">My Assignments</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Job Details</a></li>
                </ul>
            </div>
            <a href="dashboard-pct.php#assignments" class="back-btn">
                <i class='bx bx-arrow-back'></i> Back to Assignments
            </a>
        </div>

        <div class="card-container">
            <!-- Top Section with Customer and Service Information -->
            <div class="top-sections-container">
                <!-- Customer Information Section - Left -->
                <div class="detail-section customer-section">
                    <div class="section-header">
                        <h3><i class='bx bx-user'></i> Customer Information</h3>
                    </div>
                    <div class="section-content">
                        <div class="info-grid">
                            <div class="info-group">
                                <label>Customer Name</label>
                                <p><?php echo htmlspecialchars($appointment['customer_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($appointment['customer_email'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Phone</label>
                                <p><?php echo htmlspecialchars($appointment['customer_phone'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Address</label>
                                <p><?php echo htmlspecialchars($appointment['street_address'] ?? '') . ', ' . 
                                    htmlspecialchars($appointment['barangay'] ?? '') . ', ' . 
                                    htmlspecialchars($appointment['city'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Information Section - Right -->
                <div class="detail-section service-section">
                    <div class="section-header">
                        <h3><i class='bx bx-package'></i> Service Information</h3>
                    </div>
                    <div class="section-content">
                        <div class="info-grid">
                            <div class="info-group">
                                <label>Service Type</label>
                                <p><?php echo htmlspecialchars($appointment['service_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Appointment Type</label>
                                <p>
                                    <?php 
                                    if ($appointment['is_followup']) {
                                        echo 'Follow-up Visit';
                                    } elseif (isset($appointment['service_name']) && $appointment['service_name'] == 'Ocular Inspection' || 
                                        isset($appointment['service_id']) && $appointment['service_id'] == 17 ||
                                        isset($appointment['service_type']) && strtolower($appointment['service_type']) == 'ocular inspection') {
                                        echo 'Ocular Inspection';
                                    } else {
                                        echo 'Treatment';
                                    } 
                                    ?>
                                </p>
                            </div>
                            <div class="info-group">
                                <label>Appointment Date</label>
                                <p><?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Appointment Time</label>
                                <p><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Status</label>
                                <span class="status <?php echo $appointment['is_followup'] ? 'followup' : strtolower($appointment['status']); ?>">
                                    <?php echo $appointment['is_followup'] ? 'Follow-up Visit' : htmlspecialchars($appointment['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Details Section -->
            <div class="detail-section property-section">
                <div class="section-header">
                    <h3><i class='bx bx-building-house'></i> Property Details</h3>
                </div>
                <div class="section-content">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Property Type</label>
                            <p><?php echo ucfirst(htmlspecialchars($appointment['property_type'] ?? 'Residential')); ?>
                            <?php if(isset($appointment['establishment_name']) && !empty($appointment['establishment_name'])): ?>
                                (<?php echo htmlspecialchars($appointment['establishment_name']); ?>)
                            <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if(isset($appointment['property_area']) && !empty($appointment['property_area'])): ?>
                        <div class="info-group">
                            <label>Property Area</label>
                            <p><?php echo htmlspecialchars($appointment['property_area']); ?> sq.m</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($appointment['pest_concern']) && !empty($appointment['pest_concern'])): ?>
                        <div class="info-group pest-concern">
                            <label>Pest Concern</label>
                            <p><?php echo nl2br(htmlspecialchars($appointment['pest_concern'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Treatment Details Section -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class='bx bx-spray-can'></i> Treatment Details</h3>
                </div>
                <div class="section-content">
                    <!-- Treatment Method Section -->
                    <div class="treatment-method-section">
                        <h4>Treatment Method</h4>
                        <div class="method-options">
                            <?php if(!empty($savedMethods)): ?>
                                <?php foreach($savedMethods as $method): ?>
                                    <div class="method-badge"><?php echo htmlspecialchars(ucfirst($method)); ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No treatment methods specified yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chemicals Section -->
                    <div class="chemicals-section">
                        <h4>Chemicals Used</h4>
                        <?php if(!empty($savedChemicals)): ?>
                            <div class="chemical-list">
                                <?php foreach($savedChemicals as $chemical): ?>
                                    <div class="chemical-item">
                                        <span><?php echo htmlspecialchars($chemical); ?></span>
                                        <span class="chemical-qty"><?php echo htmlspecialchars($chemicalQuantities[$chemical] ?? '0'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No chemicals specified yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Devices Section -->
                    <div class="devices-section">
                        <h4>Devices</h4>
                        <?php if(!empty($savedDevices)): ?>
                            <div class="device-list">
                                <?php foreach($savedDevices as $device): ?>
                                    <div class="device-item">
                                        <span><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $device))); ?></span>
                                        <span class="device-qty"><?php echo htmlspecialchars($appointment['device_quantities'] ?? '0'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No devices specified for this appointment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Technician Assignment Section -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class='bx bx-user-check'></i> Assigned Technicians</h3>
                </div>
                <div class="section-content">
                    <?php if (!empty($assignedTechs)): ?>
                        <div class="assigned-techs-list">
                            <?php foreach ($assignedTechs as $assignedId): ?>
                                <?php 
                                foreach ($technicians as $tech) {
                                    if ($tech['id'] == $assignedId) {
                                        $isCurrentUser = ($tech['id'] == $_SESSION['user_id']);
                                ?>
                                    <div class="assigned-tech <?php echo $isCurrentUser ? 'primary' : ''; ?>">
                                        <i class='bx bx-user'></i>
                                        <span><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></span>
                                        <?php if($isCurrentUser): ?>
                                            <small>(You)</small>
                                        <?php endif; ?>
                                    </div>
                                <?php
                                    }
                                }
                                ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-techs-message">No technicians have been assigned yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($appointment['status'] === 'Confirmed' || $appointment['is_followup']): ?>
                    <a href="dashboard-pct.php#submit-report?appointment_id=<?php echo $appointmentId; ?>" class="btn-submit-report">
                        <i class='bx bx-file'></i> Submit Service Report
                    </a>
                <?php endif; ?>
                <a href="dashboard-pct.php#assignments" class="back-btn">
                    <i class='bx bx-arrow-back'></i> Back to Assignments
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any client-side functionality here if needed
        });
    </script>
</body>
</html>
