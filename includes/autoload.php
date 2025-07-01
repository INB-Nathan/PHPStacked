<?php
/**
 * Simple autoloader for including all our class files
 */

$classFiles = [
    'file_handler.php',
    'candidate_manager.php',
    'position_manager.php',
    'party_manager.php',
    'election_manager.php',
    'admin_header.php',
    'database_manager.php',
    'user_manager.php',
    'security.php'
];

foreach ($classFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    } else {
        trigger_error("Missing required class file: {$file}", E_USER_WARNING);
    }
}

require_once __DIR__ . '/db_connect.php';
?>