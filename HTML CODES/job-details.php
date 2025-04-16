<?php
session_start();
require_once '../database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
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
$database = new Database();
$db = $database->getConnection();

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
    
    // Debug output for assigned technicians
    error_log("Assigned technicians for appointment $appointmentId: " . json_encode($assignedTechs));
    
} catch(PDOException $e) {
    error_log("Error fetching appointment data: " . $e->getMessage());
    $appointment = null;
    $assignedTechs = [];
    $savedMethods = [];
    $chemicalQuantities = [];
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
    
} catch(PDOException $e) {
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
                'chemicals' => ['Bosny Rat Glue', 'Big Cage Traps', 'Rodent Bait Station']
            ],
            'device_installation' => [
                'name' => 'Device Installation',
                'chemicals' => ['Insect Light Trap']
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

            <!-- Time Slot Section - Middle -->
            <div class="detail-section time-section">
                <div class="section-header">
                    <h3><i class='bx bx-time'></i> Time Information</h3>
                </div>
                <div class="section-content time-content">
                    <div class="time-display-container">
                        <div class="time-field">
                            <label>Time In</label>
                            <input type="time" name="time_in" class="time-input" value="<?php echo $appointment['time_in'] ?? ''; ?>">
                        </div>
                        <div class="time-field">
                            <label>Time Out</label>
                            <input type="time" name="time_out" class="time-input" value="<?php echo $appointment['time_out'] ?? ''; ?>">
                        </div>
                        <div class="time-field">
                            <label>Duration</label>
                            <input type="text" name="duration" class="time-input" readonly>
                        </div>
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
                                                                       name="chemical_qty_<?php echo md5($chemical); ?>" 
                                                                       class="quantity-input" 
                                                                       placeholder="Qty" 
                                                                       min="0" 
                                                                       step="0.01" 
                                                                       value="<?php echo isset($chemicalQuantities[$chemical]) ? htmlspecialchars($chemicalQuantities[$chemical]) : ''; ?>">
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
                        
                        <div id="customChemicalsContainer"></div>
                        
                        <button type="button" class="add-chemical-btn">+ Add Custom Chemical</button>
                    </div>

                    <div class="additional-info">
                        <div class="info-group">
                            <label>PCT</label>
                            <input type="text" name="pct" class="form-input" value="<?php echo htmlspecialchars($appointment['pct'] ?? ''); ?>">
                        </div>
                        <div class="info-group">
                            <label>Device Installation</label>
                            <input type="text" name="device_installation" class="form-input" value="<?php echo htmlspecialchars($appointment['device_installation'] ?? ''); ?>">
                        </div>
                        <div class="info-group">
                            <label>Chemical Consumables</label>
                            <input type="text" name="chemical_consumables" class="form-input" value="<?php echo htmlspecialchars($appointment['chemical_consumables'] ?? ''); ?>">
                        </div>
                        <div class="info-group">
                            <label>Frequency of Visit</label>
                            <select name="visit_frequency" class="form-input">
                                <option value="weekly" <?php echo ($appointment['visit_frequency'] ?? '') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="biweekly" <?php echo ($appointment['visit_frequency'] ?? '') == 'biweekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                <option value="monthly" <?php echo ($appointment['visit_frequency'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="quarterly" <?php echo ($appointment['visit_frequency'] ?? '') == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                            </select>
                        </div>
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
        document.getElementById('jobDetailsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get all selected technicians
            const selectedTechs = [];
            document.querySelectorAll('input[name="technician_ids[]"]:checked').forEach(tech => {
                selectedTechs.push(tech.value);
            });

            // Get all chemical quantities - updated approach
            const chemicals = [];
            const quantities = [];

            // Process all checked chemical checkboxes
            document.querySelectorAll('input[name="chemicals[]"]:checked').forEach(checkbox => {
                const chemicalName = checkbox.value;
                chemicals.push(chemicalName);
                
                // Find the corresponding quantity input using the hashed name
                const quantityInput = document.querySelector(`input[name="chemical_qty_${MD5(chemicalName)}"]`);
                quantities.push(quantityInput ? (quantityInput.value || '0') : '0');
            });

            // Show loading indicator
            const saveButton = document.querySelector('.save-btn');
            const originalButtonText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';
            saveButton.disabled = true;

            // Submit form
            const formData = new FormData(this);
            formData.append('chemicals', JSON.stringify(chemicals));
            formData.append('chemical_qty', JSON.stringify(quantities));

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
                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.innerHTML = '<i class="bx bx-check-circle"></i> ' + data.message;
                    document.querySelector('.job-details-container').prepend(successMessage);
                    
                    // Remove message after 3 seconds
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                    
                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    alert('Error saving job details: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                // Restore button
                saveButton.innerHTML = originalButtonText;
                saveButton.disabled = false;
                
                console.error('Error:', error);
                alert('An error occurred while saving');
            });
        });
        
        // Add new treatment method - Modified for improved styling
        document.querySelector('.add-method-btn').addEventListener('click', function() {
            const input = prompt('Enter new treatment method:');
            if (input && input.trim() !== '') {
                const methodOptions = document.querySelector('.method-options');
                const methodValue = input.toLowerCase().trim();
                const methodId = 'method-' + methodValue.replace(/\s+/g, '-');
                
                const div = document.createElement('div');
                div.className = 'method-option';
                div.innerHTML = `
                    <input type="checkbox" id="${methodId}" name="method[]" value="${methodValue}" checked>
                    <label for="${methodId}">
                        ${input.trim()}
                    </label>
                `;
                methodOptions.appendChild(div);
            }
        });

        // Add new custom chemical
        document.querySelector('.add-chemical-btn').addEventListener('click', function() {
            const input = prompt('Enter new chemical or material name:');
            if (input && input.trim() !== '') {
                // Create a new category for custom chemicals if it doesn't exist yet
                let customContainer = document.getElementById('customChemicalsContainer');
                
                if (!customContainer.querySelector('.custom-chemicals')) {
                    const customType = document.createElement('div');
                    customType.className = 'treatment-type custom-chemicals active';
                    customType.innerHTML = `
                        <div class="treatment-type-header">
                            <h4>Custom Chemicals & Materials</h4>
                            <i class='bx bx-chevron-up toggle-icon'></i>
                        </div>
                        <div class="treatment-type-content" style="display: block;">
                            <div class="treatment-method active">
                                <div class="treatment-method-header">
                                    <h5>Custom Items</h5>
                                    <i class='bx bx-chevron-up toggle-icon'></i>
                                </div>
                                <div class="treatment-method-content" style="display: block;">
                                    <div class="chemical-items custom-items"></div>
                                </div>
                            </div>
                        </div>
                    `;
                    customContainer.appendChild(customType);
                    
                    // Add click events to the new headers
                    customType.querySelector('.treatment-type-header').addEventListener('click', toggleTreatmentType);
                    customType.querySelector('.treatment-method-header').addEventListener('click', toggleTreatmentMethod);
                }
                
                // Add the new chemical to the custom items section
                const customItems = customContainer.querySelector('.custom-items');
                const div = document.createElement('div');
                div.className = 'chemical-item';
                const chemicalName = input.trim();
                div.innerHTML = `
                    <label>
                        <input type="checkbox" name="chemicals[]" value="${chemicalName}" checked>
                        ${chemicalName}
                    </label>
                    <input type="number" name="chemical_qty_${MD5(chemicalName)}" class="quantity-input" placeholder="Qty" min="0" step="0.01" value="0">
                `;
                customItems.appendChild(div);
            }
        });

        // Calculate duration based on time in and time out
        const timeIn = document.querySelector('input[name="time_in"]');
        const timeOut = document.querySelector('input[name="time_out"]');
        const duration = document.querySelector('input[name="duration"]');
        
        function calculateDuration() {
            if (timeIn.value && timeOut.value) {
                const startTime = new Date(`2023-01-01T${timeIn.value}:00`);
                const endTime = new Date(`2023-01-01T${timeOut.value}:00`);
                
                // Handle case where end time is on the next day
                let diff = endTime - startTime;
                if (diff < 0) {
                    diff += 24 * 60 * 60 * 1000; // Add 24 hours in milliseconds
                }
                
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                
                duration.value = `${hours} hour${hours !== 1 ? 's' : ''} ${minutes > 0 ? minutes + ' min' : ''}`;
            } else {
                duration.value = '';
            }
        }
        
        timeIn.addEventListener('change', calculateDuration);
        timeOut.addEventListener('change', calculateDuration);
        
        // Calculate initial duration
        calculateDuration();
        
        // MD5 hash function for creating unique field names
        function MD5(string) {
            function RotateLeft(lValue, iShiftBits) {
                return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
            }
            
            function AddUnsigned(lX,lY) {
                var lX4,lY4,lX8,lY8,lResult;
                lX8 = (lX & 0x80000000);
                lY8 = (lY & 0x80000000);
                lX4 = (lX & 0x40000000);
                lY4 = (lY & 0x40000000);
                lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
                if (lX4 & lY4) {
                    return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
                }
                if (lX4 | lY4) {
                    if (lResult & 0x40000000) {
                        return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                    } else {
                        return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                    }
                } else {
                    return (lResult ^ lX8 ^ lY8);
                }
            }
            
            function F(x,y,z) { return (x & y) | ((~x) & z); }
            function G(x,y,z) { return (x & z) | (y & (~z)); }
            function H(x,y,z) { return (x ^ y ^ z); }
            function I(x,y,z) { return (y ^ (x | (~z))); }
            
            function FF(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function GG(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function HH(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function II(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function ConvertToWordArray(string) {
                var lWordCount;
                var lMessageLength = string.length;
                var lNumberOfWords_temp1=lMessageLength + 8;
                var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
                var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
                var lWordArray=Array(lNumberOfWords-1);
                var lBytePosition = 0;
                var lByteCount = 0;
                while ( lByteCount < lMessageLength ) {
                    lWordCount = (lByteCount-(lByteCount % 4))/4;
                    lBytePosition = (lByteCount % 4)*8;
                    lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                    lByteCount++;
                }
                lWordCount = (lByteCount-(lByteCount % 4))/4;
                lBytePosition = (lByteCount % 4)*8;
                lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
                lWordArray[lNumberOfWords-2] = lMessageLength<<3;
                lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
                return lWordArray;
            };
            
            function WordToHex(lValue) {
                var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
                for (lCount = 0;lCount<=3;lCount++) {
                    lByte = (lValue>>>(lCount*8)) & 255;
                    WordToHexValue_temp = "0" + lByte.toString(16);
                    WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
                }
                return WordToHexValue;
            };
            
            function Utf8Encode(string) {
                string = string.replace(/\r\n/g,"\n");
                var utftext = "";
                
                for (var n = 0; n < string.length; n++) {
                    var c = string.charCodeAt(n);
                    
                    if (c < 128) {
                        utftext += String.fromCharCode(c);
                    } else if ((c > 127) && (c < 2048)) {
                        utftext += String.fromCharCode((c >> 6) | 192);
                        utftext += String.fromCharCode((c & 63) | 128);
                    } else {
                        utftext += String.fromCharCode((c >> 12) | 224);
                        utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                        utftext += String.fromCharCode((c & 63) | 128);
                    }
                }
                
                return utftext;
            };
            
            var x=Array();
            var k,AA,BB,CC,DD,a,b,c,d;
            var S11=7, S12=12, S13=17, S14=22;
            var S21=5, S22=9 , S23=14, S24=20;
            var S31=4, S32=11, S33=16, S34=23;
            var S41=6, S42=10, S43=15, S44=21;
            
            string = Utf8Encode(string);
            
            x = ConvertToWordArray(string);
            
            a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
            
            for (k=0;k<x.length;k+=16) {
                AA=a; BB=b; CC=c; DD=d;
                a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
                d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
                c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
                b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
                a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
                d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
                c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
                b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
                a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
                d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
                c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
                b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
                a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
                d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
                c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
                b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
                a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
                d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
                c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
                b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
                a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
                d=GG(d,a,b,c,x[k+10],S22,0x2441453);
                c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
                b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
                a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
                d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
                c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
                b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
                a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
                d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
                c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
                b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
                a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
                d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
                c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
                b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
                a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
                d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
                c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
                b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
                a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
                d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
                c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
                b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
                a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
                d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
                c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
                b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
                a=II(a,b,c,d,x[k+0], S41,0xF4292244);
                d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
                c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
                b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
                a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
                d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
                c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
                b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
                a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
                d=II(d,a,b,c,x[k+15], S42,0xFE2CE6E0);
                c=II(c,d,a,b,x[k+6], S43,0xA3014314);
                b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
                a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
                d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
                c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
                b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
                a=AddUnsigned(a,AA);
                b=AddUnsigned(b,BB);
                c=AddUnsigned(c,CC);
                d=AddUnsigned(d,DD);
            }
            
            var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
            
            return temp.toLowerCase();
        }

        // Collapsible categories functionality - Fixed event propagation issues
        function toggleTreatmentType(event) {
            event.preventDefault(); // Prevent default behavior
            event.stopPropagation(); // Stop event from bubbling up
            
            const header = event.currentTarget;
            const type = header.closest('.treatment-type');
            const content = type.querySelector('.treatment-type-content');
            const icon = header.querySelector('.toggle-icon');
            
            // Toggle the active class
            type.classList.toggle('active');
            
            // Toggle content display
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
            event.preventDefault(); // Prevent default behavior
            event.stopPropagation(); // Stop event from bubbling up
            
            const header = event.currentTarget;
            const method = header.closest('.treatment-method');
            const content = method.querySelector('.treatment-method-content');
            const icon = header.querySelector('.toggle-icon');
            
            // Toggle the active class
            method.classList.toggle('active');
            
            // Toggle content display
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
        
        // Add event listeners once DOM is loaded - Improved event handling
        document.addEventListener('DOMContentLoaded', function() {
            // Remove any existing event listeners first to prevent duplicates
            const typeHeaders = document.querySelectorAll('.treatment-type-header');
            const methodHeaders = document.querySelectorAll('.treatment-method-header');
            
            // Add listeners to treatment type headers with clean event handling
            typeHeaders.forEach(header => {
                // Remove existing listeners first (if any)
                const newHeader = header.cloneNode(true);
                header.parentNode.replaceChild(newHeader, header);
                
                // Add new listener
                newHeader.addEventListener('click', toggleTreatmentType);
            });
            
            // Add listeners to treatment method headers with clean event handling
            methodHeaders.forEach(header => {
                // Remove existing listeners first (if any)
                const newHeader = header.cloneNode(true);
                header.parentNode.replaceChild(newHeader, header);
                
                // Add new listener
                newHeader.addEventListener('click', toggleTreatmentMethod);
            });
            
            // Make sure chemical checkboxes don't trigger the collapsible functionality
            document.querySelectorAll('.chemical-item input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
            
            // Make sure quantity inputs don't trigger the collapsible functionality
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('click', function(e) {
                    e.stopPropagation();
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
