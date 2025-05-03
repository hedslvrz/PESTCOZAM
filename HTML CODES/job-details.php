<?php
session_start();
require_once '../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if appointment ID is provided
$appointmentId = $_GET['id'] ?? null;
if (!$appointmentId) {
    // Check user role and redirect accordingly
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor') {
        header('Location: dashboard-aos.php#work-orders');
    } else {
        header('Location: dashboard-admin.php#work-orders');
    }
    exit;
}

// Determine the return page based on user role
$returnPage = (isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor') 
    ? 'dashboard-aos.php#work-orders' 
    : 'dashboard-admin.php#work-orders';

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Failed to connect to the database.");
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("A database connection error occurred. Please try again later.");
}

// Fetch appointment details with assigned technicians
try {
    $stmt = $db->prepare("SELECT 
        a.*,
        s.service_name,
        CONCAT(u.firstname, ' ', u.lastname) as customer_name,
        u.email as customer_email,
        u.mobile_number as customer_phone,
        t.firstname as tech_firstname,
        t.lastname as tech_lastname
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users t ON a.technician_id = t.id
    WHERE a.id = ?");
    
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch all technicians assigned to this appointment from appointment_technicians table
    $techStmt = $db->prepare("SELECT technician_id FROM appointment_technicians WHERE appointment_id = ?");
    $techStmt->execute([$appointmentId]);
    $assignedTechs = $techStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Helper function to safely decode JSON data that might already be decoded
    function safeJsonDecode($data) {
        if (empty($data)) {
            return [];
        }
        
        if (is_array($data)) {
            return $data;
        }
        
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Check if it's a JSON string that needs a second decode
            if (is_string($decoded)) {
                $secondPass = json_decode($decoded, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $secondPass;
                }
            }
            return $decoded;
        }
        
        return [];
    }
    
    // Parse saved treatment methods, chemicals, and devices using the safe decode function
    $savedMethods = safeJsonDecode($appointment['treatment_methods']);
    $savedChemicals = safeJsonDecode($appointment['chemicals']);
    $savedQuantities = safeJsonDecode($appointment['chemical_quantities']);
    $savedDevices = safeJsonDecode($appointment['devices']);
    $savedDeviceQuantities = safeJsonDecode($appointment['device_quantities']);

    // Ensure the decoded data is an array
    $savedMethods = is_array($savedMethods) ? $savedMethods : [];
    $savedChemicals = is_array($savedChemicals) ? $savedChemicals : [];
    $savedQuantities = is_array($savedQuantities) ? $savedQuantities : [];
    $savedDevices = is_array($savedDevices) ? $savedDevices : [];
    $savedDeviceQuantities = is_array($savedDeviceQuantities) ? $savedDeviceQuantities : [];

    // Create an associative array of chemical => quantity for easier access
    $chemicalQuantities = [];
    foreach ($savedChemicals as $index => $chemical) {
        $chemicalQuantities[$chemical] = $savedQuantities[$index] ?? 0;
    }

    // Create an associative array of device => quantity for easier access
    $deviceQuantities = [];
    foreach ($savedDevices as $index => $device) {
        $deviceQuantities[$device] = $savedDeviceQuantities[$index] ?? 0;
    }

    // Debugging: Log the parsed data
    error_log("Parsed treatment methods: " . json_encode($savedMethods));
    error_log("Parsed chemicals: " . json_encode($savedChemicals));
    error_log("Parsed devices: " . json_encode($savedDevices));

    // Debug output for assigned technicians
    error_log("Assigned technicians for appointment $appointmentId: " . json_encode($assignedTechs));
    
} catch (PDOException $e) {
    error_log("Error fetching appointment data: " . $e->getMessage());
    die("An error occurred while fetching appointment details. Please try again later.");
}

// Fetch available technicians
try {
    $techStmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE role = 'technician'");
    $techStmt->execute();
    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug output for available technicians
    error_log("Available technicians: " . json_encode($technicians));
    
    if (empty($technicians)) {
        error_log("No technicians found with role='technician'");
    }
    
} catch (PDOException $e) {
    error_log("Error fetching technicians: " . $e->getMessage());
    $technicians = [];
}

// Define chemical categories and items with the updated structure
$treatmentTypes = [
    'general_pest_control' => [
        'name' => 'General Pest Control',
        'methods' => [
            'surface_spraying' => [
                'name' => 'Surface Spraying',
                'chemicals' => ['Cymflex', 'Permitor', 'Pervade', 'Cyflux']
            ],
            'space_spraying' => [
                'name' => 'Space Spraying',
                'chemicals' => ['Demand CS', 'Fendona', 'Fipro', 'Cyflux']
            ],
            'rodent_control' => [
                'name' => 'Rodent Control',
                'chemicals' => ['Bosny Rat Glue']
            ]
        ]
    ],
    'termite_treatment' => [
        'name' => 'Termite Treatment',
        'methods' => [
            'post_construction' => [
                'name' => 'Post-Construction (Soil Injection Treatment)',
                'chemicals' => ['Termidor', 'Optigard Termite Liquid', 'Revancha 10 EC']
            ],
            'pre_construction' => [
                'name' => 'Pre-Construction (Massive Spraying)',
                'chemicals' => ['Termidor', 'Optigard Termite Liquid', 'Revancha 10 EC']
            ],
            'reticulation' => [
                'name' => 'Reticulation',
                'chemicals' => ['Termipipes', 'PVC Pipes']
            ],
            'termite_mound' => [
                'name' => 'Termite Mound',
                'chemicals' => []
            ],
            'above_ground' => [
                'name' => 'Above-Ground Termite Treatment',
                'chemicals' => ['Termite Box']
            ],
            'in_ground' => [
                'name' => 'In-Ground Termite',
                'chemicals' => ['Inground Bait']
            ],
            'dusting' => [
                'name' => 'Dusting',
                'chemicals' => ['Fipronil', 'Exterminex Powder']
            ],
            'vapor_barrier' => [
                'name' => 'Vapor Barrier Installation',
                'chemicals' => ['6 Mil Polyethylene Sheet', '8 Mil Polyethylene Sheet']
            ]
        ]
    ]
];

// Define devices as an array for easier management
$devicesList = [
    ['value' => 'rodent_bait_station', 'label' => 'Rodent Bait Station'],
    ['value' => 'cage_trap', 'label' => 'Cage Trap'],
    ['value' => 'glue_trap', 'label' => 'Glue Trap'],
    ['value' => 'insect_light_trap', 'label' => 'Insect Light Trap'],
    ['value' => 'fly_trap', 'label' => 'Fly Trap'],
    ['value' => 'bird_scare', 'label' => 'Bird Scare'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - PESTCOZAM</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../CSS CODES/dashboard-admin.css">
    <link rel="stylesheet" href="../CSS CODES/job-details.css">
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <style>
        /* Ensure dropdowns are collapsed by default */
        .treatment-type-content,
        .treatment-method-content {
            display: none;
        }
        .treatment-type.active > .treatment-type-content,
        .treatment-method.active > .treatment-method-content {
            display: block;
        }
        /* Make headers clearly clickable - updated for treatment types to match devices */
        .treatment-type-header {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: #eef2f7;
            transition: background 0.2s;
            border-bottom: 1px solid #dde1e7;
            margin-bottom: 0;
        }
        /* Leave treatment-method-header as is or adjust similarly if needed */
        .treatment-method-header {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: #f5f5f5;
            border-radius: 4px;
            margin-bottom: 4px;
            transition: background 0.2s;
        }
        .treatment-type-header:hover,
        .treatment-method-header:hover {
            background: #e0e0e0;
        }
        .treatment-type-header h4,
        .treatment-method-header h5 {
            margin: 0;
            flex: 1;
        }
        .toggle-icon {
            font-size: 1.2em;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="job-details-container">
        <div class="head-title">
            <div class="left">
                <h1>Job Order Details #<?php echo htmlspecialchars($appointmentId); ?></h1>
                <ul class="breadcrumb">
                    <li><a href="<?php echo $returnPage; ?>">Work Orders</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Job Details</a></li>
                </ul>
            </div>
            <a href="<?php echo $returnPage; ?>" class="back-btn">
                <i class='bx bx-arrow-back'></i> Back to Work Orders
            </a>
        </div>

        <form method="POST" action="process_job_details.php" id="jobDetailsForm" class="card-container">
            <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
            
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
                                <p><?php echo htmlspecialchars(ucfirst($appointment['appointment_type'] ?? 'Treatment')); ?></p>
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
                                <span class="status <?php echo strtolower($appointment['status']); ?>">
                                    <?php echo htmlspecialchars($appointment['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add hidden fields to maintain backend functionality -->
            <input type="hidden" name="time_in" value="<?php echo $appointment['time_in'] ?? ''; ?>">
            <input type="hidden" name="time_out" value="<?php echo $appointment['time_out'] ?? ''; ?>">

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
                            <div class="method-option">
                                <input type="checkbox" id="method-spraying" name="method[]" value="spraying" <?php echo in_array('spraying', $savedMethods) ? 'checked' : ''; ?>>
                                <label for="method-spraying">
                                    Spraying
                                </label>
                            </div>
                            <div class="method-option">
                                <input type="checkbox" id="method-misting" name="method[]" value="misting" <?php echo in_array('misting', $savedMethods) ? 'checked' : ''; ?>>
                                <label for="method-misting">
                                    Misting
                                </label>
                            </div>
                            <div class="method-option">
                                <input type="checkbox" id="method-baiting" name="method[]" value="baiting" <?php echo in_array('baiting', $savedMethods) ? 'checked' : ''; ?>>
                                <label for="method-baiting">
                                    Baiting
                                </label>
                            </div>
                            <div class="method-option">
                                <input type="checkbox" id="method-dusting" name="method[]" value="dusting" <?php echo in_array('dusting', $savedMethods) ? 'checked' : ''; ?>>
                                <label for="method-dusting">
                                    Dusting
                                </label>
                            </div>
                            <div class="method-option">
                                <input type="checkbox" id="method-fogging" name="method[]" value="fogging" <?php echo in_array('fogging', $savedMethods) ? 'checked' : ''; ?>>
                                <label for="method-fogging">
                                    Fogging
                                </label>
                            </div>
                            <div class="method-option">
                                <input type="checkbox" id="method-trapping" name="method[]" value="trapping" <?php echo in_array('trapping', $savedMethods) ? 'checked' : ''; ?>>
                                <label for="method-trapping">
                                    Trapping
                                </label>
                            </div>
                        </div>
                        <button type="button" class="add-method-btn"><i class='bx bx-plus'></i> Add Method</button>
                    </div>

                    <!-- Chemical Categories Section -->
                    <div class="chemicals-section">
                        <h4>Treatment Types & Chemicals</h4>
                        
                        <?php foreach ($treatmentTypes as $typeKey => $treatmentType): ?>
                            <div class="treatment-type" data-type="<?php echo $typeKey; ?>">
                                <div class="treatment-type-header">
                                    <h4><?php echo $treatmentType['name']; ?></h4>
                                    <i class='bx bx-chevron-down toggle-icon'></i>
                                </div>
                                <div class="treatment-type-content">
                                    <?php foreach ($treatmentType['methods'] as $methodKey => $method): ?>
                                        <div class="treatment-method">
                                            <div class="treatment-method-header">
                                                <h5><?php echo $method['name']; ?></h5>
                                                <i class='bx bx-chevron-down toggle-icon'></i>
                                            </div>
                                            <div class="treatment-method-content">
                                                <div class="chemical-items">
                                                    <?php if (!empty($method['chemicals'])): ?>
                                                        <?php foreach ($method['chemicals'] as $chemical): ?>
                                                            <div class="chemical-item">
                                                                <label>
                                                                    <input type="checkbox" 
                                                                           name="chemicals[]" 
                                                                           value="<?php echo htmlspecialchars($chemical); ?>"
                                                                           <?php echo isset($chemicalQuantities[$chemical]) ? 'checked' : ''; ?>>
                                                                    <?php echo htmlspecialchars($chemical); ?>
                                                                </label>
                                                                <input type="number" 
                                                                       name="chemical_qty[<?php echo htmlspecialchars($chemical); ?>]" 
                                                                       class="quantity-input" 
                                                                       placeholder="Qty" 
                                                                       min="0" 
                                                                       step="1" 
                                                                       value="<?php echo isset($chemicalQuantities[$chemical]) ? htmlspecialchars($chemicalQuantities[$chemical]) : '0'; ?>">
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="chemical-item">
                                                            <span>No specific chemicals for this method</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Add missing container for custom chemicals -->
                        <div id="customChemicalsContainer"></div>
                    </div>

                    <!-- Devices Section -->
                    <div class="devices-section">
                        <div class="treatment-type" data-type="devices">
                            <div class="treatment-type-header">
                                <h4>Devices</h4>
                                <i class='bx bx-chevron-down toggle-icon'></i>
                            </div>
                            <div class="treatment-type-content">
                                <div class="chemical-items">
                                    <?php foreach ($devicesList as $device): ?>
                                    <div class="chemical-item">
                                        <label>
                                            <input type="checkbox" 
                                                   name="devices[]" 
                                                   value="<?php echo htmlspecialchars($device['value']); ?>" 
                                                   <?php echo in_array($device['value'], $savedDevices ?? []) ? 'checked' : ''; ?>>
                                            <?php echo htmlspecialchars($device['label']); ?>
                                        </label>
                                        <!-- Fixed name attribute to ensure proper form submission -->
                                        <input type="number" 
                                               name="device_qty[<?php echo htmlspecialchars($device['value']); ?>]" 
                                               class="quantity-input device-qty" 
                                               placeholder="Qty" 
                                               min="0" 
                                               step="1" 
                                               value="<?php echo isset($deviceQuantities[$device['value']]) ? htmlspecialchars($deviceQuantities[$device['value']]) : '0'; ?>">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Move Add Custom Chemical Button Below Devices -->
                    <div class="add-chemical-btn-container">
                        <button type="button" class="add-chemical-btn">+ Add Custom Chemical</button>
                    </div>
                </div>
            </div>

            <!-- Technician Assignment Section -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class='bx bx-user-check'></i> Technician Assignment</h3>
                </div>
                <div class="section-content">
                    <?php if (!empty($assignedTechs)): ?>
                        <div class="current-techs">
                            <h4>Currently Assigned Technicians:</h4>
                            <div class="assigned-techs-list">
                                <?php foreach ($assignedTechs as $assignedId): ?>
                                    <?php 
                                    foreach ($technicians as $tech) {
                                        if ($tech['id'] == $assignedId) {
                                    ?>
                                        <div class="assigned-tech">
                                            <i class='bx bx-user'></i>
                                            <span><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></span>
                                        </div>
                                    <?php
                                        }
                                    }
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="tech-assignments">
                        <h4>Assign Technicians</h4>
                        <?php if (empty($technicians)): ?>
                            <p class="no-techs-message">No verified technicians available. Please add technicians first.</p>
                        <?php else: ?>
                            <div class="tech-selection">
                                <?php foreach ($technicians as $tech): ?>
                                    <div class="tech-option">
                                        <input type="checkbox" 
                                               name="technician_ids[]" 
                                               value="<?php echo $tech['id']; ?>"
                                               id="tech_<?php echo $tech['id']; ?>"
                                               <?php echo in_array($tech['id'], $assignedTechs) ? 'checked' : ''; ?>>
                                        <label for="tech_<?php echo $tech['id']; ?>">
                                            <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="submit" class="save-btn">
                    <i class='bx bx-save'></i> Save Changes
                </button>
            </div>
        </form>
    </div>
    <script>
    // Updated toggle functions (removed preventDefault/stopPropagation)
    function toggleTreatmentType(event) {
        const header = event.currentTarget;
        const type = header.closest('.treatment-type');
        const content = type.querySelector('.treatment-type-content');
        const icon = header.querySelector('.toggle-icon');
        type.classList.toggle('active');
        if (type.classList.contains('active')) {
            content.style.display = 'block';
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
        } else {
            content.style.display = 'none';
            icon.classList.remove('bx-chevron-up');
            icon.classList.add('bx-chevron-down');
        }
    }
    function toggleTreatmentMethod(event) {
        const header = event.currentTarget;
        const method = header.closest('.treatment-method');
        const content = method.querySelector('.treatment-method-content');
        const icon = header.querySelector('.toggle-icon');
        method.classList.toggle('active');
        if (method.classList.contains('active')) {
            content.style.display = 'block';
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
        } else {
            content.style.display = 'none';
            icon.classList.remove('bx-chevron-up');
            icon.classList.add('bx-chevron-down');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Attach event listeners for treatment type and method headers
        document.querySelectorAll('.treatment-type-header').forEach(header => {
            header.addEventListener('click', toggleTreatmentType);
        });
        document.querySelectorAll('.treatment-method-header').forEach(header => {
            header.addEventListener('click', toggleTreatmentMethod);
        });
        
        document.getElementById('jobDetailsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading indicator
            const saveButton = document.querySelector('.save-btn');
            const originalButtonText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';
            saveButton.disabled = true;
            
            // Use FormData to get form data, which will properly capture all fields
            const formData = new FormData(this);
            
            // Submit form
            fetch('../PHP CODES/process_job_details.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Restore button
                saveButton.innerHTML = originalButtonText;
                saveButton.disabled = false;     
                if (data.success) {
                    // Show success message using SweetAlert2
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: data.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message using SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Unknown error'
                    });
                }
            })
            .catch(error => {
                // Restore button
                saveButton.innerHTML = originalButtonText;
                saveButton.disabled = false;
                
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while saving'
                });
            });
        });
        
        // Auto-expand sections with checked items
        document.querySelectorAll('input[name="chemicals[]"]:checked').forEach(checkbox => {
            // Find and expand parent treatment method
            const method = checkbox.closest('.treatment-method');
            if (method) {
                method.classList.add('active');
                const methodContent = method.querySelector('.treatment-method-content');
                const methodIcon = method.querySelector('.treatment-method-header .toggle-icon');
                if (methodContent) methodContent.style.display = 'block';
                if (methodIcon) {
                    methodIcon.classList.remove('bx-chevron-down');
                    methodIcon.classList.add('bx-chevron-up');
                }
            }
            
            // Find and expand parent treatment type
            const type = checkbox.closest('.treatment-type');
            if (type) {
                type.classList.add('active');
                const typeContent = type.querySelector('.treatment-type-content');
                const typeIcon = type.querySelector('.treatment-type-header .toggle-icon');
                if (typeContent) typeContent.style.display = 'block';
                if (typeIcon) {
                    typeIcon.classList.remove('bx-chevron-down');
                    typeIcon.classList.add('bx-chevron-up');
                }
            }
        });
    });
    </script>
</body>
</html>