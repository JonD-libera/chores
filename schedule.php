<?php
// Assuming db.php contains your database connection logic
include 'config.php';

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $repeatStart = $_POST['repeat_start'];
    $repeatInterval = $_POST['repeat_interval'];
    $description = $_POST['description'];
    $repeatStartDays = $_POST['repeat_start_days'];
    $repeatIntervalDays = $_POST['repeat_interval_days'];
    $repeatSkipDays = $_POST['repeat_skip_days'];

    // Assuming $pdo is your PDO database connection from db.php
    // Add or Edit logic here. For simplicity, we're just showing an insert. Adjust for edit by checking if an ID was passed.
    $sql = "INSERT INTO schedule (repeat_start, repeat_interval, description, repeat_start_days, repeat_interval_days, repeat_skip_days) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt= $pdo->prepare($sql);
    $stmt->execute([$repeatStart, $repeatInterval, $description, $repeatStartDays, $repeatIntervalDays, $repeatSkipDays]);

    echo "Schedule saved successfully!";
    // Redirect or inform the user of success or handle errors
}
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Function to set the repeat interval
    function setRepeatInterval(days) {
        document.getElementById("repeat_interval_days").value = days;
    }

    // Event listeners for the buttons
    document.getElementById("daily").addEventListener("click", function() { setRepeatInterval(1); });
    document.getElementById("everyOtherDay").addEventListener("click", function() { setRepeatInterval(2); });
    document.getElementById("weekly").addEventListener("click", function() { setRepeatInterval(7); });
    document.getElementById("biWeekly").addEventListener("click", function() { setRepeatInterval(14); });
});
</script>
<form action="" method="post">
    <label for="repeat_start">Repeat Start Date:</label>
    <input type="date" id="repeat_start" name="repeat_start" required>
    <br>
    <label for="repeat_interval">Repeat Interval:</label>
    <input type="number" id="repeat_interval" name="repeat_interval" required>
    <button type="button" id="daily">Daily</button>
    <button type="button" id="everyOtherDay">Every other day</button>
    <button type="button" id="weekly">Weekly</button>
    <button type="button" id="biWeekly">Bi-Weekly</button>
    <br>
    <label for="description">Description:</label>
    <input type="text" id="description" name="description" required>
    <br>
    <label for="repeat_start_days">Repeat Start Days:</label>
    <input type="number" id="repeat_start_days" name="repeat_start_days" required>
    <br>
    <label for="repeat_interval_days">Repeat Interval Days:</label>
    <input type="number" id="repeat_interval_days" name="repeat_interval_days" required>
    <br>
    <label for="repeat_skip_days">Repeat Skip Days:</label>
    <input type="number" id="repeat_skip_days" name="repeat_skip_days" required>
    <br>
    <input type="submit" value="Save Schedule">
</form>
