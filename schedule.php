<?php
require_once('config.php');

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Current week range
$monday = strtotime("last monday");
$sunday = strtotime("next sunday") + 86399;

$days = [
    "Monday" => [],
    "Tuesday" => [],
    "Wednesday" => [],
    "Thursday" => [],
    "Friday" => [],
    "Saturday" => [],
    "Sunday" => [],
];

// Query database
$sql = "select u.realname, u.id, c.name, c.description, a.id, s.repeat_interval, s.repeat_start, 
  case when (select count(1) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user) > 0 
  then 'completebutton' 
  when (select count(1) from requests r where r.assignment_id = a.id and date(r.date_requested) = date(now()) and r.user_id = u.id)  > 0 then \"pendingbutton\"
  else 'incompletebutton'
  end, (select sum(quantity) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user) as quantity,
  c.pay 
  from assignments a join users u on a.assigned_user = u.id join chores c on a.chore_id = c.id join schedule s on a.schedule_id = s.id 
  where (( to_days(curdate()) - repeat_start_days) % repeat_interval_days = 0) and c.type = 1 order by u.id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $repeatStart = $row['repeat_start'];
        $repeatInterval = $row['repeat_interval'];
        
        // Validate values
        if ($repeatStart <= 0 || $repeatInterval <= 0) {
            continue;
        }
        
        // Calculate instances for this row
        for ($time = $repeatStart; $time <= $sunday; $time += $repeatInterval) {
            if ($time < $monday) {
                continue;
            }
            $day = date("l", $time);
            $days[$day][] = date("Y-m-d H:i:s", $time);
        }
    }
} else {
    echo "0 results";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Week Schedule</title>
</head>
<body>

<h1>This Week's Instances</h1>

<table border="1">
    <thead>
        <tr>
            <?php foreach ($days as $day => $times): ?>
                <th><?php echo $day; ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php 
        $maxRows = max(array_map('count', $days));
        for ($i = 0; $i < $maxRows; $i++): ?>
            <tr>
                <?php foreach ($days as $day => $times): ?>
                    <td><?php echo isset($times[$i]) ? $times[$i] : ''; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

</body>
</html>

