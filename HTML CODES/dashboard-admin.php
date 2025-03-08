<?php
session_start();
require_once '../database.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
                <a href="#logout" class="logout">
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
        <form id="work-orders-form" method="POST" action="process_work_orders.php">
            <div class="head-title">
                <div class="left">
                    <h1>Work Orders Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Work Orders</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">List</a></li>
                    </ul>
                </div>
            </div>
            <div class="table-data">
                <div class="order-list">
                    <div class="content">
                        <h1>Job Orders</h1>
                        <ul class="box-info">
                            <li><i class='bx bxs-calendar-check'></i><span class="text"><h3>5</h3><p>Total Appointments</p></span></li>
                            <li><i class='bx bxs-group'></i><span class="text"><h3>3</h3><p>Appointments without Technician</p></span></li>
                            <li><i class='bx bxs-calendar'></i><span class="text"><h3>3</h3><p>Ongoing</p></span></li>
                            <li><i class='bx bxs-check-circle'></i><span class="text"><h3>2</h3><p>Completed Appointments</p></span></li>
                        </ul>
                        <div class="filters">
                            <div class="tabs">
                                <button type="submit" name="filter" value="all" class="active">All</button>
                                <button type="submit" name="filter" value="no_tech">Without Technician</button>
                                <button type="submit" name="filter" value="inspection">For Inspection</button>
                                <button type="submit" name="filter" value="treatment">For Treatment</button>
                                <button type="submit" name="filter" value="completed">Completed</button>
                                <button type="submit" name="action" value="assign_tech" class="assign-tech-btn">Assign Technician</button>
                            </div>
                            <input type="date" name="order_date">
                        </div>
                        <div class="table-container">
                            <form action="#" method="POST">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Appointment No.</th>
                                            <th>Date & Time</th>
                                            <th>Customer</th>
                                            <th>Technician</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2025-00001</td>
                                            <td>Mon 03 Feb - 07:00 AM</td>
                                            <td>Balmond Marasigan</td>
                                            <td class="completed">Jelimar Binay</td>
                                            <td class="status completed">Completed</td>
                                        </tr>
                                        <tr>
                                            <td>2025-00002</td>
                                            <td>Mon 03 Feb - 09:00 AM</td>
                                            <td>Juan Ponce Enrile</td>
                                            <td class="completed">Super Inggo</td>
                                            <td class="status completed">Completed</td>
                                        </tr>
                                        <tr>
                                            <td>2025-00003</td>
                                            <td>Mon 03 Feb - 11:00 AM</td>
                                            <td>Portgas D. Ace</td>
                                            <td><span class="assign-tech">Assign Technician</span></td>
                                            <td class="status for-treatment">For Treatment</td>
                                        </tr>
                                        <tr>
                                            <td>2025-00004</td>
                                            <td>Mon 03 Feb - 01:00 PM</td>
                                            <td>Naruto Ako</td>
                                            <td><span class="assign-tech">Assign Technician</span></td>
                                            <td class="status for-inspection">For Inspection</td>
                                        </tr>
                                        <tr>
                                            <td>2025-00005</td>
                                            <td>Mon 03 Feb - 01:00 PM</td>
                                            <td>Sponge Bob</td>
                                            <td><span class="assign-tech">Assign Technician</span></td>
                                            <td class="status for-inspection">For Inspection</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</section>


<!-- Employee Section -->
<section id="employees" class="section">
    <main>
        <form id="employees-form" method="POST" action="process_employees.php">
            <div class="head-title">
                <div class="left">
                    <h1>Employee Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Employees</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">List</a></li>
                    </ul>
                </div>
                <button class="btn-add">
                    <i class='bx bx-plus'></i>
                    <span class="text">Add New Employee</span>
                </button>
            </div>

            <div class="table-data">
                <div class="employee-list">
                    <div class="order-stats">
                        <div class="stat-card">
                            <i class='bx bxs-group'></i>
                            <div class="stat-details">
                                <h3>Total Employees</h3>
                                <p>25</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class='bx bxs-user-check'></i>
                            <div class="stat-details">
                                <h3>Active</h3>
                                <p>20</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class='bx bxs-user-x'></i>
                            <div class="stat-details">
                                <h3>Inactive</h3>
                                <p>5</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-header">
                        <div class="search-bar">
                            <i class='bx bx-search'></i>
                            <input type="text" placeholder="Search employee...">
                        </div>
                        <div class="filter-options">
                            <select>
                                <option value="">All Teams</option>
                                <option value="treatment">Treatment Team</option>
                                <option value="accounting">Accounting</option>
                                <option value="hr">HR Department</option>
                            </select>
                            <select>
                                <option value="">Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <th>Name</th>
                                    <th>Date of Birth</th>
                                    <th>Email</th>
                                    <th>Mobile Number</th>
                                    <th>Team</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>Juan Delacruz</td>
                                    <td>January 01, 1990</td>
                                    <td>Juan@gmail.com</td>
                                    <td>09111111111</td>
                                    <td><span class="team-label green">Treatment Team</span></td>
                                    <td>Active</td>
                                    <td>
                                        <button class="btn edit-btn">Edit</button>
                                        <button class="btn delete-btn">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>Toe Big</td>
                                    <td>January 02, 1990</td>
                                    <td>toe@gmail.com</td>
                                    <td>09111111112</td>
                                    <td><span class="team-label green">Treatment Team</span></td>
                                    <td>Active</td>
                                    <td>
                                        <button class="btn edit-btn">Edit</button>
                                        <button class="btn delete-btn">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>Trinity Marasigan</td>
                                    <td>January 03, 1990</td>
                                    <td>trinity@gmail.com</td>
                                    <td>09111111113</td>
                                    <td><span class="team-label yellow">Accounting</span></td>
                                    <td>Active</td>
                                    <td>
                                        <button class="btn edit-btn">Edit</button>
                                        <button class="btn delete-btn">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>For Yu</td>
                                    <td>January 04, 1990</td>
                                    <td>for@gmail.com</td>
                                    <td>09111111114</td>
                                    <td><span class="team-label orange">HR Department</span></td>
                                    <td>Inactive</td>
                                    <td>
                                        <button class="btn edit-btn">Edit</button>
                                        <button class="btn delete-btn">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <button>&laquo;</button>
                        <button class="active">1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>&raquo;</button>
                    </div>
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
        <form id="reports-form" method="POST" action="process_reports.php">
            <div class="head-title">
                <div class="left">
                    <h1>Reports Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Reports</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">Overview</a></li>
                    </ul>
                </div>
            </div>
            <div class="table-data">
                <div class="reports-list">
                    <!-- Reports specific content -->
                </div>
            </div>
        </form>
    </main>
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

<!-- CONTENT -->
    <script src="../JS CODES/dashboard-admin.js"></script>
</body>
</html>