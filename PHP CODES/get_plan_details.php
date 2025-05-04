<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

// Check if user is logged in and has technician role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if plan ID is provided
if(!isset($_GET['plan_id']) || empty($_GET['plan_id'])) {
    echo json_encode(['error' => 'Plan ID is required']);
    exit();
}

$planId = (int)$_GET['plan_id'];

try {
    // Get plan details
    $planQuery = "SELECT 
        fp.id as plan_id, 
        fp.appointment_id,
        fp.plan_type,
        fp.visit_frequency,
        fp.contract_duration,
        fp.duration_unit,
        fp.notes,
        fp.created_at,
        a.service_id,
        s.service_name,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as customer_name,
        CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location
    FROM followup_plan fp
    JOIN appointments a ON fp.appointment_id = a.id
    JOIN users u ON a.user_id = u.id
    JOIN services s ON a.service_id = s.service_id
    WHERE fp.id = :plan_id
    AND (fp.created_by = :user_id OR a.technician_id = :user_id2 OR EXISTS (
        SELECT 1 FROM appointment_technicians at WHERE at.appointment_id = a.id AND at.technician_id = :user_id3
    ))";
    
    $stmt = $db->prepare($planQuery);
    $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':user_id2', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':user_id3', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$plan) {
        echo json_encode(['error' => 'Plan not found or you do not have permission to view it']);
        exit();
    }
    
    // Get all visits for this plan
    $visitsQuery = "SELECT 
        fv.id as visit_id,
        fv.visit_date,
        fv.visit_time,
        fv.status,
        GROUP_CONCAT(DISTINCT CONCAT(t.firstname, ' ', t.lastname) SEPARATOR ', ') as technician_name
    FROM followup_visits fv
    LEFT JOIN appointments a ON fv.appointment_id = a.id
    LEFT JOIN users t ON a.technician_id = t.id
    LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
    LEFT JOIN users t2 ON at.technician_id = t2.id
    WHERE fv.followup_plan_id = :plan_id
    GROUP BY fv.id
    ORDER BY fv.visit_date ASC, fv.visit_time ASC";
    
    $stmt = $db->prepare($visitsQuery);
    $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
    $stmt->execute();
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedVisits = [];
    foreach($visits as $visit) {
        $formattedVisits[] = [
            'visit_id' => $visit['visit_id'],
            'visit_date' => date('M d, Y', strtotime($visit['visit_date'])),
            'visit_time' => $visit['visit_time'],
            'technician_name' => $visit['technician_name'] ?: 'Not assigned',
            'status' => $visit['status'] ?: 'Scheduled'
        ];
    }
    
    // Generate recurrence dates based on the plan
    $recurrenceDates = [];
    $startDate = new DateTime($visits[0]['visit_date'] ?? $plan['created_at']);
    $interval = match ($plan['plan_type']) {
        'weekly' => new DateInterval('P7D'),
        'monthly' => new DateInterval('P1M'),
        'quarterly' => new DateInterval('P3M'),
        'yearly' => new DateInterval('P1Y'),
        default => null
    };
    if ($interval) {
        $period = new DatePeriod($startDate, $interval, $plan['contract_duration']);
        foreach ($period as $date) {
            $recurrenceDates[] = $date->format('M d, Y');
        }
    }

    // Return the data
    echo json_encode([
        'plan' => $plan,
        'visits' => $formattedVisits,
        'recurrence_dates' => $recurrenceDates
    ]);
    
} catch(PDOException $e) {
    error_log("Error fetching plan details: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load plan details: ' . $e->getMessage()]);
    exit();
}
