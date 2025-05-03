<?php
session_start();
require_once '../database.php';
require_once 'mailer.php';

// Set content type to JSON and disable output buffering
header('Content-Type: application/json');
ob_start();

// Check if user is authorized
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get appointment ID from the form
$appointmentId = $_POST['appointment_id'] ?? null;

if (!$appointmentId) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
    exit();
}

try {
    // Initialize database
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // Get basic form data
    $methods = $_POST['method'] ?? [];
    $chemicals = $_POST['chemicals'] ?? [];
    $devices = $_POST['devices'] ?? [];
    
    // Create arrays to store the quantities
    $chemical_qty = [];
    $device_qty = [];
    
    // Process chemical quantities - collect them directly from form data
    foreach ($chemicals as $chemical) {
        $qty = isset($_POST['chemical_qty'][$chemical]) ? (int)$_POST['chemical_qty'][$chemical] : 0;
        $chemical_qty[] = $qty;
    }
    
    // Process device quantities - simplified and direct approach
    if (!empty($devices) && isset($_POST['device_qty']) && is_array($_POST['device_qty'])) {
        foreach ($devices as $device) {
            // Try to get the quantity directly from the POST array
            $qty = 0;
            if (isset($_POST['device_qty'][$device])) {
                $qty = (int)$_POST['device_qty'][$device];
            }
            
            // Add the quantity to our ordered array
            $device_qty[] = $qty;
        }
    }

    // Encode data as JSON for storage
    $methodsJson = !empty($methods) ? json_encode($methods) : null;
    $chemicalsJson = !empty($chemicals) ? json_encode($chemicals) : null;
    $quantitiesJson = !empty($chemical_qty) ? json_encode($chemical_qty) : null;
    $devicesJson = !empty($devices) ? json_encode($devices) : null;
    $deviceQuantitiesJson = !empty($device_qty) ? json_encode($device_qty) : null;
    
    // Process other form data
    $pct = $_POST['pct'] ?? '';
    $deviceInstallation = $_POST['device_installation'] ?? '';
    $chemicalConsumables = $_POST['chemical_consumables'] ?? '';
    $visitFrequency = $_POST['visit_frequency'] ?? '';
    
    // Process time information
    $timeIn = $_POST['time_in'] ?? null;
    $timeOut = $_POST['time_out'] ?? null;
    
    // Get selected technician IDs
    $technicianIds = $_POST['technician_ids'] ?? [];
    $technicianIds = is_array($technicianIds) ? $technicianIds : [];
    
    // Clear existing technicians first
    $clearStmt = $db->prepare("DELETE FROM appointment_technicians WHERE appointment_id = ?");
    $clearStmt->execute([$appointmentId]);
    
    // Insert new technician assignments
    if (!empty($technicianIds)) {
        $insertStmt = $db->prepare("INSERT INTO appointment_technicians (appointment_id, technician_id) VALUES (?, ?)");
        
        foreach ($technicianIds as $techId) {
            $insertStmt->execute([$appointmentId, $techId]);
        }
        
        // Update the main technician in the appointments table (first technician as primary)
        $updateStmt = $db->prepare("UPDATE appointments SET technician_id = ?, status = 'Confirmed' WHERE id = ?");
        $updateStmt->execute([$technicianIds[0], $appointmentId]);
    }

    // Save treatment details to appointment
    $updateStmt = $db->prepare("UPDATE appointments SET 
        treatment_methods = ?, 
        chemicals = ?, 
        chemical_quantities = ?,
        devices = ?, 
        device_quantities = ?, 
        pct = ?,
        device_installation = ?,
        chemical_consumables = ?,
        visit_frequency = ?,
        time_in = ?,
        time_out = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
        
    $params = [
        $methodsJson,
        $chemicalsJson,
        $quantitiesJson,
        $devicesJson,
        $deviceQuantitiesJson,
        $_POST['pct'] ?? null,
        $_POST['device_installation'] ?? null,
        $_POST['chemical_consumables'] ?? null,
        $_POST['visit_frequency'] ?? null,
        $_POST['time_in'] ?? null,
        $_POST['time_out'] ?? null,
        $appointmentId
    ];
    
    $updateStmt->execute($params);
    
    // Commit transaction
    $db->commit();
    
    // Send technician notification email if technicians were assigned
    if (!empty($technicianIds)) {
        // Get appointment details
        $stmt = $db->prepare("SELECT 
            a.*,
            s.service_name,
            CONCAT(u.firstname, ' ', u.lastname) as customer_name,
            u.email as customer_email,
            u.mobile_number as customer_phone
        FROM appointments a
        JOIN services s ON a.service_id = s.service_id
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ?");
        
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get technician details
        $techStmt = $db->prepare("SELECT t.firstname, t.lastname, t.mobile_number 
                                FROM users t 
                                WHERE t.id IN (" . implode(',', array_fill(0, count($technicianIds), '?')) . ")");
        $techStmt->execute($technicianIds);
        $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Only send email if we have customer email and technician data
        if (!empty($appointment['customer_email']) && !empty($technicians)) {
            // Format the appointment date and time
            $formattedDate = date('F d, Y', strtotime($appointment['appointment_date']));
            $formattedTime = date('h:i A', strtotime($appointment['appointment_time']));
            
            // Build technicians list HTML
            $techniciansList = '';
            foreach ($technicians as $tech) {
                $techniciansList .= '<li>' . htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']) . ' - ' . 
                                    htmlspecialchars($tech['mobile_number']) . '</li>';
            }
            
            // Get customer name
            $customerName = $appointment['customer_name'];
            if (empty($customerName)) {
                $customerName = $appointment['is_for_self'] ? 'Client' : ($appointment['firstname'] . ' ' . $appointment['lastname']);
            }
            
            // Build treatments and chemicals list for email (if any)
            $treatmentMethodsList = '';
            if (!empty($methods)) {
                $treatmentMethodsList .= '<h4>Treatment Methods:</h4><ul>';
                foreach ($methods as $method) {
                    $treatmentMethodsList .= '<li>' . ucfirst(htmlspecialchars($method)) . '</li>';
                }
                $treatmentMethodsList .= '</ul>';
            }
            
            $chemicalsList = '';
            if (!empty($chemicals)) {
                $chemicalsList .= '<h4>Chemicals & Materials to be Used:</h4><ul>';
                foreach ($chemicals as $index => $chemical) {
                    $quantity = $chemical_qty[$index] ?? 0;
                    $chemicalsList .= '<li>' . htmlspecialchars($chemical) . 
                                    ($quantity > 0 ? ' (' . $quantity . ')' : '') . '</li>';
                }
                $chemicalsList .= '</ul>';
            }
            
            // Prepare email content
            $emailSubject = "PESTCOZAM - Technician Assigned to Your Appointment";
            $emailBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; }
                        .header { background-color: #1e88e5; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { padding: 20px; border: 1px solid #e0e0e0; border-top: none; }
                        .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #757575; padding: 10px; background-color: #f5f5f5; }
                        .technician-info { background-color: #e3f2fd; padding: 15px; border-left: 4px solid #1e88e5; margin: 15px 0; }
                        .treatment-info { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #4caf50; margin: 15px 0; }
                        ul { padding-left: 20px; }
                        li { margin-bottom: 8px; }
                        h4 { margin: 10px 0; color: #333; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Technician Assignment Notification</h2>
                        </div>
                        <div class='content'>
                            <p>Dear " . htmlspecialchars($customerName) . ",</p>
                            <p>We are pleased to inform you that technicians have been assigned to your upcoming appointment with PESTCOZAM:</p>
                            <ul>
                                <li><strong>Service:</strong> " . htmlspecialchars($appointment['service_name']) . "</li>
                                <li><strong>Date:</strong> " . $formattedDate . "</li>
                                <li><strong>Time:</strong> " . $formattedTime . "</li>
                                <li><strong>Location:</strong> " . htmlspecialchars($appointment['street_address'] . ', ' . 
                                    $appointment['barangay'] . ', ' . $appointment['city']) . "</li>
                            </ul>
                            
                            <div class='technician-info'>
                                <h3>Your Assigned Technician(s):</h3>
                                <ul>
                                    $techniciansList
                                </ul>
                            </div>";
                            
            // Only include treatment details if they exist
            if (!empty($treatmentMethodsList) || !empty($chemicalsList)) {
                $emailBody .= "<div class='treatment-info'>
                                <h3>Treatment Details:</h3>
                                $treatmentMethodsList
                                $chemicalsList
                              </div>";
            }
                            
            $emailBody .= "<p>Our technician(s) will arrive at your location during the scheduled time. If you need to make any changes to your appointment or have any questions, please contact us at 0905-177-5662.</p>
                            <p>Thank you for choosing PESTCOZAM for your pest control needs!</p>
                        </div>
                        <div class='footer'>
                            <p>© 2025 PESTCOZAM. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Send the email
            $emailResult = sendEmail($appointment['customer_email'], $emailSubject, $emailBody);
        }
    }
    
    // Clear output buffer and send JSON response
    ob_end_clean();
    echo json_encode([
        'success' => true, 
        'message' => !empty($technicianIds) ? 
            'Job details saved and customer notified of technician assignment.' : 
            'Job details saved successfully.'
    ]);
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
