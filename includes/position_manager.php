<?php
/**
 * PositionManager Class - Handles position CRUD operations
 */
class PositionManager {
    protected $pdo;
    
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
    }
    
    public function getAll($election_id = null): array {
        try {
            if ($election_id) {
                $stmt = $this->pdo->prepare("SELECT * FROM positions WHERE election_id = ? ORDER BY position_name ASC");
                $stmt->execute([$election_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return $this->pdo
                    ->query("SELECT * FROM positions ORDER BY position_name ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log('Error fetching positions: ' . $e->getMessage());
            return [];
        }
    }
    
    public function add(string $position_name, ?int $election_id = null): bool {
        $stmt = $this->pdo->prepare("INSERT INTO positions (position_name, election_id) VALUES (?, ?)");
        return $stmt->execute([$position_name, $election_id]);
    }
    
    public function update(int $id, string $position_name, ?int $election_id = null): bool {
        $stmt = $this->pdo->prepare("UPDATE positions SET position_name=?, election_id=? WHERE id=?");
        return $stmt->execute([$position_name, $election_id, $id]);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM positions WHERE id=?");
        return $stmt->execute([$id]);
    }
}
