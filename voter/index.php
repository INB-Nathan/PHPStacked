<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

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
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/voter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
</head>
<body>
    <?php voterHeader('dashboard'); ?>
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
    <p>You are logged in as a voter.</p>
    <div class="dashboard-container">
        <ul class="dashboard-list">
            <li>
                <a href="election_results.php">
                    <i class="fa-solid fa-chart-simple"></i>
                    <span>View Election Results</span>
                </a>
            </li>
            <li>
                <a href="available_elections.php">
                    <i class="fa-solid fa-vote-yea"></i>
                    <span>Available Elections</span>
                </a>
            </li>
        </ul>
    </div>
</body>
</html>