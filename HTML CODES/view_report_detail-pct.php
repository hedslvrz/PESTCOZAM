<?php
session_start();
require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in and has PCT role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header("Location: login.php");
    exit();
}

// Check if report ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_submitted_reports-pct.php");
    exit();
}

$reportId = $_GET['id'];
$technicianId = $_SESSION['user_id'];

// Get report details
try {
    $reportQuery = "SELECT 
        sr.*,
        a.id as appointment_id,
        CASE 
            WHEN a.is_for_self = 1 THEN u.firstname
            ELSE a.firstname
        END as client_firstname,
        CASE 
            WHEN a.is_for_self = 1 THEN u.lastname
            ELSE a.lastname
        END as client_lastname,
        s.service_name,
        CONCAT(tech.firstname, ' ', tech.lastname) as technician_name,
        tech.email as technician_email
    FROM service_reports sr
    LEFT JOIN appointments a ON sr.appointment_id = a.id
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN users tech ON sr.technician_id = tech.id
    WHERE sr.id = ? AND sr.technician_id = ?";
    
    $stmt = $db->prepare($reportQuery);
    $stmt->execute([$reportId, $technicianId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$report) {
        // Report not found or does not belong to this technician
        header("Location: view_submitted_reports-pct.php");
        exit();
    }
    
    // Fetch report photos
    $photosQuery = "SELECT * FROM report_photos WHERE report_id = ? ORDER BY id ASC";
    $stmt = $db->prepare($photosQuery);
    $stmt->execute([$reportId]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch report feedback/comments
    $feedbackQuery = "SELECT 
        rf.*,
        CONCAT(u.firstname, ' ', u.lastname) as commenter_name,
        u.role as commenter_role
    FROM report_feedback rf
    JOIN users u ON rf.user_id = u.id
    WHERE rf.report_id = ?
    ORDER BY rf.created_at ASC";
    $stmt = $db->prepare($feedbackQuery);
    $stmt->execute([$reportId]);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error fetching report details: " . $e->getMessage());
    $_SESSION['error'] = "Error loading report details.";
    header("Location: view_submitted_reports-pct.php");
    exit();
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS CODES/dashboard-pct.css">
    <!-- Add SweetAlert2 for improved alerts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <title>Report Details | PCT Dashboard</title>
    <style>
        .report-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin: 30px 0;
            overflow: hidden;
        }
        
        .report-header {
            background: linear-gradient(135deg, #144578, #1a5a99);
            color: white;
            padding: 25px 30px;
            position: relative;
        }
        
        .report-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .report-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .report-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .report-content {
            padding: 30px;
        }
        
        .report-section {
            margin-bottom: 30px;
        }
        
        .report-section h3 {
            color: #144578;
            font-size: 1.3rem;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e9f2;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 1rem;
        }
        
        .treatment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .treatment-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .treatment-description {
            margin-top: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            min-width: 100px;
        }
        
        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-badge.approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .photos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .photo-item {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            cursor: pointer;
            height: 200px;
        }
        
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .photo-item:hover img {
            transform: scale(1.05);
        }
        
        .photo-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 12px;
            font-size: 0.9rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .photo-item:hover .photo-caption {
            opacity: 1;
        }
        
        .feedback-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .feedback-item {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }
        
        .feedback-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .commenter-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .commenter-name {
            font-weight: 600;
            color: #144578;
        }
        
        .commenter-role {
            background-color: #e3f2fd;
            color: #144578;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .feedback-date {
            color: #777;
            font-size: 0.9rem;
        }
        
        .feedback-content {
            color: #333;
            line-height: 1.5;
        }
        
        .no-feedback {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background-color: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background-color: #e0e0e0;
        }
        
        /* Photo Gallery Modal */
        .photo-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            padding: 20px;
            box-sizing: border-box;
        }
        
        .photo-modal-content {
            position: relative;
            margin: auto;
            width: 80%;
            max-width: 1000px;
            height: 80%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .photo-modal-image-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .photo-modal img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .photo-modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .photo-modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .photo-modal-prev {
            left: -80px;
        }
        
        .photo-modal-next {
            right: -80px;
        }
        
        .photo-modal-caption {
            color: white;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            font-size: 0.9rem;
        }
        
        /* For tablets and smaller screens */
        @media (max-width: 992px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* For mobile phones */
        @media (max-width: 768px) {
            .info-grid, .treatment-details-grid {
                grid-template-columns: 1fr;
            }
            
            .report-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .photos-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .photo-modal-content {
                width: 95%;
            }
            
            .photo-modal-nav {
                width: 40px;
                height: 40px;
            }
            
            .photo-modal-prev {
                left: -50px;
            }
            
            .photo-modal-next {
                right: -50px;
            }
        }
    </style>
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
                <a href="dashboard-pct.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="dashboard-pct.php#assignments">
                    <i class='bx bxs-briefcase'></i>
                    <span class="text">My Assignments</span>
                </a>
            </li>
            <li class="active">
                <a href="dashboard-pct.php#submit-report">
                    <i class='bx bx-file'></i>
                    <span class="text">Submit Service Report</span>
                </a>
            </li>
            <li>
                <a href="dashboard-pct.php#schedule-followup">
                    <i class='bx bx-calendar-plus'></i>
                    <span class="text">Schedule Follow-up</span>
                </a>
            </li>
            <li>
                <a href="dashboard-pct.php#profile">
                    <i class='bx bx-user'></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout">
                    <i class='bx bx-log-out'></i>
                    <span class="text">Log out</span>
                </a>
            </li>
        </ul>
    </section>

    <!-- MAIN NAVBAR -->
    <nav id="main-navbar">
        <i class='bx bx-menu'></i>
        <form action="#">
            <div class="form-input">
                <input type="search" placeholder="Search">
                <button type="submit" class="search"><i class='bx bx-search'></i></button>
            </div>
        </form>
        <a href="#" class="notification">
            <i class='bx bxs-bell'></i>
            <span class="num">3</span>
        </a>
        <a href="#" class="profile">
            <img src="../Pictures/tech-profile.jpg" alt="Technician Profile">
        </a>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="head-title">
            <div class="left">
                <h1>Report Details</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard-pct.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a href="view_submitted_reports-pct.php">Submitted Reports</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Report Details</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Back button -->
        <a href="view_submitted_reports-pct.php" class="back-button">
            <i class='bx bx-arrow-back'></i> Back to Reports List
        </a>
        
        <!-- Report Container -->
        <div class="report-container">
            <div class="report-header">
                <h2>Service Report #<?php echo $reportId; ?></h2>
                <div class="report-meta">
                    <span>
                        <i class='bx bx-calendar'></i>
                        Treatment Date: <?php echo date('F d, Y', strtotime($report['date_of_treatment'])); ?>
                    </span>
                    <span>
                        <i class='bx bx-time'></i>
                        Time: <?php echo date('h:i A', strtotime($report['time_in'])); ?> - <?php echo date('h:i A', strtotime($report['time_out'])); ?>
                    </span>
                    <span>
                        <i class='bx bx-check-circle'></i>
                        Status: <span class="status-badge <?php echo strtolower($report['status'] ?: 'pending'); ?>">
                            <?php echo ucfirst($report['status'] ?: 'Pending'); ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="report-content">
                <!-- Client Information -->
                <div class="report-section">
                    <h3>Client Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Account/Client Name</div>
                            <div class="info-value">
                                <?php 
                                if (!empty($report['client_firstname']) && !empty($report['client_lastname'])) {
                                    echo htmlspecialchars($report['client_firstname'] . ' ' . $report['client_lastname']);
                                } else {
                                    echo htmlspecialchars($report['account_name']);
                                }
                                ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Location</div>
                            <div class="info-value"><?php echo htmlspecialchars($report['location']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($report['contact_no']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Treatment Information -->
                <div class="report-section">
                    <h3>Treatment Information</h3>
                    <div class="treatment-details">
                        <div class="treatment-details-grid">
                            <div class="info-item">
                                <div class="info-label">Treatment Type</div>
                                <div class="info-value"><?php echo htmlspecialchars($report['treatment_type']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Pest Count</div>
                                <div class="info-value"><?php echo htmlspecialchars($report['pest_count'] ?: 'Not specified'); ?></div>
                            </div>
                        </div>
                        
                        <div class="treatment-description">
                            <div class="info-label">Treatment Method</div>
                            <div class="info-value method-items">
                                <?php 
                                $treatment_methods = [];
                                try {
                                    if (!empty($report['treatment_method'])) {
                                        $treatment_methods = json_decode($report['treatment_method'], true);
                                        // If it's not a valid JSON or not an array, treat it as a single value
                                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($treatment_methods)) {
                                            $treatment_methods = [$report['treatment_method']];
                                        }
                                    }
                                } catch (Exception $e) {
                                    $treatment_methods = [$report['treatment_method']];
                                }
                                
                                if (!empty($treatment_methods)): 
                                    foreach ($treatment_methods as $method): ?>
                                    <div class="item-entry"><?php echo htmlspecialchars($method); ?></div>
                                    <?php endforeach; 
                                else: ?>
                                    <div class="item-entry">Not specified</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if(!empty($report['device_installation'])): ?>
                        <div class="treatment-description">
                            <div class="info-label">Device Installation</div>
                            <div class="info-value device-items">
                                <?php 
                                $devices = [];
                                try {
                                    $devices = json_decode($report['device_installation'], true);
                                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($devices)) {
                                        $devices = [$report['device_installation']];
                                    }
                                } catch (Exception $e) {
                                    $devices = [$report['device_installation']];
                                }
                                
                                if (!empty($devices)): 
                                    foreach ($devices as $device): ?>
                                    <div class="item-entry"><?php echo htmlspecialchars($device); ?></div>
                                    <?php endforeach; 
                                else: ?>
                                    <div class="item-entry">Not specified</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($report['consumed_chemicals'])): ?>
                        <div class="treatment-description">
                            <div class="info-label">Consumed Chemicals/Products</div>
                            <div class="info-value chemical-items">
                                <?php 
                                $chemicals = [];
                                try {
                                    $chemicals = json_decode($report['consumed_chemicals'], true);
                                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($chemicals)) {
                                        $chemicals = [$report['consumed_chemicals']];
                                    }
                                } catch (Exception $e) {
                                    $chemicals = [$report['consumed_chemicals']];
                                }
                                
                                if (!empty($chemicals)): 
                                    foreach ($chemicals as $chemical): ?>
                                    <div class="item-entry"><?php echo htmlspecialchars($chemical); ?></div>
                                    <?php endforeach; 
                                else: ?>
                                    <div class="item-entry">Not specified</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Photos Section -->
                <?php if(!empty($photos)): ?>
                <div class="report-section">
                    <h3>Service Photos</h3>
                    <div class="photos-container">
                        <?php foreach($photos as $index => $photo): ?>
                        <div class="photo-item" onclick="openPhotoModal(<?php echo $index; ?>)">
                            <img src="../uploads/report_photos/<?php echo htmlspecialchars($photo['filename']); ?>" 
                                 alt="Report Photo <?php echo $index + 1; ?>">
                            <div class="photo-caption">
                                Photo <?php echo $index + 1; ?>
                                <?php if(!empty($photo['caption'])): ?>
                                - <?php echo htmlspecialchars($photo['caption']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Feedback/Comments Section -->
                <div class="report-section">
                    <h3>Feedback & Comments</h3>
                    <div class="feedback-section">
                        <?php if(!empty($feedback)): ?>
                            <?php foreach($feedback as $item): ?>
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <div class="commenter-info">
                                        <span class="commenter-name"><?php echo htmlspecialchars($item['commenter_name']); ?></span>
                                        <span class="commenter-role"><?php echo ucfirst(htmlspecialchars($item['commenter_role'])); ?></span>
                                    </div>
                                    <div class="feedback-date">
                                        <?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="feedback-content">
                                    <?php echo nl2br(htmlspecialchars($item['comment'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-feedback">
                                <p>No feedback or comments yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Photo Modal -->
        <?php if(!empty($photos)): ?>
        <div id="photoModal" class="photo-modal">
            <span class="photo-modal-close">&times;</span>
            
            <div class="photo-modal-content">
                <div class="photo-modal-image-container">
                    <img id="modalImage">
                </div>
                
                <div class="photo-modal-caption" id="modalCaption"></div>
                
                <a class="photo-modal-nav photo-modal-prev">&#10094;</a>
                <a class="photo-modal-nav photo-modal-next">&#10095;</a>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script src="../JS CODES/dashboard-pct.js"></script>
    <script>
        // Handle sidebar toggle
        document.querySelector('#main-navbar .bx-menu').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('hide');
        });
        
        <?php if(!empty($photos)): ?>
        // Photo Gallery Modal Functions
        let slideIndex = 0;
        const photoModal = document.getElementById('photoModal');
        const modalImage = document.getElementById('modalImage');
        const modalCaption = document.getElementById('modalCaption');
        const photos = <?php 
            $photoArray = array_map(function($photo) {
                return [
                    'src' => '../uploads/report_photos/' . $photo['filename'],
                    'caption' => !empty($photo['caption']) ? $photo['caption'] : ''
                ];
            }, $photos);
            echo json_encode($photoArray);
        ?>;
        
        function openPhotoModal(photoIndex) {
            slideIndex = photoIndex;
            showSlide(slideIndex);
            photoModal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent body scrolling
        }
        
        function showSlide(index) {
            if (index >= photos.length) {
                slideIndex = 0;
            } else if (index < 0) {
                slideIndex = photos.length - 1;
            } else {
                slideIndex = index;
            }
            
            modalImage.src = photos[slideIndex].src;
            modalCaption.textContent = photos[slideIndex].caption || `Photo ${slideIndex + 1}`;
        }
        
        // Close modal
        document.querySelector('.photo-modal-close').addEventListener('click', function() {
            photoModal.style.display = 'none';
            document.body.style.overflow = ''; // Restore body scrolling
        });
        
        // Navigation buttons
        document.querySelector('.photo-modal-prev').addEventListener('click', function() {
            showSlide(slideIndex - 1);
        });
        
        document.querySelector('.photo-modal-next').addEventListener('click', function() {
            showSlide(slideIndex + 1);
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (photoModal.style.display === 'block') {
                if (e.key === 'Escape') {
                    photoModal.style.display = 'none';
                    document.body.style.overflow = ''; // Restore body scrolling
                } else if (e.key === 'ArrowLeft') {
                    showSlide(slideIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    showSlide(slideIndex + 1);
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
