<?php
/**
 * InputValidator Class - Provides centralized input validation functions
 */
class InputValidator {
    /**
     * Validate a username
     *
     * @param string $username The username to validate
     * @return array ['valid' => bool, 'message' => string] Result with validation message
     */
    public static function validateUsername(string $username): array {
        $username = trim($username);
        
        if (empty($username)) {
            return ['valid' => false, 'message' => 'Username is required'];
        }
        
        if (strlen($username) < 3) {
            return ['valid' => false, 'message' => 'Username must be at least 3 characters'];
        }
        
        if (strlen($username) > 50) {
            return ['valid' => false, 'message' => 'Username cannot exceed 50 characters'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
        }
        
        return ['valid' => true, 'message' => 'Username is valid'];
    }
    
    /**
     * Validate an email address
     *
     * @param string $email The email to validate
     * @return array ['valid' => bool, 'message' => string] Result with validation message
     */
    public static function validateEmail(string $email): array {
        $email = trim($email);
        
        if (empty($email)) {
            return ['valid' => false, 'message' => 'Email is required'];
        }
        
        if (strlen($email) > 100) {
            return ['valid' => false, 'message' => 'Email cannot exceed 100 characters'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Please enter a valid email address'];
        }
        
        return ['valid' => true, 'message' => 'Email is valid'];
    }
    
    /**
     * Validate a password
     *
     * @param string $password The password to validate
     * @return array ['valid' => bool, 'message' => string] Result with validation message
     */
    public static function validatePassword(string $password): array {
        if (empty($password)) {
            return ['valid' => false, 'message' => 'Password is required'];
        }
        
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Password must be at least 8 characters'];
        }
        
        // Check for password complexity
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasDigit = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z\d]/', $password);
        
        if (!($hasLower && $hasUpper && $hasDigit) && !$hasSpecial) {
            return [
                'valid' => false, 
                'message' => 'Password must contain at least three of the following: lowercase letters, uppercase letters, numbers, and special characters'
            ];
        }
        
        return ['valid' => true, 'message' => 'Password is valid'];
    }
    
    /**
     * Validate a name
     *
     * @param string $name The name to validate
     * @return array ['valid' => bool, 'message' => string] Result with validation message
     */
    public static function validateName(string $name): array {
        $name = trim($name);
        
        if (empty($name)) {
            return ['valid' => false, 'message' => 'Name is required'];
        }
        
        if (strlen($name) > 255) {
            return ['valid' => false, 'message' => 'Name cannot exceed 255 characters'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_ \'.-]+$/', $name)) {
            return ['valid' => false, 'message' => 'Name contains invalid characters'];
        }
        
        return ['valid' => true, 'message' => 'Name is valid'];
    }
    
    /**
     * Validate a date
     *
     * @param string $date The date to validate (Y-m-d H:i:s format)
     * @return array ['valid' => bool, 'message' => string] Result with validation message
     */
    public static function validateDate(string $date): array {
        if (empty($date)) {
            return ['valid' => false, 'message' => 'Date is required'];
        }
        
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        $valid = $d && $d->format('Y-m-d H:i:s') === $date;
        
        if (!$valid) {
            $d = DateTime::createFromFormat('Y-m-d\TH:i', $date);
            $valid = $d && $d->format('Y-m-d\TH:i') === $date;
        }
        
        if (!$valid) {
            return ['valid' => false, 'message' => 'Invalid date format'];
        }
        
        return ['valid' => true, 'message' => 'Date is valid'];
    }
    
    /**
     * Sanitize and validate integer ID
     *
     * @param mixed $id The ID to validate
     * @param int $min Minimum acceptable value (default 1)
     * @return int|null The validated integer or null if invalid
     */
    public static function validateId($id, int $min = 1): ?int {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        
        if ($id === false || $id < $min) {
            return null;
        }
        
        return $id;
    }
    
    /**
     * Clean HTML content to prevent XSS
     *
     * @param string $html The HTML content to clean
     * @return string Sanitized HTML
     */
    public static function sanitizeHtml(string $html): string {
        // Remove all potentially dangerous HTML tags and attributes
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize a string for safe database storage (but not for HTML output)
     *
     * @param string $input The input to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeString(string $input): string {
        $input = trim($input);
        // Remove null bytes and control characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        return $input;
    }
    
    /**
     * Escape data for safe HTML output
     * 
     * @param mixed $data Data to be escaped
     * @return mixed Escaped data
     */
    public static function escapeOutput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::escapeOutput($value);
            }
            return $data;
        }
        
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
}
?>
