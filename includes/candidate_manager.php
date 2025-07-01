<?php

/**
 * CandidateManager Class - Handles candidate CRUD operations
 */
class CandidateManager
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addCandidate(
        int $electionId,
        string $name,
        int $position_id,
        ?int $party_id,
        string $description,
        ?string $photoPath = null
    ): bool|string {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO candidates
                 (election_id, name, position_id, party_id, bio, photo)
                 VALUES
                 (:election_id, :name, :position_id, :party_id, :bio, :photo)"
            );
            $stmt->execute([
                'election_id' => $electionId,
                'name'        => $name,
                'position_id' => $position_id,
                'party_id'    => $party_id,
                'bio'         => $description,
                'photo'       => $photoPath
            ]);
            return true;
        } catch (PDOException $e) {
            return 'DB Error (Add Candidate): ' . $e->getMessage();
        }
    }

    public function getCandidates(): array
    {
        $sql = "
          SELECT
            c.id,
            c.election_id,
            c.name,
            p.position_name   AS position,
            COALESCE(pt.name,'Independent') AS partylist,
            c.bio             AS description,
            c.photo           AS photo_path,
            e.title           AS election_title
          FROM candidates c
          JOIN positions p   ON c.position_id = p.id
          LEFT JOIN parties pt ON c.party_id = pt.id
          LEFT JOIN elections e ON c.election_id = e.id
          ORDER BY c.name ASC
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCandidateById(int $id): array|null|string
    {
        try {
            $sql = "
              SELECT
                c.id,
                c.election_id,
                c.name,
                c.position_id,
                c.party_id,
                p.position_name   AS position,
                COALESCE(pt.name,'Independent') AS partylist,
                c.bio             AS description,
                c.photo           AS photo_path
              FROM candidates c
              JOIN positions p   ON c.position_id = p.id
              LEFT JOIN parties pt ON c.party_id    = pt.id
              WHERE c.id = :id
              LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return 'DB Error (Get Candidate): ' . $e->getMessage();
        }
    }

    public function updateCandidate(
        int $id,
        string $name,
        int $position_id,
        ?int $party_id,
        string $description,
        ?string $photoPath = null
    ): bool|string {
        try {
            if ($photoPath !== null) {
                $sql = "
                  UPDATE candidates SET
                    name        = :name,
                    position_id = :position_id,
                    party_id    = :party_id,
                    bio         = :bio,
                    photo       = :photo
                  WHERE id = :id
                ";
                $params = [
                    'name'        => $name,
                    'position_id' => $position_id,
                    'party_id'    => $party_id,
                    'bio'         => $description,
                    'photo'       => $photoPath,
                    'id'          => $id
                ];
            } else {
                $sql = "
                  UPDATE candidates SET
                    name        = :name,
                    position_id = :position_id,
                    party_id    = :party_id,
                    bio         = :bio
                  WHERE id = :id
                ";
                $params = [
                    'name'        => $name,
                    'position_id' => $position_id,
                    'party_id'    => $party_id,
                    'bio'         => $description,
                    'id'          => $id
                ];
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return true;
        } catch (PDOException $e) {
            return 'DB Error (Update Candidate): ' . $e->getMessage();
        }
    }

    public function deleteCandidate(int $id): bool|string
    {
        try {
            $candidate = $this->getCandidateById($id);
            if (is_array($candidate) && !empty($candidate['photo_path'])) {
                FileHandler::deleteFile($candidate['photo_path']);
            }
            $stmt = $this->pdo->prepare("DELETE FROM candidates WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return true;
        } catch (PDOException $e) {
            return 'DB Error (Delete Candidate): ' . $e->getMessage();
        }
    }

    public function getIndependentCandidates(?int $election_id = null): array {
        try {
            $sql = "
                SELECT 
                    c.id, 
                    c.name, 
                    c.bio, 
                    c.photo,
                    p.position_name, 
                    e.title as election_title,
                    e.status as election_status
                FROM 
                    candidates c
                JOIN 
                    positions p ON c.position_id = p.id
                JOIN 
                    elections e ON c.election_id = e.id
                WHERE 
                    c.party_id IS NULL OR c.party_id = (SELECT id FROM parties WHERE name = 'Independent' LIMIT 1)
            ";
            
            if ($election_id) {
                $sql .= " AND c.election_id = :election_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':election_id', $election_id, PDO::PARAM_INT);
            } else {
                $stmt = $this->pdo->prepare($sql);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting independent candidates: " . $e->getMessage());
            return [];
        }
    }
}
