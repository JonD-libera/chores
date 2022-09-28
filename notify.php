<?php
include_once (dirname(__FILE__)."/config.php");
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  exit();
}
if (isset($argv['1'])) {
  $level = $argv['1'];
} else {
	$level = 1;
}
if (isset($argv['2'])) {
  $userlist = "and u.id in (".$argv['2'].")";
} else {
  $userlist = "";
}
$sql = "select group_concat(c.name), 
n.address,
a.id,
u.id,
(select count(1) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user)
+ (select sum(count) from requests r where r.assignment_id = a.id and date(r.date_requested) = date(now()) and r.user_id = u.id),
c.expected
from assignments a 
left join users u on a.assigned_user = u.id 
join notification n on u.id = n.user_id join chores c on a.chore_id = c.id 
join schedule s on a.schedule_id = s.id 
where (( to_days(curdate()) - repeat_start_days) % repeat_interval_days = 0) 
and (a.assigned_user IS NOT NULL)"
.$userlist. 
"group by n.address,a.id;";
$statement = $mysqli->prepare($sql);

if ($statement->execute())
{
  $statement->store_result();
  $statement->bind_result($list, $address);
  if ($statement->num_rows > 0)
  {
	while ($statement->fetch()) {
      shell_exec('/usr/bin/php /var/www/html/sendsms.php 4848540682 '.$address.' "Chores not complete: '.$list.'"');
      echo "You have not completed ".$list."\n";
    }
  }
}
