<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

// Include security checks
$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout('../login.php');
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

$voteManager = new VoteManager($pdo);

$electionId = InputValidator::validateId($_GET['election_id'] ?? '');

if (!$electionId) {
    header("Location: available_elections.php");
    exit;
}

if ($voteManager->hasUserVoted($userId, $electionId)) {
    echo "<div style='color:red;'>You have already voted in this election.</div>";
    header("Refresh: 3; URL=available_elections.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['party_id'])) {
    $partyId = (int)$_POST['party_id'];
    $voterName = $_SESSION['full_name'] ?? 'Unknown Voter';
    
    $result = $voteManager->castVotesByParty($userId, $voterName, $electionId, $partyId);
    
    if ($result === true) {
        header("Location: confirmation.php?election_id=$electionId");
        exit;
    } else {
        $message = $result;
    }
}

$electionManager = new ElectionManager($pdo);
$election = $electionManager->getById($electionId);

$parties = $voteManager->getElectionParties($electionId);

$pageTitle = "Vote by Party - " . ($election['title'] ?? 'Election');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/voter.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/party_vote.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
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
    
    <div class="container">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        
        <?php if (!empty($message)): ?>
            <div class="message error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="election-info">
            <h2><?= htmlspecialchars($election['title'] ?? 'Election') ?></h2>
            <p><?= htmlspecialchars($election['description'] ?? '') ?></p>
            <p><strong>Start Date:</strong> <?= date('F j, Y, g:i a', strtotime($election['start_date'] ?? 'now')) ?></p>
            <p><strong>End Date:</strong> <?= date('F j, Y, g:i a', strtotime($election['end_date'] ?? 'now')) ?></p>
            <p class="note">Note: Voting for a party will automatically cast votes for all candidates from that party.</p>
            
            <?php if (!empty($parties)): ?>
                <p class="note">
                    <strong>Individual Voting:</strong> If you prefer to vote for individual candidates instead of by party, 
                    <a href="vote.php?election_id=<?= $electionId ?>">click here</a>.
                </p>
            <?php endif; ?>
        </div>
        
        <?php if (empty($parties)): ?>
            <div class="message error">
                No parties found for this election. Please use the <a href="vote.php?election_id=<?= $electionId ?>">regular voting</a> method.
            </div>
        <?php else: ?>
            <h2>Select a Party</h2>
            <div class="party-list">
                <?php foreach ($parties as $party): ?>
                    <div class="party-card">
                        <h3><?= htmlspecialchars($party['name']) ?></h3>
                        <div class="party-info">
                            <?= htmlspecialchars($party['description'] ?? 'No description available.') ?>
                        </div>
                        <div class="party-stats">
                            <span>Candidates: <?= (int)$party['candidate_count'] ?></span>
                            <span>Positions: <?= (int)$party['position_count'] ?></span>
                        </div>
                        <div class="party-action">
                            <button class="view-candidates-btn" data-party-id="<?= (int)$party['id'] ?>">
                                View Candidates
                            </button>
                            <form method="post" action="" onsubmit="return confirm('Are you sure you want to vote for all candidates from <?= htmlspecialchars($party['name']) ?>?');">
                                <input type="hidden" name="party_id" value="<?= (int)$party['id'] ?>">
                                <button type="submit" class="vote-party-btn">
                                    Vote for Party
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="available_elections.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Available Elections
            </a>
        </div>
    </div>
        
        <div id="candidatesModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modalTitle">Party Candidates</h2>
                <div id="candidatesList" class="candidate-list">
                    <p>Loading candidates...</p>
                </div>
            </div>
        </div>
        
        <footer>
            <p><a href="vote.php?election_id=<?= $electionId ?>">Switch to Individual Candidate Voting</a></p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('candidatesModal');
            const modalTitle = document.getElementById('modalTitle');
            const candidatesList = document.getElementById('candidatesList');
            const closeBtn = document.querySelector('.close');
            const viewCandidatesBtns = document.querySelectorAll('.view-candidates-btn');
            
            closeBtn.onclick = function() {
                modal.style.display = "none";
            }
            
            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }
            
            viewCandidatesBtns.forEach(function(btn) {
                btn.onclick = function() {
                    const partyId = this.getAttribute('data-party-id');
                    const partyName = this.closest('.party-card').querySelector('h3').textContent;
                    
                    modalTitle.textContent = partyName + ' - Candidates';
                    
                    modal.style.display = "block";
                    
                    loadPartyCandidates(partyId);
                }
            });
            
            function loadPartyCandidates(partyId) {
                candidatesList.innerHTML = '<p>Loading candidates...</p>';
                
                const xhr = new XMLHttpRequest();
                
                xhr.open('GET', 'get_party_candidates.php?election_id=<?= $electionId ?>&party_id=' + partyId, true);
                
                xhr.send();
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        candidatesList.innerHTML = xhr.responseText;
                    } else {
                        candidatesList.innerHTML = '<p>Error loading candidates. Please try again.</p>';
                    }
                };
                
                xhr.onerror = function() {
                    candidatesList.innerHTML = '<p>Error loading candidates. Please try again.</p>';
                };
            }
        });
    </script>
</body>
</html>
