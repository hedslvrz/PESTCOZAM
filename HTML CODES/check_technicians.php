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

// Check appointments with completion status
try {
    $completedQuery = "SELECT 
        a.id, 
        a.status, 
        a.technician_id,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name
    FROM appointments a
    LEFT JOIN users t ON a.technician_id = t.id
    WHERE a.status = 'Completed'
    ORDER BY a.id DESC";
    
    $stmt = $db->prepare($completedQuery);
    $stmt->execute();
    $completedAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Check the appointment_technicians table
try {
    $techAssignQuery = "SELECT 
        at.id,
        at.appointment_id,
        at.technician_id,
        CONCAT(u.firstname, ' ', u.lastname) as technician_name,
        a.status as appointment_status
    FROM appointment_technicians at
    JOIN users u ON at.technician_id = u.id
    JOIN appointments a ON at.appointment_id = a.id
    WHERE a.status = 'Completed'
    ORDER BY at.appointment_id DESC, at.id ASC";
    
    $stmt = $db->prepare($techAssignQuery);
    $stmt->execute();
    $techAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Update a test appointment to Completed status
if (isset($_GET['mark_completed']) && is_numeric($_GET['mark_completed'])) {
    $appointmentId = $_GET['mark_completed'];
    
    try {
        $stmt = $db->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?");
        $stmt->execute([$appointmentId]);
        
        if ($stmt->rowCount() > 0) {
            echo "<div style='padding: 10px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; margin-bottom: 15px;'>
                    Successfully marked appointment #$appointmentId as Completed
                  </div>";
        } else {
            echo "<div style='padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; margin-bottom: 15px;'>
                    No changes made. Appointment #$appointmentId may not exist or is already completed.
                  </div>";
        }
    } catch(PDOException $e) {
        echo "<div style='padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; margin-bottom: 15px;'>
                Error: " . $e->getMessage() . "
              </div>";
    }
}

// Add a test technician to an appointment
if (isset($_GET['add_technician']) && isset($_GET['appointment_id']) && isset($_GET['technician_id'])) {
    $appointmentId = $_GET['appointment_id'];
    $technicianId = $_GET['technician_id'];
    
    try {
        $stmt = $db->prepare("INSERT INTO appointment_technicians (appointment_id, technician_id) VALUES (?, ?)");
        $stmt->execute([$appointmentId, $technicianId]);
        
        echo "<div style='padding: 10px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; margin-bottom: 15px;'>
                Successfully added technician #$technicianId to appointment #$appointmentId
              </div>";
    } catch(PDOException $e) {
        echo "<div style='padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; margin-bottom: 15px;'>
                Error: " . $e->getMessage() . "
              </div>";
    }
}

// Test getting appointment details with technicians
if (isset($_GET['test_appointment']) && is_numeric($_GET['test_appointment'])) {
    $appointmentId = $_GET['test_appointment'];
    
    try {
        $detailsQuery = "SELECT 
            a.id,
            a.service_id,
            a.technician_id,
            CONCAT(t.firstname, ' ', t.lastname) as technician_name,
            (SELECT GROUP_CONCAT(at.technician_id) 
             FROM appointment_technicians at 
             WHERE at.appointment_id = a.id) as all_technician_ids,
            (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname) SEPARATOR ', ') 
             FROM appointment_technicians at 
             JOIN users u2 ON at.technician_id = u2.id 
             WHERE at.appointment_id = a.id) as all_technician_names
        FROM appointments a
        LEFT JOIN users t ON a.technician_id = t.id
        WHERE a.id = ?";
        
        $stmt = $db->prepare($detailsQuery);
        $stmt->execute([$appointmentId]);
        $appointmentDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($appointmentDetails) {
            echo "<div style='padding: 10px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; margin-bottom: 15px;'>";
            echo "<h3>Appointment #$appointmentId Details:</h3>";
            echo "<ul>";
            echo "<li>Main Technician ID: " . ($appointmentDetails['technician_id'] ?? 'None') . "</li>";
            echo "<li>Main Technician Name: " . ($appointmentDetails['technician_name'] ?? 'None') . "</li>";
            echo "<li>All Technician IDs: " . ($appointmentDetails['all_technician_ids'] ?? 'None') . "</li>";
            echo "<li>All Technician Names: " . ($appointmentDetails['all_technician_names'] ?? 'None') . "</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; margin-bottom: 15px;'>";
            echo "Appointment #$appointmentId not found.";
            echo "</div>";
        }
    } catch(PDOException $e) {
        echo "<div style='padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; margin-bottom: 15px;'>";
        echo "Error fetching appointment details: " . $e->getMessage();
        echo "</div>";
    }
}

// Get all appointments
try {
    $allAppointmentsQuery = "SELECT 
        a.id, 
        a.status, 
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as customer_name,
        s.service_name,
        a.appointment_date
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN services s ON a.service_id = s.service_id
    ORDER BY a.id DESC
    LIMIT 10";
    
    $stmt = $db->prepare($allAppointmentsQuery);
    $stmt->execute();
    $allAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Get all technicians
try {
    $allTechniciansQuery = "SELECT 
        id, 
        firstname, 
        lastname, 
        role 
    FROM users 
    WHERE role = 'technician'
    ORDER BY id ASC";
    
    $stmt = $db->prepare($allTechniciansQuery);
    $stmt->execute();
    $allTechnicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Run the exact query we use in the form
try {
    $formQuery = "SELECT 
        a.id as appointment_id, 
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as customer_name,
        a.service_id,
        s.service_name,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.technician_id,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name,
        CONCAT(a.street_address, ', ', a.barangay, ', ', a.city) as location,
        GROUP_CONCAT(at.technician_id) as all_technician_ids,
        GROUP_CONCAT(CONCAT(tech.firstname, ' ', tech.lastname) SEPARATOR ', ') as all_technician_names
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN services s ON a.service_id = s.service_id
    LEFT JOIN users t ON a.technician_id = t.id
    LEFT JOIN appointment_technicians at ON a.id = at.appointment_id
    LEFT JOIN users tech ON at.technician_id = tech.id
    WHERE a.status = 'Completed'
    GROUP BY a.id
    ORDER BY a.appointment_date DESC";
    
    $stmt = $db->prepare($formQuery);
    $stmt->execute();
    $formResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error in form query: " . $e->getMessage();
    $formResults = [];
}

// Add a supplemental query to check appointments with zero technicians
try {
    $noTechQuery = "SELECT 
        a.id as appointment_id, 
        a.status,
        a.appointment_date,
        (SELECT COUNT(*) FROM appointment_technicians at WHERE at.appointment_id = a.id) as tech_count
    FROM appointments a
    WHERE a.status = 'Completed'
    HAVING tech_count = 0
    ORDER BY a.id DESC";
    
    $stmt = $db->prepare($noTechQuery);
    $stmt->execute();
    $noTechResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error in no tech query: " . $e->getMessage();
    $noTechResults = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Assignment Diagnostics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
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
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .tools {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        a {
            color: #144578;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #144578;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn:hover {
            background-color: #0d2f4f;
            text-decoration: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input {
            padding: 8px;
            width: 100%;
            max-width: 300px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <h1>Technician Assignment Diagnostics</h1>
    
    <div class="tools">
        <h2>Diagnostics Tools</h2>
        
        <div class="section">
            <h3>Mark an Appointment as Completed</h3>
            <form action="" method="GET">
                <div class="form-group">
                    <label for="mark_completed">Select Appointment:</label>
                    <select name="mark_completed" id="mark_completed">
                        <?php foreach ($allAppointments as $appointment): ?>
                            <option value="<?php echo $appointment['id']; ?>">
                                #<?php echo $appointment['id']; ?> - 
                                <?php echo htmlspecialchars($appointment['customer_name']); ?> - 
                                <?php echo htmlspecialchars($appointment['service_name']); ?> - 
                                <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?> - 
                                Status: <?php echo $appointment['status']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Mark as Completed</button>
            </form>
        </div>
        
        <div class="section">
            <h3>Add Technician to Appointment</h3>
            <form action="" method="GET">
                <div class="form-group">
                    <label for="appointment_id">Select Appointment:</label>
                    <select name="appointment_id" id="appointment_id">
                        <?php foreach ($allAppointments as $appointment): ?>
                            <option value="<?php echo $appointment['id']; ?>">
                                #<?php echo $appointment['id']; ?> - 
                                <?php echo htmlspecialchars($appointment['customer_name']); ?> - 
                                Status: <?php echo $appointment['status']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="technician_id">Select Technician:</label>
                    <select name="technician_id" id="technician_id">
                        <?php foreach ($allTechnicians as $technician): ?>
                            <option value="<?php echo $technician['id']; ?>">
                                #<?php echo $technician['id']; ?> - 
                                <?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn" name="add_technician" value="1">Add Technician</button>
            </form>
        </div>

        <div class="section">
            <h3>Test Appointment Technician Details</h3>
            <form action="" method="GET">
                <div class="form-group">
                    <label for="test_appointment">Enter Appointment ID:</label>
                    <input type="number" name="test_appointment" id="test_appointment" required>
                </div>
                <button type="submit" class="btn">Check Technician Details</button>
            </form>
        </div>
    </div>
    
    <div class="section">
        <h2>Data Analysis</h2>
        
        <!-- Completed Appointments -->
        <?php displayTable("Completed Appointments", $completedAppointments); ?>
        
        <!-- Technician Assignments -->
        <?php displayTable("Technician Assignments for Completed Appointments", $techAssignments); ?>
        
        <!-- Form Query Results -->
        <?php displayTable("Results from Customer Selection Query", $formResults); ?>

        <!-- Appointments with No Technicians -->
        <?php displayTable("Completed Appointments with No Technicians", $noTechResults); ?>
    </div>
    
    <p><a href="dashboard-aos.php" class="btn">Return to Dashboard</a></p>
</body>
</html>
