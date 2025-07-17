<?php
/**
 * SecurityHelper - Centralized security functions for PHPStacked
 */
class SecurityHelper {
    /**
     * Generate a cryptographically secure random token
     *
     * @param int $length Length of the token
     * @return string The generated token
     */
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Perform timing-safe comparison of two strings
     *
     * @param string $known_string The known string
     * @param string $user_string The user-supplied string
     * @return bool True if the strings are equal
     */
    public static function timingSafeEquals(string $known_string, string $user_string): bool {
        return hash_equals($known_string, $user_string);
    }
    
    /**
     * Hash a password using the configured algorithm
     *
     * @param string $password The password to hash
     * @return string The hashed password
     */
    public static function hashPassword(string $password): string {
        $config = require __DIR__ . '/config.php';
        return password_hash($password, $config['password']['hash_algorithm'], $config['password']['hash_options']);
    }
    
    /**
     * Verify a password against a hash
     *
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     * @return bool True if the password is valid
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if a password needs rehashing
     *
     * @param string $hash The hash to check
     * @return bool True if the hash needs rehashing
     */
    public static function passwordNeedsRehash(string $hash): bool {
        $config = require __DIR__ . '/config.php';
        return password_needs_rehash($hash, $config['password']['hash_algorithm'], $config['password']['hash_options']);
    }
    
    /**
     * Create a secure filename for uploaded files
     *
     * @param string $originalName Original filename
     * @param string $extension File extension
     * @return string Secure filename
     */
    public static function secureFilename(string $originalName, string $extension): string {
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $sanitized = substr($sanitized, 0, 50); // Limit length
        return $sanitized . '_' . self::generateToken(8) . '.' . $extension;
    }
    
    /**
     * Rate limiting for logins and other sensitive actions
     *
     * @param string $key Identifier for the rate limit (e.g., IP address or username)
     * @param string $action The action being rate limited (e.g., 'login', 'password_reset')
     * @return bool True if action is allowed, false if rate limited
     */
    public static function checkRateLimit(string $key, string $action = 'login'): bool {
        $config = require __DIR__ . '/config.php';
        
        if (!$config['rate_limiting']['enabled']) {
            return true;
        }
        
        $maxAttempts = $config['rate_limiting']['max_login_attempts'];
        $lockoutTime = $config['rate_limiting']['lockout_time'];
        
        $rateLimit = self::getRateLimitData($key, $action);
        
        // If locked out and lockout period not expired
        if ($rateLimit['locked_until'] > time()) {
            return false;
        }
        
        // Reset if outside the window
        if ($rateLimit['last_attempt'] < (time() - $lockoutTime)) {
            self::resetRateLimit($key, $action);
            return true;
        }
        
        // Increment attempt count
        $rateLimit['attempts']++;
        
        // Check if max attempts reached
        if ($rateLimit['attempts'] >= $maxAttempts) {
            $rateLimit['locked_until'] = time() + $lockoutTime;
        }
        
        $rateLimit['last_attempt'] = time();
        
        // Store updated rate limit data
        self::storeRateLimitData($key, $action, $rateLimit);
        
        return $rateLimit['attempts'] < $maxAttempts;
    }
    
    /**
     * Reset rate limiting for a key and action
     *
     * @param string $key Identifier for the rate limit
     * @param string $action The action being rate limited
     */
    public static function resetRateLimit(string $key, string $action = 'login'): void {
        self::storeRateLimitData($key, $action, [
            'attempts' => 0,
            'last_attempt' => 0,
            'locked_until' => 0
        ]);
    }
    
    /**
     * Get rate limit data from session
     *
     * @param string $key Identifier for the rate limit
     * @param string $action The action being rate limited
     * @return array Rate limit data
     */
    private static function getRateLimitData(string $key, string $action): array {
        $sessionKey = "rate_limit_{$action}_{$key}";
        
        if (isset($_SESSION[$sessionKey])) {
            return $_SESSION[$sessionKey];
        }
        
        return [
            'attempts' => 0,
            'last_attempt' => 0,
            'locked_until' => 0
        ];
    }
    
    /**
     * Store rate limit data in session
     *
     * @param string $key Identifier for the rate limit
     * @param string $action The action being rate limited
     * @param array $data Rate limit data
     */
    private static function storeRateLimitData(string $key, string $action, array $data): void {
        $sessionKey = "rate_limit_{$action}_{$key}";
        $_SESSION[$sessionKey] = $data;
    }
}
?>
