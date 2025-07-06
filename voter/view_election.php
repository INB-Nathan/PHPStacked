<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

// If not logged in or not a voter, redirect to login page
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    header("Location: ../login.php");
    exit;
}

// Get the ID of the logged-in voter
$userId = $_SESSION['user_id'] ?? 0; // Ensure user_id is set in session after login

// Redirect or show an error if user ID is not available in session
if (!$userId) {
    header("Location: ../login.php?msg=invalid_session");
    exit;
}

$electionManager = new ElectionManager($pdo); // Still needed to fetch candidate votes for each election
$voteManager = new VoteManager($pdo); // Use VoteManager for voter-specific elections

// Get all eligible elections for the current voter
$eligibleElections = $voteManager->getEligibleElections($userId);

// Filter for elections that are currently 'active' (not just upcoming)
$activeElections = [];
foreach ($eligibleElections as $election) {
    // Check if the election's display_status is 'active'
    // The getEligibleElections method's query already filters by start/end dates and sets 'display_status'
    // 'Active' means it's currently running, 'upcoming' means it's assigned but not started yet.
    if ($election['display_status'] === 'active') {
        // Fetch candidates and their votes for this specific election
        $election['candidates'] = $electionManager->getCandidatesWithVotes($election['id']);
        // Attach candidates data to the election array
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

    <?php if (!empty($activeElections)): ?>
        <?php foreach ($activeElections as $election): ?>
            <div class="election-card">
                <h2><?php echo htmlspecialchars($election['title']); ?></h2>
                <p><?php echo htmlspecialchars($election['description']); ?></p>
                <p>Status: <span style="font-weight: bold; color: green;"><?php echo htmlspecialchars($election['status']); ?></span> (Ends: <?php echo date('M d, Y H:i A', strtotime($election['end_date'])); ?>)</p>

                <?php if (!empty($election['candidates'])): ?>
                    <table class="party-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($election['candidates'] as $candidate): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                    <td><?php echo (int)$candidate['votes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No candidates found for this election.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No active elections assigned to you right now.</p>
    <?php endif; ?>
</body>
</html>