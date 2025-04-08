<?php
session_start();
require_once '../database.php';

// Check if user is logged in and has admin role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if customer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard-admin.php#customers");
    exit();
}

$customerId = $_GET['id'];

// Get customer details
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        // Customer not found or not a user
        header("Location: dashboard-admin.php#customers");
        exit();
    }
    
    // Get appointment history
    $appointmentsQuery = "SELECT a.*, 
                            s.service_name, 
                            CONCAT(t.firstname, ' ', t.lastname) as technician_name,
                            (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname) SEPARATOR ', ') 
                            FROM appointment_technicians at 
                            JOIN users u2 ON at.technician_id = u2.id 
                            WHERE at.appointment_id = a.id) as all_technicians
                          FROM appointments a
                          JOIN services s ON a.service_id = s.service_id
                          LEFT JOIN users t ON a.technician_id = t.id
                          WHERE a.user_id = ?
                          ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                          
    $appointmentsStmt = $db->prepare($appointmentsQuery);
    $appointmentsStmt->execute([$customerId]);
    $appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error fetching customer data: " . $e->getMessage());
    $error = "Database error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Appointments | PESTCOZAM</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS CODES/dashboard-admin.css">
    <style>
        .appointments-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0f2f5;
            color: #344767;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }
        
        .back-button:hover {
            background: #e0e4e9;
        }
        
        .customer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .customer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #144578;
        }
        
        .customer-name h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #144578;
        }
        
        .customer-name p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .filter-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-controls label {
            font-size: 0.9rem;
            color: #555;
        }
        
        .filter-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .appointments-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        
        .appointments-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .appointments-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.pending {
            background: #fff2c6;
            color: #a77b06;
        }
        
        .status-badge.confirmed {
            background: #b7e9ff;
            color: #0072a4;
        }
        
        .status-badge.completed {
            background: #c6ffd9;
            color: #00732a;
        }
        
        .status-badge.canceled {
            background: #ffd0d0;
            color: #b92c2c;
        }
        
        .view-btn, .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .view-btn {
            background: #e6f3ff;
            color: #144578;
        }
        
        .view-btn:hover {
            background: #c9e6ff;
        }
        
        .print-btn {
            background: #f0f2f5;
            color: #344767;
        }
        
        .print-btn:hover {
            background: #e0e4e9;
        }
        
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
        }
        
        @media screen and (max-width: 768px) {
            .customer-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .view-btn, .print-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Main content -->
    <section class="section active" style="padding-top: 20px; left: 0; width: 100%;">
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Customer Appointment History</h1>
                    <ul class="breadcrumb">
                        <li><a href="dashboard-admin.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a href="dashboard-admin.php#customers">Customers</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a href="view-customer.php?id=<?php echo $customerId; ?>">Customer Details</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Appointment History</a></li>
                    </ul>
                </div>
            </div>
            
            <a href="view-customer.php?id=<?php echo $customerId; ?>" class="back-button">
                <i class='bx bx-arrow-back'></i> Back to Customer Details
            </a>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php else: ?>
                <div class="appointments-container">
                    <!-- Customer header -->
                    <div class="customer-header">
                        <div class="customer-info">
                            <div class="customer-avatar">
                                <i class='bx bx-user'></i>
                            </div>
                            <div class="customer-name">
                                <h2><?php echo htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']); ?></h2>
                                <p>Total Appointments: <?php echo count($appointments); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Controls -->
                    <div class="filter-controls">
                        <label for="status-filter">Filter by Status:</label>
                        <select id="status-filter">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    
                    <!-- Appointments Table -->
                    <?php if (!empty($appointments)): ?>
                        <div class="table-responsive">
                            <table class="appointments-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Service</th>
                                        <th>Location</th>
                                        <th>Technician(s)</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr data-status="<?php echo strtolower($appointment['status']); ?>">
                                            <td>
                                                <div style="font-weight: 500;"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                                <div style="color: #666; font-size: 0.9rem;"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                            <td>
                                                <?php
                                                    $address = [];
                                                    if (!empty($appointment['street_address'])) $address[] = $appointment['street_address'];
                                                    if (!empty($appointment['barangay'])) $address[] = $appointment['barangay'];
                                                    if (!empty($appointment['city'])) $address[] = $appointment['city'];
                                                    
                                                    echo !empty($address) ? htmlspecialchars(implode(', ', $address)) : 'No address provided';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    if (!empty($appointment['all_technicians'])) {
                                                        echo htmlspecialchars($appointment['all_technicians']);
                                                    } elseif (!empty($appointment['technician_name'])) {
                                                        echo htmlspecialchars($appointment['technician_name']);
                                                    } else {
                                                        echo '<span style="color: #999; font-style: italic;">Not Assigned</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $statusClass = strtolower($appointment['status']);
                                                    echo '<span class="status-badge ' . $statusClass . '">' . 
                                                        htmlspecialchars($appointment['status']) . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="job-details.php?id=<?php echo $appointment['id']; ?>" class="view-btn">
                                                        <i class='bx bx-show'></i> View Details
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-calendar-x' style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                            <p>No appointments found for this customer.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </section>
    
    <script>
        // Filter appointments by status
        document.getElementById('status-filter').addEventListener('change', function() {
            const selectedStatus = this.value.toLowerCase();
            const rows = document.querySelectorAll('.appointments-table tbody tr');
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (selectedStatus === 'all' || rowStatus === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Check if any rows are visible
            const visibleRows = document.querySelectorAll('.appointments-table tbody tr:not([style*="display: none"])');
            const tableEl = document.querySelector('.table-responsive');
            const emptyStateEl = document.querySelector('.empty-state');
            
            if (visibleRows.length === 0 && !emptyStateEl) {
                // Create empty state if no rows match filter
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state filtered-empty';
                emptyState.innerHTML = `
                    <p>No appointments found with status "${selectedStatus}".</p>
                    <button class="back-button" onclick="document.getElementById('status-filter').value='all';document.getElementById('status-filter').dispatchEvent(new Event('change'));">
                        Show All Appointments
                    </button>
                `;
                tableEl.style.display = 'none';
                tableEl.parentNode.appendChild(emptyState);
            } else {
                // Remove empty state if rows match filter
                const filteredEmptyState = document.querySelector('.filtered-empty');
                if (filteredEmptyState) {
                    filteredEmptyState.remove();
                    tableEl.style.display = '';
                }
            }
        });
    </script>
</body>
</html>
