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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php voterHeader('results'); ?>
    <h1>View Completed Election Results</h1>

    <?php if (!empty($completedElections)): // Kung may completed elections na na-assign sayo ?>
        <?php foreach ($completedElections as $election): // Loop through each completed election ?>
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
    <?php else: // Kung walang completed elections assigned sa voter ?>
        <p>No completed elections are available for viewing at this time. Completed elections will be shown here after they've ended.</p>
    <?php endif; ?>
</body>
</html>