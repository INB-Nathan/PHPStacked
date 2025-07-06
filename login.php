<?php
require_once "includes/autoload.php";

$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();

session_start();
$securityManager->checkSessionTimeout(); //para sa session management
$csrf_token = $securityManager->generateCSRFToken();

$error = "";

if (isset($_GET['msg']) && $_GET['msg'] === 'timeout') {
    $error = "Your session has expired due to inactivity. Please log in again.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token for security.
    if (!$securityManager->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security validation failed. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($username && $password) {
            try {
                // Prepare and execute the query to fetch user details.
                $stmt = $pdo->prepare("SELECT id, username, pass_hash, user_type, is_active FROM users WHERE username = :username LIMIT 1");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();
                
                // Check if user exists and is active.
                if ($user && $user['is_active']) {
                    // Verify the password.
                    if (password_verify($password, $user['pass_hash'])) {
                        // Regenerate session ID para sa session fixation protection.
                        $securityManager->regenerateSession();
                        
                        // Set session variables.
                        $_SESSION['loggedin'] = true;
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_type'] = $user['user_type'];
                        // $_SESSION['last_activity'] is already set by secureSession() and updated by checkSessionTimeout()
                        
                        // Redirect user based on user type.
                        if ($user['user_type'] == 'admin') {
                            header("Location: admin/");
                            exit;
                        } else {
                            header("Location: voter/");
                            exit;
                        }
                    }
                }
                // Kung invalid credentials or inactive user.
                $error = "Invalid username or password.";
            } catch (PDOException $e) {
                // Catch any database errors.
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            // Kung may kulang sa username o password.
            $error = "Please enter both username and password.";
        }
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" name="submit">Login</button>
            </form>
            <div class="error-message">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    </div>
</body>

</html>