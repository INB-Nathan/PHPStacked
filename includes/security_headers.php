<?php
/**
 * Security Headers Middleware - Sets security headers for the application
 */

if (!defined('APP_LOADED')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

$config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

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

$cspHeaderValue = "";
foreach ($cspDirectives as $directive => $sources) {
    $cspHeaderValue .= $directive . " " . implode(" ", $sources) . "; ";
}
header("Content-Security-Policy: $cspHeaderValue");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()");

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

header_remove("X-Powered-By");
?>