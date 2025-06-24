<?php
    require_once '../includes/db_connect.php';
    require_once '../includes/session_manager.php';
    session_start();
    $sessionManager->requireLogin();
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Welcome to the Admin Dashboard</h1>
    <p>You are logged in as <?php echo $_SESSION['username']; ?></p>
</body>
</html>