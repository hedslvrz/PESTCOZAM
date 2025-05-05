<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Get appointment ID
    $appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    if (!$appointmentId) {
        throw new Exception('Invalid appointment ID');
    }
    
    // First update the timestamp in appointments table
    $stmt = $db->prepare("UPDATE appointments SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$appointmentId]);

    // Handle technician assignments
    $technicianIds = $_POST['technician_ids'] ?? [];
    
    // Log received technician IDs
    error_log("Received technician IDs for appointment $appointmentId: " . json_encode($technicianIds));
    
    // Delete existing assignments
    $stmt = $db->prepare("DELETE FROM appointment_technicians WHERE appointment_id = ?");
    $stmt->execute([$appointmentId]);
    error_log("Deleted existing technician assignments for appointment $appointmentId");
    
    // Add new assignments if any technicians are selected
    if (!empty($technicianIds)) {
        $stmt = $db->prepare("INSERT INTO appointment_technicians (appointment_id, technician_id) VALUES (?, ?)");
        foreach ($technicianIds as $techId) {
            try {
                $stmt->execute([$appointmentId, $techId]);
                error_log("Assigned technician $techId to appointment $appointmentId");
            } catch (PDOException $e) {
                // Skip duplicate entries
                if ($e->getCode() != 23000) throw $e;
                error_log("Duplicate entry for technician $techId in appointment $appointmentId: " . $e->getMessage());
            }
        }
        
        // Update the primary technician in the appointments table (using the first selected technician)
        $primaryTechId = $technicianIds[0];
        $stmt = $db->prepare("UPDATE appointments SET technician_id = ? WHERE id = ?");
        $stmt->execute([$primaryTechId, $appointmentId]);
        error_log("Set primary technician $primaryTechId for appointment $appointmentId");
        
        // Always update status to confirmed when technicians are assigned
        $stmt = $db->prepare("UPDATE appointments SET status = 'Confirmed' WHERE id = ?");
        $stmt->execute([$appointmentId]);
        error_log("Updated status to Confirmed for appointment $appointmentId with technicians assigned");
    } else {
        // If no technicians are selected, set primary technician to NULL
        $stmt = $db->prepare("UPDATE appointments SET technician_id = NULL WHERE id = ?");
        $stmt->execute([$appointmentId]);
        error_log("No technicians selected, set primary technician to NULL for appointment $appointmentId");
    }

    // Store treatment details
    // Check if appointment_details table exists, if not create it
    $tableCheckQuery = "SHOW TABLES LIKE 'appointment_details'";
    $tableExists = $db->query($tableCheckQuery)->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table if it doesn't exist
        $createTableQuery = "CREATE TABLE `appointment_details` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `appointment_id` int(11) NOT NULL,
            `chemicals_used` text DEFAULT NULL,
            `chemical_quantities` text DEFAULT NULL,
            `treatment_methods` text DEFAULT NULL,
            `pct` varchar(255) DEFAULT NULL,
            `device_installation` text DEFAULT NULL,
            `chemical_consumables` text DEFAULT NULL,
            `frequency_of_visits` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `appointment_id` (`appointment_id`),
            CONSTRAINT `fk_appointment_details_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $db->exec($createTableQuery);
    }
    
    // Process and store the treatment details
    $chemicals = json_decode($_POST['chemicals'] ?? '[]', true);
    $quantities = json_decode($_POST['chemical_qty'] ?? '[]', true);
    $methods = $_POST['method'] ?? [];
    
    $stmt = $db->prepare("
        INSERT INTO appointment_details 
        (appointment_id, chemicals_used, chemical_quantities, treatment_methods, pct, 
         device_installation, chemical_consumables, frequency_of_visits)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        chemicals_used = VALUES(chemicals_used),
        chemical_quantities = VALUES(chemical_quantities),
        treatment_methods = VALUES(treatment_methods),
        pct = VALUES(pct),
        device_installation = VALUES(device_installation),
        chemical_consumables = VALUES(chemical_consumables),
        frequency_of_visits = VALUES(frequency_of_visits)
    ");
    
    $stmt->execute([
        $appointmentId,
        json_encode($chemicals),
        json_encode($quantities),
        json_encode($methods),
        $_POST['pct'] ?? '',
        $_POST['device_installation'] ?? '',
        $_POST['chemical_consumables'] ?? '',
        $_POST['visit_frequency'] ?? ''
    ]);

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Job details saved successfully',
        'technicians' => $technicianIds
    ]);
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
