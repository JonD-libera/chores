<?php include_once("../config.php");
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Coates Chores</title>
  <link rel="stylesheet" href="styles/chores-main.css">
</head>
<body>

  <div class="lcars-bar lcars-elbow-left-bottom">
    <span class="lcars-title">Chores 001</span>
  </div>
  
  <div class="lcars-column">
    <?php
      $statement = $mysqli->prepare("select realname, id from users where type != 1");
      $statement->execute();
      $statement->store_result();
      $statement->bind_result($name, $id);
      if ($statement->num_rows > 0)
      {
        while ($statement->fetch())
        {
          echo "<button class=\"lcars-button\" onclick=\"buttonClicked('" . $id . "')\">" . $name . "</button>\n";
        }
      }
      echo "<button class=\"lcars-button\" onclick=\"buttonClicked('System')\">Management</button>\n";
    ?>
  </div>

  <div class="lcars-main-content" id="lcars-main-content">
    <p>Welcome to the choreinator 9000 system.</p>
  </div>
  <script src="js/lookup.js" defer></script>  
</body>
</html>