<?php
header('Content-Type: application/json');

require dirname(__DIR__) . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? trim($_GET['endpoint']) : '';
if ($endpoint === '') {
    $endpoint = 'probe';
}

switch ($endpoint) {
    case 'probe':
        handleProbe($pdo, $method);
        break;
    case 'grant':
        handleGrant($pdo, $method);
        break;
    case 'recent':
        handleRecent($pdo, $method);
        break;
    case 'status':
        handleStatus($pdo, $method);
        break;
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint not found',
            'message' => 'Available endpoints: probe, grant, recent, status'
        ]);
        break;
}

function readInputData() {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (stripos($contentType, 'application/json') !== false) {
        $payload = json_decode(file_get_contents('php://input'), true);
        return is_array($payload) ? $payload : [];
    }

    return $_POST;
}

function handleProbe($pdo, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $username = isset($_GET['username']) ? trim($_GET['username']) : '';
    $hostname = isset($_GET['hostname']) ? trim($_GET['hostname']) : '';

    if ($username === '' || $hostname === '') {
        echo json_encode(['success' => false, 'error' => 'Missing username or hostname.']);
        return;
    }

    try {
        $upsert = $pdo->prepare(
            'INSERT INTO screen_time_targets (username, hostname, last_probe_at, created_at, updated_at) '
            . 'VALUES (:username, :hostname, NOW(), NOW(), NOW()) '
            . 'ON DUPLICATE KEY UPDATE last_probe_at = VALUES(last_probe_at), updated_at = NOW()'
        );
        $upsert->execute([
            ':username' => $username,
            ':hostname' => $hostname
        ]);

        $fetch = $pdo->prepare(
            'SELECT id, allowed_until, last_probe_at FROM screen_time_targets WHERE username = :username AND hostname = :hostname'
        );
        $fetch->execute([
            ':username' => $username,
            ':hostname' => $hostname
        ]);
        $row = $fetch->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'error' => 'Screen time target not found.']);
            return;
        }

        $remaining = 0;
        $allowedUntilValue = null;
        if (!empty($row['allowed_until'])) {
            $allowedUntil = new DateTime($row['allowed_until']);
            $now = new DateTime();
            $remaining = $allowedUntil->getTimestamp() - $now->getTimestamp();
            if ($remaining < 0) {
                $remaining = 0;
            }
            $allowedUntilValue = $allowedUntil->format('Y-m-d H:i:s');
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'target_id' => intval($row['id']),
                'username' => $username,
                'hostname' => $hostname,
                'allowed_until' => $allowedUntilValue,
                'remaining_seconds' => $remaining,
                'last_probe_at' => $row['last_probe_at']
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}

function handleGrant($pdo, $method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $data = readInputData();
    $targetId = isset($data['target_id']) ? intval($data['target_id']) : 0;
    $allowedUntil = isset($data['allowed_until']) ? trim($data['allowed_until']) : '';
    $minutes = isset($data['minutes']) ? intval($data['minutes']) : 0;

    if ($targetId <= 0 || ($allowedUntil === '' && $minutes <= 0)) {
        echo json_encode(['success' => false, 'error' => 'Missing target_id and allowed_until or minutes.']);
        return;
    }

    if ($minutes > 0) {
        $date = new DateTime();
        $date->modify('+' . $minutes . ' minutes');
        $allowedUntil = $date->format('Y-m-d H:i:s');
    } else {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $allowedUntil);
        if (!$date) {
            echo json_encode(['success' => false, 'error' => 'Invalid allowed_until format. Use Y-m-d H:i:s.']);
            return;
        }
        $allowedUntil = $date->format('Y-m-d H:i:s');
    }

    try {
        $update = $pdo->prepare('UPDATE screen_time_targets SET allowed_until = :allowed_until WHERE id = :id');
        $update->execute([
            ':allowed_until' => $allowedUntil,
            ':id' => $targetId
        ]);

        echo json_encode([
            'success' => true,
            'data' => [
                'target_id' => $targetId,
                'allowed_until' => $allowedUntil
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}

function handleRecent($pdo, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
    if ($days <= 0) {
        $days = 7;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT id, username, hostname, allowed_until, last_probe_at '
            . 'FROM screen_time_targets '
            . 'WHERE last_probe_at >= DATE_SUB(NOW(), INTERVAL :days DAY) '
            . 'ORDER BY last_probe_at DESC'
        );
        $statement->bindValue(':days', $days, PDO::PARAM_INT);
        $statement->execute();

        echo json_encode([
            'success' => true,
            'data' => $statement->fetchAll()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}

function handleStatus($pdo, $method) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $targetId = isset($_GET['target_id']) ? intval($_GET['target_id']) : 0;
    $username = isset($_GET['username']) ? trim($_GET['username']) : '';
    $hostname = isset($_GET['hostname']) ? trim($_GET['hostname']) : '';

    if ($targetId <= 0 && ($username === '' || $hostname === '')) {
        echo json_encode(['success' => false, 'error' => 'Missing target_id or username and hostname.']);
        return;
    }

    try {
        if ($targetId > 0) {
            $fetch = $pdo->prepare(
                'SELECT id, username, hostname, allowed_until, last_probe_at FROM screen_time_targets WHERE id = :id'
            );
            $fetch->execute([':id' => $targetId]);
        } else {
            $fetch = $pdo->prepare(
                'SELECT id, username, hostname, allowed_until, last_probe_at FROM screen_time_targets '
                . 'WHERE username = :username AND hostname = :hostname'
            );
            $fetch->execute([':username' => $username, ':hostname' => $hostname]);
        }

        $row = $fetch->fetch();
        if (!$row) {
            echo json_encode(['success' => false, 'error' => 'Screen time target not found.']);
            return;
        }

        $remaining = 0;
        $allowedUntilValue = null;
        if (!empty($row['allowed_until'])) {
            $allowedUntil = new DateTime($row['allowed_until']);
            $now = new DateTime();
            $remaining = $allowedUntil->getTimestamp() - $now->getTimestamp();
            if ($remaining < 0) {
                $remaining = 0;
            }
            $allowedUntilValue = $allowedUntil->format('Y-m-d H:i:s');
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'target_id' => intval($row['id']),
                'username' => $row['username'],
                'hostname' => $row['hostname'],
                'allowed_until' => $allowedUntilValue,
                'remaining_seconds' => $remaining,
                'last_probe_at' => $row['last_probe_at']
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}
?>
