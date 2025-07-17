<?php
/**
 * Security Headers Middleware - Sets security headers for the application
 * Include this file at the top of your pages before any output
 */

// Prevent this file from being accessed directly
if (!defined('APP_LOADED')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

// Content Security Policy
// Load CSP configuration from config file if available
$config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

// Default CSP directives if not in config
$cspDirectives = [
    "default-src" => ["'self'"],
    "script-src" => ["'self'", "https://cdnjs.cloudflare.com"],
    "style-src" => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.googleapis.com", "'unsafe-inline'"],
    "img-src" => ["'self'", "data:"],
    "font-src" => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com"],
    "connect-src" => ["'self'"],
    "form-action" => ["'self'"],
    "frame-ancestors" => ["'self'"],
    "base-uri" => ["'self'"],
    "object-src" => ["'none'"],
    "media-src" => ["'self'"]
];

// Build CSP header string
$cspHeaderValue = "";
foreach ($cspDirectives as $directive => $sources) {
    $cspHeaderValue .= $directive . " " . implode(" ", $sources) . "; ";
}

// Set Content Security Policy
header("Content-Security-Policy: $cspHeaderValue");

// X-Content-Type-Options to prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// X-Frame-Options to prevent clickjacking
header("X-Frame-Options: SAMEORIGIN");

// X-XSS-Protection for legacy browsers
header("X-XSS-Protection: 1; mode=block");

// Referrer Policy to control information in HTTP Referer header
header("Referrer-Policy: strict-origin-when-cross-origin");

// Permissions Policy to limit browser features
header("Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()");

// Set Strict-Transport-Security header if using HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Cache control for sensitive pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Remove X-Powered-By header if sent by PHP
header_remove("X-Powered-By");
?>
