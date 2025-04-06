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

// Check if service ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard-admin.php#services");
    exit();
}

$serviceId = (int)$_GET['id'];

// Fetch service details
try {
    $stmt = $db->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        // Service not found
        header("Location: dashboard-admin.php#services");
        exit();
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header("Location: dashboard-admin.php#services");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare update query
        $query = "UPDATE services SET service_name = ?, description = ?, starting_price = ?";
        $params = [
            $_POST['service_name'],
            $_POST['description'],
            $_POST['starting_price']
        ];
        
        // Check if new image is uploaded
        $hasNewImage = !empty($_FILES['service_image']['name']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK;
        
        if ($hasNewImage) {
            // Process image upload
            $targetDir = "../Pictures/";
            $fileName = basename($_FILES["service_image"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow certain file formats
            $allowTypes = array('jpg', 'png', 'jpeg');
            if (in_array(strtolower($fileType), $allowTypes)) {
                // Upload file
                if (move_uploaded_file($_FILES["service_image"]["tmp_name"], $targetFilePath)) {
                    $query .= ", image_path = ?";
                    $params[] = $fileName;
                }
            }
        }
        
        $query .= " WHERE service_id = ?";
        $params[] = $serviceId;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        header("Location: dashboard-admin.php#services");
        exit();
    } catch(PDOException $e) {
        $errorMessage = "Error updating service: " . $e->getMessage();
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
    <link rel="stylesheet" href="../CSS CODES/add-service.css">
    <title>Edit Service - Pestcozam</title>
</head>
<body>
    <section class="section active">
        <main>
            <div class="page-header">
                <div class="head-title">
                    <div class="left">
                        <h1>Edit Service</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="dashboard-admin.php#services">Services</a>
                            </li>
                            <li><i class='bx bx-right-arrow-alt' ></i></li>
                            <li>
                                <a class="active" href="#">Edit Service</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <form class="add-service-form" method="POST" action="edit-service.php?id=<?php echo $serviceId; ?>" enctype="multipart/form-data">
                <div class="form-section">
                    <h2>Basic Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service_name">Service Name <span class="required-field">*</span></label>
                            <input type="text" id="service_name" name="service_name" value="<?php echo htmlspecialchars($service['service_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Service Description <span class="required-field">*</span></label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                        <div class="helper-text">Provide a detailed description of the service for customers</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Pricing Information</h2>
                    <div class="form-group">
                        <label for="starting_price">Starting Price (₱) <span class="required-field">*</span></label>
                        <input type="text" id="starting_price" name="starting_price" placeholder="e.g. 1500.00" value="<?php echo htmlspecialchars($service['starting_price']); ?>" required>
                        <div class="helper-text">This is the base price before inspection</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Service Image</h2>
                    <div class="form-group">
                        <label for="service_image">Upload Service Image</label>
                        <div class="file-upload">
                            <div class="file-upload-btn <?php echo !empty($service['image_path']) ? 'has-image' : ''; ?>" id="upload-container">
                                <?php if (!empty($service['image_path'])): ?>
                                    <img src="../Pictures/<?php echo htmlspecialchars($service['image_path']); ?>" alt="Current Service Image">
                                <?php else: ?>
                                    <i class='bx bx-upload'></i>
                                    <span>Click to upload image or drag and drop</span>
                                    <p>PNG, JPG, JPEG (Max 5MB)</p>
                                <?php endif; ?>
                            </div>
                            <input type="file" id="service_image" name="service_image" accept="image/*">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="dashboard-admin.php#services" class="btn btn-secondary">
                        <i class='bx bx-x'></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save'></i> Update Service
                    </button>
                </div>
            </form>
        </main>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview functionality
            const serviceImage = document.getElementById('service_image');
            const uploadContainer = document.getElementById('upload-container');
            
            serviceImage.addEventListener('change', function() {
                const file = this.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        uploadContainer.innerHTML = `<img src="${e.target.result}" alt="Service Image Preview">`;
                        uploadContainer.classList.add('has-image');
                    }
                    
                    reader.readAsDataURL(file);
                } else {
                    <?php if (!empty($service['image_path'])): ?>
                        uploadContainer.innerHTML = `<img src="../Pictures/<?php echo htmlspecialchars($service['image_path']); ?>" alt="Current Service Image">`;
                    <?php else: ?>
                        uploadContainer.innerHTML = `
                            <i class='bx bx-upload'></i>
                            <span>Click to upload image or drag and drop</span>
                            <p>PNG, JPG, JPEG (Max 5MB)</p>
                        `;
                        uploadContainer.classList.remove('has-image');
                    <?php endif; ?>
                }
            });
        });
    </script>
</body>
</html>
