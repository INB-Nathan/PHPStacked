<?php
/**
 * DatabaseManager Class - Handles database maintenance operations
 */
class DatabaseManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function verifyAdminPassword(string $password): bool {
        try {
            $stmt = $this->pdo->prepare("SELECT pass_hash FROM users WHERE username = 'admin' AND user_type = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $admin && password_verify($password, $admin['pass_hash']);
        } catch (PDOException $e) {
            error_log("Admin password verification error: " . $e->getMessage());
            return false;
        }
    }
    
    public function clearUsers(): array {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_type != 'admin'");
            $stmt->execute();
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Voter accounts have been cleared. {$count} records deleted.",
                'count' => $count
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "Error clearing users: " . $e->getMessage()
            ];
        }
    }
    
    public function clearElections(): array {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM elections");
            $stmt->execute();
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Elections and related data have been cleared. {$count} elections deleted.",
                'count' => $count
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "Error clearing elections: " . $e->getMessage()
            ];
        }
    }
    

    public function clearPositions(): array {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM positions");
            $stmt->execute();
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Positions have been cleared. {$count} positions deleted.",
                'count' => $count
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "Error clearing positions: " . $e->getMessage()
            ];
        }
    }
    
    public function clearParties(): array {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM parties WHERE name != 'Independent'");
            $stmt->execute();
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Parties have been cleared. {$count} parties deleted.",
                'count' => $count
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "Error clearing parties: " . $e->getMessage()
            ];
        }
    }
    
    public function clearVotes(): array {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM votes");
            $stmt->execute();
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Votes have been cleared. {$count} votes deleted.",
                'count' => $count
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => "Error clearing votes: " . $e->getMessage()
            ];
        }
    }
    
    public function resetDatabase(): array {
        try {
            $this->pdo->beginTransaction();
            
            // Delete in proper order to avoid foreign key constraints
            $this->pdo->exec("DELETE FROM votes");
            $this->pdo->exec("DELETE FROM candidates");
            $this->pdo->exec("DELETE FROM positions");
            $this->pdo->exec("DELETE FROM parties WHERE name != 'Independent'");
            $this->pdo->exec("DELETE FROM elections");
            $this->pdo->exec("DELETE FROM users WHERE user_type != 'admin'");
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Database has been reset to initial state."
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => "Error resetting database: " . $e->getMessage()
            ];
        }
    }
    
    public function getRecordCounts(): array {
        try {
            return [
                'users' => $this->pdo->query("SELECT COUNT(*) FROM users WHERE user_type != 'admin'")->fetchColumn(),
                'elections' => $this->pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn(),
                'positions' => $this->pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn(),
                'parties' => $this->pdo->query("SELECT COUNT(*) FROM parties WHERE name != 'Independent'")->fetchColumn(),
                'votes' => $this->pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn(),
            ];
        } catch (PDOException $e) {
            error_log("Error getting record counts: " . $e->getMessage());
            return [
                'users' => 'Error',
                'elections' => 'Error',
                'positions' => 'Error',
                'parties' => 'Error',
                'votes' => 'Error',
            ];
        }
    }
}
?>