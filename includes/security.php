<?php
class SecurityManager {
    private $pdo;
    private $sessionTimeout;
    private $config;

    public function __construct(PDO $pdo, $timeoutMinutes = null) {
        $this->pdo = $pdo;
        
        $this->config = require __DIR__ . '/config.php';
        
        $this->sessionTimeout = $timeoutMinutes ?? $this->config['session']['timeout_minutes'];

        if (!class_exists('SecurityHelper')) {
            require_once __DIR__ . '/security_helper.php';
        }
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = SecurityHelper::generateToken(
                $this->config['csrf']['token_length']
            );
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && SecurityHelper::timingSafeEquals($_SESSION['csrf_token'], $token);
    }

    public function secureSession() {
        ini_set('session.use_strict_mode', $this->config['session']['strict_mode'] ? 1 : 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', $this->config['session']['http_only'] ? 1 : 0);
        ini_set('session.cookie_samesite', $this->config['session']['same_site']);
        
        if ($this->config['session']['secure_cookies'] && 
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
            ini_set('session.cookie_secure', 1);
        }
        
        $_SESSION['last_activity'] = time();
        
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } else if (time() - $_SESSION['last_regeneration'] > $this->config['session']['regenerate_interval']) {
            $this->regenerateSession();
        }
    }
        public function regenerateSession() {
        $old_session_data = $_SESSION;
        
        session_regenerate_id(true);
        
        $_SESSION = $old_session_data;
        $_SESSION['last_regeneration'] = time();
    }

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
    
    public function checkLoginRateLimit($identifier) {
        return SecurityHelper::checkRateLimit($identifier, 'login');
    }
    
    public function resetLoginRateLimit($identifier) {
        SecurityHelper::resetRateLimit($identifier, 'login');
    }
}
?>