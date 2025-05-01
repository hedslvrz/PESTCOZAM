<?php
// API endpoint to generate a preview of follow-up visits based on plan parameters

// Set headers for JSON response
header('Content-Type: application/json');

// Get JSON data from POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate required inputs
if (!isset($data['plan_type'], $data['frequency'], $data['duration'], $data['start_date'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$plan_type = $data['plan_type'];
$frequency = intval($data['frequency']);
$duration = intval($data['duration']);
$start_date = $data['start_date'];

// Validate inputs
if (!in_array($plan_type, ['weekly', 'monthly', 'quarterly', 'yearly'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plan type']);
    exit;
}

if ($frequency < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Frequency must be at least 1']);
    exit;
}

if ($duration < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Duration must be at least 1 month']);
    exit;
}

// Default time for visits
$default_time = '09:00:00';

// Generate visit dates
$visits = [];
$start = new DateTime($start_date);
$visit_number = 1;
$interval = null;

// Set interval based on plan type and frequency
switch ($plan_type) {
    case 'weekly':
        $interval = new DateInterval('P' . $frequency . 'W');
        $total_visits = ceil($duration * 4.33 / $frequency); // Approx 4.33 weeks per month
        break;
        
    case 'monthly':
        $interval = new DateInterval('P' . $frequency . 'M');
        $total_visits = ceil($duration / $frequency);
        break;
        
    case 'quarterly':
        $interval = new DateInterval('P3M'); // 3 months
        $total_visits = ceil($duration / 3);
        break;
        
    case 'yearly':
        $interval = new DateInterval('P1Y');
        $total_visits = ceil($duration / 12);
        break;
}

$current_date = clone $start;

// Generate visit dates
for ($i = 0; $i < $total_visits; $i++) {
    if ($i > 0) { // Skip first iteration to use start_date as first visit
        $current_date->add($interval);
    }
    
    $visits[] = [
        'date' => $current_date->format('Y-m-d'),
        'time' => $default_time,
        'visit_number' => $visit_number++
    ];
}

// Return the generated visits as JSON
echo json_encode($visits);
