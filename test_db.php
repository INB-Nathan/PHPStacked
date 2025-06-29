<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php';


echo password_hash('pogiako123', PASSWORD_DEFAULT);

if (isset($pdo) && $pdo instanceof PDO) {
    echo "Connection successful!";
} else {
    echo "Connection failed!";
}
?>