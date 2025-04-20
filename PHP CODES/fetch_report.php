<?php
session_start();
require_once '../database.php';

// This is a utility script to help debug report data

// Check if user is logged in with appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'technician', 'supervisor'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    if (isset($_GET['id'])) {
        // Get a specific report with all necessary fields
        $stmt = $db->prepare("SELECT 
            sr.*,
            CONCAT(u.firstname, ' ', u.lastname) AS tech_name
        FROM service_reports sr
        JOIN users u ON sr.technician_id = u.id
        WHERE sr.report_id = ?");
        $stmt->execute([$_GET['id']]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($report) {
            // Process photos if needed
            if (isset($report['photos']) && !empty($report['photos'])) {
                // Check if it's already JSON or a string
                if (!is_array($report['photos']) && $report['photos'][0] !== '[') {
                    // It's likely a single filename - wrap it as a JSON array
                    $report['photos'] = json_encode([$report['photos']]);
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $report]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Report not found']);
        }
    } else {
        // Get all reports with pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        // Get total count
        $countStmt = $db->query("SELECT COUNT(*) FROM service_reports");
        $total = $countStmt->fetchColumn();
        
        // Get reports for current page
        $stmt = $db->prepare("SELECT 
            sr.*,
            CONCAT(u.firstname, ' ', u.lastname) AS tech_name
        FROM service_reports sr
        JOIN users u ON sr.technician_id = u.id
        ORDER BY sr.date_of_treatment DESC
        LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process photos for each report
        foreach ($reports as &$report) {
            if (isset($report['photos']) && !empty($report['photos'])) {
                // Check if it's already JSON or a string
                if (!is_array($report['photos']) && $report['photos'][0] !== '[') {
                    // It's likely a single filename - wrap it as a JSON array
                    $report['photos'] = json_encode([$report['photos']]);
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'count' => count($reports), 
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'data' => $reports
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error in fetch_report.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
