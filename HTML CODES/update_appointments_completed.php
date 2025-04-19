<?php
session_start();
require_once '../database.php';

// Only allow access to supervisors/admins
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin')) {
    echo "Unauthorized access";
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Function to update appointment status
function updateAppointmentStatus($db, $appointmentId, $status) {
    try {
        $query = "UPDATE appointments SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $appointmentId);
        
        if ($stmt->execute()) {
            return "Successfully updated appointment #$appointmentId to $status status.";
        } else {
            return "Failed to update appointment #$appointmentId.";
        }
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointmentId = $_POST['appointment_id'];
    $status = $_POST['status'];
    $message = updateAppointmentStatus($db, $appointmentId, $status);
}

// Get all appointments for selection
try {
    $query = "SELECT 
        a.id, 
        a.appointment_date, 
        a.status,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as customer_name,
        s.service_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN services s ON a.service_id = s.service_id
    ORDER BY a.id DESC
    LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
    $message = "Error retrieving appointments: " . $e->getMessage();
}

// Check how many appointments are already completed
try {
    $query = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $statusCounts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Appointments Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #144578;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        form {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        select, button {
            padding: 8px;
            margin: 5px 0;
        }
        button {
            background-color: #144578;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            background-color: #f9f9f9;
            border-left: 5px solid #144578;
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
        .status-counts {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .status-count {
            padding: 10px 15px;
            background: #f5f9ff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <h1>Update Appointments Status</h1>
    
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <h2>Current Status Counts</h2>
    <div class="status-counts">
        <?php foreach ($statusCounts as $status): ?>
            <div class="status-count">
                <strong><?php echo $status['status']; ?>:</strong> 
                <?php echo $status['count']; ?> appointments
            </div>
        <?php endforeach; ?>
    </div>
    
    <form method="POST">
        <h2>Update an Appointment Status</h2>
        <label for="appointment_id">Select Appointment:</label>
        <select id="appointment_id" name="appointment_id" required>
            <?php foreach ($appointments as $appointment): ?>
                <option value="<?php echo $appointment['id']; ?>">
                    #<?php echo $appointment['id']; ?> - 
                    <?php echo htmlspecialchars($appointment['customer_name']); ?> - 
                    <?php echo htmlspecialchars($appointment['service_name']); ?> - 
                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?> - 
                    Status: <?php echo $appointment['status']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="status">New Status:</label>
        <select id="status" name="status" required>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Completed" selected>Completed</option>
            <option value="Canceled">Canceled</option>
        </select>
        
        <button type="submit">Update Status</button>
    </form>
    
    <h2>Recent Appointments</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td>#<?php echo $appointment['id']; ?></td>
                    <td><?php echo htmlspecialchars($appointment['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                    <td><?php echo $appointment['status']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <a href="dashboard-aos.php" class="back-link">← Return to Dashboard</a>
</body>
</html>
