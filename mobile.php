<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
    <style>
        .completebox {
            width: 120px;
            height: 120px;
            border: 1px solid white;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
        }
        .incompletebox {
            width: 120px;
            height: 120px;
            border: 1px solid white;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
        }
    </style>
</head>
<body>
<?php
// Database connection
include_once (dirname(__FILE__)."/config.php");
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Fetch data from database
$sql = "select u.realname, u.id, c.name, c.description, a.id, 
case when (select count(1) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user) > 0 
then 'completebutton' 
when (select count(1) from requests r where r.assignment_id = a.id and date(r.date_requested) = date(now()) and r.user_id = u.id)  > 0 then \"pendingbutton\"
else 'incompletebutton'
end as button, (select sum(quantity) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user) as quantity,
c.pay 
from assignments a join users u on a.assigned_user = u.id join chores c on a.chore_id = c.id join schedule s on a.schedule_id = s.id 
where (( to_days(curdate()) - repeat_start_days) % repeat_interval_days = 0) and c.type = 1 order by u.id, c.id";
$result = $conn->query($sql);

// Display grid
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $text = $row['realname']."<br><br>".$row['name'];
        echo '<div class="'.$row['button'].'">' . $text . '</div>';
    }
} else {
    echo 'No data found.';
}

// Close database connection
$conn->close();
?>
</body>
</html>
