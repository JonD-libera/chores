<?php
include_once ("./config.php");
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Handle approval submissions
  if (isset($_REQUEST['code']) && isset($_REQUEST['req'])) {
    $statement = $mysqli->prepare("select u.realname, u.id, u.pin from users u where u.id = 1");
    if ($statement->execute()) {
      $statement->store_result();
      $statement->bind_result($approvername, $approver, $code);
      if ($statement->num_rows > 0) {
        $statement->fetch();
        if ($code == $_REQUEST['code']) {
          $statement = $mysqli->prepare("insert into activity (date, timestamp, assignment_id, user_id, payrate, quantity) select DATE(r.date_requested), r.date_requested, r.assignment_id, r.user_id, c.pay, r.count from requests r join assignments a on r.assignment_id = a.id join chores c on a.chore_id = c.id where r.id = ?");
          foreach($_REQUEST['req'] as $key => $val) {
            if ($val == "approve") {
              echo "Approving $key<br>";
              $mysqli->query("update requests set approval_status = 1 where id = ".$key);
              $statement->bind_param('i',$key);
              $statement->execute();
            } elseif ($val == "deny") {
              echo "Denying $key<br>";
              $mysqli->query("update requests set approval_status = 2 where id = ".$key);
            }
          }
        } else {
          echo "<p>Code invalid</p>";
        }
      }
    }
  }

  // Handle chore add/edit
  if (isset($_POST['chore_action'])) {
    if ($_POST['chore_action'] == 'add') {
      $stmt = $mysqli->prepare("INSERT INTO chores (name, description, pay, required, expected, max, type) VALUES (?, ?, ?, ?, ?, ?, 1)");
      $stmt->bind_param('ssdiid', $_POST['name'], $_POST['description'], $_POST['pay'], $_POST['required'], $_POST['expected'], $_POST['max']);
      $stmt->execute();
      echo "<p style='color: green;'>Chore added successfully!</p>";
    } elseif ($_POST['chore_action'] == 'edit') {
      $stmt = $mysqli->prepare("UPDATE chores SET name = ?, description = ?, pay = ?, required = ?, expected = ?, max = ? WHERE id = ?");
      $stmt->bind_param('ssdiidi', $_POST['name'], $_POST['description'], $_POST['pay'], $_POST['required'], $_POST['expected'], $_POST['max'], $_POST['chore_id']);
      $stmt->execute();
      echo "<p style='color: green;'>Chore updated successfully!</p>";
    } elseif ($_POST['chore_action'] == 'delete') {
      $stmt = $mysqli->prepare("DELETE FROM chores WHERE id = ?");
      $stmt->bind_param('i', $_POST['chore_id']);
      $stmt->execute();
      echo "<p style='color: green;'>Chore deleted successfully!</p>";
    }
  }

  // Handle assignment add/delete
  if (isset($_POST['assignment_action'])) {
    if ($_POST['assignment_action'] == 'add') {
      $stmt = $mysqli->prepare("INSERT INTO assignments (assigned_user, chore_id, schedule_id, assignment_type) VALUES (?, ?, ?, 1)");
      $stmt->bind_param('iii', $_POST['user_id'], $_POST['chore_id'], $_POST['schedule_id']);
      $stmt->execute();
      echo "<p style='color: green;'>Assignment created successfully!</p>";
    } elseif ($_POST['assignment_action'] == 'delete') {
      $stmt = $mysqli->prepare("DELETE FROM assignments WHERE id = ?");
      $stmt->bind_param('i', $_POST['assignment_id']);
      $stmt->execute();
      echo "<p style='color: green;'>Assignment deleted successfully!</p>";
    }
  }

  // Handle schedule add/edit
  if (isset($_POST['schedule_action'])) {
    if ($_POST['schedule_action'] == 'add') {
      $stmt = $mysqli->prepare("INSERT INTO schedule (description, repeat_start_days, repeat_interval_days) VALUES (?, ?, ?)");
      $stmt->bind_param('sii', $_POST['description'], $_POST['repeat_start_days'], $_POST['repeat_interval_days']);
      $stmt->execute();
      echo "<p style='color: green;'>Schedule created successfully!</p>";
    } elseif ($_POST['schedule_action'] == 'edit') {
      $stmt = $mysqli->prepare("UPDATE schedule SET description = ?, repeat_start_days = ?, repeat_interval_days = ? WHERE id = ?");
      $stmt->bind_param('siii', $_POST['description'], $_POST['repeat_start_days'], $_POST['repeat_interval_days'], $_POST['schedule_id']);
      $stmt->execute();
      echo "<p style='color: green;'>Schedule updated successfully!</p>";
    } elseif ($_POST['schedule_action'] == 'delete') {
      $stmt = $mysqli->prepare("DELETE FROM schedule WHERE id = ?");
      $stmt->bind_param('i', $_POST['schedule_id']);
      $stmt->execute();
      echo "<p style='color: green;'>Schedule deleted successfully!</p>";
    }
  }
}

// Fetch all chores
$chores = [];
$result = $mysqli->query("SELECT id, name, description, pay, required, expected, max FROM chores ORDER BY name");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $chores[] = $row;
  }
}

// Fetch all assignments with related data
$assignments = [];
$result = $mysqli->query("
  SELECT a.id, a.assigned_user, a.chore_id, a.schedule_id,
         u.realname as user_name, c.name as chore_name,
         s.description as schedule_desc, s.repeat_interval_days
  FROM assignments a
  LEFT JOIN users u ON a.assigned_user = u.id
  LEFT JOIN chores c ON a.chore_id = c.id
  LEFT JOIN schedule s ON a.schedule_id = s.id
  ORDER BY u.realname, c.name
");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
  }
}

// Fetch all schedules
$schedules = [];
$result = $mysqli->query("SELECT id, description, repeat_start_days, repeat_interval_days FROM schedule ORDER BY description");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
  }
}

// Fetch all users
$users = [];
$result = $mysqli->query("SELECT id, realname FROM users ORDER BY realname");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
}
?>
<html>
  <head>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <title>Chores Admin Dashboard</title>
    <style>
      body {
        background-color: #0a0a0a;
        color: #88cc88;
      }
      h1, h2 {
        color: #66bb66;
      }
      .tabs {
        display: flex;
        background-color: #1a1a1a;
        border-bottom: 2px solid #4a8f4a;
        margin-bottom: 20px;
      }
      .tab {
        padding: 15px 30px;
        cursor: pointer;
        color: #88cc88;
        border: none;
        background-color: transparent;
        font-size: 16px;
        transition: background-color 0.3s;
      }
      .tab:hover {
        background-color: #2a2a2a;
      }
      .tab.active {
        background-color: #2d5f2d;
        color: #ccffcc;
        font-weight: bold;
      }
      .tab-content {
        display: none;
      }
      .tab-content.active {
        display: block;
      }
      .form-group {
        margin: 15px 0;
      }
      .form-group label {
        display: inline-block;
        width: 150px;
        color: #88cc88;
      }
      .form-group input, .form-group textarea, .form-group select {
        width: 300px;
        padding: 8px;
        background-color: #1a1a1a;
        color: #ccffcc;
        border: 1px solid #4a8f4a;
        border-radius: 3px;
      }
      .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
        outline: none;
        border-color: #66bb66;
        background-color: #222;
      }
      .form-group small {
        color: #666;
      }
      .btn {
        background-color: #3a6ea5;
        color: white;
        padding: 10px 20px;
        border: 1px solid #4a7eb5;
        cursor: pointer;
        border-radius: 5px;
        margin: 5px;
        font-size: 14px;
      }
      .btn-success {
        background-color: #4a8f4a;
        border-color: #5aa55a;
      }
      .btn-danger {
        background-color: #b85450;
        border-color: #c86460;
      }
      .btn:hover {
        opacity: 0.85;
        filter: brightness(1.1);
      }
      .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.85);
      }
      .modal-content {
        background-color: #0f0f0f;
        margin: 5% auto;
        padding: 20px;
        border: 2px solid #4a8f4a;
        width: 500px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
      }
      .close {
        color: #88cc88;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
      }
      .close:hover {
        color: #ff6666;
      }
      table.dataTable {
        color: #88cc88;
      }
      table.dataTable tbody tr {
        background-color: #0f0f0f;
      }
      table.dataTable tbody tr:hover {
        background-color: #1a1a1a;
      }
      table.dataTable thead th {
        background-color: #1a1a1a;
        color: #66bb66;
        border-bottom: 2px solid #4a8f4a;
      }
      table.dataTable td {
        border-bottom: 1px solid #2a2a2a;
      }
      .dataTables_wrapper .dataTables_filter input,
      .dataTables_wrapper .dataTables_length select {
        background-color: #1a1a1a;
        color: #88cc88;
        border: 1px solid #4a8f4a;
        padding: 5px;
      }
      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate {
        color: #88cc88;
      }
      .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #88cc88 !important;
        background-color: #1a1a1a;
        border: 1px solid #4a8f4a;
      }
      .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #2a2a2a;
        color: #ccffcc !important;
      }
      .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background-color: #2d5f2d !important;
        color: #ccffcc !important;
      }
    </style>
  </head>
  <body>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

    <h1>Chores Admin Dashboard</h1>

    <div style="margin-bottom: 20px;">
      <a href="chore-report.php" class="btn btn-success" style="text-decoration: none;">📊 View 7-Day Report</a>
      <a href="index.php" class="btn" style="text-decoration: none;">🏠 Home</a>
    </div>

    <div class="tabs">
      <button class="tab active" onclick="showTab('approvals')">Approvals</button>
      <button class="tab" onclick="showTab('chores')">Manage Chores</button>
      <button class="tab" onclick="showTab('assignments')">Manage Assignments</button>
      <button class="tab" onclick="showTab('schedules')">Manage Schedules</button>
      <button class="tab" onclick="showTab('payout')">Payout</button>
    </div>

    <!-- Tab 1: Approvals -->
    <div id="approvals" class="tab-content active">
      <?php
      $statement = $mysqli->prepare("select u.realname, c.name, r.date_requested, r.approval_status, r.count, r.id
                                     from requests r
                                     join assignments a on r.assignment_id = a.id
                                     left join users u on r.user_id = u.id
                                     join chores c on a.chore_id = c.id
                                     where r.approval_status = 0;");
      $statement->execute();
      $statement->store_result();
      $statement->bind_result($name, $chore, $date, $status, $count, $requestid);
      if ($statement->num_rows > 0) {
        ?>
        <form method="POST">
        <table id="approvalsTable" class="display">
          <thead>
            <tr>
              <th>Name</th>
              <th>Chore</th>
              <th>Date Requested</th>
              <th>Count</th>
              <th>Approve</th>
              <th>Deny</th>
              <th>Hold</th>
            </tr>
          </thead>
          <tbody>
        <?php
        while ($statement->fetch()) {
          echo "<tr>
                  <td>".$name."</td>
                  <td>".$chore."</td>
                  <td>".$date."</td>
                  <td>".$count."</td>
                  <td><input type=\"radio\" name=\"req[".$requestid."]\" value=\"approve\" checked></td>
                  <td><input type=\"radio\" name=\"req[".$requestid."]\" value=\"deny\"></td>
                  <td><input type=\"radio\" name=\"req[".$requestid."]\" value=\"hold\"></td>
                </tr>\n";
        }
        ?>
          </tbody>
        </table>
        <input type="password" name="code" placeholder="Enter PIN"><br/>
        <input class="namebutton" type="submit" value="Submit"/>
        </form>
        <?php
      } else {
        echo "<p>No pending approvals</p>";
      }
      ?>
    </div>

    <!-- Tab 2: Manage Chores -->
    <div id="chores" class="tab-content">
      <h2>Manage Chores</h2>
      <button class="btn btn-success" onclick="showAddChoreModal()">Add New Chore</button>
      <br><br>
      <table id="choresTable" class="display">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Pay</th>
            <th>Required</th>
            <th>Expected</th>
            <th>Max</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($chores as $chore): ?>
          <tr>
            <td><?php echo $chore['id']; ?></td>
            <td><?php echo htmlspecialchars($chore['name']); ?></td>
            <td><?php echo htmlspecialchars($chore['description']); ?></td>
            <td>$<?php echo number_format($chore['pay'], 2); ?></td>
            <td><?php echo $chore['required']; ?></td>
            <td><?php echo $chore['expected']; ?></td>
            <td><?php echo $chore['max']; ?></td>
            <td>
              <button class="btn" onclick='editChore(<?php echo json_encode($chore); ?>)'>Edit</button>
              <button class="btn btn-danger" onclick="deleteChore(<?php echo $chore['id']; ?>)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Tab 3: Manage Assignments -->
    <div id="assignments" class="tab-content">
      <h2>Manage Assignments</h2>
      <button class="btn btn-success" onclick="showAddAssignmentModal()">Add New Assignment</button>
      <br><br>
      <table id="assignmentsTable" class="display">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Chore</th>
            <th>Schedule</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assignments as $assignment): ?>
          <tr>
            <td><?php echo $assignment['id']; ?></td>
            <td><?php echo htmlspecialchars($assignment['user_name']); ?></td>
            <td><?php echo htmlspecialchars($assignment['chore_name']); ?></td>
            <td><?php echo htmlspecialchars($assignment['schedule_desc']); ?> (every <?php echo $assignment['repeat_interval_days']; ?> days)</td>
            <td>
              <button class="btn btn-danger" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Tab 4: Manage Schedules -->
    <div id="schedules" class="tab-content">
      <h2>Manage Schedules</h2>
      <button class="btn btn-success" onclick="showAddScheduleModal()">Add New Schedule</button>
      <br><br>
      <table id="schedulesTable" class="display">
        <thead>
          <tr>
            <th>ID</th>
            <th>Description</th>
            <th>Start Days</th>
            <th>Interval (days)</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($schedules as $schedule): ?>
          <tr>
            <td><?php echo $schedule['id']; ?></td>
            <td><?php echo htmlspecialchars($schedule['description']); ?></td>
            <td><?php echo $schedule['repeat_start_days']; ?></td>
            <td><?php echo $schedule['repeat_interval_days']; ?></td>
            <td>
              <button class="btn" onclick='editSchedule(<?php echo json_encode($schedule); ?>)'>Edit</button>
              <button class="btn btn-danger" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Tab 5: Payout -->
    <div id="payout" class="tab-content">
      <iframe src="payout.php" width="100%" height="800px" style="border: 1px solid green; background-color: black;"></iframe>
    </div>

    <!-- Modal for Add/Edit Chore -->
    <div id="choreModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeChoreModal()">&times;</span>
        <h2 id="choreModalTitle">Add New Chore</h2>
        <form method="POST">
          <input type="hidden" name="chore_action" id="chore_action" value="add">
          <input type="hidden" name="chore_id" id="chore_id">
          <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" id="choreName" required>
          </div>
          <div class="form-group">
            <label>Description:</label>
            <textarea name="description" id="choreDescription" required></textarea>
          </div>
          <div class="form-group">
            <label>Pay:</label>
            <input type="number" step="0.01" name="pay" id="chorePay" required>
          </div>
          <div class="form-group">
            <label>Required:</label>
            <input type="number" name="required" id="choreRequired" value="1" required>
          </div>
          <div class="form-group">
            <label>Expected:</label>
            <input type="number" name="expected" id="choreExpected" value="1" required>
          </div>
          <div class="form-group">
            <label>Max:</label>
            <input type="number" name="max" id="choreMax" value="1" required>
          </div>
          <button type="submit" class="btn btn-success">Save Chore</button>
          <button type="button" class="btn" onclick="closeChoreModal()">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Modal for Add Assignment -->
    <div id="assignmentModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeAssignmentModal()">&times;</span>
        <h2>Add New Assignment</h2>
        <form method="POST">
          <input type="hidden" name="assignment_action" value="add">
          <div class="form-group">
            <label>User:</label>
            <select name="user_id" required>
              <option value="">Select User</option>
              <?php foreach ($users as $user): ?>
              <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['realname']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Chore:</label>
            <select name="chore_id" required>
              <option value="">Select Chore</option>
              <?php foreach ($chores as $chore): ?>
              <option value="<?php echo $chore['id']; ?>"><?php echo htmlspecialchars($chore['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Schedule:</label>
            <select name="schedule_id" required>
              <option value="">Select Schedule</option>
              <?php foreach ($schedules as $schedule): ?>
              <option value="<?php echo $schedule['id']; ?>"><?php echo htmlspecialchars($schedule['description']); ?> (every <?php echo $schedule['repeat_interval_days']; ?> days)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-success">Create Assignment</button>
          <button type="button" class="btn" onclick="closeAssignmentModal()">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Modal for Add/Edit Schedule -->
    <div id="scheduleModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeScheduleModal()">&times;</span>
        <h2 id="scheduleModalTitle">Add New Schedule</h2>
        <form method="POST">
          <input type="hidden" name="schedule_action" id="schedule_action" value="add">
          <input type="hidden" name="schedule_id" id="schedule_id">
          <div class="form-group">
            <label>Description:</label>
            <input type="text" name="description" id="scheduleDescription" placeholder="e.g., Daily, Weekly, etc." required>
          </div>
          <div class="form-group">
            <label>Start Days:</label>
            <input type="number" name="repeat_start_days" id="scheduleStartDays" value="0" required>
            <br><small style="color: #888; margin-left: 155px;">Days since epoch to start (0 = today)</small>
          </div>
          <div class="form-group">
            <label>Interval (days):</label>
            <input type="number" name="repeat_interval_days" id="scheduleIntervalDays" required>
            <br><small style="color: #888; margin-left: 155px;">Repeat every X days (1=daily, 7=weekly, etc.)</small>
          </div>
          <div style="margin-left: 155px;">
            <button type="button" class="btn" onclick="setInterval(1)">Daily</button>
            <button type="button" class="btn" onclick="setInterval(7)">Weekly</button>
            <button type="button" class="btn" onclick="setInterval(14)">Bi-Weekly</button>
          </div>
          <button type="submit" class="btn btn-success">Save Schedule</button>
          <button type="button" class="btn" onclick="closeScheduleModal()">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Hidden forms for delete operations -->
    <form id="deleteChoreForm" method="POST" style="display:none;">
      <input type="hidden" name="chore_action" value="delete">
      <input type="hidden" name="chore_id" id="deleteChoreId">
    </form>

    <form id="deleteAssignmentForm" method="POST" style="display:none;">
      <input type="hidden" name="assignment_action" value="delete">
      <input type="hidden" name="assignment_id" id="deleteAssignmentId">
    </form>

    <form id="deleteScheduleForm" method="POST" style="display:none;">
      <input type="hidden" name="schedule_action" value="delete">
      <input type="hidden" name="schedule_id" id="deleteScheduleId">
    </form>

    <script>
      $(document).ready(function() {
        $('#approvalsTable').DataTable();
        $('#choresTable').DataTable();
        $('#assignmentsTable').DataTable();
        $('#schedulesTable').DataTable();
      });

      function showTab(tabName) {
        $('.tab-content').removeClass('active');
        $('.tab').removeClass('active');
        $('#' + tabName).addClass('active');
        $('button[onclick="showTab(\'' + tabName + '\')"]').addClass('active');
      }

      // Chore functions
      function showAddChoreModal() {
        $('#choreModalTitle').text('Add New Chore');
        $('#chore_action').val('add');
        $('#chore_id').val('');
        $('#choreName').val('');
        $('#choreDescription').val('');
        $('#chorePay').val('');
        $('#choreRequired').val('1');
        $('#choreExpected').val('1');
        $('#choreMax').val('1');
        $('#choreModal').show();
      }

      function editChore(chore) {
        $('#choreModalTitle').text('Edit Chore');
        $('#chore_action').val('edit');
        $('#chore_id').val(chore.id);
        $('#choreName').val(chore.name);
        $('#choreDescription').val(chore.description);
        $('#chorePay').val(chore.pay);
        $('#choreRequired').val(chore.required);
        $('#choreExpected').val(chore.expected);
        $('#choreMax').val(chore.max);
        $('#choreModal').show();
      }

      function deleteChore(choreId) {
        if (confirm('Are you sure you want to delete this chore?')) {
          $('#deleteChoreId').val(choreId);
          $('#deleteChoreForm').submit();
        }
      }

      function closeChoreModal() {
        $('#choreModal').hide();
      }

      // Assignment functions
      function showAddAssignmentModal() {
        $('#assignmentModal').show();
      }

      function deleteAssignment(assignmentId) {
        if (confirm('Are you sure you want to delete this assignment?')) {
          $('#deleteAssignmentId').val(assignmentId);
          $('#deleteAssignmentForm').submit();
        }
      }

      function closeAssignmentModal() {
        $('#assignmentModal').hide();
      }

      // Schedule functions
      function showAddScheduleModal() {
        $('#scheduleModalTitle').text('Add New Schedule');
        $('#schedule_action').val('add');
        $('#schedule_id').val('');
        $('#scheduleDescription').val('');
        $('#scheduleStartDays').val('0');
        $('#scheduleIntervalDays').val('');
        $('#scheduleModal').show();
      }

      function editSchedule(schedule) {
        $('#scheduleModalTitle').text('Edit Schedule');
        $('#schedule_action').val('edit');
        $('#schedule_id').val(schedule.id);
        $('#scheduleDescription').val(schedule.description);
        $('#scheduleStartDays').val(schedule.repeat_start_days);
        $('#scheduleIntervalDays').val(schedule.repeat_interval_days);
        $('#scheduleModal').show();
      }

      function deleteSchedule(scheduleId) {
        if (confirm('Are you sure you want to delete this schedule?')) {
          $('#deleteScheduleId').val(scheduleId);
          $('#deleteScheduleForm').submit();
        }
      }

      function closeScheduleModal() {
        $('#scheduleModal').hide();
      }

      function setInterval(days) {
        $('#scheduleIntervalDays').val(days);
      }
    </script>
  </body>
</html>
