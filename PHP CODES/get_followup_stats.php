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
    
    // Get today's date and other date boundaries
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    $monthStart = date('Y-m-d', strtotime('first day of this month'));
    $monthEnd = date('Y-m-d', strtotime('last day of this month'));
    
    // Get counts of followups for different periods
    $todayQuery = "SELECT COUNT(*) as count FROM appointments 
                  WHERE appointment_date = :today AND status = 'confirmed'";
    $todayStmt = $db->prepare($todayQuery);
    $todayStmt->execute(['today' => $today]);
    $todayCount = $todayStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $weekQuery = "SELECT COUNT(*) as count FROM appointments 
                 WHERE appointment_date BETWEEN :start AND :end AND status = 'confirmed'";
    $weekStmt = $db->prepare($weekQuery);
    $weekStmt->execute(['start' => $weekStart, 'end' => $weekEnd]);
    $weekCount = $weekStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $monthQuery = "SELECT COUNT(*) as count FROM appointments 
                  WHERE appointment_date BETWEEN :start AND :end AND status = 'confirmed'";
    $monthStmt = $db->prepare($monthQuery);
    $monthStmt->execute(['start' => $monthStart, 'end' => $monthEnd]);
    $monthCount = $monthStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $totalQuery = "SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed'";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $totalCount = $totalStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get upcoming followups (next 5)
    $upcomingQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as client_name,
        s.service_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN services s ON a.service_id = s.service_id
        WHERE a.appointment_date >= :today AND a.status = 'confirmed'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 5";
    
    $upcomingStmt = $db->prepare($upcomingQuery);
    $upcomingStmt->execute(['today' => $today]);
    $upcomingFollowups = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the data
    echo json_encode([
        'success' => true,
        'today' => (int)$todayCount,
        'this_week' => (int)$weekCount,
        'this_month' => (int)$monthCount,
        'total' => (int)$totalCount,
        'upcoming_followups' => $upcomingFollowups
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
