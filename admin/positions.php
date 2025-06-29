<?php
require_once '../includes/admin_header.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Positions Management</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
</head>
<body>
    <?php adminHeader('positions'); ?>
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    <h1>Positions Management</h1>
    <!-- Positions management content goes here -->

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