<?php
/**
 * ElectionManager Class - Handles election CRUD operations
 */
class ElectionManager {
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
                  title             = :title,
                  description       = :description,
                  start_date        = :start_date,
                  end_date          = :end_date,
                  status            = :status,
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
            return [];
        }
    }
}
?>