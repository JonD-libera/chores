<?php
require dirname(__DIR__, 1) . '/bootstrap.php'; // Adjust the path as necessary
header('Content-Type: application/json');

// Authenticate the request
//authenticate();

// Get the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getUserChores':
        getUserChores($pdo);
        break;
    // Other cases...
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getUserChores($pdo) {
    $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;
    $familyId = isset($_GET['familyId']) ? (int)$_GET['familyId'] : 0;
    if ($userId ===0 || $customerId === 0) {
        echo json_encode(['error' => 'Invalid user or customer ID']);
        return;
    }
    $stmt = $pdo->prepare("
        SELECT 
            u.realname, 
            c.name, 
            c.description, 
            a.id, 
            CASE 
                WHEN (SELECT COUNT(1) FROM activity act WHERE act.assignment_id = a.id AND act.date = DATE(NOW()) AND act.user_id = a.assigned_user) > 0 THEN 'completebutton'
                WHEN (SELECT COUNT(1) FROM requests r WHERE r.assignment_id = a.id AND DATE(r.date_requested) = DATE(NOW()) AND r.user_id = u.id) > 0 THEN 'pendingbutton'
                ELSE 'incompletebutton' 
            END AS button_status, 
            c.pay
        FROM 
            assignments a 
            LEFT JOIN users u ON a.assigned_user = u.id 
            JOIN chores c ON a.chore_id = c.id 
            JOIN schedule s ON a.schedule_id = s.id 
        WHERE 
            (( TO_DAYS(CURDATE()) - repeat_start_days) % repeat_interval_days = 0) 
            AND (a.assigned_user = ? OR a.assigned_user IS NULL)
    ");
    
    $stmt->execute([$userId]);
    $chores = $stmt->fetchAll();
    echo json_encode($chores);
}
?>
