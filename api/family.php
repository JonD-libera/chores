<?php
require dirname(__DIR__, 1) . '/bootstrap.php'; // Adjust the path as necessary
header('Content-Type: application/json');

// Authenticate the request
//authenticate();

// Get the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getUsers':
        getUsers($pdo);
        break;
    case 'getSchedules':
        getSchedules($pdo);
        break;
    case 'getChores':
        getChores($pdo);
        break;
    case 'getEvents':
        getEvents($pdo);
        break;
    case 'createChore':
        createChore($pdo);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
function getUsers($pdo) {
    $familyId = isset($_GET['familyId']) ? (int)$_GET['familyId'] : 0;
    if ($familyId === 0) {
        echo json_encode(['error' => 'Invalid family ID']);
        return;
    }
    $stmt = $pdo->prepare("SELECT id, realname FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    echo json_encode($users);
}
function getChores($pdo) {
    $familyId = isset($_GET['familyId']) ? (int)$_GET['familyId'] : 0;
    if ($familyId === 0) {
        echo json_encode(['error' => 'Invalid family ID']);
        return;
    }
    $stmt = $pdo->prepare("SELECT id, name, description, pay, required, expected, max FROM chores");
    $stmt->execute();
    $chores = $stmt->fetchAll();
    echo json_encode($chores);
}
function getEvents($pdo) {
    $familyId = isset($_GET['familyId']) ? (int)$_GET['familyId'] : 0;
    if ($familyId === 0) {
        echo json_encode(['error' => 'Invalid family ID']);
        return;
    }
    $stmt = $pdo->prepare("SELECT id, description, date FROM events");
    $stmt->execute();
    $events = $stmt->fetchAll();
    echo json_encode($events);
}
function createSchedule($pdo) {
    $familyId = isset($_GET['familyId']) ? (int)$_GET['familyId'] : 0;
    $description = isset($_GET['description']) ? $_GET['description'] : '';
    $repeatStartDays = isset($_GET['repeatStartDays']) ? (int)$_GET['repeatStartDays'] : 0;
    $repeatIntervalDays = isset($_GET['repeatIntervalDays']) ? (int)$_GET['repeatIntervalDays'] : 0;
    if ($familyId === 0 || $description === '' || $repeatStartDays === 0 || $repeatIntervalDays === 0) {
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO schedule (description, repeat_start_days, repeat_interval_days) VALUES (?, ?, ?)");
    $stmt->execute([$description, $repeatStartDays, $repeatIntervalDays]);
    echo json_encode(['success' => 'Schedule created']);
}
function createChore($pdo) {
    $familyId = isset($_GET['familyId']) ? (int)$_GET['familyId'] : 0;
    $name = isset($_GET['name']) ? $_GET['name'] : '';
    $description = isset($_GET['description']) ? $_GET['description'] : '';
    $pay = isset($_GET['pay']) ? (float)$_GET['pay'] : 0;
    $required = isset($_GET['required']) ? (int)$_GET['required'] : 0;
    $expected = isset($_GET['expected']) ? (int)$_GET['expected'] : 0;
    $max = isset($_GET['max']) ? (int)$_GET['max'] : 0;
    if ($familyId === 0 || $name === '' || $description === '' || $pay === 0 || $required === 0 || $expected === 0 || $max === 0) {
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO chores (name, description, pay, required, expected, max) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $pay, $required, $expected, $max]);
    echo json_encode(['success' => 'Chore created']);
}
?>
