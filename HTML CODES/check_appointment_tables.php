<?php
session_start();
require_once '../database.php';

// Check if user is authorized
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin')) {
    echo "Unauthorized access";
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Function to display data in a readable format
function displayTable($title, $data) {
    echo "<h3>$title</h3>";
    
    if (empty($data)) {
        echo "<p>No data found</p>";
        return;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    
    // Get column names from first row
    echo "<tr>";
    foreach (array_keys($data[0]) as $column) {
        echo "<th>" . htmlspecialchars($column) . "</th>";
    }
    echo "</tr>";
    
    // Get data rows
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

// Check database tables
try {
    // Check appointments table structure
    $tableStructureQuery = "DESCRIBE appointments";
    $stmt = $db->prepare($tableStructureQuery);
    $stmt->execute();
    $appointmentsStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check appointment_technicians table structure
    $techTableStructureQuery = "DESCRIBE appointment_technicians";
    $stmt = $db->prepare($techTableStructureQuery);
    $stmt->execute();
    $techTableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent appointments
    $recentAppointmentsQuery = "SELECT 
        id, user_id, service_id, appointment_date, appointment_time, 
        technician_id, status, created_at
    FROM appointments 
    ORDER BY id DESC 
    LIMIT 10";
    $stmt = $db->prepare($recentAppointmentsQuery);
    $stmt->execute();
    $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent technician assignments
    $techAssignmentsQuery = "SELECT * FROM appointment_technicians ORDER BY id DESC LIMIT 10";
    $stmt = $db->prepare($techAssignmentsQuery);
    $stmt->execute();
    $techAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count appointments by status
    $statusQuery = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
    $stmt = $db->prepare($statusQuery);
    $stmt->execute();
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count completed appointments with technicians
    $completedWithTechQuery = "SELECT 
        COUNT(DISTINCT a.id) as appointment_count,
        COUNT(at.id) as technician_assignment_count
    FROM appointments a
    LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
    WHERE a.status = 'Completed'";
    $stmt = $db->prepare($completedWithTechQuery);
    $stmt->execute();
    $completedWithTechData = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
    exit();
}

// Function to test appointment creation
function createTestAppointment($db) {
    try {
        // Get a sample user
        $userQuery = "SELECT id FROM users WHERE role = 'customer' LIMIT 1";
        $stmt = $db->prepare($userQuery);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return "No customer users found to create test appointment.";
        }
        
        $userId = $user['id'];
        
        // Get a sample service
        $serviceQuery = "SELECT service_id FROM services LIMIT 1";
        $stmt = $db->prepare($serviceQuery);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            return "No services found to create test appointment.";
        }
        
        $serviceId = $service['service_id'];
        
        // Get a sample technician
        $techQuery = "SELECT id FROM users WHERE role = 'technician' LIMIT 1";
        $stmt = $db->prepare($techQuery);
        $stmt->execute();
        $technician = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$technician) {
            return "No technician users found to create test appointment.";
        }
        
        $technicianId = $technician['id'];
        
        // Insert test appointment
        $db->beginTransaction();
        
        $appointmentQuery = "INSERT INTO appointments (
            user_id, service_id, region, province, city, barangay, street_address,
            appointment_date, appointment_time, status, technician_id, is_for_self,
            firstname, lastname, email, mobile_number
        ) VALUES (
            :user_id, :service_id, 'Test Region', 'Test Province', 'Test City', 'Test Barangay', 'Test Street',
            CURDATE(), '09:00:00', 'Confirmed', :technician_id, 1,
            'Test', 'User', 'test@example.com', '09123456789'
        )";
        
        $stmt = $db->prepare($appointmentQuery);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':service_id', $serviceId);
        $stmt->bindParam(':technician_id', $technicianId);
        $stmt->execute();
        
        $appointmentId = $db->lastInsertId();
        
        // Insert technician assignment
        $techAssignQuery = "INSERT INTO appointment_technicians (appointment_id, technician_id) VALUES (:appointment_id, :technician_id)";
        $stmt = $db->prepare($techAssignQuery);
        $stmt->bindParam(':appointment_id', $appointmentId);
        $stmt->bindParam(':technician_id', $technicianId);
        $stmt->execute();
        
        $db->commit();
        
        return "Successfully created test appointment #$appointmentId with technician #$technicianId";
        
    } catch(PDOException $e) {
        $db->rollBack();
        return "Error creating test appointment: " . $e->getMessage();
    }
}

// Handle test appointment creation
$testResult = "";
if (isset($_POST['create_test'])) {
    $testResult = createTestAppointment($db);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #144578;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .status {
            display: flex;
            margin-bottom: 20px;
        }
        .status-item {
            padding: 15px;
            margin-right: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        button {
            background-color: #144578;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .success {
            color: green;
            background-color: #e8f5e9;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .error {
            color: red;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #144578;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Appointment System Database Diagnostics</h1>
    
    <div class="section">
        <h2>System Status</h2>
        
        <div class="status">
            <?php foreach ($statusCounts as $status): ?>
                <div class="status-item">
                    <strong><?php echo $status['status']; ?>:</strong> <?php echo $status['count']; ?> appointments
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="status">
            <div class="status-item">
                <strong>Completed Appointments:</strong> <?php echo $completedWithTechData['appointment_count']; ?>
            </div>
            <div class="status-item">
                <strong>Technician Assignments:</strong> <?php echo $completedWithTechData['technician_assignment_count']; ?>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2>Test Appointment Creation</h2>
        
        <form method="post">
            <button type="submit" name="create_test">Create Test Appointment</button>
        </form>
        
        <?php if (!empty($testResult)): ?>
            <div class="<?php echo (strpos($testResult, 'Error') !== false) ? 'error' : 'success'; ?>">
                <?php echo $testResult; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Database Tables</h2>
        
        <?php displayTable("Appointments Table Structure", $appointmentsStructure); ?>
        
        <?php displayTable("Appointment Technicians Table Structure", $techTableStructure); ?>
    </div>
    
    <div class="section">
        <h2>Recent Data</h2>
        
        <?php displayTable("Recent Appointments", $recentAppointments); ?>
        
        <?php displayTable("Recent Technician Assignments", $techAssignments); ?>
    </div>
    
    <a href="dashboard-aos.php" class="back-link">← Return to Dashboard</a>
</body>
</html>
