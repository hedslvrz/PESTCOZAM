<?php
session_start();
require_once '../database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../HTML CODES/login.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get selected dates
    $selected_dates_json = $_POST['selected_dates'] ?? '[]';
    $selected_dates = json_decode($selected_dates_json, true);
    
    if (empty($selected_dates)) {
        $_SESSION['timeslot_error'] = "No dates selected";
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
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['timeslot_success'] = "Time slot configuration saved successfully";
    } catch (Exception $e) {
        // Roll back transaction
        $db->rollBack();
        $_SESSION['timeslot_error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ../HTML CODES/dashboard-admin.php");
    exit();
}

// If not POST method, redirect
header("Location: ../HTML CODES/dashboard-admin.php");
exit();
?>
