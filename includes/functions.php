<?php
// Ensure db_connect.php is included for PDO connection
// Using __DIR__ ensures the path is absolute and relative to the current file's directory.
require_once __DIR__ . '/db_connect.php';

/**
 * Adds a new candidate to the database.
 *
 * @param int $electionId The ID of the election this candidate belongs to.
 * @param string $name The name of the candidate.
 * @param string $position The position the candidate is running for.
 * @param string $description A description or platform of the candidate (maps to 'bio' in DB).
 * @param string $photoPath The file path to the candidate's photo (maps to 'photo' in DB).
 * @return bool|string True on success, or an error message string on failure.
 */
function addCandidate($electionId, $name, $position, $description, $photoPath = null) {
    global $pdo; // Access the PDO object from db_connect.php
    try {
        // Updated query to include election_id and changed photo_path to photo, description to bio
        $stmt = $pdo->prepare("INSERT INTO candidates (election_id, name, position, bio, photo) VALUES (:election_id, :name, :position, :bio, :photo)");
        $stmt->execute([
            'election_id' => $electionId,
            'name' => $name,
            'position' => $position,
            'bio' => $description, // I-map ang description from form to bio sa DB
            'photo' => $photoPath  // Changed photo_path to photo
        ]);
        return true;
    } catch (PDOException $e) {
        // Return the error message for display during debugging
        return "Database Error (Add Candidate): " . $e->getMessage();
    }
}

/**
 * Fetches all candidates from the database.
 *
 * @return array An array of candidate records, or an empty array if none found.
 */
function getCandidates() {
    global $pdo;
    try {
        // Changed photo_path to photo, description to bio
        $stmt = $pdo->query("SELECT id, election_id, name, position, bio AS description, photo AS photo_path FROM candidates ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Return the error message for display during debugging
        error_log("Error fetching candidates: " . $e->getMessage()); // Keep logging as fallback
        return []; // Still return empty array so page doesn't break
    }
}

/**
 * Fetches a single candidate by their ID.
 *
 * @param int $id The ID of the candidate.
 * @return array|null|string An associative array of candidate data, null if not found, or an error message string on failure.
 */
function getCandidateById($id) {
    global $pdo;
    try {
        // Changed photo_path to photo, description to bio
        $stmt = $pdo->prepare("SELECT id, election_id, name, position, bio AS description, photo AS photo_path FROM candidates WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Return the error message for display during debugging
        return "Database Error (Get Candidate By ID): " . $e->getMessage();
    }
}

/**
 * Updates an existing candidate's information.
 *
 * @param int $id The ID of the candidate to update.
 * @param string $name The new name.
 * @param string $position The new position.
 * @param string $description The new description (maps to 'bio' in DB).
 * @param string $photoPath The new photo path (can be null if not updated, maps to 'photo' in DB).
 * @return bool|string True on success, or an error message string on failure.
 */
function updateCandidate($id, $name, $position, $description, $photoPath = null) {
    global $pdo;
    try {
        if ($photoPath) {
            // Updated query to change photo_path to photo, description to bio
            $stmt = $pdo->prepare("UPDATE candidates SET name = :name, position = :position, bio = :bio, photo = :photo WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'position' => $position,
                'bio' => $description, // I-map ang description from form to bio sa DB
                'photo' => $photoPath,  // Changed photo_path to photo
                'id' => $id
            ]);
        } else {
            // Update without changing photo if it's null (changed photo_path to photo, description to bio)
            $stmt = $pdo->prepare("UPDATE candidates SET name = :name, position = :position, bio = :bio WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'position' => $position,
                'bio' => $description, // I-map ang description from form to bio sa DB
                'id' => $id
            ]);
        }
        return $stmt->rowCount() > 0; // Check if any rows were affected
    } catch (PDOException $e) {
        // Return the error message for display during debugging
        return "Database Error (Update Candidate): " . $e->getMessage();
    }
}

/**
 * Deletes a candidate from the database.
 *
 * @param int $id The ID of the candidate to delete.
 * @return bool|string True on success, or an error message string on failure.
 */
function deleteCandidate($id) {
    global $pdo;
    try {
        // First, get the photo path to delete the file
        $candidate = getCandidateById($id);
        // Important: check if $candidate is an array (success) or string (error)
        if (is_array($candidate) && $candidate && !empty($candidate['photo_path'])) {
            $full_photo_path = __DIR__ . '/../' . $candidate['photo_path'];
            // Check if file exists before attempting to delete
            if (file_exists($full_photo_path)) {
                unlink($full_photo_path); // Delete the photo file
            }
        } elseif (is_string($candidate)) {
            // If getCandidateById returned an error string, use it
            return "Delete Candidate Error: " . $candidate;
        }

        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Return the error message for display during debugging
        return "Database Error (Delete Candidate): " . $e->getMessage();
    }
}
