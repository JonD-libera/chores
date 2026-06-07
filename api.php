<?php
/**
 * Chores API for Android Application
 * Version: 1.0
 * 
 * This API provides endpoints for managing chores, users, and activities
 * All responses are in JSON format
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include_once(dirname(__FILE__)."/config.php");

// Database connection
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'message' => $mysqli->connect_error
    ]);
    exit();
}

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Route the request
switch ($endpoint) {
    case 'users':
        handleUsers($mysqli, $method);
        break;
    case 'user_chores':
        handleUserChores($mysqli, $method);
        break;
    case 'all_chores':
        handleAllChores($mysqli, $method);
        break;
    case 'chore_detail':
        handleChoreDetail($mysqli, $method);
        break;
    case 'submit_chore':
        handleSubmitChore($mysqli, $method, $emailuser, $emailpass, $emailfrom, $emailto, $emailreply);
        break;
    case 'approve_chore':
        handleApproveChore($mysqli, $method);
        break;
    case 'verify_pin':
        handleVerifyPin($mysqli, $method);
        break;
    case 'bonus':
        handleBonus($mysqli, $method);
        break;
    case 'activity_history':
        handleActivityHistory($mysqli, $method);
        break;
    case 'user_balance':
        handleUserBalance($mysqli, $method);
        break;
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint not found',
            'message' => 'Available endpoints: users, user_chores, all_chores, chore_detail, submit_chore, approve_chore, verify_pin, bonus, activity_history, user_balance'
        ]);
        break;
}

$mysqli->close();

/**
 * Get list of all users (excluding admin types)
 */
function handleUsers($mysqli, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $statement = $mysqli->prepare("SELECT id, realname, type FROM users WHERE type != 1 ORDER BY realname");
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($id, $realname, $type);
        
        $users = [];
        while ($statement->fetch()) {
            $users[] = [
                'id' => $id,
                'name' => $realname,
                'type' => $type
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Get chores for a specific user
 * Required parameter: user_id
 */
function handleUserChores($mysqli, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing user_id parameter']);
        return;
    }

    $userId = intval($_GET['user_id']);
    
    $statement = $mysqli->prepare("SELECT u.realname, c.name, c.description, a.id, 
        CASE 
            WHEN (SELECT COUNT(1) FROM activity act WHERE act.assignment_id = a.id AND act.date = DATE(NOW()) AND act.user_id = a.assigned_user) > 0 THEN 'complete'
            WHEN (SELECT COUNT(1) FROM requests r WHERE r.assignment_id = a.id AND DATE(r.date_requested) = DATE(NOW()) AND r.user_id = u.id) > 0 THEN 'pending'
            ELSE 'incomplete'
        END as status,
        c.pay,
        c.max,
        c.id as chore_id
        FROM assignments a 
        LEFT JOIN users u ON a.assigned_user = u.id 
        JOIN chores c ON a.chore_id = c.id 
        JOIN schedule s ON a.schedule_id = s.id 
        WHERE ((TO_DAYS(CURDATE()) - repeat_start_days) % repeat_interval_days = 0) 
        AND (a.assigned_user = ? OR a.assigned_user IS NULL)");
    
    $statement->bind_param('i', $userId);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($username, $choreName, $description, $assignmentId, $status, $pay, $max, $choreId);
        
        $chores = [];
        while ($statement->fetch()) {
            $chores[] = [
                'user_name' => $username,
                'chore_name' => $choreName,
                'description' => $description,
                'assignment_id' => $assignmentId,
                'chore_id' => $choreId,
                'status' => $status,
                'pay' => $pay,
                'max_quantity' => $max
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chores
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Get all chores for today across all users
 */
function handleAllChores($mysqli, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $statement = $mysqli->prepare("SELECT u.realname, u.id, c.name, c.description, a.id, 
        CASE 
            WHEN (SELECT COUNT(1) FROM activity act WHERE act.assignment_id = a.id AND act.date = DATE(NOW()) AND act.user_id = a.assigned_user) > 0 THEN 'complete'
            WHEN (SELECT COUNT(1) FROM requests r WHERE r.assignment_id = a.id AND DATE(r.date_requested) = DATE(NOW()) AND r.user_id = u.id) > 0 THEN 'pending'
            ELSE 'incomplete'
        END as status,
        (SELECT SUM(quantity) FROM activity act WHERE act.assignment_id = a.id AND act.date = DATE(NOW()) AND act.user_id = a.assigned_user) as quantity,
        c.pay,
        c.max,
        c.id as chore_id
        FROM assignments a 
        JOIN users u ON a.assigned_user = u.id 
        JOIN chores c ON a.chore_id = c.id 
        JOIN schedule s ON a.schedule_id = s.id 
        WHERE ((TO_DAYS(CURDATE()) - repeat_start_days) % repeat_interval_days = 0) 
        AND c.type = 1 
        ORDER BY u.id");
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($username, $userId, $choreName, $description, $assignmentId, $status, $quantity, $pay, $max, $choreId);
        
        $chores = [];
        while ($statement->fetch()) {
            $chores[] = [
                'user_id' => $userId,
                'user_name' => $username,
                'chore_name' => $choreName,
                'description' => $description,
                'assignment_id' => $assignmentId,
                'chore_id' => $choreId,
                'status' => $status,
                'quantity' => $quantity ? intval($quantity) : 0,
                'pay' => $pay,
                'max_quantity' => $max
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chores
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Get detailed information about a specific chore
 * Required parameters: assignment_id, user_id
 */
function handleChoreDetail($mysqli, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    if (!isset($_GET['assignment_id']) || !isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing assignment_id or user_id parameter']);
        return;
    }

    $assignmentId = intval($_GET['assignment_id']);
    $userId = intval($_GET['user_id']);
    
    $statement = $mysqli->prepare("SELECT c.name, c.description, c.pay, a.id, a.assigned_user, c.max, c.id as chore_id 
        FROM chores c 
        JOIN assignments a ON a.chore_id = c.id 
        LEFT JOIN users u ON a.assigned_user = u.id 
        WHERE (a.assigned_user = ? OR a.assigned_user IS NULL) AND a.id = ?");
    
    $statement->bind_param('ii', $userId, $assignmentId);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($choreName, $description, $pay, $assignmentId, $assignedUser, $max, $choreId);
        
        if ($statement->fetch()) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'chore_name' => $choreName,
                    'description' => $description,
                    'pay' => $pay,
                    'assignment_id' => $assignmentId,
                    'chore_id' => $choreId,
                    'assigned_user' => $assignedUser,
                    'max_quantity' => $max
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Chore not found'
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Submit a chore for approval (sends email notification)
 * Required POST parameters: assignment_id, user_id, count
 */
function handleSubmitChore($mysqli, $method, $emailuser, $emailpass, $emailfrom, $emailto, $emailreply) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['assignment_id']) || !isset($data['user_id']) || !isset($data['count'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters: assignment_id, user_id, count']);
        return;
    }

    $assignmentId = intval($data['assignment_id']);
    $userId = intval($data['user_id']);
    $count = intval($data['count']);

    // Get chore details
    $statement = $mysqli->prepare("SELECT u.realname, c.name, a.id, (c.pay * ?), c.pay 
        FROM assignments a 
        JOIN chores c ON c.id = a.chore_id 
        JOIN users u ON a.assigned_user = u.id 
        WHERE a.id = ?");
    $statement->bind_param('ii', $count, $assignmentId);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($username, $chorename, $assignment, $pay, $payrate);
        
        if ($statement->num_rows > 0) {
            $statement->fetch();
            
            // Insert request
            $statement2 = $mysqli->prepare("INSERT INTO requests (date_requested, assignment_id, count, approval_status, user_id) VALUES (NOW(), ?, ?, 0, ?)");
            $statement2->bind_param('iii', $assignmentId, $count, $userId);
            
            if ($statement2->execute()) {
                // Send email notification (optional - requires PHPMailer)
                // Email code omitted for simplicity in API
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Chore submitted for approval',
                    'data' => [
                        'username' => $username,
                        'chore_name' => $chorename,
                        'count' => $count,
                        'total_pay' => $pay
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to submit request',
                    'message' => $statement2->error
                ]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Assignment not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Approve a chore with PIN verification
 * Required POST parameters: assignment_id, user_id, approver_id, pin, count
 */
function handleApproveChore($mysqli, $method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['assignment_id']) || !isset($data['user_id']) || !isset($data['approver_id']) || !isset($data['pin']) || !isset($data['count'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters: assignment_id, user_id, approver_id, pin, count']);
        return;
    }

    $assignmentId = intval($data['assignment_id']);
    $userId = intval($data['user_id']);
    $approverId = intval($data['approver_id']);
    $pin = $data['pin'];
    $count = intval($data['count']);

    // Verify PIN
    $statement = $mysqli->prepare("SELECT u.realname, u.id, u.pin FROM users u WHERE u.id = ?");
    $statement->bind_param('i', $approverId);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($approvername, $approver, $code);
        
        if ($statement->num_rows > 0) {
            $statement->fetch();
            
            if ($code == $pin) {
                // Get chore pay information
                $statement2 = $mysqli->prepare("SELECT a.id, (c.pay * ?), c.pay 
                    FROM assignments a 
                    JOIN chores c ON c.id = a.chore_id 
                    WHERE a.id = ?");
                $statement2->bind_param('ii', $count, $assignmentId);
                
                if ($statement2->execute()) {
                    $statement2->store_result();
                    $statement2->bind_result($assignment, $pay, $payrate);
                    
                    if ($statement2->num_rows > 0) {
                        $statement2->fetch();
                        
                        // Insert activity
                        $statement3 = $mysqli->prepare("INSERT INTO activity (date, timestamp, assignment_id, user_id, payrate, quantity, approval_status) VALUES (CURDATE(), NOW(), ?, ?, ?, ?, 1)");
                        $statement3->bind_param('iidi', $assignmentId, $userId, $payrate, $count);
                        $statement3->execute();
                        
                        // Update request status
                        $statement4 = $mysqli->prepare("INSERT INTO requests (date_requested, assignment_id, user_id, count, approval_status) VALUES (NOW(), ?, ?, ?, 1)");
                        $statement4->bind_param('iii', $assignmentId, $userId, $count);
                        $statement4->execute();
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Chore approved successfully',
                            'data' => [
                                'approved_by' => $approvername,
                                'total_pay' => $pay,
                                'payrate' => $payrate,
                                'quantity' => $count
                            ]
                        ]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Assignment not found']);
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Database query failed']);
                }
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Approver not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Verify a user's PIN
 * Required POST parameters: user_id, pin
 */
function handleVerifyPin($mysqli, $method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['pin'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters: user_id, pin']);
        return;
    }

    $userId = intval($data['user_id']);
    $pin = $data['pin'];

    $statement = $mysqli->prepare("SELECT realname, pin FROM users WHERE id = ?");
    $statement->bind_param('i', $userId);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($realname, $storedPin);
        
        if ($statement->fetch()) {
            if ($storedPin == $pin) {
                echo json_encode([
                    'success' => true,
                    'message' => 'PIN verified',
                    'data' => ['user_name' => $realname]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Submit a bonus activity
 * Required POST parameters: user_id, approver_id, pin, count
 */
function handleBonus($mysqli, $method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['approver_id']) || !isset($data['pin']) || !isset($data['count'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters: user_id, approver_id, pin, count']);
        return;
    }

    $userId = intval($data['user_id']);
    $approverId = intval($data['approver_id']);
    $pin = $data['pin'];
    $count = intval($data['count']);

    // Verify PIN
    $statement = $mysqli->prepare("SELECT u.realname, u.id, u.pin FROM users u WHERE u.id = ?");
    $statement->bind_param('i', $approverId);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($approvername, $approver, $code);
        
        if ($statement->num_rows > 0) {
            $statement->fetch();
            
            if ($code == $pin) {
                $payrate = 0.50;
                $pay = $count * $payrate;
                $assignmentId = 0; // Bonus has no assignment
                
                // Insert bonus activity
                $statement2 = $mysqli->prepare("INSERT INTO activity (date, timestamp, assignment_id, user_id, payrate, quantity, approval_status) VALUES (CURDATE(), NOW(), ?, ?, ?, ?, 1)");
                $statement2->bind_param('iidi', $assignmentId, $userId, $payrate, $count);
                
                if ($statement2->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Bonus approved successfully',
                        'data' => [
                            'approved_by' => $approvername,
                            'total_pay' => $pay,
                            'payrate' => $payrate,
                            'quantity' => $count
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to insert bonus',
                        'message' => $statement2->error
                    ]);
                }
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Approver not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Get activity history for a user
 * Required parameter: user_id
 * Optional parameters: start_date, end_date, limit
 */
function handleActivityHistory($mysqli, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing user_id parameter']);
        return;
    }

    $userId = intval($_GET['user_id']);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    $query = "SELECT a.date, a.timestamp, c.name as chore_name, a.payrate, a.quantity, (a.payrate * a.quantity) as total_pay 
        FROM activity a 
        LEFT JOIN assignments ass ON a.assignment_id = ass.id 
        LEFT JOIN chores c ON ass.chore_id = c.id 
        WHERE a.user_id = ? AND a.approval_status = 1 
        ORDER BY a.timestamp DESC 
        LIMIT ?";
    
    $statement = $mysqli->prepare($query);
    $statement->bind_param('ii', $userId, $limit);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($date, $timestamp, $choreName, $payrate, $quantity, $totalPay);
        
        $activities = [];
        while ($statement->fetch()) {
            $activities[] = [
                'date' => $date,
                'timestamp' => $timestamp,
                'chore_name' => $choreName ? $choreName : 'Bonus',
                'payrate' => $payrate,
                'quantity' => $quantity,
                'total_pay' => $totalPay
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $activities
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}

/**
 * Get user's total balance
 * Required parameter: user_id
 * Optional parameters: start_date, end_date
 */
function handleUserBalance($mysqli, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing user_id parameter']);
        return;
    }

    $userId = intval($_GET['user_id']);
    
    // Get total earnings
    $statement = $mysqli->prepare("SELECT COALESCE(SUM(payrate * quantity), 0) as total 
        FROM activity 
        WHERE user_id = ? AND approval_status = 1");
    $statement->bind_param('i', $userId);
    
    if ($statement->execute()) {
        $statement->store_result();
        $statement->bind_result($total);
        $statement->fetch();
        
        // Get user name
        $statement2 = $mysqli->prepare("SELECT realname FROM users WHERE id = ?");
        $statement2->bind_param('i', $userId);
        $statement2->execute();
        $statement2->store_result();
        $statement2->bind_result($username);
        $statement2->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'user_name' => $username,
                'total_balance' => floatval($total)
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed',
            'message' => $statement->error
        ]);
    }
}
?>
