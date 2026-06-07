<?php
include_once ("./config.php");
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  exit();
}

// Build report for next 7 days
$report = [];
$userColors = ['#4a8f4a', '#3a6ea5', '#b85450', '#9a6fb0', '#c89050', '#50a5a5', '#a55050'];
$colorIndex = 0;
$userColorMap = [];

for ($i = 0; $i < 7; $i++) {
  $date = date('Y-m-d', strtotime("+$i days"));
  $dayName = date('l, M j', strtotime("+$i days"));
  $daysFromEpoch = "TO_DAYS(DATE_ADD(CURDATE(), INTERVAL $i DAY))";

  $query = "SELECT u.realname, u.id as user_id, c.name, c.description, c.pay, a.id as assignment_id
            FROM assignments a
            JOIN users u ON a.assigned_user = u.id
            JOIN chores c ON a.chore_id = c.id
            JOIN schedule s ON a.schedule_id = s.id
            WHERE (($daysFromEpoch - repeat_start_days) % repeat_interval_days = 0)
            AND c.type = 1
            ORDER BY u.realname, c.name";

  $result = $mysqli->query($query);
  $dayChores = [];

  if ($result) {
    while ($row = $result->fetch_assoc()) {
      if (!isset($userColorMap[$row['user_id']])) {
        $userColorMap[$row['user_id']] = $userColors[$colorIndex % count($userColors)];
        $colorIndex++;
      }
      $row['color'] = $userColorMap[$row['user_id']];
      $dayChores[] = $row;
    }
  }

  $report[] = [
    'date' => $date,
    'dayName' => $dayName,
    'chores' => $dayChores
  ];
}

// Calculate totals by user
$userTotals = [];
foreach ($report as $day) {
  foreach ($day['chores'] as $chore) {
    if (!isset($userTotals[$chore['realname']])) {
      $userTotals[$chore['realname']] = [
        'count' => 0,
        'total_pay' => 0,
        'color' => $chore['color']
      ];
    }
    $userTotals[$chore['realname']]['count']++;
    $userTotals[$chore['realname']]['total_pay'] += $chore['pay'];
  }
}
?>
<html>
  <head>
    <link rel="stylesheet" href="style.css">
    <title>7-Day Chore Report</title>
    <style>
      body {
        background-color: #0a0a0a;
        color: #88cc88;
        font-family: Arial, sans-serif;
        padding: 20px;
      }
      h1 {
        color: #66bb66;
        border-bottom: 2px solid #4a8f4a;
        padding-bottom: 10px;
      }
      h2 {
        color: #66bb66;
      }
      .nav-buttons {
        margin-bottom: 20px;
      }
      .btn {
        background-color: #3a6ea5;
        color: white;
        padding: 10px 20px;
        border: 1px solid #4a7eb5;
        cursor: pointer;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        margin-right: 10px;
      }
      .btn:hover {
        filter: brightness(1.1);
      }
      .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
      }
      .summary-card {
        background-color: #1a1a1a;
        border-left: 4px solid;
        padding: 15px;
        border-radius: 5px;
      }
      .summary-card h3 {
        margin: 0 0 10px 0;
        color: #ccffcc;
        font-size: 16px;
      }
      .summary-card .stat {
        font-size: 14px;
        color: #88cc88;
      }
      .day-section {
        background-color: #1a1a1a;
        margin-bottom: 20px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #2a2a2a;
      }
      .day-header {
        background-color: #2d5f2d;
        color: #ccffcc;
        padding: 15px 20px;
        font-size: 18px;
        font-weight: bold;
      }
      .day-header.today {
        background-color: #4a8f4a;
      }
      .day-content {
        padding: 15px 20px;
      }
      .no-chores {
        color: #666;
        font-style: italic;
        padding: 10px 0;
      }
      .user-group {
        margin-bottom: 15px;
        padding: 10px;
        background-color: #0f0f0f;
        border-radius: 5px;
        border-left: 4px solid;
      }
      .user-name {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 8px;
        color: #ccffcc;
      }
      .chore-item {
        padding: 8px 12px;
        margin: 5px 0;
        background-color: #1a1a1a;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .chore-name {
        color: #88cc88;
        font-weight: 500;
      }
      .chore-pay {
        color: #66bb66;
        font-weight: bold;
        background-color: #0a0a0a;
        padding: 4px 10px;
        border-radius: 3px;
      }
      .total-section {
        background-color: #1a1a1a;
        padding: 20px;
        border-radius: 8px;
        margin-top: 30px;
        border: 2px solid #4a8f4a;
      }
      .total-section h2 {
        margin-top: 0;
      }
      @media print {
        body {
          background-color: white;
          color: black;
        }
        .nav-buttons {
          display: none;
        }
      }
    </style>
  </head>
  <body>
    <div class="nav-buttons">
      <a href="admin.php" class="btn">Back to Admin</a>
      <a href="javascript:window.print()" class="btn">Print Report</a>
    </div>

    <h1>7-Day Chore Schedule Report</h1>
    <p style="color: #888;">Generated: <?php echo date('l, F j, Y \a\t g:i A'); ?></p>

    <!-- Summary Cards -->
    <div class="summary-cards">
      <?php foreach ($userTotals as $userName => $data): ?>
      <div class="summary-card" style="border-color: <?php echo $data['color']; ?>">
        <h3><?php echo htmlspecialchars($userName); ?></h3>
        <div class="stat"><?php echo $data['count']; ?> chores</div>
        <div class="stat">$<?php echo number_format($data['total_pay'], 2); ?> potential earnings</div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Daily Breakdown -->
    <?php foreach ($report as $index => $day): ?>
    <div class="day-section">
      <div class="day-header <?php echo $index === 0 ? 'today' : ''; ?>">
        <?php echo $day['dayName']; ?>
        <?php if ($index === 0) echo ' (Today)'; ?>
      </div>
      <div class="day-content">
        <?php if (empty($day['chores'])): ?>
          <div class="no-chores">No scheduled chores</div>
        <?php else: ?>
          <?php
          // Group by user
          $byUser = [];
          foreach ($day['chores'] as $chore) {
            $byUser[$chore['realname']][] = $chore;
          }

          foreach ($byUser as $userName => $userChores):
            $userColor = $userChores[0]['color'];
          ?>
          <div class="user-group" style="border-color: <?php echo $userColor; ?>">
            <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
            <?php foreach ($userChores as $chore): ?>
            <div class="chore-item">
              <div>
                <span class="chore-name"><?php echo htmlspecialchars($chore['name']); ?></span>
                <?php if ($chore['description']): ?>
                <br><small style="color: #666;"><?php echo htmlspecialchars($chore['description']); ?></small>
                <?php endif; ?>
              </div>
              <div class="chore-pay">$<?php echo number_format($chore['pay'], 2); ?></div>
            </div>
            <?php endforeach; ?>
            <div style="text-align: right; margin-top: 8px; color: #888; font-size: 14px;">
              Total: $<?php echo number_format(array_sum(array_column($userChores, 'pay')), 2); ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Weekly Totals -->
    <div class="total-section">
      <h2>Weekly Summary</h2>
      <?php
      $grandTotal = 0;
      $totalChores = 0;
      foreach ($userTotals as $userName => $data):
        $grandTotal += $data['total_pay'];
        $totalChores += $data['count'];
      ?>
      <div style="padding: 8px; margin: 5px 0; background-color: #0f0f0f; border-radius: 4px;">
        <span style="color: #ccffcc; font-weight: bold;"><?php echo htmlspecialchars($userName); ?>:</span>
        <span style="color: #88cc88;"><?php echo $data['count']; ?> chores</span>
        <span style="color: #66bb66; float: right; font-weight: bold;">$<?php echo number_format($data['total_pay'], 2); ?></span>
      </div>
      <?php endforeach; ?>
      <div style="border-top: 2px solid #4a8f4a; margin-top: 15px; padding-top: 15px; font-size: 18px;">
        <strong style="color: #ccffcc;">Grand Total:</strong>
        <span style="color: #88cc88;"><?php echo $totalChores; ?> chores</span>
        <span style="color: #66bb66; float: right; font-weight: bold; font-size: 20px;">$<?php echo number_format($grandTotal, 2); ?></span>
      </div>
    </div>
  </body>
</html>
