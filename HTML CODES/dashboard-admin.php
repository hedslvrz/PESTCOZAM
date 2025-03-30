<?php
session_start();
require_once '../database.php';
include_once("../employee/functions/fetch.php");

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in and has admin role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get admin data from database
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $admin = null;
}

// Get dashboard statistics
try {
    $stats = $db->query("SELECT 
        (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_jobs,
        (SELECT COUNT(*) FROM users WHERE role = 'technician' AND status = 'active') as active_technicians,
        (SELECT COUNT(*) FROM service_reports) as total_reports"
    )->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $stats = null;
}

// Get employee statistics
try {
    $stmt = $db->query("SELECT 
        COUNT(*) AS total, 
        SUM(role = 'technician') AS technicians,
        SUM(role = 'supervisor') AS supervisors,
        SUM(role = 'technician' AND status = 'verified') AS verified_technicians,
        SUM(role = 'supervisor' AND status = 'verified') AS verified_supervisors,
        SUM(role = 'technician' AND status = 'unverified') AS unverified_technicians,
        SUM(role = 'supervisor' AND status = 'unverified') AS unverified_supervisors
    FROM users
    WHERE role IN ('technician', 'supervisor')");

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure $data is not null
    if (!$data) {
        $data = [
            'total' => 0, 
            'technicians' => 0, 
            'supervisors' => 0, 
            'verified_technicians' => 0, 
            'verified_supervisors' => 0, 
            'unverified_technicians' => 0, 
            'unverified_supervisors' => 0
        ];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $data = [
        'total' => 0, 
        'technicians' => 0, 
        'supervisors' => 0, 
        'verified_technicians' => 0, 
        'verified_supervisors' => 0, 
        'unverified_technicians' => 0, 
        'unverified_supervisors' => 0
    ];
}

// Replace the existing appointments query with this
try {
    $appointmentsQuery = "SELECT 
        a.id as appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.street_address,
        a.barangay,
        a.city,
        a.technician_id,
        a.is_for_self,
        s.service_name,
        CASE 
            WHEN a.is_for_self = 1 THEN u.firstname
            ELSE a.firstname
        END as client_firstname,
        CASE 
            WHEN a.is_for_self = 1 THEN u.lastname
            ELSE a.lastname
        END as client_lastname,
        t.firstname as tech_firstname,
        t.lastname as tech_lastname
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users t ON a.technician_id = t.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $db->prepare($appointmentsQuery);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $appointments = [];
}

// Get available technicians
try {
    $techQuery = "SELECT id, firstname, lastname 
                 FROM users 
                 WHERE role = 'technician' 
                 AND status = 'verified'";
    $techStmt = $db->prepare($techQuery);
    $techStmt->execute();
    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching technicians: " . $e->getMessage());
    $technicians = [];
}

// Get service reports
try {
    $serviceReportsQuery = "SELECT 
        sr.report_id,
        sr.date_of_treatment,
        sr.time_in,
        sr.time_out,
        sr.treatment_type,
        sr.treatment_method,
        sr.pest_count,
        sr.device_installation,
        sr.consumed_chemicals,
        sr.frequency_of_visits,
        sr.photos,
        sr.location,
        sr.account_name,
        sr.contact_no,
        sr.status,
        CONCAT(u.firstname, ' ', u.lastname) AS tech_name
    FROM service_reports sr
    JOIN users u ON sr.technician_id = u.id
    ORDER BY sr.date_of_treatment DESC";

    $stmt = $db->prepare($serviceReportsQuery);
    $stmt->execute();
    $serviceReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching service reports: " . $e->getMessage());
    $serviceReports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS CODES/dashboard-admin.css">
    <title>Pestcozam Dashboard</title>
</head>
<body>
<!-- SIDEBAR SECTION -->
    <section id="sidebar">
        <div class="logo-container">
            <img src="../Pictures/pest_logo.png" alt="Flower Logo" class="flower-logo">
            <span class="brand-name">PESTCOZAM</span>
        </div>
        <ul class="side-menu top">
            <li class="active">
                <a href="#dashboard" onclick="showSection('content')">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#work-orders" onclick="showSection('work-orders')">
                    <i class='bx bxs-briefcase'></i>
                    <span class="text">Manage Job Orders</span>
                </a>
            </li>
            <li>
                <a href="#employees" onclick="showSection('employees')">
                    <i class='bx bx-child'></i>
                    <span class="text">Manage Employees</span>
                </a>
            </li>
            <li>
                <a href="#services" onclick="showSection('services')">
                    <i class='bx bx-dish' ></i>
                    <span class="text">Manage Services</span>
                </a>
            </li>
            <li>
                <a href="#customers" onclick="showSection('customers')">
                    <i class='bx bx-run'></i>
                    <span class="text">Manage Customers</span>
                </a>
            </li>
            <li>
                <a href="#reports" onclick="showSection('reports')">
                    <i class='bx bxs-report' ></i>
                    <span class="text">Manage Reports</span>
                </a>
            </li><li>
                <a href="#billing" onclick="showSection('billing')">
                    <i class='bx bx-money-withdraw' ></i>
                    <span class="text">Manage Billing</span>
                </a>
            </li>
            
            <li>
                <a href="#profile" onclick="showSection('profile')">
                    <i class='bx bx-user' ></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="#settings" onclick="showSection('settings')">
                    <i class='bx bx-cog' ></i>
                    <span class="text">Settings</span>
                </a>
            </li>
            <li>
                <a href="Login.php" class="logout">
                    <i class='bx bx-log-out' ></i>
                    <span class="text">Log out</span>
                </a>
            </li>
        </ul>
    </section>
<!-- SIDEBAR SECTION -->

<!-- MAIN NAVBAR -->
<nav id="main-navbar" class="standard-nav">
    <i class='bx bx-menu'></i>
    <a href="#" class="nav-link">Categories</a>
    <form action="#">
        <div class="form-input">
            <input type="search" placeholder="Search">
            <button type="submit" class="search"><i class='bx bx-search'></i></button>
        </div>
    </form>
    <a href="#" class="notification">
        <i class='bx bxs-bell'></i>
        <span class="num">8</span>
    </a>
    <a href="#" class="profile">
        <img src="images/heds.png">
    </a>
</nav>

<!--DASHBOARD CONTENT -->
<section id="content" class="section active">
    <main>
        <form id="dashboard-form" method="POST" action="process_dashboard.php">
            <div class="head-title">
                <div class="left">
                    <h1>Dashboard</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Dashboard</a>
                        </li>
                        <li><i class='bx bx-right-arrow-alt' ></i></li>
                        <li>
                            <a class="active" href="#">Home</a>
                        </li>
                    </ul>
                </div>
            </div>  

            <ul class="box-info">
                <li>
                    <i class='bx bxs-calendar-check' ></i>
                    <span class="text">
                        <h3><?php echo htmlspecialchars($stats['pending_jobs'] ?? '0'); ?></h3>
                        <p>Appointments</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group' ></i>
                    <span class="text">
                        <h3>6</h3>
                        <p>Available</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-dollar-circle' ></i>
                    <span class="text">
                        <h3>₱2025</h3>
                        <p>Total Sales</p>
                        <p>Appointments</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group' ></i>
                    <span class="text">
                        <h3>6</h3>
                        <p>Available</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-dollar-circle' ></i>
                    <span class="text">
                        <h3>₱2025</h3>
                        <p>Total Sales</p>
                    </span>
                </li>
            </ul>

            <div class="table-data">
                <div class="recent-appointments">
                    <div class="head">
                        <h3>Recent Appointments</h3>
                        <input type="text" name="search" placeholder="Search appointments...">
                        <button type="submit" name="filter_appointments"><i class="bx bx-filter"></i></button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Date Order</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="../Pictures/boy.png" class="user-img">
                                    <p>John Doe</p>
                                </td>
                                <td>01-10-2025</td>
                                <td><span class="status-completed">Completed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="todo">
                    <div class="head">
                        <h3>Recent Appointments</h3>
                        <input type="text" name="todo_search" placeholder="Search todos...">
                        <button type="submit" name="filter_todos"><i class="bx bx-filter"></i></button>
                    </div>
                    <ul class="todo-list">
                        <li>
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="not-completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                        <li class="not-completed">
                            <p>Todo List</p>
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </main>
</section>
<!--DASHBOARD CONTENT -->


<!-- Job Orders Section -->
<section id="work-orders" class="section">
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Work Orders Management</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Work Orders</a></li>
                </ul>
            </div>
        </div>

        <div class="table-data">
            <div class="order-list">
                <div class="table-header">
                    <div class="search-filters">
                        <div class="search-box">
                            <i class='bx bx-search'></i>
                            <input type="text" id="searchAppointments" placeholder="Search appointments...">
                        </div>
                        <div class="filter-buttons">
                            <button type="button" class="filter-btn active" data-filter="all">All</button>
                            <button type="button" class="filter-btn" data-filter="pending">Pending</button>
                            <button type="button" class="filter-btn" data-filter="confirmed">Confirmed</button>
                            <button type="button" class="filter-btn" data-filter="completed">Completed</button>
                        </div>
                        <div class="date-filter">
                            <input type="date" id="filterDate">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="work-orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Schedule</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Technician</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php if (!empty($appointments)): ?>
        <?php foreach ($appointments as $appointment): ?>
            <tr>
                <td>#<?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                <td>
                    <div class="schedule-info">
                        <i class='bx bx-calendar'></i>
                        <div>
                            <span class="date"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></span>
                            <span class="time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="customer-info">
                        <i class='bx bx-user'></i>
                        <span><?php echo htmlspecialchars($appointment['client_firstname'] . ' ' . 
                            $appointment['client_lastname']); ?></span>
                    </div>
                </td>
                <td>
                    <div class="service-info">
                        <i class='bx bx-package'></i>
                        <span><?php echo htmlspecialchars($appointment['service_name']); ?></span>
                    </div>
                </td>
                <td>
                    <div class="tech-info">
                        <?php if (!empty($appointment['tech_firstname'])): ?>
                            <i class='bx bx-user-check'></i>
                            <span><?php echo htmlspecialchars($appointment['tech_firstname'] . ' ' . 
                                $appointment['tech_lastname']); ?></span>
                        <?php else: ?>
                            <span class="no-tech">Not Assigned</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <span class="status <?php echo strtolower($appointment['status']); ?>">
                        <?php echo htmlspecialchars($appointment['status']); ?>
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <?php if ($appointment['status'] === 'Pending' || $appointment['status'] === 'Confirmed'): ?>
                            <form class="inline-assign-form" data-appointment-id="<?php echo $appointment['appointment_id']; ?>">
                                <select class="tech-select" name="technician_id" required>
                                    <option value="">-- Select Technician --</option>
                                    <?php foreach ($technicians as $tech): ?>
                                        <option value="<?php echo $tech['id']; ?>" 
                                            <?php echo ($appointment['technician_id'] == $tech['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="assign-btn">
                                    <?php if (empty($appointment['technician_id'])): ?>
                                        <i class='bx bx-check'></i> Assign
                                    <?php else: ?>
                                        <i class='bx bx-refresh'></i> Update
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="status-message">Job completed</span>
                        <?php endif; ?>
                        <button type="button" class="view-btn" onclick="viewDetails(<?php echo $appointment['appointment_id']; ?>)">
                            <i class='bx bx-show'></i> View Details
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="no-records">No appointments found</td>
        </tr>
    <?php endif; ?>
</tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<!-- Employee Section -->
<section id="employees" class="section">
    <main>
        <form id="employees-form" method="POST" action="../employee/forms/addform.php">
            <div class="head-title">
                <div class="left">
                    <h1>Employee Management</h1>
                </div>
                <a href="../employee/forms/addform.php" class="btn-add-employees">
                    <i class='bx bx-plus'></i>
                    <span class="text">Add New Employee</span>
                </a>
            </div>
            <div class="table-data">

                <div class="employee-list">
                    <div class="order-stats">
                        <div class="stat-card">
                            <i class='bx bxs-group'></i>
                            <div class="stat-details">
                                <h3>Total Employees</h3>
                                <p><?php echo $data['technicians'] + $data['supervisors']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class='bx bxs-user-check'></i>
                            <div class="stat-details">
                                <h3>Verified</h3>
                                <p><?php echo $data['verified_technicians'] + $data['verified_supervisors']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class='bx bxs-user-x'></i>
                            <div class="stat-details">
                                <h3>Unverified</h3>
                                <p><?php echo $data['unverified_technicians'] + $data['unverified_supervisors']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="table-header">
                        <div class="search-bar">
                            <i class='bx bx-search'></i>
                            <input type="text" id="searchInput" placeholder="Search employee...">
                        </div>
                        <div class="filter-options">
                            <select id="sortBy">
                                <option value="">Sort by</option>
                                <option value="asc">A - Z</option>
                                <option value="desc">Z - A</option>
                            </select>
                            <select id="filterRole">
                                <option value="">Roles</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="technician">Technician</option>
                            </select>
                            <select id="filterStatus">
                                <option value="">Status</option>
                                <option value="verified">Verified</option>
                                <option value="unverified">Unverified</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="employeeTable">
                            <thead>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <th>Name</th>
                                    <th>Date of Birth</th>
                                    <th>Email</th>
                                    <th>Mobile Number</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($employees)): ?>
                                    <?php foreach ($employees as $res): ?>
                                        <?php if ($res['role'] === 'technician' || $res['role'] === 'supervisor'): ?>
                                        <tr data-role="<?php echo htmlspecialchars($res['role']); ?>" data-status="<?php echo htmlspecialchars($res['status']); ?>">
                                            <td><input type="checkbox"></td>
                                            <td><?php echo htmlspecialchars(($res['firstname'] ?? '') . ' ' . ($res['lastname'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($res['dob'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['mobile_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['role'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($res['status'] ?? 'unverified'); ?></td>
                                            <td class="action-buttons">
                                                <a href="../employee/forms/editform.php?id=<?php echo urlencode($res['id'] ?? ''); ?>">Edit</a>
                                                <a href="../employee/functions/delete.php?id=<?php echo urlencode($res['id'] ?? ''); ?>" 
                                                   onclick="return confirmDelete(event);">Delete</a>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">No Technicians or Supervisors available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script>
                        $(document).ready(function () {
                            function filterTable() {
                                let searchValue = $("#searchInput").val().toLowerCase().trim();
                                let selectedRole = $("#filterRole").val();
                                let selectedStatus = $("#filterStatus").val();

                                $("#employeeTable tbody tr").each(function () {
                                    let name = $(this).find("td:nth-child(2)").text().toLowerCase();
                                    let email = $(this).find("td:nth-child(4)").text().toLowerCase();
                                    let mobile = $(this).find("td:nth-child(5)").text().toLowerCase();
                                    let role = $(this).attr("data-role");
                                    let status = $(this).attr("data-status");

                                    let searchMatch = searchValue === "" || name.includes(searchValue) || email.includes(searchValue) || mobile.includes(searchValue);
                                    let roleMatch = selectedRole === "" || role === selectedRole;
                                    let statusMatch = selectedStatus === "" || status === selectedStatus;

                                    $(this).toggle(searchMatch && roleMatch && statusMatch);
                                });
                            }

                            function sortTable() {
                                let rows = $("#employeeTable tbody tr").get();
                                let sortOrder = $("#sortBy").val();

                                if (!sortOrder) return; // No sorting if default option is selected

                                rows.sort(function (a, b) {
                                    let nameA = $(a).find("td:nth-child(2)").text().trim().toLowerCase();
                                    let nameB = $(b).find("td:nth-child(2)").text().trim().toLowerCase();

                                    return sortOrder === "asc" ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
                                });

                                $.each(rows, function (index, row) {
                                    $("#employeeTable tbody").append(row);
                                });
                            }

                            $("#searchInput, #filterRole, #filterStatus").on("input change", filterTable);
                            $("#sortBy").on("change", function () {
                                sortTable();
                                filterTable();
                            });
                        });

                        async function confirmDelete(event) {
                            event.preventDefault();
                            const confirmed = await confirm('Are you sure you want to delete this employee?');
                            if (confirmed) {
                                window.location.href = event.target.href;
                            }
                            return false;
                        }
                    </script>
                </div>
            </div>
        </form>
    </main>
</section>

<!-- Services Section -->
<section id="services" class="section">
    <main>
        <form id="services-form" method="POST" action="process_services.php">
            <div class="head-title">
                <div class="left">
                    <h1>Services Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Services</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">Available Services</a></li>
                    </ul>
                </div>
                <button type="submit" name="action" value="add_service" class="btn-add">
                    <i class='bx bx-plus'></i>
                    <span class="text">Add New Service</span>
                </button>
            </div>

            <div class="table-data">
                <div class="services-list">
                    <div class="head">
                        <h3>Available Services</h3>
                        <div class="actions">
                            <i class="bx bx-search"></i>
                            <i class="bx bx-filter"></i>
                        </div>
                    </div>
                    <div class="service-cards">
                        <div class="service-card">
                            <input type="hidden" name="service_id[]" value="1">
                            <div class="service-image">
                                <img src="../Pictures/card 1 offer.jpg" alt="Soil Poisoning">
                            </div>
                            <div class="service-details">
                                <input type="text" name="service_name[]" value="Soil Poisoning" hidden>
                                <h4>Soil Poisoning</h4>
                                <textarea name="description[]" hidden>Professional pre and post-construction soil treatment using advanced chemicals to create a long-lasting barrier against subterranean pests. Protects foundations and structures.</textarea>
                                <p class="description">Professional pre and post-construction soil treatment using advanced chemicals to create a long-lasting barrier against subterranean pests. Protects foundations and structures.</p>
                                <div class="estimated-time">
                                    <i class='bx bx-time-five'></i>
                                    <span>Estimated time: 2-3 hours</span>
                                </div>
                                <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                <div class="service-actions">
                                    <button type="submit" name="action" value="edit_1" class="btn-edit"><i class='bx bx-edit'></i> Edit</button>
                                    <button type="submit" name="action" value="delete_1" class="btn-delete"><i class='bx bx-trash'></i> Delete</button>
                                </div>
                            </div>
                        </div>

                        <div class="service-card">
                            <input type="hidden" name="service_id[]" value="2">
                            <div class="service-image">
                                <img src="../Pictures/mound-demolition.jpg" alt="Mound Demolition">
                            </div>
                            <div class="service-details">
                                <input type="text" name="service_name[]" value="Mound Demolition" hidden>
                                <h4>Mound Demolition</h4>
                                <textarea name="description[]" hidden>Expert termite mound removal service with thorough colony elimination. Includes site assessment, strategic demolition, and preventive treatment.</textarea>
                                <p class="description">Expert termite mound removal service with thorough colony elimination. Includes site assessment, strategic demolition, and preventive treatment.</p>
                                <div class="estimated-time">
                                    <i class='bx bx-time-five'></i>
                                    <span>Estimated time: 1-2 hours</span>
                                </div>
                                <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                <div class="service-actions">
                                    <button type="submit" name="action" value="edit_2" class="btn-edit"><i class='bx bx-edit'></i> Edit</button>
                                    <button type="submit" name="action" value="delete_2" class="btn-delete"><i class='bx bx-trash'></i> Delete</button>
                                </div>
                            </div>
                        </div>

                        <div class="service-card">
                            <input type="hidden" name="service_id[]" value="3">
                            <div class="service-image">
                                <img src="../Pictures/termite control.jpg" alt="Termite Control">
                            </div>
                            <div class="service-details">
                                <input type="text" name="service_name[]" value="Termite Control" hidden>
                                <h4>Termite Control</h4>
                                <textarea name="description[]" hidden>Complete termite management solution using state-of-the-art detection and elimination methods. Includes barrier treatment and ongoing monitoring.</textarea>
                                <p class="description">Complete termite management solution using state-of-the-art detection and elimination methods. Includes barrier treatment and ongoing monitoring.</p>
                                <div class="estimated-time">
                                    <i class='bx bx-time-five'></i>
                                    <span>Estimated time: 3-4 hours</span>
                                </div>
                                <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                <div class="service-actions">
                                    <button type="submit" name="action" value="edit_3" class="btn-edit"><i class='bx bx-edit'></i> Edit</button>
                                    <button type="submit" name="action" value="delete_3" class="btn-delete"><i class='bx bx-trash'></i> Delete</button>
                                </div>
                            </div>
                        </div>

                        <div class="service-card">
                            <input type="hidden" name="service_id[]" value="4">
                            <div class="service-image">
                                <img src="../Pictures/general pest control.jpg" alt="General Pest Control">
                            </div>
                            <div class="service-details">
                                <input type="text" name="service_name[]" value="General Pest Control" hidden>
                                <h4>General Pest Control</h4>
                                <textarea name="description[]" hidden>Comprehensive pest management for homes and businesses. Targets multiple pest species with eco-friendly solutions and preventive measures.</textarea>
                                <p class="description">Comprehensive pest management for homes and businesses. Targets multiple pest species with eco-friendly solutions and preventive measures.</p>
                                <div class="estimated-time">
                                    <i class='bx bx-time-five'></i>
                                    <span>Estimated time: 2-3 hours</span>
                                </div>
                                <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                <div class="service-actions">
                                    <button type="submit" name="action" value="edit_4" class="btn-edit"><i class='bx bx-edit'></i> Edit</button>
                                    <button type="submit" name="action" value="delete_4" class="btn-delete"><i class='bx bx-trash'></i> Delete</button>
                                </div>
                            </div>
                        </div>

                        <div class="service-card">
                            <input type="hidden" name="service_id[]" value="5">
                            <div class="service-image">
                                <img src="../Pictures/Mosquito control.jpg" alt="Mosquito Control">
                            </div>
                            <div class="service-details">
                                <input type="text" name="service_name[]" value="Mosquito Control" hidden>
                                <h4>Mosquito Control</h4>
                                <textarea name="description[]" hidden>Advanced mosquito reduction program including breeding site elimination, barrier spraying, and ongoing population management.</textarea>
                                <p class="description">Advanced mosquito reduction program including breeding site elimination, barrier spraying, and ongoing population management.</p>
                                <div class="estimated-time">
                                    <i class='bx bx-time-five'></i>
                                    <span>Estimated time: 1-2 hours</span>
                                </div>
                                <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                <div class="service-actions">
                                    <button type="submit" name="action" value="edit_5" class="btn-edit"><i class='bx bx-edit'></i> Edit</button>
                                    <button type="submit" name="action" value="delete_5" class="btn-delete"><i class='bx bx-trash'></i> Delete</button>
                                </div>
                            </div>
                        </div>

                        <div class="service-card">
                            <input type="hidden" name="service_id[]" value="6">
                            <div class="service-image">
                                <img src="../Pictures/Other-flying-insects.jpg" alt="Rat Control">
                            </div>
                            <div class="service-details">
                                <input type="text" name="service_name[]" value="Rat Control" hidden>
                                <h4>Rat Control</h4>
                                <textarea name="description[]" hidden>Specialized rat elimination program including entry point sealing, baiting systems, and sanitation recommendations.</textarea>
                                <p class="description">Specialized rat elimination program including entry point sealing, baiting systems, and sanitation recommendations.</p>
                                <div class="estimated-time">
                                    <i class='bx bx-time-five'></i>
                                    <span>Estimated time: 2-3 hours</span>
                                </div>
                                <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                <div class="service-actions">
                                    <button type="submit" name="action" value="edit_6" class="btn-edit"><i class='bx bx-edit'></i> Edit</button>
                                    <button type="submit" name="action" value="delete_6" class="btn-delete"><i class='bx bx-trash'></i> Delete</button>
                                </div>
                            </div>
                        </div>

                        <div class="service-card">
                            <input type="hidden" name="service_id[]" value="7">
                            <div class="service-image">
                                <img src="../Pictures/Extraction.jpg" alt="Emergency Pest Control">
                            </div>
                            <div class="service-details">
                                <input type="text" name="service_name[]" value="Emergency Pest Control" hidden>
                                <h4>Emergency Pest Control</h4>
                                <textarea name="description[]" hidden>24/7 rapid response service for urgent pest situations. Immediate assessment and treatment with priority scheduling.</textarea>
                                <p class="description">24/7 rapid response service for urgent pest situations. Immediate assessment and treatment with priority scheduling.</p>
                                <div class="estimated-time">
                                    <i class='bx bx-time-five'></i>
                                    <span>Estimated time: 1-2 hours</span>
                                </div>
                                <div class="inspection-notice">* Final pricing will be determined upon inspection</div>
                                <div class="service-actions">
                                    <button type="submit" name="action" value="edit_7" class="btn-edit"><i class='bx bx-edit'></i> Edit</button>
                                    <button type="submit" name="action" value="delete_7" class="btn-delete"><i class='bx bx-trash'></i> Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</section>
<!-- SERVICES SECTION -->

<!-- Customers Section -->
<section id="customers" class="section">
    <main>
        <form id="customers-form" method="POST" action="process_customers.php">
            <div class="head-title">
                <div class="left">
                    <h1>Customer</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Customer</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">List</a></li>
                    </ul>
                </div>
            </div>
            <div class="customer-container">
                <div class="customer-header">
                    <h2>List of Customer</h2>
                </div>
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>First name</th>
                            <th>Last name</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Mobile number</th>
                            <th>No. of Appointments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Daniel</td>
                            <td>Patilla</td>
                            <td>Tetuan, Hotdog drive, Zamboanga City</td>
                            <td>Daniel.Patilla@gmail.com</td>
                            <td>0953342986</td>
                            <td>454949498</td>
                        </tr>
                        <tr>
                            <td>Daniel</td>
                            <td>Patilla</td>
                            <td>Southcom wingo sub, Zamboanga City</td>
                            <td>Daniel.Patilla@gmail.com</td>
                            <td>0978121217</td>
                            <td>454949498</td>
                        </tr>
                        <tr>
                            <td>Jane</td>
                            <td>Doe</td>
                            <td>123 Main St, Zamboanga City</td>
                            <td>Jane.Doe@example.com</td>
                            <td>0912345678</td>
                            <td>123456</td>
                        </tr>
                        <tr>
                            <td>John</td>
                            <td>Smith</td>
                            <td>456 Elm St, Zamboanga City</td>
                            <td>John.Smith@example.com</td>
                            <td>0987654321</td>
                            <td>789012</td>
                        </tr>
                        <tr>
                            <td>Mary</td>
                            <td>Johnson</td>
                            <td>789 Oak St, Zamboanga City</td>
                            <td>Mary.Johnson@example.com</td>
                            <td>0911223344</td>
                            <td>345678</td>
                        </tr>
                        <tr>
                            <td>James</td>
                            <td>Williams</td>
                            <td>101 Pine St, Zamboanga City</td>
                            <td>James.Williams@example.com</td>
                            <td>0922334455</td>
                            <td>567890</td>
                        </tr>
                        <tr>
                            <td>James</td>
                            <td>Williams</td>
                            <td>101 Pine St, Zamboanga City</td>
                            <td>James.Williams@example.com</td>
                            <td>0922334455</td>
                            <td>567890</td>
                        </tr>
                        <tr>
                            <td>James</td>
                            <td>Williams</td>
                            <td>101 Pine St, Zamboanga City</td>
                            <td>James.Williams@example.com</td>
                            <td>0922334455</td>
                            <td>567890</td>
                        </tr>
                        <tr>
                            <td>James</td>
                            <td>Williams</td>
                            <td>101 Pine St, Zamboanga City</td>
                            <td>James.Williams@example.com</td>
                            <td>0922334455</td>
                            <td>567890</td>
                        </tr>
                        <tr>
                            <td>James</td>
                            <td>Williams</td>
                            <td>101 Pine St, Zamboanga City</td>
                            <td>James.Williams@example.com</td>
                            <td>0922334455</td>
                            <td>567890</td>
                        </tr>
                        <tr>
                            <td>James</td>
                            <td>Williams</td>
                            <td>101 Pine St, Zamboanga City</td>
                            <td>James.Williams@example.com</td>
                            <td>0922334455</td>
                            <td>567890</td>
                        </tr>
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
                <div class="pagination">
                    <button>&laquo;</button>
                    <button class="active">1</button>
                    <button>2</button>
                    <button>3</button>
                    <button>4</button>
                    <button>5</button>
                    <button>&raquo;</button>
                </div>
            </div>
        </form>
    </main>
</section>

<!-- Reports Section -->
<section id="reports" class="section">
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Manage Technicians Reports</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Reports</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Technician Reports</a></li>
                </ul>
            </div>
        </div>

        <div class="reports-grid">
            <!-- Sample Report Card -->
            <div class="report-card" data-report-id="1" onclick="openReportModal(1)">
                <div class="report-header">
                    <div class="report-status pending">Pending Review</div>
                    <div class="report-date">March 15, 2024</div>
                </div>
                <div class="report-body">
                    <div class="technician-info">
                        <img src="../Pictures/boy.png" alt="Technician">
                        <div>
                            <h3>John Smith</h3>
                            <span>Senior Technician</span>
                        </div>
                    </div>
                    <div class="report-preview">
                        <p><i class='bx bx-map'></i> Tetuan, Zamboanga City</p>
                        <p><i class='bx bx-user'></i> Client: Maria Garcia</p>
                        <p><i class='bx bx-spray-can'></i> Service: Pest Control</p>
                    </div>
                </div>
            </div>

            <!-- More Sample Cards -->
            <div class="report-card" data-report-id="2" onclick="openReportModal(2)">
                <div class="report-header">
                    <div class="report-status approved">Approved</div>
                    <div class="report-date">March 14, 2024</div>
                </div>
                <div class="report-body">
                    <div class="technician-info">
                        <img src="../Pictures/boy.png" alt="Technician">
                        <div>
                            <h3>Mike Johnson</h3>
                            <span>Pest Control Specialist</span>
                        </div>
                    </div>
                    <div class="report-preview">
                        <p><i class='bx bx-map'></i> Sta. Maria, Zamboanga City</p>
                        <p><i class='bx bx-user'></i> Client: John Doe</p>
                        <p><i class='bx bx-spray-can'></i> Service: Termite Control</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Report Details Modal -->
    <div id="reportModal" class="modal">
        <div class="report-modal-content">
            <span class="close-modal" onclick="closeReportModal()">&times;</span>
            <form id="reportForm" class="report-form">
                <h2>Service Report Details</h2>
                
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Report ID</label>
                            <input type="text" value="REP-2024-001" readonly>
                        </div>
                        <div class="form-group">
                            <label>Date Submitted</label>
                            <input type="text" value="March 15, 2024" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Technician Name</label>
                            <input type="text" value="John Smith" readonly>
                        </div>
                        <div class="form-group">
                            <label>Client Name</label>
                            <input type="text" value="Maria Garcia" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Service Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Service Type</label>
                            <input type="text" value="Pest Control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" value="Tetuan, Zamboanga City" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Time In</label>
                            <input type="time" value="09:00" readonly>
                        </div>
                        <div class="form-group">
                            <label>Time Out</label>
                            <input type="time" value="11:30" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Treatment Information</h3>
                    <div class="form-group full-width">
                        <label>Treatment Method</label>
                        <textarea readonly>Spray treatment and bait installation for comprehensive pest control</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Chemicals Used</label>
                            <input type="text" value="PestAway Pro, RoachGuard" readonly>
                        </div>
                        <div class="form-group">
                            <label>Quantity Used</label>
                            <input type="text" value="2L, 500g" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Documentation</h3>
                    <div class="image-gallery">
                        <div class="image-item">
                            <img src="../Pictures/sample-report-1.jpg" alt="Before Treatment">
                            <span>Before Treatment</span>
                        </div>
                        <div class="image-item">
                            <img src="../Pictures/sample-report-2.jpg" alt="After Treatment">
                            <span>After Treatment</span>
                        </div>
                        <div class="image-item">
                            <img src="../Pictures/sample-report-3.jpg" alt="Area Treated">
                            <span>Area Treated</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Additional Notes</h3>
                    <div class="form-group full-width">
                        <textarea readonly>Client requested follow-up treatment in 3 months. Areas treated: kitchen, bathroom, and garden perimeter. Recommended preventive measures explained to client.</textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-approve" onclick="approveReport()">
                        <i class='bx bx-check'></i> Approve Report
                    </button>
                    <button type="button" class="btn-reject" onclick="rejectReport()">
                        <i class='bx bx-x'></i> Reject Report
                    </button>
                    <button type="button" class="btn-print" onclick="printReport()">
                        <i class='bx bx-printer'></i> Print Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Manage Billing Section -->
<section id="billing" class="section">
    <main>
        <form id="billing-form" method="POST" action="process_billing.php">
            <div class="head-title">
                <div class="left">
                    <h1>Billing Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Billing</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">Overview</a></li>
                    </ul>
                </div>
            </div>
            <div class="main-wrapper">
                <section class="billing-section">
                    <h2>Billing History</h2>
                    <div class="filters">
                        <button type="submit" name="filter" value="all" class="filter-btn active">All</button>
                        <button type="submit" name="filter" value="paid" class="filter-btn">Paid</button>
                        <button type="submit" name="filter" value="unpaid" class="filter-btn">Unpaid</button>
                        <select name="date_range" class="date-range">
                            <option value="6">Last 6 Months</option>
                            <option value="3">Last 3 Months</option>
                            <option value="12">Last Year</option>
                        </select>
                    </div>
                    <table class="billing-table">
                        <thead>
                            <tr>
                                <th>Issue Date</th>
                                <th>Billing #</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>August 1, 2024</td>
                                <td>4528642854</td>
                                <td>Cash</td>
                                <td>Unpaid</td>
                            </tr>
                            <tr>
                                <td>August 2, 2024</td>
                                <td>4528642855</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 3, 2024</td>
                                <td>4528642856</td>
                                <td>Cash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 4, 2024</td>
                                <td>4528642857</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 5, 2024</td>
                                <td>4528642858</td>
                                <td>Cash</td>
                                <td>Unpaid</td>
                            </tr>
                            <tr>
                                <td>August 6, 2024</td>
                                <td>4528642859</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 7, 2024</td>
                                <td>4528642860</td>
                                <td>Cash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 8, 2024</td>
                                <td>4528642861</td>
                                <td>GCash</td>
                                <td>Paid</td>
                            </tr>
                            <tr>
                                <td>August 9, 2024</td>
                                <td>4528642862</td>
                                <td>Cash</td>
                                <td>Unpaid</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <form class="pagination-container">
                        <div class="pagination">
                            <button type="submit" name="page" value="prev" class="page-btn">&laquo;</button>
                            <button type="submit" name="page" value="1" class="page-btn active">1</button>
                            <button type="submit" name="page" value="2" class="page-btn">2</button>
                            <button type="submit" name="page" value="3" class="page-btn">3</button>
                            <button type="submit" name="page" value="next" class="page-btn">&raquo;</button>
                        </div>
                    </form>
                </section>
                <aside class="payment-section">
                    <div class="payment-info">
                        <h3>Payment Info</h3>
                        <p class="payment-note">
                            Cash or GCash does not show your Credit Card Info.
                        </p>
                    </div>
                    
                    <div class="billing-details">
                        <div class="header-row">
                            <h3>Billing Details</h3>
                            <a href="#" class="edit-link">Edit</a>
                        </div>
                        <p><strong>Service Summary:</strong> Pestcontrol, Thermal Control</p>
                        <p><strong>Billing Name:</strong> John Doe</p>
                        <p><strong>Billing Address:</strong> Lorem Address</p>
                    </div>
                    
                    <div class="billing-notification">
                        <h4>Billing Notification Sent To</h4>
                        <p>Email Address: <strong>name@pestco2am.com</strong></p>
                        <p>Phone Number: <strong>+63 123 456 789</strong></p>
                    </div>
                </aside>
            </div>
        </form>
    </main>
</section>

<!-- Profile Section -->
<section id="profile" class="section">
    <main>
        <form id="profile-form" method="POST" action="process_profile.php" enctype="multipart/form-data">
            <div class="head-title">
                <div class="left">
                    <h1>My Profile</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Profile</a>
                        </li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li>
                            <a class="active" href="#">Details</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="profile-container">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <input type="file" name="profile_image" id="profile_image" hidden>
                        <label for="profile_image">
                            <img src="images/profile-image.png" alt="Profile Picture">
                        </label>
                    </div>
                    <div class="profile-info">
                        <h3>Daniel Patilla</h3>
                        <p>Admin</p>
                        <p>Tetuan, Zamboanga City</p>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="info-section">
                    <div class="section-header">
                        <h3>Personal Information</h3>
                        <button class="edit-btn"><i class="bx bx-edit"></i> Edit</button>
                    </div>
                    <div class="info-content">
                        <div class="info-row">
                            <p><strong>First Name</strong></p>
                            <p><strong>Last Name</strong></p>
                            <p><strong>Date of Birth</strong></p>
                        </div>
                        <div class="info-row">
                            <p>Daniel</p>
                            <p>Patilla</p>
                            <p>03-05-2003</p>
                        </div>
                        <div class="info-row">
                            <p><strong>Email:</strong></p>
                            <p><strong>Phone Number:</strong></p>
                            <p><strong>User Role:</strong></p>
                        </div>
                        <div class="info-row">
                            <p>Daniel.Patilla@gmail.com</p>
                            <p>0953-654-4541</p>
                            <p>Admin</p>
                        </div>
                    </div>
                </div>

                <!-- Address Section -->
                <div class="info-section">
                    <div class="section-header">
                        <h3>Address</h3>
                        <button class="edit-btn"><i class="bx bx-edit"></i> Edit</button>
                    </div>
                    <div class="info-content">
                        <div class="info-row">
                            <p><strong>Country</strong></p>
                            <p><strong>City:</strong></p>
                            <p><strong>City Address</strong></p>
                            <p><strong>Postal Code</strong></p>
                        </div>
                        <div class="info-row">
                            <p>Philippines</p>
                            <p>Zamboanga City</p>
                            <p>Tetuan, Hotdog Drive</p>
                            <p>7000</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</section>

<!-- Settings Section -->
<section id="settings" class="section">
    <main>
        <form id="settings-form" method="POST" action="process_settings.php">
            <div class="head-title">
                <div class="left">
                    <h1>Settings Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Settings</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">System Settings</a></li>
                    </ul>
                </div>
            </div>
            <div class="table-data">
                <div class="settings-list">
                    <!-- Settings specific content -->
                </div>
            </div>
        </form>
    </main>
</section>

<!-- MODAL FOR LOGOUT -->
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
    </div>
    <script src="../JS CODES/dashboard-admin.js"></script>
</body>
</html>