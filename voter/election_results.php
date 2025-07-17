<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

// Include security checks
$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

// Kung hindi naka-login or hindi voter, redirect sa login page
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    header("Location: ../login.php");
    exit;
}

// Kunin ang ID ng naka-login na voter
$userId = $_SESSION['user_id'] ?? 0; // Make sure na may user_id sa session after login

// I-redirect or mag-show ng error kung wala ang user ID sa session
if (!$userId) {
    header("Location: ../login.php?msg=invalid_session");
    exit;
}

$electionManager = new ElectionManager($pdo); // para ma-fetch ang candidate votes for each election
$voteManager = new VoteManager($pdo); // VoteManager for voter-specific elections

$electionManager->updateElectionStatuses();

// Kunin lahat ng eligible elections para sa current voter
$eligibleElections = $voteManager->getEligibleElections($userId);

// I-filter ang mga elections na 'completed' lang (hindi 'active' o 'upcoming')
$completedElections = [];
foreach ($eligibleElections as $election) {
    // Check kung ang display_status ng election ay 'completed'
    // Ang query ng getEligibleElections method ay nagfi-filter na by start/end dates at nagse-set ng 'display_status'
    // 'Completed' means ang election ay tapos na at pwede nang makita ang results
    if ($election['display_status'] === 'completed' || $election['status'] === 'completed') {
        // I-fetch ang candidates at ang kanilang votes for this specific election
        $election['candidates'] = $electionManager->getCandidatesWithVotes($election['id']); // Dito kinukuha ang votes
        // I-attach ang candidates data sa election array
        $completedElections[] = $election;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Election Results</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/voter_view_election.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <script src="../js/logout.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php voterHeader('results'); ?>
    
    <div class="container">
        <h1 class="main-title"></i> Election Results</h1>

        <?php if (!empty($completedElections)): // Kung may completed elections na na-assign sayo ?>
        <?php foreach ($completedElections as $election): // Loop through each completed election ?>
            <div class="election-card">
                <h2><?php echo htmlspecialchars($election['title']); ?></h2>
                <p><?php echo htmlspecialchars($election['description']); ?></p>
                <p>Status: <span class="status-text <?php echo strtolower(htmlspecialchars($election['status'])); ?>"><?php echo htmlspecialchars($election['status']); ?></span> (Ends: <?php echo date('M d, Y H:i A', strtotime($election['end_date'])); ?>)</p>

                <?php if (isset($election['candidates']) && is_array($election['candidates']) && !empty($election['candidates'])): ?>
                    <?php 
                    // Group candidates by position
                    $candidatesByPosition = [];
                    foreach ($election['candidates'] as $candidate) {
                        // Check if position_name exists, if not use a default
                        $positionName = $candidate['position_name'] ?? 'General Candidate';
                        if (!isset($candidatesByPosition[$positionName])) {
                            $candidatesByPosition[$positionName] = [];
                        }
                        $candidatesByPosition[$positionName][] = $candidate;
                    }
                    
                    // Find winner for each position
                    foreach ($candidatesByPosition as $position => $candidates) {
                        // Sort candidates by vote count in descending order
                        usort($candidates, function($a, $b) {
                            return $b['vote_count'] - $a['vote_count'];
                        });
                        $candidatesByPosition[$position] = $candidates;
                    }
                    ?>
                    
                    <div class="election-results">
                        <?php foreach ($candidatesByPosition as $position => $candidates): ?>
                            <div class="position-results">
                                <h3 class="position-name"><?php echo htmlspecialchars($position); ?></h3>
                                <table class="party-table">
                                    <thead>
                                        <tr>
                                            <th>Candidate</th>
                                            <th>Party</th>
                                            <th>Votes</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $highestVotes = count($candidates) > 0 ? $candidates[0]['vote_count'] : 0;
                                        $tiedWinners = false;
                                        
                                        // Check if there's a tie for first place
                                        if (count($candidates) > 1 && $candidates[1]['vote_count'] == $highestVotes && $highestVotes > 0) {
                                            $tiedWinners = true;
                                        }
                                        
                                        foreach ($candidates as $index => $candidate): 
                                            $isWinner = ($candidate['vote_count'] == $highestVotes && $highestVotes > 0);
                                        ?>
                                            <tr class="<?php echo $isWinner ? 'winner' : ''; ?>">
                                                <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['party_name'] ?? 'Independent'); ?></td>
                                                <td><?php echo (int)$candidate['vote_count']; ?></td>
                                                <td>
                                                    <?php if ($isWinner && !$tiedWinners): ?>
                                                        <span class="winner-badge">Winner</span>
                                                    <?php elseif ($isWinner && $tiedWinners): ?>
                                                        <span class="tied-badge">Tied</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: // Kung walang candidates found for this election ?>
                    <p class="no-candidates-message">No candidates found for this election.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: // Kung walang completed elections assigned sa voter ?>
        <div class="no-elections-message">
            <i class="fas fa-info-circle"></i>
            <h3>No Completed Elections Yet</h3>
            <p>There are no completed elections available for viewing at this time.</p>
            <p>Completed elections will be shown here after they've ended.</p>
        </div>
    <?php endif; ?>
    </div>
    
    <!-- Logout Modal -->
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
</body>
</html>