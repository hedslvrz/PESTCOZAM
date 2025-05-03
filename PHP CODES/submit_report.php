<?php
session_start();
require_once '../database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    $_SESSION['report_error'] = "Unauthorized access. Please log in as a technician.";
    header("Location: ../HTML CODES/login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['report_error'] = "Invalid request method.";
    header("Location: ../HTML CODES/dashboard-pct.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Collect and sanitize form data
    $technician_id = $_SESSION['user_id'];
    $appointment_id = !empty($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
    $date_of_treatment = $_POST['date_of_treatment'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];
    $treatment_type = trim($_POST['treatment_type']);
    $treatment_method = trim($_POST['treatment_method']);
    $pest_count = !empty($_POST['pest_count']) ? trim($_POST['pest_count']) : null;
    $device_installation = !empty($_POST['device_installation']) ? trim($_POST['device_installation']) : null;
    $consumed_chemicals = !empty($_POST['consumed_chemicals']) ? trim($_POST['consumed_chemicals']) : null;
    $frequency_of_visits = !empty($_POST['frequency_of_visits']) ? trim($_POST['frequency_of_visits']) : null;
    $location = trim($_POST['location']);
    $account_name = trim($_POST['account_name']);
    $contact_no = trim($_POST['contact_no']);
    
    // Log the data being inserted
    error_log("Submitting report: " . json_encode([
        'technician_id' => $technician_id,
        'appointment_id' => $appointment_id,
        'date_of_treatment' => $date_of_treatment,
        'treatment_type' => $treatment_type,
        'account_name' => $account_name,
        'location' => $location
    ]));
    
    // Handle photo uploads
    $photos = [];
    $uploadDir = '../uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        error_log("Created uploads directory: $uploadDir");
    }
    
    // Process each uploaded file
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['photos']['error'][$key] === 0) {
                // Generate a unique filename
                $filename = 'report_' . time() . '_' . $key . '_' . basename($_FILES['photos']['name'][$key]);
                $targetFile = $uploadDir . $filename;
                
                // Check file size (max 2MB)
                if ($_FILES['photos']['size'][$key] > 2 * 1024 * 1024) {
                    $_SESSION['report_error'] = "File size exceeds 2MB limit. Please upload smaller files.";
                    header("Location: ../HTML CODES/dashboard-pct.php#submit-report");
                    exit();
                }
                
                // Check if it's an actual image
                $check = getimagesize($_FILES['photos']['tmp_name'][$key]);
                if ($check === false) {
                    $_SESSION['report_error'] = "One of the files is not a valid image.";
                    header("Location: ../HTML CODES/dashboard-pct.php#submit-report");
                    exit();
                }
                
                // Allowed file formats
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $_SESSION['report_error'] = "Only JPG, JPEG, PNG and GIF files are allowed.";
                    header("Location: ../HTML CODES/dashboard-pct.php#submit-report");
                    exit();
                }
                
                // Move the file to the uploads directory
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$key], $targetFile)) {
                    $photos[] = $filename;
                    error_log("Uploaded photo: $filename");
                } else {
                    error_log("Failed to move uploaded file from {$_FILES['photos']['tmp_name'][$key]} to $targetFile");
                    $_SESSION['report_error'] = "There was an error uploading one of your files.";
                    header("Location: ../HTML CODES/dashboard-pct.php#submit-report");
                    exit();
                }
            }
        }
    }
    
    // Convert photos array to JSON for database storage
    $photosJson = !empty($photos) ? json_encode($photos) : null;
    error_log("Photos JSON: " . ($photosJson ?? 'null'));
    
    // Check if a rejected report already exists for this technician and appointment
    $stmt = $db->prepare("SELECT report_id FROM service_reports WHERE technician_id = ? AND appointment_id = ? AND status = 'rejected'");
    $stmt->execute([$technician_id, $appointment_id]);
    $existingReport = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingReport) {
        // Update the existing rejected report
        $updateStmt = $db->prepare("
            UPDATE service_reports 
            SET date_of_treatment = :date_of_treatment, time_in = :time_in, time_out = :time_out,
                treatment_type = :treatment_type, treatment_method = :treatment_method, pest_count = :pest_count,
                device_installation = :device_installation, consumed_chemicals = :consumed_chemicals,
                frequency_of_visits = :frequency_of_visits, photos = :photos, location = :location,
                account_name = :account_name, contact_no = :contact_no, status = 'pending', updated_at = NOW()
            WHERE report_id = :report_id
        ");
        $updateStmt->bindParam(':date_of_treatment', $date_of_treatment);
        $updateStmt->bindParam(':time_in', $time_in);
        $updateStmt->bindParam(':time_out', $time_out);
        $updateStmt->bindParam(':treatment_type', $treatment_type);
        $updateStmt->bindParam(':treatment_method', $treatment_method);
        $updateStmt->bindParam(':pest_count', $pest_count);
        $updateStmt->bindParam(':device_installation', $device_installation);
        $updateStmt->bindParam(':consumed_chemicals', $consumed_chemicals);
        $updateStmt->bindParam(':frequency_of_visits', $frequency_of_visits);
        $updateStmt->bindParam(':photos', $photosJson);
        $updateStmt->bindParam(':location', $location);
        $updateStmt->bindParam(':account_name', $account_name);
        $updateStmt->bindParam(':contact_no', $contact_no);
        $updateStmt->bindParam(':report_id', $existingReport['report_id']);

        if ($updateStmt->execute()) {
            error_log("Report updated successfully, ID: " . $existingReport['report_id']);
            $_SESSION['report_success'] = "Service report #" . $existingReport['report_id'] . " updated successfully! Your report is pending review by admin.";
        } else {
            error_log("Error executing update statement: " . implode(", ", $updateStmt->errorInfo()));
            $_SESSION['report_error'] = "Error updating report. Please try again.";
        }
    } else {
        // Insert into service_reports table
        $sql = "INSERT INTO service_reports (
                    technician_id, appointment_id, date_of_treatment, time_in, time_out,
                    treatment_type, treatment_method, pest_count, device_installation,
                    consumed_chemicals, frequency_of_visits, photos, location,
                    account_name, contact_no, status, created_at, updated_at
                ) VALUES (
                    :technician_id, :appointment_id, :date_of_treatment, :time_in, :time_out,
                    :treatment_type, :treatment_method, :pest_count, :device_installation,
                    :consumed_chemicals, :frequency_of_visits, :photos, :location,
                    :account_name, :contact_no, 'pending', NOW(), NOW()
                )";
        
        $stmt = $db->prepare($sql);
        
        $stmt->bindParam(':technician_id', $technician_id);
        $stmt->bindParam(':appointment_id', $appointment_id);
        $stmt->bindParam(':date_of_treatment', $date_of_treatment);
        $stmt->bindParam(':time_in', $time_in);
        $stmt->bindParam(':time_out', $time_out);
        $stmt->bindParam(':treatment_type', $treatment_type);
        $stmt->bindParam(':treatment_method', $treatment_method);
        $stmt->bindParam(':pest_count', $pest_count);
        $stmt->bindParam(':device_installation', $device_installation);
        $stmt->bindParam(':consumed_chemicals', $consumed_chemicals);
        $stmt->bindParam(':frequency_of_visits', $frequency_of_visits);
        $stmt->bindParam(':photos', $photosJson);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':account_name', $account_name);
        $stmt->bindParam(':contact_no', $contact_no);
        
        if ($stmt->execute()) {
            $report_id = $db->lastInsertId();
            error_log("Report saved successfully, ID: $report_id");
            $_SESSION['report_success'] = "Service report #$report_id submitted successfully! Your report is pending review by admin.";
        } else {
            error_log("Error executing insert statement: " . implode(", ", $stmt->errorInfo()));
            $_SESSION['report_error'] = "Error submitting report. Please try again.";
        }
    }
    
    // Redirect back to dashboard
    header("Location: ../HTML CODES/dashboard-pct.php#submit-report");
    exit();
    
} catch (PDOException $e) {
    error_log("Database Error submitting report: " . $e->getMessage());
    $_SESSION['report_error'] = "Database error: " . $e->getMessage();
    header("Location: ../HTML CODES/dashboard-pct.php#submit-report");
    exit();
} catch (Exception $e) {
    error_log("General error in submit_report.php: " . $e->getMessage());
    $_SESSION['report_error'] = "An error occurred: " . $e->getMessage();
    header("Location: ../HTML CODES/dashboard-pct.php#submit-report");
    exit();
}
?>