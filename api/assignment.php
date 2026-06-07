<?php
require dirname(__DIR__, 1) . '/bootstrap.php';
header('Content-Type: application/json');

// Authenticate the request
//authenticate();

// Get the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createAssignment($pdo);
        break;
    case 'list':
        getAssignments($pdo);
        break;
    case 'read':
        getAssignment($pdo);
        break;
    case 'delete':
        deleteAssignment($pdo);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function createAssignment($pdo) {
    $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;
    $choreId = isset($_GET['choreId']) ? (int)$_GET['choreId'] : 0;
    $scheduleId = isset($_GET['scheduleId']) ? (int)$_GET['scheduleId'] : 0;

    if ($userId === 0 || $choreId === 0 || $scheduleId === 0) {
        echo json_encode(['error' => 'Invalid input - userId, choreId, and scheduleId are required']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO assignments (assigned_user, chore_id, schedule_id) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $choreId, $scheduleId]);
    echo json_encode(['message' => 'Assignment created', 'id' => $pdo->lastInsertId()]);
}

function getAssignments($pdo) {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.assigned_user,
            a.chore_id,
            a.schedule_id,
            u.realname as user_name,
            c.name as chore_name,
            s.description as schedule_desc,
            s.repeat_interval_days
        FROM assignments a
        LEFT JOIN users u ON a.assigned_user = u.id
        LEFT JOIN chores c ON a.chore_id = c.id
        LEFT JOIN schedule s ON a.schedule_id = s.id
        ORDER BY u.realname, c.name
    ");
    $stmt->execute();
    $assignments = $stmt->fetchAll();
    echo json_encode($assignments);
}

function getAssignment($pdo) {
    $assignmentId = isset($_GET['assignmentId']) ? (int)$_GET['assignmentId'] : 0;

    if ($assignmentId === 0) {
        echo json_encode(['error' => 'Invalid assignment ID']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.assigned_user,
            a.chore_id,
            a.schedule_id,
            u.realname as user_name,
            c.name as chore_name,
            s.description as schedule_desc
        FROM assignments a
        LEFT JOIN users u ON a.assigned_user = u.id
        LEFT JOIN chores c ON a.chore_id = c.id
        LEFT JOIN schedule s ON a.schedule_id = s.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch();
    echo json_encode($assignment);
}

function deleteAssignment($pdo) {
    $assignmentId = isset($_GET['assignmentId']) ? (int)$_GET['assignmentId'] : 0;

    if ($assignmentId === 0) {
        echo json_encode(['error' => 'Invalid assignment ID']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$assignmentId]);
    echo json_encode(['message' => 'Assignment deleted']);
}
?>
