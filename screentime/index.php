<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require dirname(__DIR__) . '/config.php';

$success = '';
$error = '';

// Handle deletion if a "delete" query parameter is provided.
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    if ($deleteId > 0) {
        $clear = $pdo->prepare('UPDATE screen_time_targets SET allowed_until = NULL WHERE id = :id');
        if ($clear->execute([':id' => $deleteId])) {
            $success = 'Screen time setting has been cleared.';
        } else {
            $error = 'Unable to clear screen time.';
        }
    }
}

// Handle POST submission for adding/updating screen time.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = isset($_POST['targetId']) ? intval($_POST['targetId']) : 0;
    $allowedUntil = isset($_POST['allowedUntil']) ? trim($_POST['allowedUntil']) : '';
    $quickMinutes = isset($_POST['quickMinutes']) ? intval($_POST['quickMinutes']) : 0;

    if ($targetId <= 0 || ($allowedUntil === '' && $quickMinutes <= 0)) {
        $error = 'Select a target and provide a time.';
    } else {
        if ($quickMinutes > 0) {
            $date = new DateTime();
            $date->modify('+' . $quickMinutes . ' minutes');
        } else {
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $allowedUntil);
        }

        if (!$date) {
            $error = 'Invalid date/time format.';
        } else {
            $allowedUntilFormatted = $date->format('Y-m-d H:i:s');
            $update = $pdo->prepare('UPDATE screen_time_targets SET allowed_until = :allowed_until WHERE id = :id');
            if ($update->execute([':allowed_until' => $allowedUntilFormatted, ':id' => $targetId])) {
                $success = 'Screen time updated until ' . htmlspecialchars($allowedUntilFormatted) . '.';
            } else {
                $error = 'Unable to update screen time.';
            }
        }
    }
}

// Load recent screen time settings for display (only include probes within 1 week).
$recentTargets = array();
$recentStmt = $pdo->prepare(
    'SELECT id, username, hostname, allowed_until, last_probe_at '
    . 'FROM screen_time_targets '
    . 'WHERE last_probe_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) '
    . 'ORDER BY last_probe_at DESC'
);
if ($recentStmt->execute()) {
    $recentTargets = $recentStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Screen Time Management</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Screen Time Management</h2>
        <?php if ($success !== ''): ?>
            <p style="color:green;"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p style="color:red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="post" action="" id="grantForm">
            <label for="targetId">User and Machine:</label>
            <select name="targetId" id="targetId" required>
                <option value="">--Select Recent Probe--</option>
                <?php foreach ($recentTargets as $target): ?>
                    <option value="<?php echo htmlspecialchars($target['id']); ?>"><?php echo htmlspecialchars($target['username'] . ' @ ' . $target['hostname']); ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <label for="allowedUntil">Allowed Until (Select Date/Time):</label>
            <input type="datetime-local" name="allowedUntil" id="allowedUntil" required /><br>
            <input type="hidden" name="quickMinutes" id="quickMinutes" value="120" />
            <div class="quick-buttons">
                <button type="button" data-minutes="15">15 min</button>
                <button type="button" data-minutes="30">30 min</button>
                <button type="button" data-minutes="60">60 min</button>
                <button type="button" data-minutes="120">120 min</button>
                <button type="button" data-minutes="180">180 min</button>
            </div>
            <input type="submit" value="Set Screen Time" />
        </form>

        <h3>Current Screen Time Settings</h3>
        <?php if (!empty($recentTargets)): ?>
            <table>
                <tr>
                    <th>Username</th>
                    <th>Hostname</th>
                    <th>Allowed Until</th>
                    <th>Last Probe</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($recentTargets as $target): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($target['username']); ?></td>
                        <td><?php echo htmlspecialchars($target['hostname']); ?></td>
                        <td><?php echo htmlspecialchars($target['allowed_until']); ?></td>
                        <td><?php echo htmlspecialchars($target['last_probe_at']); ?></td>
                        <td>
                            <a class="delete-link" href="index.php?delete=<?php echo urlencode($target['id']); ?>" onclick="return confirm('Are you sure you want to clear screen time for <?php echo htmlspecialchars($target['username']); ?>?');">Clear</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No recent probes available.</p>
        <?php endif; ?>
        <p><a href="logout.php">Logout</a></p>
    </div>
    <script>
        (function () {
            var buttons = document.querySelectorAll('.quick-buttons button');
            var quickMinutesInput = document.getElementById('quickMinutes');
            var form = document.getElementById('grantForm');
            var allowedUntilInput = document.getElementById('allowedUntil');

            function setDateMinutes(minutes) {
                var now = new Date();
                now.setMinutes(now.getMinutes() + minutes);
                var iso = new Date(now.getTime() - (now.getTimezoneOffset() * 60000))
                    .toISOString()
                    .slice(0, 16);
                allowedUntilInput.value = iso;
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var minutes = parseInt(button.getAttribute('data-minutes'), 10);
                    if (!minutes) {
                        return;
                    }
                    quickMinutesInput.value = minutes;
                    setDateMinutes(minutes);
                    form.submit();
                });
            });

            if (!allowedUntilInput.value) {
                setDateMinutes(120);
            }

            allowedUntilInput.addEventListener('input', function () {
                quickMinutesInput.value = 0;
            });
        })();
    </script>
</body>
</html>
