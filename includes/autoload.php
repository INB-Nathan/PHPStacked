<?php
/**
 * Simple autoloader for including all our class files
 */

// Define that application is loaded for security headers
if (!defined('APP_LOADED')) {
    define('APP_LOADED', true);
}

// Include security headers first
$securityHeadersPath = __DIR__ . '/security_headers.php';
if (file_exists($securityHeadersPath)) {
    require_once $securityHeadersPath;
}

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Include security helpers and validators
$helperFiles = [
    'config.php',
    'security_helper.php',
    'input_validator.php'
];

foreach ($helperFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

$classFiles = [
    'file_handler.php',
    'candidate_manager.php',
    'position_manager.php',
    'party_manager.php',
    'election_manager.php',
    'admin_header.php',
    'database_manager.php',
    'user_manager.php',
    'security.php',
    'vote_manager.php',
    'statistics_manager.php'
];

foreach ($classFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    } else {
        trigger_error("Missing required class file: {$file}", E_USER_WARNING);
    }
}