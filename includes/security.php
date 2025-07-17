<?php
class SecurityManager {
    private $pdo;
    private $sessionTimeout;
    private $config;

    public function __construct(PDO $pdo, $timeoutMinutes = null) {
        $this->pdo = $pdo;
        
        // Load configuration
        $this->config = require __DIR__ . '/config.php';
        
        // Set session timeout from config or parameter
        $this->sessionTimeout = $timeoutMinutes ?? $this->config['session']['timeout_minutes'];
        
        // Include the SecurityHelper class if not already included
        if (!class_exists('SecurityHelper')) {
            require_once __DIR__ . '/security_helper.php';
        }
    }
    
    /**
     * Generate CSRF token for form protection
     * 
     * @return string The CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = SecurityHelper::generateToken(
                $this->config['csrf']['token_length']
            );
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token from form submission
     * 
     * @param string $token The token to validate
     * @return bool True if the token is valid
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && SecurityHelper::timingSafeEquals($_SESSION['csrf_token'], $token);
    }

    /**
     * Configure secure session settings
     */
    public function secureSession() {
        // PHP will not accept an uninitialized session id
        ini_set('session.use_strict_mode', $this->config['session']['strict_mode'] ? 1 : 0);
        
        // Force PHP to only use cookies for storing the session id
        ini_set('session.use_only_cookies', 1);
        
        // Session cookie should be HTTP only
        ini_set('session.cookie_httponly', $this->config['session']['http_only'] ? 1 : 0);
        
        // Set SameSite attribute
        ini_set('session.cookie_samesite', $this->config['session']['same_site']);
        
        // Set secure flag for HTTPS connections
        if ($this->config['session']['secure_cookies'] && 
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
            ini_set('session.cookie_secure', 1);
        }
        
        // Track last activity time
        $_SESSION['last_activity'] = time();
        
        // Regenerate session if needed
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } else if (time() - $_SESSION['last_regeneration'] > $this->config['session']['regenerate_interval']) {
            $this->regenerateSession();
        }
    }
    
    /**
     * Regenerate session ID to prevent session fixation
     */
    public function regenerateSession() {
        $old_session_data = $_SESSION;
        
        session_regenerate_id(true);
        
        $_SESSION = $old_session_data;
        $_SESSION['last_regeneration'] = time();
    }

    /**
     * Check for session timeout due to inactivity
     * 
     * @param string $redirectUrl URL to redirect to on timeout
     * @return bool True if session timed out
     */
    public function checkSessionTimeout($redirectUrl = '../login.php') {
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['loggedin'])) {
            return false;
        }
        
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            $timeoutSeconds = $this->sessionTimeout * 60;
            
            if ($inactiveTime > $timeoutSeconds) {
                session_unset();
                session_destroy();
                
                if (!headers_sent() && !empty($redirectUrl)) {
                    header("Location: $redirectUrl?msg=timeout");
                    exit;
                }
                
                return true;
            }
        }
        
        $_SESSION['last_activity'] = time();
        
        return false;
    }
    
    /**
     * Verify a password against a hash and handle rehashing if needed
     * 
     * @param string $password The plain text password
     * @param string $hash The password hash
     * @param int $userId User ID for rehashing
     * @return bool True if password is valid
     */
    public function verifyPassword($password, $hash, $userId = null) {
        $valid = SecurityHelper::verifyPassword($password, $hash);
        
        // If password is valid and needs rehashing, update it
        if ($valid && $userId && SecurityHelper::passwordNeedsRehash($hash)) {
            $newHash = SecurityHelper::hashPassword($password);
            
            try {
                $stmt = $this->pdo->prepare("UPDATE users SET pass_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $userId]);
            } catch (PDOException $e) {
                error_log("Failed to update password hash: " . $e->getMessage());
            }
        }
        
        return $valid;
    }
    
    /**
     * Check rate limiting for login attempts
     * 
     * @param string $identifier Username or IP address
     * @return bool True if not rate limited
     */
    public function checkLoginRateLimit($identifier) {
        return SecurityHelper::checkRateLimit($identifier, 'login');
    }
    
    /**
     * Reset login rate limit for a user
     * 
     * @param string $identifier Username or IP address
     */
    public function resetLoginRateLimit($identifier) {
        SecurityHelper::resetRateLimit($identifier, 'login');
    }
}
?>
?>