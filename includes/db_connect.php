<?php
/**
 * Database Connection for PHPStacked - Election System
 * This file establishes a secure PDO connection to the database
 */

// Database configuration - consider moving to an environment file in production
$host = 'localhost';
$dbname = 'PHPStacked_DB';
$username = 'root';
$password = '';

// Connection options to enhance security
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // Throw exceptions for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Set default fetch mode to associative array
    PDO::ATTR_EMULATE_PREPARES => false,                   // Use real prepared statements
    PDO::MYSQL_ATTR_FOUND_ROWS => true,                    // Return found rows instead of affected rows
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,            // Use buffered queries for better memory management
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'    // Set character set
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // Log the error but don't expose details in production
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}