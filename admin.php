<html>
  <head>
    <link rel="stylesheet" href="style.css">
    <title>Chores UI parents page</title>
  </head>
  <body>
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
?>
</body>
