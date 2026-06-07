<?php
// Include database configuration
require 'config.php'; // Ensure this contains your $mysqli connection

header('Content-Type: application/json');
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  exit();
}


// SQL query to fetch incomplete chores
$sql = "
    SELECT 
        CONCAT(u.realname, ' has not completed ', c.name) AS message
    FROM 
        assignments a
    JOIN 
        users u ON a.assigned_user = u.id
    JOIN 
        chores c ON a.chore_id = c.id
    JOIN 
        schedule s ON a.schedule_id = s.id
    WHERE 
        ((TO_DAYS(CURDATE()) - repeat_start_days) % repeat_interval_days = 0)
        AND c.type = 1
        AND (
            SELECT COUNT(1) 
            FROM activity act 
            WHERE act.assignment_id = a.id 
                AND act.date = DATE(NOW()) 
                AND act.user_id = a.assigned_user
        ) = 0
        AND (
            SELECT COUNT(1) 
            FROM requests r 
            WHERE r.assignment_id = a.id 
                AND DATE(r.date_requested) = DATE(NOW()) 
                AND r.user_id = u.id
        ) = 0
";

// Execute the query
if ($result = $mysqli->query($sql)) {
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row['message'];
    }

    // Prepare Alexa-compatible response
    $speechText = empty($messages)
        ? "There are no incomplete chores."
        : implode(". ", $messages) . ".";

    $response = [
        'version' => '1.0',
        'response' => [
            'outputSpeech' => [
                'type' => 'PlainText',
                'text' => $speechText,
            ],
            'shouldEndSession' => true,
        ],
    ];

    // Output the response
    echo json_encode($response);
} else {
    // Handle query errors
    $response = [
        'version' => '1.0',
        'response' => [
            'outputSpeech' => [
                'type' => 'PlainText',
                'text' => 'An error occurred while fetching the chores.',
            ],
            'shouldEndSession' => true,
        ],
    ];
    echo json_encode($response);
}

// Close the database connection
$mysqli->close();
