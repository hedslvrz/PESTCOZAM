<?php
// API endpoint to get all visits for a specific follow-up plan
require_once '../includes/config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if plan_id is provided
if (!isset($_GET['plan_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing plan_id parameter']);
    exit;
}

$plan_id = intval($_GET['plan_id']);

// Get all visits for the plan from the view
$stmt = $conn->prepare("
    SELECT 
        sf.visit_id,
        sf.visit_seq,
        sf.visit_date,
        sf.visit_time,
        sf.visit_status,
        sf.tech_id,
        sf.visit_notes,
        CONCAT(u.firstname, ' ', u.lastname) as technician_name
    FROM scheduled_followups sf
    LEFT JOIN users u ON sf.tech_id = u.id
    WHERE sf.plan_id = ? AND sf.visit_id IS NOT NULL
    ORDER BY sf.visit_seq
");

$stmt->bind_param("i", $plan_id);
$stmt->execute();
$result = $stmt->get_result();

$visits = [];
while ($row = $result->fetch_assoc()) {
    $visits[] = [
        'visit_id' => $row['visit_id'],
        'visit_seq' => $row['visit_seq'],
        'visit_date' => $row['visit_date'],
        'visit_time' => $row['visit_time'],
        'visit_status' => $row['visit_status'],
        'technician_id' => $row['tech_id'],
        'technician_name' => $row['technician_name'],
        'visit_notes' => $row['visit_notes']
    ];
}

// Return the visits as JSON
echo json_encode($visits);
