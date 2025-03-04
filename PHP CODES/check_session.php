<?php
session_start();

$response = [
    'loggedIn' => false,
    'profilePic' => null
];

if (isset($_SESSION['user_id'])) {
    $response['loggedIn'] = true;
    // If you have a profile picture stored in the session or need to fetch it from database
    $response['profilePic'] = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../Pictures/default-profile.png';
}

header('Content-Type: application/json');
echo json_encode($response);
