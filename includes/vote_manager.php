<?php
class VoteManager {
    private $pdo;
    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }
    
    // first function always to check if the user has voted so that it can skip the later functions if this returns true
    public function hasUserVoted(int $userId, int $electionId): bool {
        try {
            // just checks if the count of the user_id's count on votes on an election id.
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ? AND election_id = ?");
            $stmt->execute([$userId, $electionId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // puts the errorlog into lampp logs error.txt
            error_log("Error checking if user voted: " . $e->getMessage());
            return false;
        }
    }

    // first checks your user id and cross references it to eligible election condition
    // 1st condition is that you are given access to the election by the admin
    // 2nd condition is that the election is already ongoing or upcoming by 24 hours.
    // the upcoming by 24 hours is still upcoming, and voters cannot vote on that election yet.
    public function getEligibleElections(int $userId): array {
        // try catch lang for error exception
        try{
            $now = date("Y-m-d H:i:s");
            $upcoming = date("Y-m-d H:i:s", strtotime("+24 hours"));
            // select mo election id, title, description, start, end, status and max vote per user.
            // then may calculated field na display_status which will check if ung start date ng election is still in the future by comparing sa current time
            // if so it labels the election as upcoming, otherwise it labels it as active.
            $stmt = $this->pdo->prepare("SELECT e.id, e.title, e.description, e.start_date, e.end_date, e.status, e.max_votes_per_user, CASE WHEN e.start_date > NOW() THEN 'upcoming' ELSE 'active' END AS display_status FROM elections e INNER JOIN voter_elections ve ON e.id = ve.election_id WHERE ve.voter_id = ? AND ((e.status = 'active' AND e.start_date <= ? AND e.end_date >= ?) OR (e.status = 'active' AND e.start_date > ? AND e.start_date <=?)) ORDER BY e.start_date ASC");
            $stmt->execute([$userId, $now, $now, $now, $upcoming]);
            $elections = $stmt -> fetchAll(pdo::FETCH_ASSOC);
            
            // foreach loop para iprocess each election sa array.
            // checks muna kung ung user has voted in a specific election by using hasUserVoted.
            // then mag assign siya ng result sa has_voted
            // if ung election naman display status niya is upcoming
            // icacalculate niya kung gano katagal nalang ung time bago mag start ung election
            // after nun mag rereturn na siya ng updated array of elections.
            foreach($elections as &$election){
                $election['has_voted'] = $this->hasUserVoted($userId, $election['id']);

                if ($election['display_status'] === 'upcoming') {
                    $startTime = strtotime($election['start_date']);
                    $currentTime = time();
                    $timeUntilStart = $startTime - $currentTime;
                    
                    $hours = floor($timeUntilStart /3600);
                    $minutes = floor(($timeUntilStart % 3600)/60);

                    $election['time_until_start'] = "{$hours}h {$minutes}m";
                }
            } 
            return $elections;
        } catch (PDOException $e) {
            error_log("Error getting eligible elections: " . $e->getMessage());
            return [];
        }
    }

    // this function will just fetch all completed elections
    // first query muna, ung query reretrieve lahat ng elections that the user_id has participated in
    // or whose end date has already passed.
    // then mag inner join siya para mag link ung elections table sa voter_elections
    // ung where clause ififilter ung results by the voter's id and mag chcheck if whether the election is completed or ended in the past.
    // lastly mag sosort na siya ng list of electrions by their end date in descending order.
    public function getCompletedElections(int $userId): array {
        try {
            $stmt = $this->pdo->prepare("SELECT e.id, e.title, e.description, e.start_date, e.end_date, e.status, e.max_votes_per_user FROM elections e INNER JOIN voter_elections ve ON e.id = ve.election_id WHERE ve.voter_id = ? AND (e.status = 'completed' OR e.end_date < NOW())ORDER BY e.end_date DESC");
            $stmt->execute([$userId]);
            
            $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($elections as &$election) {
                $election['has_voted'] = $this->hasUserVoted($userId, $election['id']);
            }
            
            return $elections;
        } catch (PDOException $e) {
            error_log("Error getting completed elections: " . $e->getMessage());
            return [];
        }
    }

    // self explanatory na ung name ung logic nalang iiexplain ko
    // query for retreiving lahat ng positions for a specific election then lagayt mo sa $positions ung results.
    // then mag loloop siya sa positions array and runs a query para kunin lahat ng candidate tsaka party lists.
    public function getElectionCandidates(int $electionId): array {
        try {
            $positions_stmt = $this->pdo->prepare("SELECT id, position_name FROM positions WHERE election_id = ? ORDER BY position_name");
            $positions_stmt->execute([$electionId]);
            $positions = $positions_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($positions as $position) {
                $candidates_stmt = $this->pdo->prepare("SELECT c.id, c.name, c.bio, c.photo AS photo, p.name AS party_name, p.id AS party_id FROM candidates c LEFT JOIN parties p ON c.party_id = p.id WHERE c.election_id = ? AND c.position_id = ? ORDER BY c.name");
                $candidates_stmt->execute([$electionId, $position['id']]);
                $candidates = $candidates_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $result[] = [
                    'position_id' => $position['id'],
                    'position_name' => $position['position_name'],
                    'candidates' => $candidates
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting election candidates: " . $e->getMessage());
            return [];
        }
    }

    // allows a voter to cast votes for candidates in an election
    // still to be implemented (marked as 'tba' - to be added)
    // will handle the process of recording votes and updating candidate vote counts
    // returns boolean for success/failure or string for error messages
    public function castVotes(int $userId, string $voterName, int $electionId, array $candidateIds): bool|string {
        try {
            // Start a transaction to ensure all votes are recorded properly
            $this->pdo->beginTransaction();
            
            // Check if user has already voted in this election
            if ($this->hasUserVoted($userId, $electionId)) {
                $this->pdo->rollBack();
                return "You have already voted in this election.";
            }
            
            // Get user's IP and user agent
            $voterIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Insert votes for each candidate
            $stmt = $this->pdo->prepare("INSERT INTO votes (election_id, candidate_id, user_id, voter_name, voter_ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($candidateIds as $candidateId) {
                $stmt->execute([$electionId, $candidateId, $userId, $voterName, $voterIp, $userAgent]);
                
                // Update candidate vote count
                $updateStmt = $this->pdo->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
                $updateStmt->execute([$candidateId]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error casting votes: " . $e->getMessage());
            return "An error occurred while casting your votes. Please try again.";
        }
    }
    
    // New method to cast votes for all candidates from a specific party in an election
    public function castVotesByParty(int $userId, string $voterName, int $electionId, int $partyId): bool|string {
        try {
            // Start a transaction
            $this->pdo->beginTransaction();
            
            // Check if user has already voted in this election
            if ($this->hasUserVoted($userId, $electionId)) {
                $this->pdo->rollBack();
                return "You have already voted in this election.";
            }
            
            // Get all candidates from the party in this election
            $stmt = $this->pdo->prepare("
                SELECT c.id 
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                WHERE c.election_id = ? AND c.party_id = ? AND c.is_active = TRUE
                GROUP BY p.id
                HAVING c.id = (
                    SELECT c2.id 
                    FROM candidates c2 
                    WHERE c2.position_id = p.id AND c2.party_id = ? 
                    ORDER BY c2.id ASC 
                    LIMIT 1
                )
            ");
            $stmt->execute([$electionId, $partyId, $partyId]);
            $candidates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($candidates)) {
                $this->pdo->rollBack();
                return "No candidates found for this party in the election.";
            }
            
            // Get user's IP and user agent
            $voterIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Insert votes for each candidate
            $insertStmt = $this->pdo->prepare("INSERT INTO votes (election_id, candidate_id, user_id, voter_name, voter_ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($candidates as $candidateId) {
                $insertStmt->execute([$electionId, $candidateId, $userId, $voterName, $voterIp, $userAgent]);
                
                // Update candidate vote count
                $updateStmt = $this->pdo->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
                $updateStmt->execute([$candidateId]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error casting party votes: " . $e->getMessage());
            return "An error occurred while casting your party votes. Please try again.";
        }
    }
    
    // Get list of parties with candidates in a specific election
    public function getElectionParties(int $electionId): array {
        try {
            $sql = "SELECT DISTINCT p.id, p.name, p.description,
                    (SELECT COUNT(*) FROM candidates c WHERE c.party_id = p.id AND c.election_id = ?) AS candidate_count,
                    (SELECT COUNT(DISTINCT position_id) FROM candidates WHERE party_id = p.id AND election_id = ?) AS position_count
                    FROM parties p
                    JOIN candidates c ON p.id = c.party_id
                    WHERE c.election_id = ?
                    ORDER BY p.name";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$electionId, $electionId, $electionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting election parties: " . $e->getMessage());
            return [];
        }
    }
    
    // Get all candidates from a specific party in an election
    public function getPartyCandidates(int $electionId, int $partyId): array {
        try {
            $positions_stmt = $this->pdo->prepare("
                SELECT id, position_name 
                FROM positions 
                WHERE election_id = ? 
                ORDER BY position_name
            ");
            $positions_stmt->execute([$electionId]);
            $positions = $positions_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($positions as $position) {
                $candidates_stmt = $this->pdo->prepare("
                    SELECT c.id, c.name, c.bio, c.photo, c.platform
                    FROM candidates c
                    WHERE c.election_id = ? AND c.position_id = ? AND c.party_id = ?
                    ORDER BY c.name
                ");
                $candidates_stmt->execute([$electionId, $position['id'], $partyId]);
                $candidates = $candidates_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($candidates)) {
                    $result[] = [
                        'position_id' => $position['id'],
                        'position_name' => $position['position_name'],
                        'candidates' => $candidates
                    ];
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting party candidates: " . $e->getMessage());
            return [];
        }
    }

    // generatereceiptcode is just creating a code na unique for the user based on the election id and the time stamp
    private function generateReceiptCode($userId, $electionId, $timestamp) {
        $data = $userId . '|' . $electionId . '|' . $timestamp;
        
        $hash = hash('sha256', $data);
        $code = substr($hash, 0, 12);
        
        return 'VOTE-' . strtoupper(substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4));
    }

    // gets and processes election results data
    // first checks if the election exists and whether results can be shown (completed or past end date)
    // then gets all positions for the election and retrieves candidates with their vote counts
    // for each candidate calculate the percentage of votes received
    // returns formatted results or an error message if results aren't available yet
    public function getElectionResults(int $electionId): array|string {
        try {
            $stmt = $this->pdo->prepare("SELECT status, end_date FROM elections WHERE id = ?");
            $stmt->execute([$electionId]);
            $election = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$election) {
                return "Election not found.";
            }
            
            $isCompleted = ($election['status'] === 'completed');
            $isPastEndDate = (strtotime($election['end_date']) < time());
            
            if (!$isCompleted && !$isPastEndDate) {
                return "Results are not yet available for this election.";
            }
            
            $positions_stmt = $this->pdo->prepare("SELECT id, position_name FROM positions WHERE election_id = ? ORDER BY position_name");
            $positions_stmt->execute([$electionId]);
            $positions = $positions_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            foreach ($positions as $position) {
                $candidates_stmt = $this->pdo->prepare("SELECT c.id, c.name, c.vote_count, p.name AS party_name FROM candidates c LEFT JOIN parties p ON c.party_id = p.id WHERE c.election_id = ? AND c.position_id = ? ORDER BY c.vote_count DESC, c.name ASC");
                $candidates_stmt->execute([$electionId, $position['id']]);
                $candidates = $candidates_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $total_votes = array_sum(array_column($candidates, 'vote_count'));
                
                foreach ($candidates as &$candidate) {
                    $candidate['percentage'] = $total_votes > 0 
                        ? round(($candidate['vote_count'] / $total_votes) * 100, 2) 
                        : 0;
                }
                unset($candidate);
                
                $results[] = [
                    'position_id' => $position['id'],
                    'position_name' => $position['position_name'],
                    'candidates' => $candidates,
                    'total_votes' => $total_votes
                ];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Error getting election results: " . $e->getMessage());
            return "An error occurred while retrieving results.";
        }
    }
    
    // retrieves the receipt information for a voter's participation in an election
    // looks up the timestamp of when the vote was cast, then generates a unique receipt code
    // returns election ID, timestamp, and the generated receipt code or false if no vote record exists
    public function getVoterReceipt(int $userId, int $electionId) {
        try {
            $stmt = $this->pdo->prepare("SELECT timestamp FROM votes WHERE user_id = ? AND election_id = ? LIMIT 1");
            $stmt->execute([$userId, $electionId]);
            $vote = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vote) {
                return false;
            }
            
            return [
                'election_id' => $electionId,
                'timestamp' => $vote['timestamp'],
                'receipt_code' => $this->generateReceiptCode($userId, $electionId, $vote['timestamp'])
            ];
        } catch (PDOException $e) {
            error_log("Error getting voter receipt: " . $e->getMessage());
            return false;
        }
    }
    
    // counts the total number of unique voters who participated in a specific election
    // uses DISTINCT in the SQL query to count each user only once even if they voted for multiple positions
    // returns the count as an integer, or 0 if an error occurs
    public function getTotalVoteCount(int $electionId): int {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM votes WHERE election_id = ?");
            $stmt->execute([$electionId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total vote count: " . $e->getMessage());
            return 0;
        }
    }

}
?>