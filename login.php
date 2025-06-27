<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/session_manager.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';

   
    if ($username && $password){
        try{
            $stmt = $pdo -> prepare("SELECT id, username, pass_hash, user_type, is_active FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            if ($user && $user['is_active']){
                if (password_verify($password, $user['pass_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    if ($user['user_type'] == 'admin') {
                        header("Location: admin/");
                        exit;
                    } else {
                        header("Location: voter/");
                        exit;
                    }
                }
            }
                $error = "Invalid username or password.";
        } catch (PDOException $e) {
            $error = "Database error.";
        }
    } else {
        $error = "Please enter both username and password.";
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
            <h2>Login</h2>
            <form action="" method="post">
                <input type="text" id="username" name="username" placeholder="Username" required>
                <br>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <br>
                <button type="submit" name="submit">Login</button>
            </form>
            <div class="error-message">
                <p><?php echo $error; ?></p>
            </div>
        </div>
    </div>
</body>

</html>