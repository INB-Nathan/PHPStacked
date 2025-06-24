<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_manager.php';


$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    //Supposed to be validating credentials against the database
    // $query = $db->query("SELECT `id`, `username`, `email`, `pass_hash`, `full_name`, `user_type`, `is_active`, `last_login`, `session_id`, `session_expires`, `ip_address`, `created_at`, `updated_at` FROM `users` WHERE username = '$username' AND password_hash = sha1('$password');");

    // $execQuery = $query->fetch_assoc();

    // if ($execQuery) {
    //     $_SESSION['loggedin'] = true;
    //     $_SESSION['username'] = $execQuery['username'];
    //     header("Location: dashboard.php");
    //     exit;
    // } else {
    //     $error = "Invalid username or password.";
    // }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Login Page</title>
</head>

<body>
    <div class="container">
        <div class="trademark">
            <h1>PHPStacked</h1>
        </div>
        <div class="login-container">
            <h2>Admin Login</h2>
            <form action="" method="post">
                <input type="text" id="username" name="username" placeholder="username" required>
                <br>
                <input type="password" id="password" name="password" placeholder="password" required>
                <br>
                <button type="submit" name="submit">Login</button>
            </form>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        </div>
    </div>
</body>

</html>