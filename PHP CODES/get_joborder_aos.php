<?php
session_start();
require_once '../database.php';

// Check if user is logged in as a supervisor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get counts of job orders by status
    $countsQuery = "SELECT 
        status, 
        COUNT(*) as count 
        FROM appointments 
        GROUP BY status";
    
    $countsStmt = $db->prepare($countsQuery);
    $countsStmt->execute();
    
    // Initialize counts
    $counts = [
        'pending' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'total' => 0
    ];
    
    // Fill in the actual counts
    while ($row = $countsStmt->fetch(PDO::FETCH_ASSOC)) {
        $status = strtolower($row['status']);
        $counts[$status] = (int)$row['count'];
        $counts['total'] += (int)$row['count'];
    }
    
    // Get recent job orders (last 5)
    $recentQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.is_for_self,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname, ' aos')
            ELSE CONCAT(a.firstname, ' ', a.lastname, ' aos')
        END as client_name,
        s.service_name,
        CONCAT(t.firstname, ' ', t.lastname, ' aos') as tech_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN services s ON a.service_id = s.service_id
        LEFT JOIN users t ON a.technician_id = t.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 5";
    
    $recentStmt = $db->prepare($recentQuery);
    $recentStmt->execute();
    $recentJobOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get today's job orders
    $todayDate = date('Y-m-d');
    $todayQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname, ' aos')
            ELSE CONCAT(a.firstname, ' ', a.lastname, ' aos')
        END as client_name,
        s.service_name,
        CONCAT(t.firstname, ' ', t.lastname, ' aos') as tech_name,
        (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname, ' aos') SEPARATOR ', ') 
         FROM appointment_technicians at 
         JOIN users u2 ON at.technician_id = u2.id 
         WHERE at.appointment_id = a.id) as all_technicians
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN services s ON a.service_id = s.service_id
        LEFT JOIN users t ON a.technician_id = t.id
        WHERE a.appointment_date = :today
        ORDER BY a.appointment_time ASC";
    
    $todayStmt = $db->prepare($todayQuery);
    $todayStmt->execute(['today' => $todayDate]);
    $todayJobOrders = $todayStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming job orders (next 7 days excluding today)
    $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
    $nextWeekDate = date('Y-m-d', strtotime('+7 days'));
    
    $upcomingQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname, ' aos')
            ELSE CONCAT(a.firstname, ' ', a.lastname, ' aos')
        END as client_name,
        s.service_name,
        CONCAT(t.firstname, ' ', t.lastname, ' aos') as tech_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN services s ON a.service_id = s.service_id
        LEFT JOIN users t ON a.technician_id = t.id
        WHERE a.appointment_date BETWEEN :tomorrow AND :nextWeek
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    $upcomingStmt = $db->prepare($upcomingQuery);
    $upcomingStmt->execute([
        'tomorrow' => $tomorrowDate,
        'nextWeek' => $nextWeekDate
    ]);
    $upcomingJobOrders = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unassigned job orders
    $unassignedQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname, ' aos')
            ELSE CONCAT(a.firstname, ' ', a.lastname, ' aos')
        END as client_name,
        s.service_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN services s ON a.service_id = s.service_id
        LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
        WHERE a.technician_id IS NULL 
        AND at.technician_id IS NULL
        AND a.status IN ('pending', 'confirmed')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 10";
    
    $unassignedStmt = $db->prepare($unassignedQuery);
    $unassignedStmt->execute();
    $unassignedJobOrders = $unassignedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the data
    echo json_encode([
        'success' => true,
        'pending' => $counts['pending'],
        'confirmed' => $counts['confirmed'],
        'completed' => $counts['completed'],
        'cancelled' => $counts['cancelled'] ?? 0,
        'total' => $counts['total'],
        'recent_job_orders' => $recentJobOrders,
        'today_job_orders' => $todayJobOrders,
        'upcoming_job_orders' => $upcomingJobOrders,
        'unassigned_job_orders' => $unassignedJobOrders
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
