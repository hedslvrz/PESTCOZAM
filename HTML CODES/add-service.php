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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $service_name = $_POST['service_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $estimated_time = $_POST['estimated_time'] ?? '';
    $starting_price = $_POST['starting_price'] ?? 0;
    
    // Initialize variables for image handling
    $imageData = null;
    $imageType = null;
    
    // Check if an image was uploaded
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        // Read the image data
        $imageData = file_get_contents($_FILES['service_image']['tmp_name']);
        $imageType = $_FILES['service_image']['type'];
        
        // Validate image type (optional)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($imageType, $allowedTypes)) {
            $error = "Invalid image type. Please upload a JPEG, PNG, or GIF image.";
        }
    }

    if (empty($error)) {
        try {
            // Insert new service
            $stmt = $db->prepare("INSERT INTO services (service_name, description, estimated_time, starting_price, image_data, image_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$service_name, $description, $estimated_time, $starting_price, $imageData, $imageType]);

            // Redirect back to services management page
            header("Location: dashboard-admin.php#services");
            exit();
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            exit();
        }
    }
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
    <link rel="stylesheet" href="../CSS CODES/add-service.css">
    <title>Add New Service</title>
</head>
<body>
    <!-- SIDEBAR SECTION -->
    <section id="sidebar">
        <div class="logo-container">
            <img src="../Pictures/pest_logo.png" alt="Flower Logo" class="flower-logo">
            <span class="brand-name">PESTCOZAM</span>
        </div>
        <ul class="side-menu top">
            <li>
                <a href="dashboard-admin.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="dashboard-admin.php#work-orders">
                    <i class='bx bxs-briefcase'></i>
                    <span class="text">Manage Job Orders</span>
                </a>
            </li>
            <li>
                <a href="dashboard-admin.php#employees">
                    <i class='bx bx-child'></i>
                    <span class="text">Manage Employees</span>
                </a>
            </li>
            <li class="active">
                <a href="dashboard-admin.php#services">
                    <i class='bx bx-dish'></i>
                    <span class="text">Manage Services</span>
                </a>
            </li>
            <li>
                <a href="dashboard-admin.php#customers">
                    <i class='bx bx-run'></i>
                    <span class="text">Manage Customers</span>
                </a>
            </li>
            <li>
                <a href="dashboard-admin.php#reports">
                    <i class='bx bxs-report'></i>
                    <span class="text">Manage Technician Reports</span>
                </a>
            </li>
            <li>
                <a href="dashboard-admin.php#billing">
                    <i class='bx bx-money-withdraw'></i>
                    <span class="text">Manage Billing</span>
                </a>
            </li>
            <li>
                <a href="dashboard-admin.php#profile">
                    <i class='bx bx-user'></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="dashboard-admin.php#settings">
                    <i class='bx bx-cog'></i>
                    <span class="text">Settings</span>
                </a>
            </li>
            <li>
                <a href="Login.php" class="logout">
                    <i class='bx bx-log-out'></i>
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
    <!-- MAIN NAVBAR -->

    <section class="section active">
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Add New Service</h1>
                    <ul class="breadcrumb">
                        <li><a href="dashboard-admin.php#services">Services</a></li>
                        <li><i class='bx bx-right-arrow-alt'></i></li>
                        <li><a class="active" href="#">Add New Service</a></li>
                    </ul>
                </div>
                <a href="dashboard-admin.php#services" class="btn-back">
                    <i class='bx bx-arrow-back'></i>
                    <span class="text">Back to Services</span>
                </a>
            </div>
            
            <form class="add-service-form" method="POST" action="add-service.php" enctype="multipart/form-data">
                <div class="form-section">
                    <h2>Basic Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service_name">Service Name <span class="required-field">*</span></label>
                            <input type="text" id="service_name" name="service_name" required>
                        </div>
                        <div class="form-group">
                            <label for="estimated_time">Estimated Time <span class="required-field">*</span></label>
                            <input type="text" id="estimated_time" name="estimated_time" placeholder="e.g. 2-3 hours" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Service Description <span class="required-field">*</span></label>
                        <textarea id="description" name="description" required></textarea>
                        <div class="helper-text">Provide a detailed description of the service for customers</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Pricing Information</h2>
                    <div class="form-group">
                        <label for="starting_price">Starting Price (₱) <span class="required-field">*</span></label>
                        <input type="text" id="starting_price" name="starting_price" placeholder="e.g. 1500.00" required>
                        <div class="helper-text">This is the base price before inspection</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Service Image</h2>
                    <div class="form-group">
                        <label for="service_image">Upload Service Image <span class="required-field">*</span></label>
                        <div class="file-upload">
                            <div class="file-upload-btn" id="upload-container">
                                <i class='bx bx-upload'></i>
                                <span>Click to upload image or drag and drop</span>
                                <p>PNG, JPG, JPEG (Max 5MB)</p>
                            </div>
                            <input type="file" id="service_image" name="service_image" accept="image/*" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="dashboard-admin.php#services" class="btn btn-secondary">
                        <i class='bx bx-x'></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save'></i> Save Service
                    </button>
                </div>
            </form>
        </main>
    </section>

    <!-- Delete Service Modal -->
    <div id="deleteServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this service? This action cannot be undone.</p>
                <div class="warning-icon">
                    <i class='bx bx-error-circle'></i>
                </div>
                <form id="deleteServiceForm" method="POST" action="delete-service.php">
                    <input type="hidden" id="service_id" name="service_id">
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete Service</button>
            </div>
        </div>
    </div>

    <script src="../JS CODES/dashboard-admin.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('service_image').addEventListener('change', function(e) {
            const uploadContainer = document.getElementById('upload-container');
            
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Clear the container
                    uploadContainer.innerHTML = '';
                    uploadContainer.classList.add('has-image');
                    
                    // Add the image preview
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = "Service Image Preview";
                    uploadContainer.appendChild(img);
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Prevent form submission on Enter key
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                event.preventDefault();
            }
        });
        
        // Format price input to ensure it's numeric with 2 decimal places
        document.getElementById('starting_price').addEventListener('blur', function() {
            let value = this.value.replace(/[^0-9.]/g, '');
            if (value) {
                value = parseFloat(value).toFixed(2);
                this.value = value;
            }
        });
    </script>

    <script>
        // Delete Service Modal Functionality
        const deleteServiceModal = document.getElementById('deleteServiceModal');
        const closeButtons = document.getElementsByClassName('close-modal');
        
        // Function to open the delete modal with service ID
        function openDeleteModal(serviceId) {
            document.getElementById('service_id').value = serviceId;
            deleteServiceModal.style.display = 'flex';
        }
        
        // Close modal when clicking X or Cancel button
        for (let i = 0; i < closeButtons.length; i++) {
            closeButtons[i].addEventListener('click', function() {
                deleteServiceModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target == deleteServiceModal) {
                deleteServiceModal.style.display = 'none';
            }
        });
        
        // Handle delete confirmation
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            document.getElementById('deleteServiceForm').submit();
        });
    </script>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: #fff;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from {transform: translateY(-20px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        
        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #888;
        }
        
        .close-modal:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 20px;
            text-align: center;
        }
        
        .warning-icon {
            font-size: 48px;
            color: #ff6b6b;
            margin: 15px 0;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            padding: 15px 20px;
            border-top: 1px solid #eee;
            gap: 10px;
        }
        
        .btn-danger {
            background-color: #ff6b6b;
        }
        
        .btn-danger:hover {
            background-color: #ff4f4f;
        }
    </style>
</body>
</html>
