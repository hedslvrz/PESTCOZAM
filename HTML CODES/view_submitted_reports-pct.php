<?php
session_start();
require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in and has PCT role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header("Location: login.php");
    exit();
}

// Get technician ID from session
$technicianId = $_SESSION['user_id'];

// Get technician's submitted reports
try {
    // Fetch submitted reports for the logged-in technician
    $reportsQuery = "SELECT 
        sr.id as report_id,
        sr.date_of_treatment,
        sr.status,
        sr.treatment_type,
        sr.location,
        CONCAT(a.firstname, ' ', a.lastname) as client_name
    FROM service_reports sr
    LEFT JOIN appointments a ON sr.appointment_id = a.id
    WHERE sr.technician_id = :technicianId
    ORDER BY sr.date_of_treatment DESC";

    $stmt = $db->prepare($reportsQuery);
    $stmt->execute([':technicianId' => $_SESSION['user_id']]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($reports)) {
        $reports = []; // Ensure $reports is an empty array if no results are found
    }

} catch (PDOException $e) {
    error_log("Error fetching submitted reports: " . $e->getMessage());
    $_SESSION['error'] = "Error loading submitted reports.";
    $reports = [];
}

// Get technician data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'technician'");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $technician = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Reports - PESTCOZAM</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../CSS CODES/view-submitted-reports.css">
</head>
<body>
    <div class="submitted-reports-container">
        <div class="head-title">
            <div class="left">
                <h1>Submitted Reports</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard-pct.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Submitted Reports</a></li>
                </ul>
            </div>
            <a href="dashboard-pct.php#submit-report" class="back-btn">
                <i class='bx bx-arrow-back'></i> Back to Submit Report
            </a>
        </div>

        <?php if (empty($reports)): ?>
            <div class="no-reports">
                <i class='bx bx-file'></i>
                <p>You haven't submitted any reports yet.</p>
            </div>
        <?php else: ?>
            <div class="reports-list">
                <?php foreach ($reports as $report): ?>
                    <div class="report-card">
                        <div class="report-header">
                            <h3>Report #<?php echo htmlspecialchars($report['report_id']); ?></h3>
                            <span class="status-badge <?php echo strtolower($report['status']); ?>">
                                <?php echo ucfirst($report['status']); ?>
                            </span>
                        </div>
                        <div class="report-content">
                            <div class="info-group">
                                <label>Account/Client</label>
                                <p><?php echo htmlspecialchars($report['client_name']); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Location</label>
                                <p><?php echo htmlspecialchars($report['location']); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Treatment Date</label>
                                <p><?php echo date('F d, Y', strtotime($report['date_of_treatment'])); ?></p>
                            </div>
                            <div class="info-group">
                                <label>Treatment Type</label>
                                <p><?php echo htmlspecialchars($report['treatment_type']); ?></p>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <a href="view_submitted_reports-pct.php?id=<?php echo $report['report_id']; ?>" class="view-report-btn">
                                <i class='bx bx-file-find'></i> View Report
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
