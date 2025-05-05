<?php
session_start();
require_once '../database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Correct the parameter name to 'plan_id'
$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
if (!$plan_id) {
    header("Location: dashboard-admin.php#recurrence-plan");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch recurrence plan details
    $planQuery = "SELECT * FROM followup_plan WHERE id = ?";
    $stmt = $db->prepare($planQuery);
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        throw new Exception('Plan not found');
    }

    // Fetch visits
    $visitsQuery = "SELECT 
        rv.*,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name
    FROM recurrence_visits rv
    LEFT JOIN users t ON rv.technician_id = t.id
    WHERE rv.plan_id = ?
    ORDER BY rv.visit_date ASC";
    
    $stmt = $db->prepare($visitsQuery);
    $stmt->execute([$plan_id]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch available technicians
    $techniciansQuery = "SELECT id, firstname, lastname 
        FROM users 
        WHERE role = 'technician' 
        AND status = 'active'
        ORDER BY firstname, lastname";
    
    $stmt = $db->prepare($techniciansQuery);
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    $_SESSION['error'] = "Error loading plan details: " . $e->getMessage();
    header("Location: dashboard-admin.php#recurrence-plan");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurrence Plan Details</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../CSS CODES/dashboard-admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <div class="plan-details-container">
        <a href="dashboard-admin.php#recurrence-plan" class="back-button">
            <i class='bx bx-arrow-back'></i> Back to Plans
        </a>

        <div class="section-card">
            <h2>Customer Information</h2>
            <div class="info-grid">
                <div>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($plan['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($plan['customer_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($plan['customer_phone']); ?></p>
                </div>
                <div>
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($plan['service_name']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($plan['location']); ?></p>
                    <p><strong>Visit Frequency:</strong> <?php echo htmlspecialchars($plan['visit_frequency']); ?></p>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h2>Visit Schedule</h2>
            <div class="table-responsive">
                <table class="visits-table">
                    <thead>
                        <tr>
                            <th>Visit Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Assigned Technician</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $visit): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($visit['visit_time'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($visit['status']); ?>">
                                        <?php echo ucfirst($visit['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($visit['status'] !== 'completed'): ?>
                                        <select class="technician-select" data-visit-id="<?php echo $visit['visit_id']; ?>">
                                            <option value="">Select Technician</option>
                                            <?php foreach ($technicians as $tech): ?>
                                                <option value="<?php echo $tech['id']; ?>" 
                                                    <?php echo $tech['id'] == $visit['technician_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($visit['technician_name'] ?: 'Unassigned'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($visit['status'] !== 'completed'): ?>
                                        <button onclick="saveTechnicianAssignment(<?php echo $visit['visit_id']; ?>)" 
                                                class="btn-save">Save</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        function saveTechnicianAssignment(visitId) {
            const select = document.querySelector(`select[data-visit-id="${visitId}"]`);
            const technicianId = select.value;

            if (!technicianId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a technician'
                });
                return;
            }

            fetch('../PHP CODES/update_visit_technician.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    visit_id: visitId,
                    technician_id: technicianId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Technician assignment updated successfully'
                    });
                } else {
                    throw new Error(data.message || 'Failed to update technician assignment');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            });
        }
    </script>
</body>
</html>
