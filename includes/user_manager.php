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
            // Use InputValidator for comprehensive validation
            $usernameValidation = InputValidator::validateUsername($username);
            if (!$usernameValidation['valid']) {
                return $usernameValidation['message'];
            }
            
            $emailValidation = InputValidator::validateEmail($email);
            if (!$emailValidation['valid']) {
                return $emailValidation['message'];
            }
            
            $passwordValidation = InputValidator::validatePassword($password);
            if (!$passwordValidation['valid']) {
                return $passwordValidation['message'];
            }
            
            $nameValidation = InputValidator::validateName($fullName);
            if (!$nameValidation['valid']) {
                return $nameValidation['message'];
            }
            
            // Sanitize inputs
            $username = InputValidator::sanitizeString($username);
            $email = InputValidator::sanitizeString($email);
            $fullName = InputValidator::sanitizeString($fullName);
            
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
    
    /**
     * Get the user by their ID
     * 
     * @param int $userId The user ID to fetch
     * @return array|false User data or false if not found
     */
    public function getUserById(int $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the last inserted ID (useful after adding a new user)
     * 
     * @return string|int Last inserted ID
     */
    public function getLastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get all elections a voter can participate in
     * 
     * @param int $voterId The voter's ID
     * @return array List of elections the voter can participate in
     */
    public function getVoterElections(int $voterId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.id, e.title, e.description, e.start_date, e.end_date, e.status 
                FROM elections e
                INNER JOIN voter_elections ve ON e.id = ve.election_id
                WHERE ve.voter_id = ?
                ORDER BY e.start_date DESC
            ");
            $stmt->execute([$voterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching voter elections: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update which elections a voter can participate in
     * 
     * @param int $voterId The voter's ID
     * @param array $electionIds Array of election IDs the voter should have access to
     * @return bool|string True on success, error message on failure
     */
    public function updateVoterElections(int $voterId, array $electionIds): bool|string {
        try {
            $this->pdo->beginTransaction();
            
            // First validate that the voter exists and is actually a voter
            $voterCheck = $this->pdo->prepare("SELECT id FROM users WHERE id = ? AND user_type = 'voter'");
            $voterCheck->execute([$voterId]);
            if (!$voterCheck->fetch()) {
                $this->pdo->rollBack();
                return "Invalid voter ID or user is not a voter";
            }
            
            // Delete all current election associations for this voter
            $deleteStmt = $this->pdo->prepare("DELETE FROM voter_elections WHERE voter_id = ?");
            $deleteStmt->execute([$voterId]);
            
            // If there are new elections to assign
            if (!empty($electionIds)) {
                // Prepare the insert statement for all new elections
                $insertSql = "INSERT INTO voter_elections (voter_id, election_id) VALUES ";
                $insertParams = [];
                $placeholders = [];
                
                // Build the query and parameters
                foreach ($electionIds as $electionId) {
                    if (!is_numeric($electionId)) continue; // Skip non-numeric IDs
                    $placeholders[] = "(?, ?)";
                    $insertParams[] = $voterId;
                    $insertParams[] = $electionId;
                }
                
                if (!empty($placeholders)) {
                    $insertSql .= implode(", ", $placeholders);
                    $insertStmt = $this->pdo->prepare($insertSql);
                    $insertStmt->execute($insertParams);
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating voter elections: " . $e->getMessage());
            return "Database error: " . $e->getMessage();
        }
    }
}
