<?php
require dirname(__DIR__, 1) . '/bootstrap.php'; // Adjust the path as necessary
header('Content-Type: application/json');

// Authenticate the request
//authenticate();

// Get the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createChore($pdo);
        break;
    case 'read':
        getChore($pdo);
        break;
    case 'list':
        getChores($pdo);
        break;        
    case 'update':
        updateChore($pdo);
        break;
    case 'delete':
        deleteChore($pdo);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
function createChore($pdo) {
    $name = isset($_GET['name']) ? $_GET['name'] : '';
    $description = isset($_GET['description']) ? $_GET['description'] : '';
    $pay = isset($_GET['pay']) ? (float)$_GET['pay'] : 0;
    $required = isset($_GET['required']) ? (int)$_GET['required'] : 0;
    $expected = isset($_GET['expected']) ? (int)$_GET['expected'] : 0;
    $max = isset($_GET['max']) ? (int)$_GET['max'] : 0;
    if ($name === '' || $description === '' || $pay === 0 || $required === 0 || $expected === 0 || $max === 0) {
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO chores (name, description, pay, required, expected, max) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $pay, $required, $expected, $max]);
    echo json_encode(['message' => 'Chore created']);
}
function getChore($pdo) {
    $choreId = isset($_GET['choreId']) ? (int)$_GET['choreId'] : 0;
    if ($choreId === 0) {
        echo json_encode(['error' => 'Invalid chore ID']);
        return;
    }
    $stmt = $pdo->prepare("SELECT id, name, description, pay, required, expected, max FROM chores WHERE id = ?");
    $stmt->execute([$choreId]);
    $chore = $stmt->fetch();
    echo json_encode($chore);
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
function updateChore($pdo) {
    $choreId = isset($_GET['choreId']) ? (int)$_GET['choreId'] : 0;
    $name = isset($_GET['name']) ? $_GET['name'] : '';
    $description = isset($_GET['description']) ? $_GET['description'] : '';
    $pay = isset($_GET['pay']) ? (float)$_GET['pay'] : 0;
    $required = isset($_GET['required']) ? (int)$_GET['required'] : 0;
    $expected = isset($_GET['expected']) ? (int)$_GET['expected'] : 0;
    $max = isset($_GET['max']) ? (int)$_GET['max'] : 0;
    if ($choreId === 0 || $name === '' || $description === '' || $pay === 0 || $required === 0 || $expected === 0 || $max === 0) {
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    $stmt = $pdo->prepare("UPDATE chores SET name = ?, description = ?, pay = ?, required = ?, expected = ?, max = ? WHERE id = ?");
    $stmt->execute([$name, $description, $pay, $required, $expected, $max, $choreId]);
    echo json_encode(['message' => 'Chore updated']);
}
function deleteChore($pdo) {
    $choreId = isset($_GET['choreId']) ? (int)$_GET['choreId'] : 0;
    if ($choreId === 0) {
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM chores WHERE id = ?");
    $stmt->execute([$choreId]);
    echo json_encode(['message' => 'Chore deleted']);
}