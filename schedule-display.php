<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2-Week Grid</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="weekcontainer">
    <?php
    include 'config.php';
    //Connect to the database
    $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    $days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    $currentDate = new DateTime();

    for ($dayIndex = 0; $dayIndex < 14; $dayIndex++) {
        echo "<div class='day' onclick='fillDay(this)'>";
        echo $days[$currentDate->format('w')] . "<br>";
        echo $currentDate->format('Y-m-d') . "<br>";

        // Prepare the date for the query
        $queryDate = $currentDate->format('Y-m-d');

        // Prepare the statement
        $statement = $mysqli->prepare("SELECT u.realname, u.id, c.name, c.description, a.id as assignment_id, 
            CASE 
                WHEN (SELECT COUNT(1) FROM activity act WHERE act.assignment_id = a.id AND act.date = ? AND act.user_id = a.assigned_user) > 0 
                THEN 'completebutton' 
                WHEN (SELECT COUNT(1) FROM requests r WHERE r.assignment_id = a.id AND DATE(r.date_requested) = ? AND r.user_id = u.id) > 0 
                THEN 'pendingbutton'
                ELSE 'incompletebutton'
            END AS status, 
            (SELECT SUM(quantity) FROM activity act WHERE act.assignment_id = a.id AND act.date = ? AND act.user_id = a.assigned_user) AS quantity,
            c.pay 
            FROM assignments a 
            JOIN users u ON a.assigned_user = u.id 
            JOIN chores c ON a.chore_id = c.id 
            JOIN schedule s ON a.schedule_id = s.id 
            WHERE ((TO_DAYS(?) - repeat_start_days) % repeat_interval_days = 0) AND c.type = 1 
            ORDER BY u.id");

        // Bind the parameters
        $statement->bind_param('ssss', $queryDate, $queryDate, $queryDate, $queryDate);

        // Execute the statement
        $statement->execute();

        // Bind the result variables
        $statement->bind_result($realname, $userid, $choreName, $choreDescription, $assignmentId, $status, $quantity, $pay);
        // Check query status
        if ($statement->errno) {
            echo "Error: " . $statement->error;
        }
        // Fetch the results and display them
        while ($statement->fetch()) {
            echo "<div class='chore'>";
            echo "<strong>$realname</strong><br>";
            echo "$assignmentId : $choreName<br>";
            echo "Quantity: $quantity<br>";
            echo "Pay: $pay<br>";
            echo "</div>";
        }
        $statement->close();

        echo "</div>";
        $currentDate->modify('+1 day');
    }

    $mysqli->close();
    ?>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
