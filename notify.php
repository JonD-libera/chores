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
$sql = "select user_id, address, time, last_time from notification;";
$statement = $mysqli->prepare($sql);
if ($statement->execute())
{
  $statement->store_result();
  $statement->bind_result($user_id, $address, $time, $last_time);
  if ($statement->num_rows > 0)
  {
	while ($statement->fetch()) {
      $chore_array=array();
      #shell_exec('/usr/bin/php /var/www/html/sendsms.php 4848540682 '.$address.' "Chores not complete: '.$list.'"');      
      $user_sql = "select c.name, sum(req.count),sum(act.quantity),c.expected from assignments asn
      left join requests req on (asn.id=req.assignment_id and date(req.date_requested) = date(now()))
      left join activity act on (asn.id=act.assignment_id and date(act.date) = date(now()))
      join chores c on c.id = asn.chore_id
      join schedule s on asn.schedule_id = s.id 
      where asn.assigned_user = ".$user_id." and (( to_days(curdate()) - repeat_start_days) % repeat_interval_days = 0)
      group by asn.chore_id;";
      $user_statement=$mysqli->prepare($user_sql);
      $user_statement->execute();
      $user_statement->store_result();
      $user_statement->bind_result($chore_name, $req_count, $act_count, $chore_count);
      if ($user_statement->num_rows > 0)
      {
        while ($user_statement->fetch()) {          
          if ($chore_count > ($req_count + $act_count)) 
          {
            $chore_array[]=$chore_name;
          }
        }
        $userinfo = " Chores not complete: " . implode(', ',$chore_array) . "\nVisit http://chores.home.jdsnetwork.com";
        echo $user_id." ".$userinfo;
        shell_exec('/usr/bin/php /var/www/html/sendsms.php 4848540682 '.$address.' "'.$userinfo.'"');
      }
    }
  }
}
