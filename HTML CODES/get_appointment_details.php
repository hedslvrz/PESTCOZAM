<?php
session_start();
require_once "../database.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT a.*, s.service_name, s.service_id,
              CASE 
                  WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                  ELSE CONCAT(a.firstname, ' ', a.lastname)
              END as client_name,
              CONCAT(t.firstname, ' ', t.lastname) as technician_name,
              a.is_for_self
              FROM appointments a 
              JOIN services s ON a.service_id = s.service_id 
              JOIN users u ON a.user_id = u.id
              LEFT JOIN users t ON a.technician_id = t.id
              WHERE a.id = ? AND a.user_id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($appointment) {
        // Format the date and time
        $appointment['appointment_date'] = date('F d, Y', strtotime($appointment['appointment_date']));
        $appointment['appointment_time'] = date('h:i A', strtotime($appointment['appointment_time']));

        echo json_encode([
            'success' => true,
            'details' => $appointment
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
