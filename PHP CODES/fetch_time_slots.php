<?php
session_start();
require_once "../database.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if date is provided
if (!isset($_GET['date']) || empty($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Date parameter is required']);
    exit();
}

$date = $_GET['date'];
$database = new Database();
$db = $database->getConnection();

// Define slot order for consistent sorting
$slotOrder = [
    'morning_slot_1' => 1,  // 07:00 AM - 09:00 AM
    'morning_slot_2' => 2,  // 09:00 AM - 11:00 AM
    'afternoon_slot_1' => 3, // 11:00 AM - 01:00 PM
    'afternoon_slot_2' => 4, // 01:00 PM - 03:00 PM
    'evening_slot' => 5,     // 03:00 PM - 05:00 PM
];

// Standard time slots if not configured in database
$defaultTimeSlots = [
    'morning_slot_1' => [
        'name' => 'morning_slot_1',
        'time_range' => '07:00 AM - 09:00 AM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['morning_slot_1']
    ],
    'morning_slot_2' => [
        'name' => 'morning_slot_2',
        'time_range' => '09:00 AM - 11:00 AM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['morning_slot_2']
    ],
    'afternoon_slot_1' => [
        'name' => 'afternoon_slot_1',
        'time_range' => '11:00 AM - 01:00 PM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['afternoon_slot_1']
    ],
    'afternoon_slot_2' => [
        'name' => 'afternoon_slot_2',
        'time_range' => '01:00 PM - 03:00 PM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['afternoon_slot_2']
    ],
    'evening_slot' => [
        'name' => 'evening_slot',
        'time_range' => '03:00 PM - 05:00 PM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['evening_slot']
    ],
];

try {
    // First, check if the date has configured time slots
    $query = "SELECT * FROM time_slots WHERE date = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$date]);
    $configuredTimeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array by slot_name
    $timeSlots = [];
    foreach ($configuredTimeSlots as $slot) {
        $slot['order'] = $slotOrder[$slot['slot_name']] ?? 999; // Default high order if unknown
        $timeSlots[$slot['slot_name']] = $slot;
    }
    
    // If no configured slots for this date, use defaults
    if (empty($timeSlots)) {
        $timeSlots = $defaultTimeSlots;
    }
    
    // For each time slot, count existing appointments to determine availability
    foreach ($timeSlots as $slotName => &$slotData) {
        // Extract start time from time range (e.g. "07:00 AM" from "07:00 AM - 09:00 AM")
        $timeRange = $slotData['time_range'] ?? $defaultTimeSlots[$slotName]['time_range'];
        $startTime = explode(' - ', $timeRange)[0];
        
        // Convert to 24-hour format for database comparison
        $format = 'h:i A';
        $startDateTime = DateTime::createFromFormat($format, $startTime);
        $dbTimeFormat = $startDateTime ? $startDateTime->format('H:i:00') : null;
        
        if ($dbTimeFormat) {
            // Count appointments at this date/time
            $appointmentQuery = "SELECT COUNT(*) as count FROM appointments 
                                WHERE appointment_date = ? 
                                AND appointment_time = ?";
            $appointmentStmt = $db->prepare($appointmentQuery);
            $appointmentStmt->execute([$date, $dbTimeFormat]);
            $appointmentCount = $appointmentStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Calculate available slots
            $slotLimit = $slotData['slot_limit'] ?? $defaultTimeSlots[$slotName]['slot_limit'];
            $slotData['available_slots'] = max(0, $slotLimit - $appointmentCount);
            $slotData['is_available'] = $slotData['available_slots'] > 0;
            
            // Make sure slot_limit is explicitly included in the response
            $slotData['slot_limit'] = $slotLimit;
        } else {
            // Fallback if time parsing fails
            $slotData['available_slots'] = $slotData['slot_limit'] ?? $defaultTimeSlots[$slotName]['slot_limit'];
            $slotData['is_available'] = true;
        }
    }
    
    // Convert to array and sort by order
    $sortedSlots = array_values($timeSlots);
    usort($sortedSlots, function($a, $b) {
        return ($a['order'] ?? 999) - ($b['order'] ?? 999);
    });
    
    // Return slots with availability info
    echo json_encode([
        'success' => true, 
        'date' => $date,
        'time_slots' => $sortedSlots
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit();
}
?>
