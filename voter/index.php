<?php
require_once '../includes/db_connect.php';
// palagi need to sa taas ng php script
session_start();

function requireLogin() {
    if (empty($_SESSION['loggedin']) || !isset($_SESSION['user_type'])) {
        header("Location: ../login.php");
        exit;
    }
    if ($_SESSION['user_type'] !== 'voter') {
        header("Location: ../login.php");
        exit;
    }
}

requireLogin();

?>