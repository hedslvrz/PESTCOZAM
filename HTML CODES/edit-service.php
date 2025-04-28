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

// Get service ID from URL parameter
$serviceId = $_GET['id'] ?? 0;

// Function to resize and compress image
function resizeAndCompressImage($imagePath, $maxWidth = 600, $maxHeight = 600, $quality = 65) {
    // Get image info
    list($width, $height, $type) = getimagesize($imagePath);
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    // Only resize if the image is larger than the max dimensions
    if ($ratio < 1) {
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;
    } else {
        // Keep original size if already smaller than max dimensions
        $newWidth = $width;
        $newHeight = $height;
    }
    
    // Create new image resource
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Create source image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($imagePath);
            // Preserve transparency
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($imagePath);
            break;
        default:
            return false;
    }
    
    // Resize image
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Output buffer to capture compressed image
    ob_start();
    
    // Output compressed image to buffer
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, null, $quality);
            break;
        case IMAGETYPE_PNG:
            // PNG quality is 0-9 (0=no compression, 9=max compression)
            $pngQuality = round((100 - $quality) / 11.11);
            imagepng($newImage, null, $pngQuality);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage);
            break;
    }
    
    // Get image data from buffer
    $imageData = ob_get_clean();
    
    // Free up memory
    imagedestroy($source);
    imagedestroy($newImage);
    
    return $imageData;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = $_POST['service_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $estimated_time = $_POST['estimated_time'] ?? '';
    $starting_price = $_POST['starting_price'] ?? 0;
    $keep_existing_image = isset($_POST['keep_existing_image']) ? true : false;
    
    try {
        // Initialize query and parameters
        $params = [$service_name, $description, $estimated_time, $starting_price];
        $query = "UPDATE services SET service_name = ?, description = ?, estimated_time = ?, starting_price = ?";
        
        // Handle image upload
        if (!$keep_existing_image && isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
            $imageType = $_FILES['service_image']['type'];
            
            // Validate image type (optional)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($imageType, $allowedTypes)) {
                $errorMessage = "Invalid image type. Please upload a JPEG, PNG, or GIF image.";
            } else {
                // Instead of direct file_get_contents, resize and compress the image
                $imageData = resizeAndCompressImage($_FILES['service_image']['tmp_name']);
                
                if ($imageData) {
                    // Add image data to query and parameters
                    $query .= ", image_data = ?, image_type = ?";
                    $params[] = $imageData;
                    $params[] = $imageType;
                } else {
                    $errorMessage = "Error processing the image. Please try a different image.";
                }
            }
        }
        
        // Complete the query
        $query .= " WHERE service_id = ?";
        $params[] = $serviceId;
        
        if (empty($errorMessage)) {
            // Execute the query
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            header("Location: dashboard-admin.php#services");
            exit();
        }
    } catch (PDOException $e) {
        $errorMessage = "Error updating service: " . $e->getMessage();
    }
} else {
    // Fetch existing service data
    try {
        $stmt = $db->prepare("SELECT * FROM services WHERE service_id = ?");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            $errorMessage = "Service not found.";
        }
    } catch (PDOException $e) {
        $errorMessage = "Error fetching service: " . $e->getMessage();
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
                    <h2>Additional Information</h2>
                    <div class="form-group">
                        <label for="estimated_time">Estimated Time <span class="required-field">*</span></label>
                        <input type="text" id="estimated_time" name="estimated_time" value="<?php echo htmlspecialchars($service['estimated_time'] ?? ''); ?>" required>
                        <div class="helper-text">Provide an estimated time for the service</div>
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
                        <label>Current Image:</label>
                        <div class="current-image-container">
                            <?php if (!empty($service['image_data'])): ?>
                                <?php 
                                    $imageType = $service['image_type'] ?? 'image/jpeg';
                                    $base64Image = base64_encode($service['image_data']);
                                ?>
                                <img src="data:<?php echo $imageType; ?>;base64,<?php echo $base64Image; ?>" 
                                     alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                     style="max-width: 200px; max-height: 200px;">
                            <?php elseif (!empty($service['image_path'])): ?>
                                <?php 
                                    $imagePath = $service['image_path'];
                                    $displayImagePath = "../Pictures/" . $imagePath;
                                ?>
                                <img src="<?php echo htmlspecialchars($displayImagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                     style="max-width: 200px; max-height: 200px;">
                            <?php else: ?>
                                <p>No image available</p>
                            <?php endif; ?>
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" id="keep_existing_image" name="keep_existing_image" class="form-check-input" checked>
                            <label for="keep_existing_image" class="form-check-label">Keep existing image</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_image">Upload New Image:</label>
                        <input type="file" id="service_image" name="service_image" accept="image/*">
                        <small class="form-text text-muted">Upload a new image for this service (JPEG, PNG, or GIF).</small>
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

            // Toggle file input based on checkbox
            const keepExistingImageCheckbox = document.getElementById('keep_existing_image');
            keepExistingImageCheckbox.addEventListener('change', function() {
                const fileInput = document.getElementById('service_image');
                fileInput.disabled = this.checked;
                if (this.checked) {
                    fileInput.value = ''; // Clear the file input when checkbox is checked
                }
            });
            
            // Set initial state
            document.getElementById('service_image').disabled = keepExistingImageCheckbox.checked;
        });
    </script>
</body>
</html>
