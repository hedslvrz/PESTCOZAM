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
    
    // Get counts of reports by status
    $countsQuery = "SELECT 
        status, 
        COUNT(*) as count 
        FROM service_reports 
        GROUP BY status";
    
    $countsStmt = $db->prepare($countsQuery);
    $countsStmt->execute();
    
    // Initialize counts
    $counts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total' => 0
    ];
    
    // Fill in the actual counts
    while ($row = $countsStmt->fetch(PDO::FETCH_ASSOC)) {
        $status = strtolower($row['status']);
        $counts[$status] = (int)$row['count'];
        $counts['total'] += (int)$row['count'];
    }
    
    // Get recent reports (last 5)
    $recentQuery = "SELECT 
        sr.report_id,
        sr.date_of_treatment,
        sr.account_name,
        sr.status,
        CONCAT(u.firstname, ' ', u.lastname) as tech_name
        FROM service_reports sr
        JOIN users u ON sr.technician_id = u.id
        ORDER BY sr.date_of_treatment DESC
        LIMIT 5";
    
    $recentStmt = $db->prepare($recentQuery);
    $recentStmt->execute();
    $recentReports = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the data
    echo json_encode([
        'success' => true,
        'pending' => $counts['pending'],
        'approved' => $counts['approved'],
        'rejected' => $counts['rejected'],
        'total' => $counts['total'],
        'recent_reports' => $recentReports
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
