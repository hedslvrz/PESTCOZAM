<?php
session_start();
require_once '../database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../HTML CODES/login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get selected dates
    $selected_dates = [];
    if (isset($_POST['selected_dates']) && !empty($_POST['selected_dates'])) {
        $selected_dates = json_decode($_POST['selected_dates'], true);
        
        if (!is_array($selected_dates)) {
            $_SESSION['timeslot_error'] = "Invalid date format";
            header("Location: ../HTML CODES/dashboard-admin.php");
            exit();
        }
    }
    
    // If no dates selected, show error
    if (empty($selected_dates)) {
        $_SESSION['timeslot_error'] = "Please select at least one date";
        header("Location: ../HTML CODES/dashboard-admin.php");
        exit();
    }
    
    // Get time slot limits
    $time_slots = $_POST['time_slots'] ?? [];
    $time_ranges = $_POST['time_ranges'] ?? [];
    
    if (empty($time_slots) || empty($time_ranges)) {
        $_SESSION['timeslot_error'] = "Time slot data is missing";
        header("Location: ../HTML CODES/dashboard-admin.php");
        exit();
    }
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // For each selected date, insert or update time slots
        foreach ($selected_dates as $date) {
            // Validate date format (YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue; // Skip invalid dates
            }
            
            foreach ($time_slots as $slot_key => $slot_limit) {
                $slot_name = $slot_key; // e.g., "morning_slot_1"
                $time_range = $time_ranges[$slot_key] ?? ''; // e.g., "07:00 AM - 09:00 AM"
                $available_slots = intval($slot_limit); // Convert to integer
                
                // Check if record exists for this date and slot
                $stmt = $db->prepare("SELECT id FROM time_slots WHERE date = ? AND slot_name = ?");
                $stmt->execute([$date, $slot_name]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing record
                    $slotId = $stmt->fetchColumn();
                    $updateStmt = $db->prepare("
                        UPDATE time_slots 
                        SET slot_limit = ?, available_slots = ?, time_range = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$slot_limit, $available_slots, $time_range, $slotId]);
                } else {
                    // Insert new record
                    $insertStmt = $db->prepare("
                        INSERT INTO time_slots 
                        (date, slot_name, time_range, slot_limit, available_slots, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $insertStmt->execute([$date, $slot_name, $time_range, $slot_limit, $available_slots]);
                }
            }
        }
        
        // Commit the transaction
        $db->commit();
        
        // Set success message
        $_SESSION['timeslot_success'] = "Time slots have been updated successfully";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        
        // Set error message
        $_SESSION['timeslot_error'] = "Database error: " . $e->getMessage();
        
        // Log the error
        error_log("Time Slot Error: " . $e->getMessage());
    }
    
    // Redirect back to dashboard
    header("Location: ../HTML CODES/dashboard-admin.php");
    exit();
} else {
    // If not POST request, redirect to dashboard
    header("Location: ../HTML CODES/dashboard-admin.php");
    exit();
}
