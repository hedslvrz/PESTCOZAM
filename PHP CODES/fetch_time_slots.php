<?php
session_start();
require_once '../database.php';

// Check if a date was provided
if (!isset($_GET['date'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No date provided']);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

$date = $_GET['date'];

// Default slot order
$slotOrder = [
    'morning_slot_1' => 1,    // 07:00 AM - 09:00 AM
    'morning_slot_2' => 2,    // 09:00 AM - 11:00 AM
    'afternoon_slot_1' => 3,  // 11:00 AM - 01:00 PM
    'afternoon_slot_2' => 4,  // 01:00 PM - 03:00 PM
    'evening_slot' => 5,     // 03:00 PM - 05:00 PM
];

// Define DEFAULT slot configuration - these will be used if not set by admin
$defaultTimeSlots = [
    'morning_slot_1' => [
        'name' => 'morning_slot_1',
        'slot_name' => 'morning_slot_1',
        'time_range' => '07:00 AM - 09:00 AM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['morning_slot_1']
    ],
    'morning_slot_2' => [
        'name' => 'morning_slot_2',
        'slot_name' => 'morning_slot_2',
        'time_range' => '09:00 AM - 11:00 AM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['morning_slot_2']
    ],
    'afternoon_slot_1' => [
        'name' => 'afternoon_slot_1',
        'slot_name' => 'afternoon_slot_1',
        'time_range' => '11:00 AM - 01:00 PM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['afternoon_slot_1']
    ],
    'afternoon_slot_2' => [
        'name' => 'afternoon_slot_2',
        'slot_name' => 'afternoon_slot_2',
        'time_range' => '01:00 PM - 03:00 PM',
        'slot_limit' => 3,
        'available_slots' => 3,
        'order' => $slotOrder['afternoon_slot_2']
    ],
    'evening_slot' => [
        'name' => 'evening_slot',
        'slot_name' => 'evening_slot',
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
    
    // Start with the default time slots
    $timeSlots = $defaultTimeSlots;
    
    // If we have configured slots, override the defaults
    if (!empty($configuredTimeSlots)) {
        foreach ($configuredTimeSlots as $slot) {
            if (isset($timeSlots[$slot['slot_name']])) {
                // Override default values with configured values
                $timeSlots[$slot['slot_name']]['slot_limit'] = (int)$slot['slot_limit'];
                $timeSlots[$slot['slot_name']]['time_range'] = $slot['time_range'];
                // Set the order for sorting
                $timeSlots[$slot['slot_name']]['order'] = $slotOrder[$slot['slot_name']] ?? 999;
            }
        }
    }
    
    // For each time slot, count existing appointments to determine availability
    foreach ($timeSlots as $slotName => &$slotData) {
        // Extract start time from time range (e.g. "07:00 AM" from "07:00 AM - 09:00 AM")
        $timeRange = $slotData['time_range'];
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
            $slotData['available_slots'] = max(0, $slotData['slot_limit'] - $appointmentCount);
        }
    }
    
    // Sort time slots by order
    uasort($timeSlots, function($a, $b) {
        return $a['order'] <=> $b['order'];
    });
    
    // Format the data for the frontend
    $formattedSlots = [];
    foreach ($timeSlots as $slot) {
        $formattedSlots[] = [
            'slot_name' => $slot['slot_name'],
            'time_range' => $slot['time_range'],
            'slot_limit' => (int)$slot['slot_limit'],
            'available_slots' => (int)$slot['available_slots'],
            'is_available' => (int)$slot['available_slots'] > 0,
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'time_slots' => $formattedSlots,
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving time slots: ' . $e->getMessage()
    ]);
}
?>
