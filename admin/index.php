<?php
    require_once '../includes/db_connect.php';
    require_once '../includes/admin_header.php';
    session_start();

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
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php adminHeader('dashboard'); ?>
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    
    <h1>Welcome to the Admin Dashboard</h1>
    <p>
        You are logged in as <?php echo $_SESSION['username']; ?>
    </p>
    <div class="dashboard-container">
        <ul class="dashboard-list">
            <li onclick="location.href='voters.php'">
                <i class="fa-solid fa-users"></i>
                <span>Voters Management</span>
            </li>
            <li onclick="location.href='party_position.php'">
                <i class="fa-solid fa-flag"></i>
                <span>Party and Position Management</span>
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
    <script>
        document.getElementById('logoutNavBtn').onclick = function(e) {
            e.preventDefault();
            document.getElementById('logoutModal').classList.add('active');
        };
        document.getElementById('cancelLogoutBtn').onclick = function() {
            document.getElementById('logoutModal').classList.remove('active');
        };
        document.getElementById('logoutModal').onclick = function(e) {
            if (e.target === this) this.classList.remove('active');
        };
    </script>
</body>
</html>