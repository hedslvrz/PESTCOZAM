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
    
    // Get appointment count
    $appointmentStmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
    $appointmentStmt->execute([$customerId]);
    $appointmentCount = $appointmentStmt->fetchColumn();
    
    // Get latest appointment
    $latestAppointmentStmt = $db->prepare(
        "SELECT a.*, s.service_name FROM appointments a 
         JOIN services s ON a.service_id = s.service_id
         WHERE a.user_id = ? 
         ORDER BY a.appointment_date DESC, a.appointment_time DESC 
         LIMIT 1"
    );
    $latestAppointmentStmt->execute([$customerId]);
    $latestAppointment = $latestAppointmentStmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error fetching customer details: " . $e->getMessage());
    $error = "Database error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details | PESTCOZAM</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS CODES/dashboard-admin.css">
    <style>
        .customer-details-container {
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
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .customer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #144578;
        }
        
        .customer-name {
            display: flex;
            flex-direction: column;
        }
        
        .customer-name h2 {
            margin: 0;
            font-size: 1.8rem;
            color: #144578;
        }
        
        .customer-name p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 1rem;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        
        .detail-section h3 {
            margin-top: 0;
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
        }
        
        .detail-label {
            display: block;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            color: #333;
            font-weight: 500;
        }
        
        .latest-appointment {
            background: #e6f3ff;
        }
        
        .action-buttons {
            display: flex;
            justify-content: flex-start;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #144578;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0d3057;
        }
        
        .btn-secondary {
            background: #f0f2f5;
            color: #344767;
        }
        
        .btn-secondary:hover {
            background: #e0e4e9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff2c6;
            color: #a77b06;
        }
        
        .status-confirmed {
            background: #b7e9ff;
            color: #0072a4;
        }
        
        .status-completed {
            background: #c6ffd9;
            color: #00732a;
        }
        
        .status-canceled {
            background: #ffd0d0;
            color: #b92c2c;
        }
        
        .empty-state {
            padding: 20px;
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        @media screen and (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .customer-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
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
                    <h1>Customer Details</h1>
                    <ul class="breadcrumb">
                        <li><a href="dashboard-admin.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a href="dashboard-admin.php#customers">Customers</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Customer Details</a></li>
                    </ul>
                </div>
            </div>
            
            <a href="dashboard-admin.php#customers" class="back-button">
                <i class='bx bx-arrow-back'></i> Back to Customer List
            </a>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php else: ?>
                <div class="customer-details-container">
                    <!-- Customer header -->
                    <div class="customer-header">
                        <div class="customer-avatar">
                            <i class='bx bx-user'></i>
                        </div>
                        <div class="customer-name">
                            <h2><?php echo htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']); ?></h2>
                            <p>Customer since <?php echo date('F d, Y', strtotime($customer['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <!-- Customer details grid -->
                    <div class="details-grid">
                        <!-- Contact Information -->
                        <div class="detail-section">
                            <h3>Contact Information</h3>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($customer['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone Number</span>
                                <span class="detail-value"><?php echo htmlspecialchars($customer['mobile_number']); ?></span>
                            </div>
                            <?php if (!empty($customer['middlename'])): ?>
                            <div class="detail-item">
                                <span class="detail-label">Middle Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($customer['middlename']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($customer['dob'])): ?>
                            <div class="detail-item">
                                <span class="detail-label">Date of Birth</span>
                                <span class="detail-value"><?php echo date('F d, Y', strtotime($customer['dob'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Account Information -->
                        <div class="detail-section">
                            <h3>Account Information</h3>
                            <div class="detail-item">
                                <span class="detail-label">Account ID</span>
                                <span class="detail-value">#<?php echo $customer['id']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Appointments</span>
                                <span class="detail-value"><?php echo $appointmentCount; ?></span>
                            </div>
                            <?php if (!empty($customer['created_at'])): ?>
                            <div class="detail-item">
                                <span class="detail-label">Registration Date</span>
                                <span class="detail-value"><?php echo date('F d, Y', strtotime($customer['created_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Latest Appointment -->
                        <div class="detail-section latest-appointment">
                            <h3>Latest Appointment</h3>
                            <?php if ($latestAppointment): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Service</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($latestAppointment['service_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date & Time</span>
                                    <span class="detail-value">
                                        <?php 
                                            echo date('F d, Y', strtotime($latestAppointment['appointment_date'])) . ' at ' . 
                                                 date('h:i A', strtotime($latestAppointment['appointment_time']));
                                        ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Location</span>
                                    <span class="detail-value">
                                        <?php 
                                            $address = [];
                                            if (!empty($latestAppointment['street_address'])) $address[] = $latestAppointment['street_address'];
                                            if (!empty($latestAppointment['barangay'])) $address[] = $latestAppointment['barangay'];
                                            if (!empty($latestAppointment['city'])) $address[] = $latestAppointment['city'];
                                            
                                            echo !empty($address) ? htmlspecialchars(implode(', ', $address)) : 'No address provided';
                                        ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value">
                                        <?php 
                                            $statusClass = strtolower($latestAppointment['status']);
                                            echo '<span class="status-badge status-' . $statusClass . '">' . 
                                                htmlspecialchars($latestAppointment['status']) . '</span>';
                                        ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">No appointments found for this customer.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="customer-appointments.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                            <i class='bx bx-history'></i> View Appointment History
                        </a>
                        <a href="mailto:<?php echo $customer['email']; ?>" class="btn btn-secondary">
                            <i class='bx bx-envelope'></i> Send Email
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </section>
</body>
</html>
