<html>
  <head>
    <link rel="stylesheet" href="style.css">
    <title>Chores UI main page</title>
    <meta name="viewport" content="width=device-width, initial-scale=0.9">
  </head>
  <body>
	  
<?php
//var_dump($_REQUEST);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
include_once ("./config.php");

$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  exit();
}
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "chorelist")
{
  renderuser($mysqli);
}
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "bonus")
{
  renderbonus($mysqli);
}
elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "allchores")
{
  renderallchores($mysqli);
}
elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "choredetail")
{
  renderchore($mysqli);
}
elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "authenticate")
{
  renderauth($mysqli,$emailuser,$emailpass,$emailfrom,$emailto,$emailreply);
}
elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "choreapprove")
{
  renderauth($mysqli,$emailuser,$emailpass,$emailfrom,$emailto,$emailreply);
}
elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "wiki")
{
  renderwiki($mysqli);
}
if (!isset($_REQUEST['action']))
{
  renderhome($mysqli);
}
$mysqli->close(); ?>
 </body>
</html>
<?php
function renderhome($mysqli)
{
?>
    <div class="grid-container">
    <div class="Header"><?php echo "<h2>" . welcome() . " and welcome to Chorinator 9000</h2>"; ?></div>
    <div class="Left"><?php
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"View today's list\"/>
  <input name=\"action\" type=\"hidden\" id=\"i\" value=\"allchores\"/></form>";
  $statement = $mysqli->prepare("select realname,id from users where type != 1");
  $statement->execute();
  $statement->store_result();
  $statement->bind_result($name, $id);
  if ($statement->num_rows > 0)
  {
    while ($statement->fetch())
    {
      echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"" . $name . "\"/>
              <input name=\"action\" type=\"hidden\" id=\"i\" value=\"chorelist\"/>
              <input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $id . "\"/>
              </form><p>\n";
    }
  }
 echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"View the Wiki\"/>
  <input name=\"action\" type=\"hidden\" id=\"i\" value=\"wiki\"/></form>";
?></div><div class="Right">
    <div class="report-container" id="weather-report">
    </div>
</div>
</div>
<script type="text/javascript">
function getHTTPObject() {
  var xmlhttp;
  /*@cc_on
  @if (@_jscript_version >= 5)
    try {
      xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (E) {
        xmlhttp = false;
      }
    }
  @else
    xmlhttp = false;
  @end @*/
  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
    try {
      xmlhttp = new XMLHttpRequest();
    } catch (e) {
      xmlhttp = false;
    }
  }
  return xmlhttp;
};
function renderwiki() {
echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return Home\"/></form>";
<iframe src="http://griffin/mediawiki" width="100%" height="100%"></iframe>
};
function fetchData() {
  http = getHTTPObject();
  http.open('get', "./weather.php?xhttp=true", true);
  http.onreadystatechange = function() {
    if (http != null && http.readyState == 4) {
      document.getElementById('weather-report').innerHTML = http.responseText;
    }
  };
  http.send(null);
};
 
function initPage() {
  http = null;
  fetchData();
  pageTimerHandle = setInterval('fetchData()', 15000);
};
 
window.onload = initPage;
</script>
<?php
}

function renderuser($mysqli)
{
  $statement = $mysqli->prepare("select u.realname from users u where u.id = ?");
  $statement->bind_param('i', $_REQUEST['userid']);
  if ($statement->execute())
  {
    $statement->store_result();
    $statement->bind_result($name);
    $statement->fetch();
  }
?><div class="Header"><?php echo "<h2>" . welcome() . " " . $name . "</h2>"; ?></div><?php
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return Home\"/></form>";
  $statement = $mysqli->prepare("select u.realname, c.name, c.description, a.id, case when 
  (select count(1) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user) > 0 then \"completebutton\" else \"incompletebutton\" end
  from assignments a left join users u on a.assigned_user = u.id join chores c on a.chore_id = c.id join schedule s on a.schedule_id = s.id where (( to_days(curdate()) - repeat_start_days) % repeat_interval_days = 0) and (a.assigned_user = ? or a.assigned_user is null)");
  #$statement = $mysqli->prepare("select u.realname, c.name, c.description, a.id from assignments a join users u on a.assigned_user = u.id join chores c on a.chore_id = c.id join schedule s on a.schedule_id = s.id where (( UNIX_TIMESTAMP(CURDATE()) - repeat_start) % repeat_interval = 0) and a.assigned_user = ?");
  $statement->bind_param('i', $_REQUEST['userid']);
  if ($statement->execute())
  {
    $statement->store_result();
    $statement->bind_result($name, $chore, $description, $assignment, $buttonstyle);
    if ($statement->num_rows > 0)
    {
      echo "<p>You have the following chores today</p>";
      while ($statement->fetch())
      {
        echo "<form method =\"POST\" id=\"namebutton\" action=\"./\">
              <input class=\"".$buttonstyle."\" type=\"submit\" value=\"" . $chore . "\"/>              
              <input name=\"action\" type=\"hidden\" id=\"i\" value=\"authenticate\"/>
              <input name=\"assignment\" type=\"hidden\" id=\"i\" value=\"" . $assignment . "\"/>
              <input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['userid'] . "\"/>
              </form><p>";

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
echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Bonus!\"/>
<input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['userid'] . "\"/>
<input name=\"action\" type=\"hidden\" id=\"i\" value=\"bonus\"/>
</form>";
}

function renderallchores($mysqli)
{
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return Home\"/></form>";
  $statement = $mysqli->prepare("select u.realname, u.id, c.name, c.description, a.id, 
  case when (select count(1) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user) > 0    
  then 'completebutton' else 'incompletebutton'
  end, (select sum(quantity) from activity act where act.assignment_id = a.id and act.date = date(now()) and act.user_id = a.assigned_user) as quantity 
  from assignments a join users u on a.assigned_user = u.id join chores c on a.chore_id = c.id join schedule s on a.schedule_id = s.id 
  where (( to_days(curdate()) - repeat_start_days) % repeat_interval_days = 0) and c.type = 1 order by u.id");
  if ($statement->execute())
  {
    $statement->store_result();
    $statement->bind_result($name, $userid, $chore, $description, $assignment, $buttonstyle, $quantity);
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
        echo  "<input class=\"".$buttonstyle."\" type=\"submit\" value=\"" . $chore . " " . $chorecount . "\"/>
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
}

function renderchore($mysqli)
{
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return to chore list\"/>
        <input name=\"action\" type=\"hidden\" id=\"i\" value=\"chorelist\"/>
        <input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['userid'] . "\"/>
        </form><p>\n";
  $statement = $mysqli->prepare("select c.name, c.description, c.pay, a.id, a.assigned_user from chores c join assignments a on a.chore_id = c.id join users u on a.assigned_user = u.id where a.assigned_user = ? and a.id = ?");
  $statement->bind_param('ii', $_REQUEST['userid'], $_REQUEST['assignment']);
  if ($statement->execute())
  {
    $statement->store_result();
    $statement->bind_result($chore, $description, $pay, $assignment, $user);
    if ($statement->num_rows > 0)
    {
      $statement->fetch();
      echo "<h2>" . $chore . "</h2>";
      echo "<p>" . $description . "</p>";
      echo "<p>" . $pay . "</p>";
      echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"I finished this chore\"/>
              <input name=\"action\" type=\"hidden\" id=\"i\" value=\"authenticate\"/>
              <input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $user . "\"/>
              <input name=\"assignment\" type=\"hidden\" id=\"i\" value=\"" . $assignment . "\"/>
              </form><p>\n";
    }
  }
}

function renderauth($mysqli,$emailuser,$emailpass,$emailfrom,$emailto,$emailreply)
{
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return Home\"/></form>";  
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return to chore list\"/>
        <input name=\"action\" type=\"hidden\" id=\"i\" value=\"chorelist\"/>
        <input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['userid'] . "\"/>
        </form><p>\n";  
  $statement = $mysqli->prepare("select c.name, c.description, c.pay, a.id, a.assigned_user from chores c join assignments a on a.chore_id = c.id left join users u on a.assigned_user = u.id where (a.assigned_user = ? or a.assigned_user is null) and a.id = ?");
  $statement->bind_param('ii', $_REQUEST['userid'], $_REQUEST['assignment']);
  if ($statement->execute())
  {
    $statement->store_result();
    $statement->bind_result($chore, $description, $pay, $assignment, $user);
    if ($statement->num_rows > 0)
    {
      $statement->fetch();
      echo "<h2>" . $chore . "</h2>";
      echo "<p>" . $description . "</p>";
      echo "<p>" . $pay . "</p>";
    }
  }  
  if (isset($_REQUEST['emailbutton'])) 
  {
    #send an email to parents
    $statement = $mysqli->prepare("select u.realname, c.name, a.id, (c.pay * ?), c.pay from assignments a join chores c on c.id = a.chore_id join users u on a.assigned_user = u.id where a.id = ?");
    $statement->bind_param('ii',$_REQUEST['count'],$_REQUEST['assignment']);
    if ($statement->execute())
    {
      $statement->store_result();
      $statement->bind_result($username, $chorename, $assignment, $pay, $payrate);
      if ($statement->num_rows > 0)
      {
        $statement->fetch();
      }
    }
    //$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"."?assignment=".$assignment."&action=authenticate&userid=".$_REQUEST['userid']."&count=".$_REQUEST['count'];
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/admin.php";
    $statement = $mysqli->prepare("insert into requests (date_requested, assignment_id, count, approval_status, user_id) values (NOW(), ?, ?, 0, ?)");
    $statement->bind_param('iii',$_REQUEST['assignment'],$_REQUEST['count'],$_REQUEST['userid']);
    $statement->execute();
    $mail = new PHPMailer(true);
    try {
        // Server settings    
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->Username = $emailuser; // YOUR gmail email
        $mail->Password = $emailpass; // YOUR gmail password

        // Sender and recipient settings
        $mail->setFrom($emailfrom, 'Chores Console');
        $mail->addAddress($emailto, 'Parents');
        $mail->addReplyTo($emailreply, 'Chores Console'); // to set the reply to

        // Setting the email content
        $mail->IsHTML(true);
        $mail->Subject = "Chore approval request from ".$username;
        $mail->Body = $username. " has requested a chore approval for ". $_REQUEST['count'] ." of ". $chorename.".</p><p></p>Click here <a href=\"".$actual_link.
        "\">here</a> if you would like to approve this request.</p>";
        $mail->AltBody = 'Plain text version of approval request';

        $mail->send();
        echo "Email message sent.";
    } catch (Exception $e) {
        echo "Error in sending email. Mailer Error: {$mail->ErrorInfo}";
    }    
  }
  elseif (isset($_REQUEST['code']))
  {
    $statement = $mysqli->prepare("select u.realname, u.id, u.pin from users u where u.id = ?");
    $statement->bind_param('i', $_REQUEST['approver']);
    if ($statement->execute())
    {
      $statement->store_result();
      $statement->bind_result($approvername, $approver, $code);
      if ($statement->num_rows > 0)
      {
        $statement->fetch();
        if ($code == $_REQUEST['code'])
        {
          echo "Approved by " . $approvername . "<br>";
          $statement = $mysqli->prepare("select a.id, (c.pay * ?), c.pay from assignments a join chores c on c.id = a.chore_id where a.id = ?");
          $statement->bind_param('ii',$_REQUEST['count'],$_REQUEST['assignment']);
          if ($statement->execute())
          {
            $statement->store_result();
            $statement->bind_result($assignment, $pay, $payrate);
            if ($statement->num_rows > 0)
            {
              $statement->fetch();
              echo "Pay of  $" . $pay . " for ".$_REQUEST['count']." at ".$payrate."<br>";
              $statement = $mysqli->prepare("insert into activity (date, timestamp, assignment_id, user_id, payrate, quantity, approval_status) values (CURDATE(), NOW(), ?, ?, ?, ?, 1)");
              $statement->bind_param('iidi', $_REQUEST['assignment'], $_REQUEST['userid'], $payrate, $_REQUEST['count']);
              $statement->execute();
              $statement->store_result();
              echo $statement->num_rows;
            }
          }
        }

      }
    }
  }
  else
  {
?>
<form id="keyform" action="./" method="POST" action="./">
<input name="action" type="hidden" id="i" value="choreapprove"/>
<?php echo "<input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['userid'] . "\"/>
            <input name=\"approver\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['approver'] . "\"/>
            <input name=\"assignment\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['assignment'] . "\"/>" ?>
<?php keypad();?>
<input type="password" name="code" value="" maxlength="4" class="display" /><br>
<?php
    $statement = $mysqli->prepare("select c.name, c.description, c.pay, a.id, a.assigned_user, c.max 
    from chores c join assignments a on a.chore_id = c.id left join users u on a.assigned_user = u.id 
    where (a.assigned_user = ? or a.assigned_user is null) and a.id = ?");
    $statement->bind_param('ii', $_REQUEST['userid'], $_REQUEST['assignment']);
    if ($statement->execute())
    {
      $statement->store_result();
      $statement->bind_result($chore, $description, $pay, $assignment, $user, $max);
      if ($statement->num_rows > 0)
      {
        $statement->fetch();
        if ($max > 1)
        {
?>
            <label for="count">Choose how many you did:</label>
            <select name="count" id="count" class="select-css">
            <?php
          for ($cnt = 1;$cnt <= $max;$cnt++)
          {
            if ($cnt == $_REQUEST['count'])
            {
              echo "<option value =\"" . $cnt . "\" selected>" . $cnt . "</option>\n";
            }
            else
            {
              echo "<option value =\"" . $cnt . "\">" . $cnt . "</option>\n";
            }
          }
?></select><br>
            <?php
        } else {
		  echo "<input name=\"count\" type=\"hidden\" id=\"i\" value=\"1\"/>";
	    }	
      }
    }
    $statement = $mysqli->prepare("select u.realname, u.id from  users u where u.id != ? and u.type < 3");
    $statement->bind_param('i', $_REQUEST['userid']);
    if ($statement->execute())
    {
      $statement->store_result();
      $statement->bind_result($approvername, $approverid);
      if ($statement->num_rows > 0)
      {
?>
        <label for="approver">Who is approving the chore?</label>
        <select name="approver" id="approver" class="select-css"><?php
        while ($statement->fetch())
        {
          echo "<option value =\"" . $approverid . "\">" . $approvername . "</option>\n";
        }
?></select></p><?php
      }
    }
  echo "<input name=\"emailbutton\" id=\"emailbutton\" class=\"namebutton\" type=\"submit\" value=\"Ask parents approve\"/>";
  }

?>
<p id="message">VERIFYING...</p>
</form>
    <script type="text/javascript">
    function addCode(key){
      var code = document.getElementById("keyform").code;
      if(code.value.length < 4){
        code.value = code.value + key;
      }
      if(code.value.length == 4){
        document.getElementById("message").style.display = "block";
        setTimeout(submitForm,1000);  
      }
    }

    function submitForm(){
      document.getElementById("keyform").submit();
    }

    function emptyCode(){
      document.getElementById("keyform").code.value = "";
    }
    </script>

<?php
  
}
function renderbonus($mysqli)
{
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return Home\"/></form>";  
  echo "<form method =\"POST\" id=\"namebutton\" action=\"./\"><input class=\"namebutton\" type=\"submit\" value=\"Return to chore list\"/>
        <input name=\"action\" type=\"hidden\" id=\"i\" value=\"chorelist\"/>
        <input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['userid'] . "\"/>
        </form><p>\n";
  if (isset($_REQUEST['code']))
  {
    $statement = $mysqli->prepare("select u.realname, u.id, u.pin from users u where u.id = ?");
    $statement->bind_param('i', $_REQUEST['approver']);
    if ($statement->execute())
    {
      $statement->store_result();
      $statement->bind_result($approvername, $approver, $code);
      if ($statement->num_rows > 0)
      {
        $statement->fetch();
        if ($code == $_REQUEST['code'])
        {
          echo "Bonus approved by " . $approvername . "<br>";              
          $payrate = ".50";
          $pay = $_REQUEST['count'] * $payrate;
          echo "Pay of  $" . $pay . " for ".$_REQUEST['count']." at ".$payrate."<br>";
          $statement = $mysqli->prepare("insert into activity (date, timestamp, assignment_id, user_id, payrate, quantity, approval_status) values (CURDATE(), NOW(), ?, ?, ?, ?, 1)");
          $statement->bind_param('iidi', $_REQUEST['assignment'], $_REQUEST['userid'], $payrate, $_REQUEST['count']);
          $statement->execute();
          $statement->store_result();
          echo $statement->num_rows;
        }
      }
    }
  }
  else
  {
?>
<form id="keyform" action="./" method="POST" action="./">
<input name="action" type="hidden" id="i" value="bonus"/>
<?php echo "<input name=\"userid\" type=\"hidden\" id=\"i\" value=\"" . $_REQUEST['userid'] . "\"/>
            <input name=\"approver\" type=\"hidden\" id=\"i\" value=\"1\"/>            
            <input name=\"assignment\" type=\"hidden\" id=\"i\" value=\"0\"/>" ?>
<label for="code">Enter Parents Pin</label>
<?php keypad();?>
<input type="password" name="code" value="" maxlength="4" class="display" readonly="readonly" /><br>
<?php

?>
            <label for="count">Choose quantity (each is worth 50 cents):</label>
            <select name="count" id="count" class="select-css">
            <?php
          for ($cnt = 1;$cnt <= 20;$cnt++)
          {
            
            if ($cnt == $_REQUEST['count'])
            {
              echo "<option value =\"" . $cnt . "\" selected>" . $cnt . "</option>\n";
            }
            else
            {
              echo "<option value =\"" . $cnt . "\">" . $cnt . "</option>\n";
            }
          }
?></select><br>
            <?php
    
  
?>
<p id="message">VERIFYING...</p>
</form>
    <script type="text/javascript">
    function addCode(key){
      var code = document.getElementById("keyform").code;
      if(code.value.length < 4){
        code.value = code.value + key;
      }
      if(code.value.length == 4){
        document.getElementById("message").style.display = "block";
        setTimeout(submitForm,1000);  
      }
    }

    function submitForm(){
      document.getElementById("keyform").submit();
    }

    function emptyCode(){
      document.getElementById("keyform").code.value = "";
    }
    </script>

<?php
  }
}
function keypad()
{
  ?>
  <table id="keypad" cellpadding="5" cellspacing="3">
  <tr>
      <td onclick="addCode('1');">1</td>
        <td onclick="addCode('2');">2</td>
        <td onclick="addCode('3');">3</td>
    </tr>
    <tr>
      <td onclick="addCode('4');">4</td>
        <td onclick="addCode('5');">5</td>
        <td onclick="addCode('6');">6</td>
    </tr>
    <tr>
      <td onclick="addCode('7');">7</td>
        <td onclick="addCode('8');">8</td>
        <td onclick="addCode('9');">9</td>
    </tr>
    <tr>
      <td onclick="addCode('*');">*</td>
        <td onclick="addCode('0');">0</td>
        <td onclick="addCode('#');">#</td>
    </tr>
  </table>
  <?php
}
function welcome()
{

  if (date("H") < 12)
  {

    return "Good morning";

  }
  elseif (date("H") > 11 && date("H") < 18)
  {

    return "Good afternoon";

  }
  elseif (date("H") > 17)
  {

    return "Good evening";

  }

}
?>
