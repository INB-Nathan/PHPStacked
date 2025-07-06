<?php
require_once __DIR__ . '/db_connect.php';

// --- Party CRUD ---

class Party {
    protected $pdo;
    
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
    }
    
    public function getAll($election_id = null): array {
        if ($election_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM parties WHERE election_id = ? ORDER BY name ASC");
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

// --- Position CRUD ---

class Position {
    protected $pdo;
    
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
    }
    
    public function getAll($election_id = null): array {
        if ($election_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM positions WHERE election_id = ? ORDER BY position_name ASC");
            $stmt->execute([$election_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->pdo
                ->query("SELECT * FROM positions ORDER BY position_name ASC")
                ->fetchAll(PDO::FETCH_ASSOC);
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

// --- Election CRUD ---

class Election {
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function addElection(array $data): bool|string {
        try {
            $sql = "
                INSERT INTO elections
                    (title, description, start_date, end_date, status, max_votes_per_user)
                VALUES
                    (:title, :description, :start_date, :end_date, :status, :max_votes)
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'title'       => $data['title'],
                'description' => $data['description'],
                'start_date'  => $data['start_date'],
                'end_date'    => $data['end_date'],
                'status'      => $data['status'],
                'max_votes'   => $data['max_votes_per_user'],
            ]);
            return true;
        } catch (PDOException $e) {
            return 'DB Error (addElection): ' . $e->getMessage();
        }
    }

    public function updateElection(int $id, array $data): bool|string {
        try {
            $sql = "
                UPDATE elections SET
                    title               = :title,
                    description         = :description,
                    start_date          = :start_date,
                    end_date            = :end_date,
                    status              = :status,
                    max_votes_per_user  = :max_votes
                WHERE id = :id
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'title'       => $data['title'],
                'description' => $data['description'],
                'start_date'  => $data['start_date'],
                'end_date'    => $data['end_date'],
                'status'      => $data['status'],
                'max_votes'   => $data['max_votes_per_user'],
                'id'          => $id
            ]);
            return true;
        } catch (PDOException $e) {
            return 'DB Error (updateElection): ' . $e->getMessage();
        }
    }

    public function deleteElection(int $id): bool|string {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM elections WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return true;
        } catch (PDOException $e) {
            return 'DB Error (deleteElection): ' . $e->getMessage();
        }
    }

    public function getAll(): array {
        $sql = "SELECT * FROM elections ORDER BY start_date DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|null|string {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM elections WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return 'DB Error (getById): ' . $e->getMessage();
        }
    }

    /**
     * Fetches candidates and their vote counts for a specific election.
     *
     * @param int $electionId The ID of the election.
     * @return array An array of candidate data, including their vote counts.
     */
    public function getCandidatesWithVotes(int $electionId): array {
        $sql = "
            SELECT
                c.id,
                c.name,
                c.description,
                c.photo,
                c.vote_count,
                p.position_name,
                pa.name AS party_name
            FROM
                candidates c
            JOIN
                positions p ON c.position_id = p.id
            LEFT JOIN
                parties pa ON c.party_id = pa.id
            WHERE
                c.election_id = :election_id
            ORDER BY
                p.position_name ASC, c.name ASC;
        ";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['election_id' => $electionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getCandidatesWithVotes: " . $e->getMessage());
            return []; // Return an empty array on error
        }
    }
}
?>