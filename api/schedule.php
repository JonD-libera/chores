<?php
require dirname(__DIR__, 1) . '/bootstrap.php'; // Adjust the path as necessary
header('Content-Type: application/json');

// Authenticate the request
//authenticate();

// Get the requested action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'create':
        createSchedule($pdo);
        break;
    case 'list':
        getSchedules($pdo);
        break;
    case 'read':
        getSchedule($pdo);
        break;
    case 'update':
        updateSchedule($pdo);
        break;
    case 'delete':
        deleteSchedule($pdo);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
function getSchedules($pdo) {
    $familyId = isset($_GET['familyId']) ? (int)$_GET['familyId'] : 0;
    if ($familyId === 0) {
        echo json_encode(['error' => 'Invalid family ID']);
        return;
    }
    $stmt = $pdo->prepare("SELECT id, description, repeat_start_days, repeat_interval_days FROM schedule");
    $stmt->execute();
    $schedules = $stmt->fetchAll();
    echo json_encode($schedules);
}
function getSchedule($pdo) {
    $scheduleId = isset($_GET['scheduleId']) ? (int)$_GET['scheduleId'] : 0;
    if ($scheduleId === 0) {
        echo json_encode(['error' => 'Invalid schedule ID']);
        return;
    }
    $stmt = $pdo->prepare("SELECT id, description, repeat_start_days, repeat_interval_days FROM schedule WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch();
    echo json_encode($schedule);
}
function createSchedule($pdo) {
    $familyId = isset($_POST['familyId']) ? (int)$_POST['familyId'] : 0;
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $repeatStartDays = isset($_POST['repeatStartDays']) ? (int)$_POST['repeatStartDays'] : 0;
    $repeatIntervalDays = isset($_POST['repeatIntervalDays']) ? (int)$_POST['repeatIntervalDays'] : 0;
    if ($familyId === 0 || $description === '' || $repeatStartDays === 0 || $repeatIntervalDays === 0) {
        echo json_encode(['error' => 'Invalid input, required values familyId, description, repeatStartDays, repeatIntervalDays']);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO schedule (familyId, description, repeat_start_days, repeat_interval_days) VALUES (?, ?, ?, ?)");
    $stmt->execute([$familyId, $description, $repeatStartDays, $repeatIntervalDays]);
    echo json_encode(['success' => 'Schedule created']);
}
function updateSchedule($pdo) {
    $scheduleId = isset($_POST['scheduleId']) ? (int)$_POST['scheduleId'] : 0;
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $repeatStartDays = isset($_POST['repeatStartDays']) ? (int)$_POST['repeatStartDays'] : 0;
    $repeatIntervalDays = isset($_POST['repeatIntervalDays']) ? (int)$_POST['repeatIntervalDays'] : 0;
    if ($scheduleId === 0 || $description === '' || $repeatStartDays === 0 || $repeatIntervalDays === 0) {
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    $stmt = $pdo->prepare("UPDATE schedule SET description = ?, repeat_start_days = ?, repeat_interval_days = ? WHERE id = ?");
    $stmt->execute([$description, $repeatStartDays, $repeatIntervalDays, $scheduleId]);
    echo json_encode(['success' => 'Schedule updated']);
}
function deleteSchedule($pdo) {
    $scheduleId = isset($_GET['scheduleId']) ? (int)$_GET['scheduleId'] : 0;
    if ($scheduleId === 0) {
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ?");
    $stmt->execute([$scheduleId]);
    echo json_encode(['success' => 'Schedule deleted']);
}
?>
