<?php
require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Function to print results in a readable format
function printResults($title, $results) {
    echo "<h3>$title</h3>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    echo "<hr>";
}

// Check overall appointment statistics
try {
    $query = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printResults("Appointment Status Counts", $statusCounts);
} catch(PDOException $e) {
    echo "Error checking appointment counts: " . $e->getMessage();
}

// Check if there are any completed appointments WITH TECHNICIAN INFO
try {
    $query = "SELECT 
        a.id, 
        a.appointment_date, 
        a.status,
        a.technician_id,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as customer_name,
        s.service_name,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN services s ON a.service_id = s.service_id
    LEFT JOIN users t ON a.technician_id = t.id
    WHERE a.status = 'Completed'
    ORDER BY a.appointment_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $completedAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printResults("Completed Appointments with Technician Info", $completedAppointments);
} catch(PDOException $e) {
    echo "Error fetching completed appointments: " . $e->getMessage();
}

// Function to update appointment status for testing
function updateAppointmentStatus($db, $appointmentId, $status) {
    try {
        $query = "UPDATE appointments SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $appointmentId);
        $result = $stmt->execute();
        
        if ($result) {
            echo "Successfully updated appointment #$appointmentId to status: $status<br>";
        } else {
            echo "Failed to update appointment #$appointmentId<br>";
        }
    } catch(PDOException $e) {
        echo "Error updating appointment: " . $e->getMessage() . "<br>";
    }
}

// Uncomment this section to enable marking appointments as completed
if (isset($_GET['mark_completed']) && is_numeric($_GET['mark_completed'])) {
    $appointmentId = $_GET['mark_completed'];
    updateAppointmentStatus($db, $appointmentId, 'Completed');
}

// New function to assign technician to appointment
function assignTechnicianToAppointment($db, $appointmentId, $technicianId) {
    try {
        // First update the main technician_id in the appointments table
        $query = "UPDATE appointments SET technician_id = :technician_id WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':technician_id', $technicianId);
        $stmt->bindParam(':id', $appointmentId);
        $result = $stmt->execute();
        
        if ($result) {
            echo "Successfully assigned technician #$technicianId to appointment #$appointmentId<br>";
        } else {
            echo "Failed to assign technician to appointment #$appointmentId<br>";
        }
    } catch(PDOException $e) {
        echo "Error assigning technician: " . $e->getMessage() . "<br>";
    }
}

// Handle technician assignment
if (isset($_GET['assign_technician']) && is_numeric($_GET['appointment_id']) && is_numeric($_GET['technician_id'])) {
    assignTechnicianToAppointment($db, $_GET['appointment_id'], $_GET['technician_id']);
}

// Display all appointments for reference
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
    ORDER BY a.appointment_date DESC
    LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printResults("Recent Appointments (All Statuses)", $recentAppointments);
} catch(PDOException $e) {
    echo "Error fetching recent appointments: " . $e->getMessage();
}

// Get available technicians
try {
    $query = "SELECT id, firstname, lastname FROM users WHERE role = 'technician' ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error fetching technicians: " . $e->getMessage();
    $technicians = [];
}
?>

<!-- Add some basic styling -->
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h3 { color: #144578; margin-top: 30px; }
    a { color: #144578; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .btn { 
        display: inline-block;
        padding: 8px 15px;
        background: #144578;
        color: white;
        border-radius: 4px;
        margin: 5px;
        text-decoration: none;
    }
    .btn:hover { background: #0d2f4f; text-decoration: none; }
    form { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
    select, input { padding: 8px; margin: 5px; }
    label { display: block; margin-top: 10px; font-weight: bold; }
</style>

<h1>Appointment Testing Tool</h1>

<p>
    <strong>Need to mark an appointment as completed?</strong><br>
    <a href="?mark_completed=15" class="btn">Mark Appointment #15 as Completed</a>
    <a href="?mark_completed=16" class="btn">Mark Appointment #16 as Completed</a>
    <a href="?mark_completed=22" class="btn">Mark Appointment #22 as Completed</a>
    <a href="?mark_completed=23" class="btn">Mark Appointment #23 as Completed</a>
</p>

<!-- Add form to assign technician to appointment -->
<form action="" method="GET">
    <h3>Assign Technician to Appointment</h3>
    
    <label for="appointment_id">Select Appointment:</label>
    <select name="appointment_id" id="appointment_id" required>
        <?php foreach ($completedAppointments as $appointment): ?>
            <option value="<?php echo $appointment['id']; ?>">
                #<?php echo $appointment['id']; ?> - 
                <?php echo htmlspecialchars($appointment['customer_name']); ?> - 
                <?php echo htmlspecialchars($appointment['service_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <label for="technician_id">Select Technician:</label>
    <select name="technician_id" id="technician_id" required>
        <?php foreach ($technicians as $technician): ?>
            <option value="<?php echo $technician['id']; ?>">
                #<?php echo $technician['id']; ?> - 
                <?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <br><br>
    <input type="submit" name="assign_technician" value="Assign Technician" class="btn">
</form>

<p>
    <a href="dashboard-aos.php" class="btn">Return to Dashboard</a>
</p>
