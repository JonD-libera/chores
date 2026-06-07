<?php
session_start();
if (isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Simple hard-coded authentication
    if ($username === 'admin' && $password === 'fx87^TL') {
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if ($error !== ''): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required /><br>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required /><br>
        <input type="submit" value="Login" />
    </form>
</body>
</html>
