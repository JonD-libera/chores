<html>
  <head>
    <link rel="stylesheet" href="style.css">
    <title>Chores UI parents page</title>
  </head>
  <body>
What is life?
<?php
include_once ("./config.php");
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  exit();
}
if (isset($_REQUEST['code'])) {
    $statement = $mysqli->prepare("select u.realname, u.id, u.pin from users u where u.id = 1");
    if ($statement->execute())
    {
      $statement->store_result();
      $statement->bind_result($approvername, $approver, $code);
      if ($statement->num_rows > 0)
      {
        $statement->fetch();
        if ($code == $_REQUEST['code'])
        {
          $statement = $mysqli->prepare("insert into activity (date, timestamp, assignment_id, user_id, payrate, quantity) select DATE(r.date_requested), r.date_requested, r.assignment_id, r.user_id, c.pay, r.count from requests r join assignments a on r.assignment_id = a.id join chores c on a.chore_id = c.id where r.id = ?");          
          foreach($_REQUEST['req'] as $key => $val) 
          {            
            if ($val == "approve")             
            {
              print "Approving $key<br>";
              $mysqli->query("update requests set approval_status = 1 where id = ".$key);
              $statement->bind_param('i',$key);
              $statement->execute();
            } elseif ($val == "deny") {
              print "Denying $key<br>";              
              $mysqli->query("update requests set approval_status = 2 where id = ".$key);
            }
          }
        } else {
          echo "<p>Code invalid</p>";
        }
      }
    }
  }
$statement = $mysqli->prepare("select u.realname, c.name, r.date_requested, r.approval_status, r.count, r.id
                               from requests r 
                               join assignments a on r.assignment_id = a.id 
                               left join users u on r.user_id = u.id 
                               join chores c on a.chore_id = c.id
                               where r.approval_status = 0;");
$statement->execute();
$statement->store_result();
$statement->bind_result($name, $chore, $date, $status, $count, $requestid);
if ($statement->num_rows > 0)
{
  ?>
  <form method="POST"><?php
  while ($statement->fetch())
  {
    echo "<p>".$name." requsts approval for ".$chore." on " .$date. " for ".$count." chores.
    <label style=\"direction:rtl;\" value=\"10\">Approve</label><input type=\"radio\" checked /name=\"req[".$requestid."]\" value=\"approve\">
    <label style=\"direction:rtl;\" value=\"20\">Deny</label><input type=\"radio\" name=\"req[".$requestid."]\" value=\"deny\">
    <label style=\"direction:rtl;\" value=\"30\">Hold</label><input type=\"radio\" name=\"req[".$requestid."]\" value=\"hold\"></p>\n";
  }
  ?>
    <input type="password" name="code"><br/>
    <input class="namebutton" type="submit" value="Submit"/>
  </form><?php
}
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return Home\"/></form>";
  $statement = $mysqli->prepare("select u.realname, u.id, c.name, c.description, a.id, 
  case when (select count(1) from activity act where act.assignment_id = a.id and act.date = DATE_SUB(date(now(), INTERVAL 1 DAY)) and act.user_id = a.assigned_user) > 0 
  then 'completebutton' 
  when (select count(1) from requests r where r.assignment_id = a.id and date(r.date_requested) = DATE_SUB(date(now(), INTERVAL 1 DAY)) and r.user_id = u.id)  > 0 then \"pendingbutton\"
  else 'incompletebutton'
  end, (select sum(quantity) from activity act where act.assignment_id = a.id and act.date = DATE_SUB(date(now(), INTERVAL 1 DAY)) and act.user_id = a.assigned_user) as quantity,
  c.pay 
  from assignments a join users u on a.assigned_user = u.id join chores c on a.chore_id = c.id join schedule s on a.schedule_id = s.id 
  where (( to_days(curdate()-1) - repeat_start_days) % repeat_interval_days = 0) and c.type = 1 order by u.id");
  if ($statement->execute())
  {
    $statement->store_result();
    $statement->bind_result($name, $userid, $chore, $description, $assignment, $buttonstyle, $quantity, $pay);
    if ($statement->num_rows > 0)
    {
      echo "<p>Here are all the chores for today</p>";
      while ($statement->fetch())
      {
        $chorecount = "";
        if ( $quantity > 1 ) {          
          $chorecount = "x ".$quantity;
        }
        echo "<form class =\"hlistform\" method =\"POST\" id=\"namebutton\" action=\"./\">";
              if ($userid != $lid) {
                echo "<label for=\"chore\">" . $name . "</label><br/>";
              }
        echo  "<input class=\"".$buttonstyle."\" type=\"submit\" value=\"" . $chore . " " . $chorecount . " for " .$pay."\"/>
              <input name=\"action\" type=\"hidden\" id=\"i\" value=\"authenticate\"/>
              <input name=\"assignment\" type=\"hidden\" id=\"i\" value=\"" . $assignment . "\"/>
              <input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $userid . "\"/>
              </form>";
              if ($userid != $lid) {                
                $lid = $userid;
              }
      }
    }
    else
    {
      echo "<p>You don't have any chores right now</p>";
    }
  }
  else
  {
    echo ('Error executing MySQL query: ' . $statement->error);
  }
?>
</body>
