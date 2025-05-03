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

// Get technician ID from session
$technicianId = $_SESSION['user_id'];

// Get technician's submitted reports
try {
    // Fetch all submitted reports for the logged-in technician
    $reportsQuery = "SELECT 
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
    WHERE sr.technician_id = :technicianId
    ORDER BY sr.date_of_treatment DESC";

    $stmt = $db->prepare($reportsQuery);
    $stmt->execute([':technicianId' => $_SESSION['user_id']]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get technician data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'technician'");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error fetching submitted reports: " . $e->getMessage());
    $_SESSION['error'] = "Error loading submitted reports.";
    $reports = [];
    $technician = null;
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
    <!-- Add SweetAlert2 for improved alerts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <title>My Submitted Reports | PCT Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #144578;
            font-size: 1.8rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #144578;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        
        .back-button:hover {
            background-color: #0d3057;
        }
        
        /* Reports Grid Layout */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .report-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .report-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .report-status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .report-status.approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .report-status.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .report-date {
            font-size: 14px;
            color: #666;
        }
        
        .report-body {
            padding: 15px;
        }
        
        .report-preview p {
            display: flex;
            align-items: center;
            margin: 8px 0;
            font-size: 14px;
            color: #333;
        }
        
        .report-preview p i {
            margin-right: 10px;
            font-size: 18px;
            color: #144578;
            min-width: 20px;
        }
        
        .no-reports {
            text-align: center;
            padding: 40px 20px;
            background: #f9f9f9;
            border-radius: 10px;
            grid-column: 1 / -1;
        }
        
        .no-reports i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }
        
        .no-reports p {
            font-size: 18px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .no-reports span {
            font-size: 14px;
            color: #999;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .report-modal-content {
            position: relative;
            background-color: #fff;
            margin: 50px auto;
            padding: 0;
            width: 80%;
            max-width: 800px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            animation: modalSlide 0.3s ease-out;
        }
        
        @keyframes modalSlide {
            from {opacity: 0; transform: translateY(-30px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            color: #aaa;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .close-modal:hover {
            color: #144578;
        }
        
        .report-form {
            padding: 20px;
        }
        
        .report-form h2 {
            margin: 0 0 20px;
            font-size: 24px;
            color: #144578;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            margin: 0 0 15px;
            font-size: 18px;
            color: #333;
            font-weight: 500;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background-color: #f9f9f9;
            color: #333;
        }
        
        .full-width {
            flex: 0 0 100%;
        }
        
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-item {
            border-radius: 6px;
            overflow: hidden;
            height: 120px;
            cursor: pointer;
        }
        
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }
        
        .breadcrumb li {
            font-size: 14px;
            color: #666;
            list-style-type: none;
        }
        
        .breadcrumb li a {
            color: #144578;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .breadcrumb li a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb li a.active {
            color: #333;
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .report-modal-content {
                width: 95%;
                margin: 30px auto;
            }
            
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>My Submitted Reports</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard-pct.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Submitted Reports</a></li>
                </ul>
            </div>
            <a href="dashboard-pct.php#submit-report" class="back-button">
                <i class='bx bx-arrow-back'></i> Back to Dashboard
            </a>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
            <?php if (empty($reports)): ?>
                <div class="no-reports">
                    <i class='bx bx-file-blank'></i>
                    <p>No submitted reports found.</p>
                    <span>Your submitted service reports will appear here.</span>
                </div>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <?php
                    // Determine status class
                    $statusClass = '';
                    switch (strtolower($report['status'])) {
                        case 'pending':
                            $statusClass = 'pending';
                            $statusText = 'Pending Review';
                            break;
                        case 'approved':
                            $statusClass = 'approved';
                            $statusText = 'Approved';
                            break;
                        case 'rejected':
                            $statusClass = 'rejected';
                            $statusText = 'Rejected';
                            break;
                        default:
                            $statusClass = 'pending';
                            $statusText = 'Pending Review';
                    }
                    
                    // Format date
                    $formattedDate = date('F d, Y', strtotime($report['date_of_treatment']));
                    ?>
                    <div class="report-card" data-report-id="<?php echo $report['report_id']; ?>">
                        <div class="report-header">
                            <div class="report-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                            <div class="report-date"><?php echo $formattedDate; ?></div>
                        </div>
                        <div class="report-body">
                            <div class="report-preview">
                                <p><i class='bx bx-map'></i> <?php echo htmlspecialchars($report['location']); ?></p>
                                <p><i class='bx bx-user'></i> Client: <?php echo htmlspecialchars($report['account_name']); ?></p>
                                <p><i class='bx bx-spray-can'></i> Service: <?php echo htmlspecialchars($report['treatment_type']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Report Details Modal -->
    <div id="reportModal" class="modal">
        <div class="report-modal-content">
            <span class="close-modal" onclick="closeReportModal()">&times;</span>
            <form id="reportForm" class="report-form">
                <h2>Service Report Details</h2>
                <input type="hidden" id="reportIdField" value="">
                
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Report ID</label>
                            <input type="text" id="reportIdDisplay" readonly>
                        </div>
                        <div class="form-group">
                            <label>Date of Treatment</label>
                            <input type="text" id="reportDateField" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Client Name</label>
                            <input type="text" id="clientNameField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Contact No.</label>
                            <input type="text" id="contactNoField" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" id="locationField" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Service Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Treatment Type</label>
                            <input type="text" id="treatmentTypeField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Treatment Method</label>
                            <input type="text" id="treatmentMethodField" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Time In</label>
                            <input type="text" id="timeInField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Time Out</label>
                            <input type="text" id="timeOutField" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Treatment Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pest Count</label>
                            <input type="text" id="pestCountField" readonly>
                        </div>
                        <div class="form-group">
                            <label>Device Installation</label>
                            <input type="text" id="deviceInstallationField" readonly>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Chemicals Consumed</label>
                        <textarea id="chemicalsField" readonly rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Frequency of Visits</label>
                            <input type="text" id="frequencyField" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section" id="photosSection">
                    <h3>Documentation</h3>
                    <div class="image-gallery" id="photosContainer">
                        <!-- Photos will be dynamically added here -->
                    </div>
                </div>

                <div id="statusSection" class="form-section">
                    <h3>Report Status</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Current Status</label>
                            <div id="statusDisplay" style="padding: 10px; border-radius: 6px; font-weight: 500; text-align: center;"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Make the reports data globally available through the window object
        window.reportsData = <?php echo json_encode($reports ?? []); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize report cards with event listeners
            const reportCards = document.querySelectorAll('.report-card');
            console.log(`Found ${reportCards.length} report cards to initialize`);
            
            reportCards.forEach(card => {
                card.addEventListener('click', function() {
                    const reportId = this.getAttribute('data-report-id');
                    openReportModal(reportId);
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('reportModal');
                if (event.target === modal) {
                    closeReportModal();
                }
            });
        });
        
        // Function to open report modal and populate data
        function openReportModal(reportId) {
            console.log('Opening report modal for ID:', reportId);
            
            // Find the report in the global data
            const report = window.reportsData.find(r => r.report_id == reportId);
            
            if (!report) {
                console.error('Report not found with ID:', reportId);
                return;
            }
            
            console.log('Found report:', report);
            
            // Populate the modal fields with report data
            document.getElementById('reportIdField').value = reportId;
            document.getElementById('reportIdDisplay').value = reportId;
            document.getElementById('reportDateField').value = formatDate(report.date_of_treatment);
            document.getElementById('clientNameField').value = report.account_name || 'N/A';
            document.getElementById('contactNoField').value = report.contact_no || 'N/A';
            document.getElementById('locationField').value = report.location || 'N/A';
            document.getElementById('treatmentTypeField').value = report.treatment_type || 'N/A';
            document.getElementById('treatmentMethodField').value = report.treatment_method || 'N/A';
            document.getElementById('timeInField').value = formatTime(report.time_in) || 'N/A';
            document.getElementById('timeOutField').value = formatTime(report.time_out) || 'N/A';
            document.getElementById('pestCountField').value = report.pest_count || 'N/A';
            document.getElementById('deviceInstallationField').value = report.device_installation || 'N/A';
            document.getElementById('chemicalsField').value = report.consumed_chemicals || 'N/A';
            document.getElementById('frequencyField').value = report.frequency_of_visits || 'N/A';
            
            // Set status display
            const statusDisplay = document.getElementById('statusDisplay');
            let statusClass = '';
            let statusText = '';
            
            switch(report.status.toLowerCase()) {
                case 'pending':
                    statusClass = 'pending';
                    statusText = 'Pending Review';
                    break;
                case 'approved':
                    statusClass = 'approved';
                    statusText = 'Approved';
                    break;
                case 'rejected':
                    statusClass = 'rejected';
                    statusText = 'Rejected';
                    break;
                default:
                    statusClass = 'pending';
                    statusText = 'Pending Review';
            }
            
            statusDisplay.className = '';
            statusDisplay.classList.add(statusClass);
            statusDisplay.textContent = statusText;
            
            if (statusClass === 'pending') {
                statusDisplay.style.backgroundColor = '#fff3cd';
                statusDisplay.style.color = '#856404';
            } else if (statusClass === 'approved') {
                statusDisplay.style.backgroundColor = '#d4edda';
                statusDisplay.style.color = '#155724';
            } else if (statusClass === 'rejected') {
                statusDisplay.style.backgroundColor = '#f8d7da';
                statusDisplay.style.color = '#721c24';
            }
            
            // Handle photos if any
            const photosContainer = document.getElementById('photosContainer');
            photosContainer.innerHTML = '';
            
            if (report.photos) {
                let photos;
                try {
                    if (typeof report.photos === 'string') {
                        photos = JSON.parse(report.photos);
                    } else {
                        photos = report.photos;
                    }
                    
                    if (Array.isArray(photos) && photos.length > 0) {
                        photos.forEach((photo, index) => {
                            const imgDiv = document.createElement('div');
                            imgDiv.className = 'image-item';
                            const img = document.createElement('img');
                            img.src = '../uploads/report_photos/' + photo;
                            img.alt = 'Report photo ' + (index + 1);
                            
                            imgDiv.appendChild(img);
                            photosContainer.appendChild(imgDiv);
                        });
                        document.getElementById('photosSection').style.display = 'block';
                    } else {
                        document.getElementById('photosSection').style.display = 'none';
                    }
                } catch (e) {
                    console.error('Error parsing photos:', e);
                    document.getElementById('photosSection').style.display = 'none';
                }
            } else {
                document.getElementById('photosSection').style.display = 'none';
            }
            
            // Display the modal
            document.getElementById('reportModal').style.display = 'block';
        }
        
        // Function to close report modal
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }
        
        // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        // Helper function to format time
        function formatTime(timeString) {
            if (!timeString) return 'N/A';
            
            // Check if the timeString has seconds or not
            const timeParts = timeString.split(':');
            let hours = parseInt(timeParts[0]);
            const minutes = timeParts[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            
            return hours + ':' + minutes + ' ' + ampm;
        }
    </script>
</body>
</html>
