<?php
class StatisticsManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // Get general system statistics
    public function getSystemStats(): array {
        try {
            $stats = [];
            
            // Total elections
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM elections");
            $stats['total_elections'] = $stmt->fetchColumn();
            
            // Active elections
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'active' AND start_date <= NOW() AND end_date >= NOW()");
            $stats['active_elections'] = $stmt->fetchColumn();
            
            // Upcoming elections
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'upcoming' OR (status = 'active' AND start_date > NOW())");
            $stats['upcoming_elections'] = $stmt->fetchColumn();
            
            // Completed elections
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'completed' OR (status = 'active' AND end_date < NOW())");
            $stats['completed_elections'] = $stmt->fetchColumn();
            
            // Total registered voters
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'voter'");
            $stats['total_voters'] = $stmt->fetchColumn();
            
            // Total positions
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM positions");
            $stats['total_positions'] = $stmt->fetchColumn();
            
            // Total candidates
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM candidates");
            $stats['total_candidates'] = $stmt->fetchColumn();
            
            // Total votes cast
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM votes");
            $stats['total_votes'] = $stmt->fetchColumn();
            
            // Unique voters who have voted
            $stmt = $this->pdo->query("SELECT COUNT(DISTINCT user_id) FROM votes WHERE user_id IS NOT NULL");
            $stats['unique_voters'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error getting system stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get statistics for all elections
    public function getElectionStats(): array {
        try {
            $sql = "SELECT e.id, e.title, e.status, e.start_date, e.end_date, 
                  (SELECT COUNT(*) FROM positions WHERE election_id = e.id) AS position_count,
                  (SELECT COUNT(*) FROM candidates WHERE election_id = e.id) AS candidate_count,
                  (SELECT COUNT(*) FROM voter_elections WHERE election_id = e.id) AS assigned_voters,
                  (SELECT COUNT(DISTINCT user_id) FROM votes WHERE election_id = e.id) AS voters_participated,
                  (SELECT COUNT(*) FROM votes WHERE election_id = e.id) AS total_votes
                  FROM elections e
                  ORDER BY e.start_date DESC";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting election stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get top candidates across all elections
    public function getTopCandidates($limit = 10): array {
        try {
            $sql = "SELECT c.id, c.name, c.vote_count, p.position_name AS position_name, e.title AS election_title, 
                  (SELECT name FROM parties WHERE id = c.party_id) AS party_name
                  FROM candidates c
                  JOIN positions p ON c.position_id = p.id
                  JOIN elections e ON c.election_id = e.id
                  ORDER BY c.vote_count DESC
                  LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting top candidates: " . $e->getMessage());
            return [];
        }
    }
    
    // Get voter participation rate for each election
    public function getVoterParticipationRates(): array {
        try {
            $sql = "SELECT e.id, e.title, 
                  (SELECT COUNT(*) FROM voter_elections WHERE election_id = e.id) AS assigned_voters,
                  (SELECT COUNT(DISTINCT user_id) FROM votes WHERE election_id = e.id) AS voters_participated
                  FROM elections e
                  WHERE e.status = 'completed' OR e.end_date < NOW()
                  ORDER BY e.end_date DESC";
            
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate participation rate
            foreach ($results as &$result) {
                if ($result['assigned_voters'] > 0) {
                    $result['participation_rate'] = round(($result['voters_participated'] / $result['assigned_voters']) * 100, 2);
                } else {
                    $result['participation_rate'] = 0;
                }
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Error getting voter participation rates: " . $e->getMessage());
            return [];
        }
    }
    
    // Get recent voting activity
    public function getRecentVotingActivity($limit = 10): array {
        try {
            $sql = "SELECT v.vote_timestamp, v.voter_name, 
                  (SELECT name FROM candidates WHERE id = v.candidate_id) AS candidate_name, 
                  (SELECT position_name FROM positions WHERE id = 
                      (SELECT position_id FROM candidates WHERE id = v.candidate_id)) AS position_name,
                  e.title AS election_title
                  FROM votes v
                  JOIN elections e ON v.election_id = e.id
                  ORDER BY v.vote_timestamp DESC
                  LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent voting activity: " . $e->getMessage());
            return [];
        }
    }
}
?>