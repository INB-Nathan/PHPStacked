<?php
require_once 'includes/db_connect.php';
require_once 'includes/session_manager.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use prepared statements for security
    $stmt = $pdo->prepare("SELECT id, username, pass_hash, user_type FROM users WHERE username = :username AND pass_hash = sha1(:password)");
    $stmt->execute([
        'username' => $username,
        'password' => $password
    ]);
    $execQuery = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($execQuery) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $execQuery['username'];
        if ($execQuery['user_type'] == 'admin') {
           header("Location: dashboard.php");
            exit;
        } else {
            header("Location: user_dashboard.php");
            exit;
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
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