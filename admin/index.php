<?php
    // Loads the database connection file
    require_once '../includes/db_connect.php';
    // Loads the admin header navigation
    require_once '../includes/admin_header.php';
    session_start();

    // This function would redirect users to login if not authenticated as admin
    function requireLogin() {
        if (empty($_SESSION['loggedin']) || !isset($_SESSION['user_type'])) {
            header("Location: ../login.php");
            exit;
        }
        if ($_SESSION['user_type'] !== 'admin') {
            header("Location: ../login.php");
            exit;
        }
    }
    requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin_header.css"> <!-- Styles for the header navigation -->
    <link rel="stylesheet" href="../css/admin_index.css">  <!-- Styles for the dashboard page -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> <!-- Font Awesome for dashboard icons -->
</head>
<body>
    <?php 
        // Shows the admin header with the dashboard tab highlighted
        adminHeader('dashboard'); 
    ?>
    <h1>Welcome to the Admin Dashboard</h1>
    <p>
        <!-- Shows the username of the logged-in admin -->
        You are logged in as <?php echo $_SESSION['username']; ?>
    </p>
    <div class="dashboard-container">
        <ul class="dashboard-list">
            <!-- Each item is a dashboard shortcut with an icon and label -->
            <li onclick="location.href='voters.php'">
                <i class="fa-solid fa-users"></i>
                <span>Voters Management</span>
            </li>
            <li onclick="location.href='party.php'">
                <i class="fa-solid fa-flag"></i>
                <span>Party Management</span>
            </li>
            <li onclick="location.href='positions.php'">
                <i class="fa-solid fa-list-ol"></i>
                <span>Positions Management</span>
            </li>
            <li onclick="location.href='candidates.php'">
                <i class="fa-solid fa-user-tie"></i>
                <span>Candidate Management</span>
            </li>
            <li onclick="location.href='statistics.php'">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Vote Statistics</span>
            </li>
        </ul>
    </div>
</body>
</html>