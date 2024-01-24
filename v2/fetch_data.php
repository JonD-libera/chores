<?php
header('Content-Type: application/json');

$data = [
    "1" => "Content related to Button 1",
    "2" => "Content related to Button 2",
    "3" => "Content related to Button 3",
    "System" => "Content related to System"
];

if (isset($_GET['buttonLabel'])) {
    $buttonLabel = $_GET['buttonLabel'];
    if (isset($data[$buttonLabel])) {
        echo json_encode(["content" => $data[$buttonLabel]]);
        exit;
    }
}

echo json_encode(["content" => "No content available"]);
