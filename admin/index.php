<?php
require_once '../includes/autoload.php';
session_start();

$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

// Election Manager
$electionManager = new ElectionManager($pdo);
$electionManager->updateElectionStatuses();

if (empty($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
    <script src="../js/dashboard.js"></script>
</head>
<body>
    <?php adminHeader('dashboard', $csrf_token); ?>
    
    <h1>Welcome to the Admin Dashboard</h1>
    <p>
        You are logged in as <?php echo htmlspecialchars($_SESSION['username']); ?>
    </p>
    <div class="dashboard-container">
        <ul class="dashboard-list">
            <li data-href="voters.php">
                <i class="fa-solid fa-users"></i>
                <span>Voters Management</span>
            </li>
            <li data-href="party_position.php">
                <i class="fa-solid fa-flag"></i>
                <span>Party and Position Management</span>
            </li>
            <li data-href="candidates.php">
                <i class="fa-solid fa-user-tie"></i>
                <span>Candidate Management</span>
            </li>
            <li data-href="statistics.php">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Vote Statistics</span>
            </li>
            <li data-href="election.php">
                <i class="fa-solid fa-book-open"></i>
                <span>Elections</span>
            </li>
            <li data-href="database_settings.php">
                <i class="fa-solid fa-database"></i>
                <span>Database Settings</span>
            </li>
        </ul>
    </div>
</body>
</html>