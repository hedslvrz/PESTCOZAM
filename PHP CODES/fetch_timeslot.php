<?php
session_start();
require_once '../database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if date parameter is provided
if (!isset($_GET['date']) || empty($_GET['date'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Date parameter is required']);
    exit();
}

$date = $_GET['date'];

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid date format']);
    exit();
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch time slots for the selected date
    $stmt = $db->prepare("SELECT slot_name, time_range, slot_limit, available_slots FROM time_slots WHERE date = ?");
    $stmt->execute([$date]);
    $timeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'date' => $date,
        'slots' => $timeSlots
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
