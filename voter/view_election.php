<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

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

// Kunin lahat ng eligible elections para sa current voter
$eligibleElections = $voteManager->getEligibleElections($userId);

// I-filter ang mga elections na 'active' lang (hindi lang 'upcoming')
$activeElections = [];
foreach ($eligibleElections as $election) {
    // Check kung ang display_status ng election ay 'active'
    // Ang query ng getEligibleElections method ay nagfi-filter na by start/end dates at nagse-set ng 'display_status'
    // 'Active' means currently running, 'upcoming' means assigned but hindi pa nagsisimula.
    if ($election['display_status'] === 'active') {
        // I-fetch ang candidates at ang kanilang votes for this specific election
        $election['candidates'] = $electionManager->getCandidatesWithVotes($election['id']); // Dito kinukuha ang votes
        // I-attach ang candidates data sa election array
        $activeElections[] = $election;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Election</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/voter_view_election.css">
</head>
<body>
    <?php voterHeader('view_election'); ?>
    <h1>View Election Results</h1>

    <?php if (!empty($activeElections)): // Kung may active elections na na-assign sayo ?>
        <?php foreach ($activeElections as $election): // Loop through each active election ?>
            <div class="election-card">
                <h2><?php echo htmlspecialchars($election['title']); ?></h2>
                <p><?php echo htmlspecialchars($election['description']); ?></p>
                <p>Status: <span class="status-text <?php echo strtolower(htmlspecialchars($election['status'])); ?>"><?php echo htmlspecialchars($election['status']); ?></span> (Ends: <?php echo date('M d, Y H:i A', strtotime($election['end_date'])); ?>)</p>

                <?php if (!empty($election['candidates'])): // Kung may candidates para sa election na 'to ?>
                    <table class="party-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($election['candidates'] as $candidate): // Loop through each candidate ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                    <td><?php echo (int)$candidate['vote_count']; ?></td> </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: // Kung walang candidates found for this election ?>
                    <p class="no-candidates-message">No candidates found for this election.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: // Kung walang active elections assigned sa voter ?>
        <p>No active elections assigned to you right now.</p>
    <?php endif; ?>
</body>
</html>