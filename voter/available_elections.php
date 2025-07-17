<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

// Include security checks
$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

// Ensure the user is logged in and is a voter
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    header("Location: ../login.php");
    exit;
}

// Get the current user's ID
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header("Location: ../login.php?msg=invalid_session");
    exit;
}

// Create instances of required managers
$voteManager = new VoteManager($pdo);
$electionManager = new ElectionManager($pdo);

// Update election statuses based on current time
// This ensures elections are correctly marked as active, upcoming, or completed
$electionManager->updateElectionStatuses();

// Get all eligible elections for the current voter
$eligibleElections = $voteManager->getEligibleElections($userId);

// Filter elections by status (active, upcoming)
$activeElections = [];
$upcomingElections = [];

foreach ($eligibleElections as $election) {
    // Check if the voter has already voted in this election
    $hasVoted = $voteManager->hasUserVoted($userId, $election['id']);
    $election['has_voted'] = $hasVoted;
    
    if ($election['display_status'] === 'active') {
        $activeElections[] = $election;
    } elseif ($election['display_status'] === 'upcoming') {
        $upcomingElections[] = $election;
    }
    // Completed elections are not collected, they will be shown in election_results.php
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Active & Upcoming Elections</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/voter.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/available_elections.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
    <script src="../js/election_status_updater.js" defer></script>
</head>
<body>
    <?php voterHeader('elections'); ?>
    
    <!-- Logout Modal -->
    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    
    <div class="election-container">
        <h1>Active & Upcoming Elections</h1>
        
        <?php if (!empty($activeElections) || !empty($upcomingElections)): ?>
            
            <!-- Active Elections -->
            <div class="election-section">
                <h2><i class="fas fa-vote-yea"></i> Active Elections</h2>
                
                <?php if (!empty($activeElections)): ?>
                    <?php foreach ($activeElections as $election): ?>
                        <div class="election-card" data-election-id="<?php echo htmlspecialchars($election['id']); ?>">
                            <span class="status-badge status-active">Active</span>
                            <h3><?php echo htmlspecialchars($election['title']); ?></h3>
                            <p><?php echo htmlspecialchars($election['description']); ?></p>
                            <div class="date-info">
                                <strong>Start:</strong> <?php echo date('M d, Y - h:i A', strtotime($election['start_date'])); ?><br>
                                <strong>End:</strong> <?php echo date('M d, Y - h:i A', strtotime($election['end_date'])); ?>
                            </div>
                            
                            <?php if (!$election['has_voted']): ?>
                                <a href="vote.php?election_id=<?php echo $election['id']; ?>" class="vote-button">
                                    <i class="fas fa-check-circle"></i> Cast Your Vote
                                </a>
                            <?php else: ?>
                                <span class="voted-badge">
                                    <i class="fas fa-check-double"></i> You've Voted
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-elections">No active elections available at this time.</p>
                <?php endif; ?>
            </div>
            
            <!-- Upcoming Elections -->
            <div class="election-section">
                <h2><i class="fas fa-calendar-alt"></i> Upcoming Elections</h2>
                
                <?php if (!empty($upcomingElections)): ?>
                    <?php foreach ($upcomingElections as $election): ?>
                        <div class="election-card" data-election-id="<?php echo htmlspecialchars($election['id']); ?>">
                            <span class="status-badge status-upcoming">Upcoming</span>
                            <h3><?php echo htmlspecialchars($election['title']); ?></h3>
                            <p><?php echo htmlspecialchars($election['description']); ?></p>
                            <div class="date-info">
                                <strong>Starts:</strong> <?php echo date('M d, Y - h:i A', strtotime($election['start_date'])); ?><br>
                                <strong>Ends:</strong> <?php echo date('M d, Y - h:i A', strtotime($election['end_date'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-elections">No upcoming elections scheduled at this time.</p>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <div class="no-elections">
                <p>You don't have any active or upcoming elections assigned to you at this time.</p>
                <p>Please check back later or contact an administrator if you believe this is an error.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
