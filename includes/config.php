<?php
/**
 * PHPStacked Security Configuration
 * This file contains centralized security configuration settings
 */

// Security Settings
return [
    // Session Security
    'session' => [
        'timeout_minutes' => 15,
        'regenerate_interval' => 1800, // 30 minutes
        'strict_mode' => true,
        'http_only' => true,
        'secure_cookies' => true, // Set to false if not using HTTPS
        'same_site' => 'Lax', // Options: Strict, Lax, None
    ],
    
    // CSRF Protection
    'csrf' => [
        'enabled' => true,
        'token_length' => 32,
    ],
    
    // Password Policy
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => true,
        'hash_algorithm' => PASSWORD_BCRYPT,
        'hash_options' => ['cost' => 12]
    ],
    
    // File Upload Security
    'file_upload' => [
        'max_size' => 2097152, // 2MB in bytes
        'allowed_mime_types' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif'
        ],
        'allowed_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'gif'
        ],
    ],
    
    // Database Security
    'database' => [
        'use_prepared_statements' => true,
        'emulate_prepares' => false,
    ],
    
    // Error Handling
    'errors' => [
        'log_errors' => true,
        'display_errors' => false, // Set to false in production
    ],
    
    // Rate Limiting
    'rate_limiting' => [
        'enabled' => true,
        'max_login_attempts' => 5,
        'lockout_time' => 900, // 15 minutes in seconds
    ]
];
?>
