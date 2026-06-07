<?php
// api.php

header('Content-Type: application/json');

// Simulated database or user time tracking logic
function getRemainingTimeForUser($username)
{
    // Simulate different remaining times for demonstration purposes
    $users = [
        'user1' => 45, // 45 minutes remaining
        'surve' => 10, // 10 minutes remaining
        'user3' => 0   // Time is up
    ];

    return $users[$username] ?? null; // Return null if user is not found
}

// Get the username from the URL
$username = isset($_GET['username']) ? $_GET['username'] : null;

if ($username) {
    $remainingTime = getRemainingTimeForUser($username);

    if ($remainingTime !== null) {
        echo json_encode([
            'username' => $username,
            'RemainingTimeMinutes' => $remainingTime
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'User not found'
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'error' => 'Username is required'
    ]);
}
