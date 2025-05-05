<?php
header('Content-Type: application/json');
require_once '../database.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No appointment ID provided.']);
    exit;
}

$appointment_id = intval($_GET['id']);
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT 
        a.*, 
        s.service_name,
        COALESCE(fv.status, a.status) AS display_status,
        fv.status AS followup_status,
        fv.id AS followup_visit_id,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as client_name,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name,
        u.email as user_email,
        u.mobile_number as user_mobile
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users t ON a.technician_id = t.id
    LEFT JOIN followup_visits fv ON fv.appointment_id = a.id
    WHERE a.id = ?
    LIMIT 1
");
$stmt->bindParam(1, $appointment_id);
$stmt->execute();
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($appointment) {
    // Format date/time for JS modal
    $appointment['appointment_date'] = date('F d, Y', strtotime($appointment['appointment_date']));
    $appointment['appointment_time'] = date('h:i A', strtotime($appointment['appointment_time']));
    echo json_encode(['success' => true, 'appointment' => $appointment]);
} else {
    echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
}
