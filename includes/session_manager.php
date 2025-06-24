<?php

/**
 * Session Manager for PHPStacked - Election System
 */
require_once 'db_connect.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

class SessionManager
{
    private $pdo;
    private $session_timeout = 1800;
    private $max_inactive_time = 900;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->configureSession();
    }

    private function configureSession()
    {
        ini_set('session.gc_maxlifetime', $this->session_timeout);
        session_name('PHPStacked_Election-System');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSessionId();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            $this->regenerateSessionId();
        }
    }

    public function startUserSession($user_id, $username, $user_type)
    {
        $session_id = session_id();
        $expires = date('Y-m-d H:i:s', time() + $this->session_timeout);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        try {
            $sql = "UPDATE users SET session_id = ?, session_expires = ?, last_login = NOW(), ip_address = ?, WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$session_id, $expires, $ip_address, $user_id]);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $ip_address;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            return true;
        } catch (PDOException $e) {
            error_log("Session start error: " . $e->getMessage());
            return false;
        }
    }

    public function regenerateSessionId()
    {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();

        if (isset($_SESSION['user_id'])) {
            $sql = "UPDATE users SET session_id = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([session_id(), $_SESSION['user_id']]);
        }
    }

    public function validateSession()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $this->max_inactive_time) {
            $this->destroySession();
            return false;
        }

        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            $this->destroySession();
            return false;
        }

        $sql = "SELECT session_expires, is_active FROM users WHERE id = ? AND session_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], session_id()]);
        $result = $stmt->fetch();

        if (!$result || !$result['is_active'] || strtotime($result['session_expires']) < time()) {
            $this->destroySession();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public function destroySession()
    {

        if (isset($_SESSION['user_id'])) {
            $sql = "UPDATE users SET session_id = NULL, session_expires = NULL WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
        }

        $_SESSION = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
    }

    public function isLoggedIn()
    {
        return $this->validateSession();
    }

    public function requireLogin($redirect_url = 'login.php')
    {
        if (!$this->isLoggedIn()) {
            header("Location: $redirect_url");
            exit;
        }
    }

    public function requireAdmin()
    {
        $this->requireLogin();
        if ($_SESSION['user_type'] !== 'admin') {
            header("HTTP/1.1 403 Forbidden");
            die("Access denied. Admin Privileges required.");
        }
    }

    public function getTimeRemaining()
    {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }

        $remaining = $this->max_inactive_time - (time() - $_SESSION['last_activity']);
        return max(0, $remaining);
    }

    public function getCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($token, $_SESSION['csrf_token']);
    }
}

$sessionManager = new SessionManager($pdo);
