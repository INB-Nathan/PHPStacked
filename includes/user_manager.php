<?php
/**
 * UserManager Class - Handles user CRUD operations
 */
class UserManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAllUsersByType(string $userType): array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_type = ?");
            $stmt->execute([$userType]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    
    public function addUser(
        string $username, 
        string $email, 
        string $password, 
        string $fullName, 
        string $userType = 'voter', 
        bool $isActive = true
    ): bool|string {
        try {
            // Basic sanitization and validation
            if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
                return "All fields are required.";
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                return "Username can only contain letters, numbers, and underscores.";
            }
            
            if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $fullName)) {
                return "Name contains invalid characters.";
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return "Please enter a valid email address.";
            }
            
            if (strlen($username) < 3) {
                return "Username must be at least 3 characters.";
            }
            
            if (strlen($password) < 6) {
                return "Password must be at least 6 characters.";
            }
            
            // Check if username already exists
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                return "Username is already taken.";
            }
            
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                return "Email is already registered.";
            }
            
            // Hash password and insert user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO `users` 
                (`username`, `email`, `pass_hash`, `full_name`, `user_type`, `is_active`) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $username, 
                $email, 
                $passwordHash, 
                $fullName, 
                $userType, 
                $isActive ? 1 : 0
            ]);
            
            return true;
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

    public function deleteUser(int $userId, string $userType = null): bool|string {
        try {
            $sql = "DELETE FROM users WHERE id = ?";
            $params = [$userId];
            
            // If userType is specified, add it to the WHERE clause
            if ($userType !== null) {
                $sql .= " AND user_type = ?";
                $params[] = $userType;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result && $stmt->rowCount() > 0) {
                return true;
            } else {
                return "No user found with the given ID";
            }
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
    
    public function updateUser(int $userId, array $data): bool|string {
        try {
            $allowedFields = ['username', 'email', 'full_name', 'is_active'];
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            // If no valid fields to update
            if (empty($updates)) {
                return "No valid fields to update";
            }
            
            // Add user ID to params
            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
    
    public function updatePassword(int $userId, string $newPassword): bool|string {
        try {
            if (strlen($newPassword) < 6) {
                return "Password must be at least 6 characters.";
            }
            
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET pass_hash = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $userId]);
            
            return true;
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
}
?>