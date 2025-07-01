<?php
require_once __DIR__ . '/db_connect.php';

/**
 * Handle file uploads.
 * @param array  $file        $_FILES['field']
 * @param string $relativeDir web‐relative dir (e.g. 'uploads/candidates/')
 * @param string &$error      out‐param error message
 * @return string|null        web‐relative path or null on failure
 */
function handleFileUpload(array $file, string $relativeDir, string &$error = null): ?string {
    // Build absolute target directory under project root
    $uploadDir = realpath(__DIR__ . "/../{$relativeDir}");
    if ($uploadDir === false) {
        // Directory doesn't exist – try to create it
        $base = __DIR__ . "/../";
        if (!mkdir($base . $relativeDir, 0777, true)) {
            $error = "Failed to create upload directory: {$base}{$relativeDir}";
            error_log($error);
            return null;
        }
        $uploadDir = realpath($base . $relativeDir);
    }

    // Check write permissions
    if (!is_writable($uploadDir)) {
        $error = "Upload directory is not writable: {$uploadDir}";
        error_log($error);
        return null;
    }

    // Check PHP upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error code: ' . $file['error'];
        error_log($error);
        return null;
    }

    // Validate image
    if (getimagesize($file['tmp_name']) === false) {
        $error = 'Uploaded file is not a valid image.';
        error_log($error);
        return null;
    }

    // Generate unique filename
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('candidate_', true) . '.' . $ext;
    $dest     = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    // Attempt to move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $error = 'Error moving uploaded file to: ' . $dest;
        error_log($error);
        return null;
    }

    // Return the path relative to the web root
    return rtrim($relativeDir, '/') . '/' . $filename;
}

/**
 * Delete an uploaded file by its web‐relative path.
 * @param string $relativePath
 * @return bool
 */
function deleteFile(string $relativePath): bool {
    $full = __DIR__ . "/../{$relativePath}";
    if (file_exists($full)) {
        return unlink($full);
    }
    return true;
}

// --- Candidate CRUD ---

/**
 * Adds a new candidate.
 * Uses position_id and party_id instead of raw names.
 */
function addCandidate(
    int $electionId,
    string $name,
    int $position_id,
    ?int $party_id,
    string $description,
    ?string $photoPath = null
): bool|string {
    global $pdo;
    try {
        $stmt = $pdo->prepare(
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

/**
 * Fetches all candidates and joins positions & parties to get names.
 */
function getCandidates(): array {
    global $pdo;
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
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch single candidate by ID, include position_id and party_id.
 */
function getCandidateById(int $id): array|null|string {
    global $pdo;
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
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return 'DB Error (Get Candidate): ' . $e->getMessage();
    }
}

/**
 * Updates an existing candidate.
 */
function updateCandidate(
    int $id,
    string $name,
    int $position_id,
    ?int $party_id,
    string $description,
    ?string $photoPath = null
): bool|string {
    global $pdo;
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
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return true;
    } catch (PDOException $e) {
        return 'DB Error (Update Candidate): ' . $e->getMessage();
    }
}

/**
 * Deletes a candidate and its photo.
 */
function deleteCandidate(int $id): bool|string {
    global $pdo;
    try {
        $cand = getCandidateById($id);
        if (is_array($cand) && !empty($cand['photo_path'])) {
            deleteFile($cand['photo_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return true;
    } catch (PDOException $e) {
        return 'DB Error (Delete Candidate): ' . $e->getMessage();
    }
}

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
}

?>