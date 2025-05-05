<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

// Check for required data
if (!isset($_POST['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
    exit;
}

$appointmentId = $_POST['appointment_id'];
$database = new Database();
$db = $database->getConnection();

try {
    // First check if the columns exist in the database
    $columnsStmt = $db->prepare("SHOW COLUMNS FROM appointments");
    $columnsStmt->execute();
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasDevicesColumn = in_array('devices', $columns);
    $hasDeviceQuantitiesColumn = in_array('device_quantities', $columns);
    
    // Get form data
    $methods = isset($_POST['method']) ? $_POST['method'] : [];
    $chemicals = isset($_POST['chemicals']) ? $_POST['chemicals'] : [];
    $chemicalQty = isset($_POST['chemical_qty']) ? $_POST['chemical_qty'] : [];
    $devices = isset($_POST['devices']) ? $_POST['devices'] : [];
    $deviceQty = isset($_POST['device_qty']) ? $_POST['device_qty'] : [];
    $technicianIds = isset($_POST['technician_ids']) ? $_POST['technician_ids'] : [];
    
    // Process chemical quantities into format for storage
    $chemicalQuantities = [];
    foreach ($chemicals as $chemical) {
        $chemicalQuantities[] = isset($chemicalQty[$chemical]) ? intval($chemicalQty[$chemical]) : 0;
    }
    
    // Process device quantities into format for storage
    $deviceQuantities = [];
    foreach ($devices as $device) {
        $deviceQuantities[] = isset($deviceQty[$device]) ? intval($deviceQty[$device]) : 0;
    }
    
    // JSON encode data for storage
    $methodsJSON = json_encode($methods);
    $chemicalsJSON = json_encode($chemicals);
    $chemicalQuantitiesJSON = json_encode($chemicalQuantities);
    $devicesJSON = json_encode($devices);
    $deviceQuantitiesJSON = json_encode($deviceQuantities);
    
    // Create the update SQL based on whether the columns exist
    $sql = "UPDATE appointments SET treatment_methods = ?, chemicals = ?, chemical_quantities = ?";
    $params = [$methodsJSON, $chemicalsJSON, $chemicalQuantitiesJSON];
    
    // Add devices columns to SQL if they exist
    if ($hasDevicesColumn) {
        $sql .= ", devices = ?";
        $params[] = $devicesJSON;
    }
    
    if ($hasDeviceQuantitiesColumn) {
        $sql .= ", device_quantities = ?";
        $params[] = $deviceQuantitiesJSON;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $appointmentId;
    
    $stmt = $db->prepare($sql);
    $updateSuccess = $stmt->execute($params);
    
    // Update technician assignments
    if ($updateSuccess) {
        // First, delete existing assignments
        $deleteStmt = $db->prepare("DELETE FROM appointment_technicians WHERE appointment_id = ?");
        $deleteStmt->execute([$appointmentId]);
        
        // Then, insert new assignments
        if (!empty($technicianIds)) {
            $insertSql = "INSERT INTO appointment_technicians (appointment_id, technician_id) VALUES (?, ?)";
            $insertStmt = $db->prepare($insertSql);
            
            foreach ($technicianIds as $techId) {
                $insertStmt->execute([$appointmentId, $techId]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Job details saved successfully']);
    } else {
        throw new Exception("Failed to update appointment data");
    }
    
} catch (Exception $e) {
    error_log("Error processing job details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
