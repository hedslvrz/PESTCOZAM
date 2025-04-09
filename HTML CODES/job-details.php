<?php
session_start();
require_once '../database.php';

// Check if appointment ID is provided
$appointmentId = $_GET['id'] ?? null;
if (!$appointmentId) {
    header('Location: dashboard-admin.php#work-orders');
    exit;
}

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
    
    // Debug output for assigned technicians
    error_log("Assigned technicians for appointment $appointmentId: " . json_encode($assignedTechs));
    
} catch(PDOException $e) {
    error_log("Error fetching appointment data: " . $e->getMessage());
    $appointment = null;
    $assignedTechs = [];
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
                    <li><a href="dashboard-admin.php#work-orders">Work Orders</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Job Details</a></li>
                </ul>
            </div>
            <a href="dashboard-admin.php#work-orders" class="back-btn">
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
                            <p class="time-display">09:00 AM</p>
                        </div>
                        <div class="time-field">
                            <label>Time Out</label>
                            <p class="time-display">11:00 AM</p>
                        </div>
                        <div class="time-field">
                            <label>Duration</label>
                            <p class="time-display">2 hours</p>
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
                    <div class="treatment-options">
                        <div class="method-section">
                            <h4>Treatment Method</h4>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="method[]" value="spraying"> Spraying</label>
                                <label><input type="checkbox" name="method[]" value="misting"> Misting</label>
                                <label><input type="checkbox" name="method[]" value="baiting"> Baiting</label>
                                <label><input type="checkbox" name="method[]" value="dusting"> Dusting</label>
                                <button type="button" class="add-method-btn">+ Add Method</button>
                            </div>
                        </div>

                        <div class="chemicals-section">
                            <h4>Chemicals</h4>
                            <div class="checkbox-group chemicals-list">
                                <div class="chemical-item">
                                    <label>
                                        <input type="checkbox" name="chemicals[]" value="chemical1">
                                        Chemical 1
                                    </label>
                                    <input type="number" name="chemical_qty[]" class="quantity-input" placeholder="Qty" min="0" step="0.01">
                                </div>
                                <div class="chemical-item">
                                    <label>
                                        <input type="checkbox" name="chemicals[]" value="chemical2">
                                        Chemical 2
                                    </label>
                                    <input type="number" name="chemical_qty[]" class="quantity-input" placeholder="Qty" min="0" step="0.01">
                                </div>
                                <button type="button" class="add-chemical-btn">+ Add Chemical</button>
                            </div>
                        </div>
                    </div>

                    <div class="additional-info">
                        <div class="info-group">
                            <label>PCT</label>
                            <input type="text" name="pct" class="form-input">
                        </div>
                        <div class="info-group">
                            <label>Device Installation</label>
                            <input type="text" name="device_installation" class="form-input">
                        </div>
                        <div class="info-group">
                            <label>Chemical Consumables</label>
                            <input type="text" name="chemical_consumables" class="form-input">
                        </div>
                        <div class="info-group">
                            <label>Frequency of Visit</label>
                            <select name="visit_frequency" class="form-input">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
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

            // Get all chemical quantities
            const chemicals = [];
            const quantities = [];
            document.querySelectorAll('.chemical-item').forEach(item => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                const qty = item.querySelector('input[type="number"]');
                if (checkbox && checkbox.checked) {
                    chemicals.push(checkbox.value);
                    quantities.push(qty ? (qty.value || '0') : '0');
                }
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

            fetch('process_job_details.php', {
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
                    successMessage.innerHTML = '<i class="bx bx-check-circle"></i> Job details saved successfully';
                    document.querySelector('.job-details-container').prepend(successMessage);
                    
                    // Remove message after 3 seconds
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                    
                    // Reload the page to show updated data
                    window.location.reload();
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

        // Add new treatment method
        document.querySelector('.add-method-btn').addEventListener('click', function() {
            const input = prompt('Enter new treatment method:');
            if (input) {
                const checkboxGroup = this.closest('.checkbox-group');
                const label = document.createElement('label');
                label.innerHTML = `
                    <input type="checkbox" name="method[]" value="${input.toLowerCase()}">
                    ${input}
                `;
                checkboxGroup.insertBefore(label, this);
            }
        });

        // Add new chemical
        document.querySelector('.add-chemical-btn').addEventListener('click', function() {
            const input = prompt('Enter new chemical name:');
            if (input) {
                const checkboxGroup = this.closest('.checkbox-group');
                const div = document.createElement('div');
                div.className = 'chemical-item';
                div.innerHTML = `
                    <label>
                        <input type="checkbox" name="chemicals[]" value="${input.toLowerCase()}">
                        ${input}
                    </label>
                    <input type="number" name="chemical_qty[]" class="quantity-input" placeholder="Qty" min="0" step="0.01">
                `;
                checkboxGroup.insertBefore(div, this);
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
    </script>
</body>
</html>
