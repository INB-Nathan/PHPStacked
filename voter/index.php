<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

// Only allow access if logged in and user is voter
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voter Dashboard</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php voterHeader('dashboard'); ?>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
    <p>You are logged in as a voter.</p>
    <div class="dashboard-container">
        <ul class="dashboard-list">
            <li onclick="location.href='view_election.php'">
                <i class="fa-solid fa-chart-simple"></i>
                <span>View Election</span>
            </li>
            <li onclick="location.href='vote.php'">
                <i class="fa-solid fa-vote-yea"></i>
                <span>Vote</span>
            </li>
        </ul>
    </div>
</body>
</html>