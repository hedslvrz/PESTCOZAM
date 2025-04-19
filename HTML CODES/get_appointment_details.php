<?php
session_start();
require_once '../database.php';

// Check if user is authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit();
}

$appointmentId = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

try {
    // Updated query to get appointment details with more complete information
    $query = "SELECT 
        a.id,
        a.user_id,
        a.service_id,
        a.street_address,
        a.barangay,
        a.city,
        a.region,
        a.technician_id,
        a.is_for_self,
        a.firstname,
        a.lastname,
        u.firstname as user_firstname,
        u.lastname as user_lastname,
        s.service_name,
        t.firstname as tech_firstname,
        t.lastname as tech_lastname,
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
    WHERE a.id = :appointment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointmentId);
    $stmt->execute();
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit();
    }
    
    // Format the response with additional information
    $response = [
        'success' => true,
        'appointment_id' => $appointment['id'],
        'user_id' => $appointment['user_id'],
        'service_id' => $appointment['service_id'],
        'service_name' => $appointment['service_name'],
        'address' => [
            'street_address' => $appointment['street_address'],
            'barangay' => $appointment['barangay'],
            'city' => $appointment['city'],
            'region' => $appointment['region']
        ],
        'address_formatted' => trim($appointment['street_address'] . ', ' . $appointment['barangay'] . ', ' . $appointment['city'], ', '),
        'technician_id' => $appointment['technician_id'],
        'technician_name' => $appointment['tech_firstname'] ? $appointment['tech_firstname'] . ' ' . $appointment['tech_lastname'] : null,
        'all_technician_ids' => $appointment['all_technician_ids'] ? explode(',', $appointment['all_technician_ids']) : [],
        'all_technician_names' => $appointment['all_technician_names'] ? $appointment['all_technician_names'] : null,
        'customer' => [
            'firstname' => $appointment['is_for_self'] ? $appointment['user_firstname'] : $appointment['firstname'],
            'lastname' => $appointment['is_for_self'] ? $appointment['user_lastname'] : $appointment['lastname']
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
