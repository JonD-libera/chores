<?php
require_once 'vendor/autoload.php';

session_start();

// Set up Google Client
$client = new Google_Client();
$client->setClientId('808433765512-g40d7iikr8si6vvldbpl68273smf184q.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://chores.jdsnetwork.com/frontend/');
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    // Authenticate the code received from Google
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['access_token'] = $token;
    $client->setAccessToken($token);

    // Get user info from Google
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    $_SESSION['google_id'] = $userInfo->id;
    $_SESSION['google_email'] = $userInfo->email;
    $_SESSION['google_name'] = $userInfo->name;
}

if (!isset($_SESSION['access_token'])) {
    $authUrl = $client->createAuthUrl();
    echo "<a href='" . htmlspecialchars($authUrl) . "'>Log in with Google</a>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If the form is submitted, handle the submission
    $realname = $_POST['realname'];
    $pin = $_POST['pin'];
    $password = $_POST['password'];
    
    // Create an array with the POST data
    $data = array(
        "realname" => $realname,
        "pin" => $pin,
        "password" => $password,
        "type" => 2 // Default type for new user
    );

    // Convert the array to JSON
    $data_json = json_encode($data);

    // Set up the cURL request to the endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://chores.home.jdsnetwork.com/apiv2/api/auth/google");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_SESSION['access_token']['access_token']
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // Display the response
        $response_data = json_decode($response, true);
        if (isset($response_data['success'])) {
            echo "<p>User created successfully. User ID: " . htmlspecialchars($response_data['id']) . "</p>";
        } else {
            echo "<p>Error: " . htmlspecialchars($response_data['error'] ?? 'Unknown error') . "</p>";
        }
    }

    // Close the cURL session
    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
</head>
<body>
    <h1>Create a New User</h1>
    <?php if (isset($_SESSION['access_token'])): ?>
        <form method="POST" action="">
            <label for="realname">Real Name:</label><br>
            <input type="text" id="realname" name="realname" required><br><br>
            
            <label for="pin">PIN:</label><br>
            <input type="text" id="pin" name="pin" required><br><br>
            
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>
            
            <button type="submit">Create User</button>
        </form>
    <?php else: ?>
        <p>Please log in with Google to create a user.</p>
    <?php endif; ?>
</body>
</html>
