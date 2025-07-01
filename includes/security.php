<?php
class SecurityManager {
    private $pdo;
    private $sessionTimeout = 15;

    public function __construct(PDO $pdo, $timeoutMinutes = 15) {
        $this->pdo = $pdo;
        $this->sessionTimeout = $timeoutMinutes;
    }
    /*
    generatecsrftoken is para sa security function natin, bali copy paste lang to sa boiler plate in github pero nag change ako ng kaonting logic.
    basically csrf is cross-site request forgery, attack siya na kapag unauthorized command is transmitted from a user that web application trusts.
    by using a token na stored sa website ng user, ung system natin pwede iverify if ung form submissions orignate from legitimate user sessions and not machines.
    */
    public function generateCSRFToken() {
        // checheck muna if naka define na si csrf_token, but ung if statement na to ichecheck if null siya, if null siya gagawa siya ng csrf_token based from random_bytes.
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        // pero if di siya na detect na null siya, return na ung value ni csrf_token na based sa $_session array.
        return $_SESSION['csrf_token'];
    }
    
    /*
    validatecsrftoken generally ginagawa nya lang is self explanatory, chinecheck niya lang ung csrftoken and mag rereturn siya ng boolean
    if true ibigsabihin neto nag matmatch ung tokens ng action and ng user.
    first conditional checks if ung csrf_token is set, if wala and ung statement nya is && false agad ung kakalabasan nya
    then the second is ung hash_equals, bali ginagawa neto icocompare niya lang ung user string sa form submission sa known string sa session array.
    */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /*
    secure session is just for configuring session security parameters para iprevent ung session vulnerabilities.
    */
    public function secureSession() {
        // php will not accept an uninitialized session id.
        ini_set('session.use_strict_mode', 1);
        // forces php to only use cookies for storing the session id, para bawal ung session ids passed in urls.
        ini_set('session.use_only_cookies', 1);
        // session cookie dapat http only lang, this stops xss exploits na kukunin ung session cookie and gamitin nila for untracable acts!
        ini_set('session.cookie_httponly', 1);
        
        // wat dis do is if ung server connection is https and naka on ung https value gagawin nya is ung session cookie is only sent through secure https channels.
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        // tracks the last activity time with current timestamp.
        $_SESSION['last_activity'] = time();
        
        // ensures na nireregen ung session para di masession hijack.
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } else if (time() - $_SESSION['last_regeneration'] > 1800) {
            $this->regenerateSession();
        }
    }
    
    // regenerate session ginagawa is papaltan nya ng bagong sessiond data ung $_session array if called
    public function regenerateSession() {
        $old_session_data = $_SESSION;
        
        session_regenerate_id(true);
        
        $_SESSION = $old_session_data;
        $_SESSION['last_regeneration'] = time();
    }

    // basically just checks if need na isession timeout dahil sa inactivity.
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
}
?>