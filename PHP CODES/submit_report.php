<?php
session_start();
require_once '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $account_name = $_POST['account_name'];
    $location = $_POST['location'];
    $contact_no = $_POST['contact_no'];
    $date_of_treatment = $_POST['date_of_treatment'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];
    $treatment_type = $_POST['treatment_type'];
    $treatment_method = $_POST['treatment_method'];
    $pest_count = $_POST['pest_count'];
    $device_installation = $_POST['device_installation'];
    $consumed_chemicals = $_POST['consumed_chemicals'];
    $frequency_of_visits = $_POST['frequency_of_visits'];

    // Handle photo uploads
    $uploadedPhotos = [];
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = '../uploads/';
        foreach ($_FILES['photos']['name'] as $key => $photoName) {
            $photoTmpName = $_FILES['photos']['tmp_name'][$key];
            $photoPath = $uploadDir . basename($photoName);
            if (move_uploaded_file($photoTmpName, $photoPath)) {
                $uploadedPhotos[] = $photoPath;
            }
        }
    }

    $photos = implode(',', $uploadedPhotos);

    try {
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("INSERT INTO service_reports (
            appointment_id, account_name, location, contact_no, date_of_treatment, time_in, time_out, 
            treatment_type, treatment_method, pest_count, device_installation, consumed_chemicals, 
            frequency_of_visits, photos
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $appointment_id, $account_name, $location, $contact_no, $date_of_treatment, $time_in, $time_out,
            $treatment_type, $treatment_method, $pest_count, $device_installation, $consumed_chemicals,
            $frequency_of_visits, $photos
        ]);

        $_SESSION['success'] = 'Service report submitted successfully!';
        header('Location: ../HTML CODES/dashboard-pct.php');
    } catch (PDOException $e) {
        error_log("Error submitting report: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to submit the service report.';
        header('Location: ../HTML CODES/dashboard-pct.php');
    }
} else {
    header('Location: ../HTML CODES/dashboard-pct.php');
}   