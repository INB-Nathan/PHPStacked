<?php
/**
 * PartyManager Class - Handles party CRUD operations
 */
class PartyManager {
    protected $pdo;
    
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
    }
    
    public function getAll($election_id = null): array {
        if ($election_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM parties WHERE election_id = ? OR election_id IS NULL ORDER BY name ASC");
            $stmt->execute([$election_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->pdo
                ->query("SELECT * FROM parties ORDER BY name ASC")
                ->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    public function add(string $name, ?string $desc = null, ?int $election_id = null): bool {
        $stmt = $this->pdo->prepare("INSERT INTO parties (name, description, election_id) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $desc, $election_id]);
    }

    public function update(int $id, string $name, ?string $desc = null, ?int $election_id = null): bool {
        $stmt = $this->pdo->prepare("UPDATE parties SET name=?, description=?, election_id=? WHERE id=?");
        return $stmt->execute([$name, $desc, $election_id, $id]);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM parties WHERE id=?");
        return $stmt->execute([$id]);
    }
}