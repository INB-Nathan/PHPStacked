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

// Create an instance of VoteManager
$voteManager = new VoteManager($pdo);

// Get the election ID from the query string and validate it
$electionId = InputValidator::validateId($_GET['election_id'] ?? '');

// Check if the election ID is valid
if (!$electionId) {
    header("Location: available_elections.php");
    exit;
}

// Check if the user has already voted in this election
if ($voteManager->hasUserVoted($userId, $electionId)) {
    echo "<div style='color:red;'>You have already voted in this election.</div>";
    header("Refresh: 3; URL=available_elections.php");
    exit;
}

// Create an instance of ElectionManager
$electionManager = new ElectionManager($pdo);

// Get election details
$election = $electionManager->getById($electionId);
if (!$election) {
    echo "<div style='color:red;'>Election not found.</div>";
    header("Refresh: 3; URL=available_elections.php");
    exit;
}

// Check if the election is active
$currentTimestamp = time();

// Parse election dates
$startTimestamp = strtotime($election['start_date']);
$endTimestamp = strtotime($election['end_date']);

// Handle parsing failures
if ($startTimestamp === false) {
    $startTimestamp = 0;
}
if ($endTimestamp === false) {
    $endTimestamp = PHP_INT_MAX;
}

// Election is active if:
// 1. Status is 'active' AND
// 2. Current time is between start and end dates (inclusive)
$statusActive = ($election['status'] === 'active');
$timeInRange = ($currentTimestamp >= $startTimestamp && $currentTimestamp <= $endTimestamp);

if (!$statusActive || !$timeInRange) {
    $reason = '';
    if (!$statusActive) {
        $reason = "Election status is '{$election['status']}', not 'active'";
    } elseif ($currentTimestamp < $startTimestamp) {
        $reason = "Election hasn't started yet. It will begin on " . date('F j, Y \a\t g:i A', $startTimestamp) . ".";
    } elseif ($currentTimestamp > $endTimestamp) {
        $reason = "Election has ended. It concluded on " . date('F j, Y \a\t g:i A', $endTimestamp) . ".";
    }
    
    echo "<div style='color:red; padding: 20px; margin: 20px 0; border-radius: 5px; background-color: #ffe6e6; border: 1px solid #ff9999;'>";
    echo "<h3 style='margin-top: 0;'>This election is not currently available for voting.</h3>";
    echo "<p><strong>Reason:</strong> $reason</p>";
    echo "<p><strong>Current time:</strong> " . date('F j, Y \a\t g:i A', $currentTimestamp) . "</p>";
    echo "<p style='margin-bottom: 0;'><a href='available_elections.php'>← Back to Available Elections</a></p>";
    echo "</div>";
    exit;
}

// Get all candidates for this election
$candidates = $voteManager->getElectionCandidates($electionId);

// Process the form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    // Validate the selection
    $selectedCandidates = $_POST['candidates'] ?? [];
    
    // Check if any candidates were selected
    if (empty($selectedCandidates)) {
        $message = "Please select at least one candidate to vote for.";
    } else {
        // Validate each candidate ID using InputValidator
        $validCandidateIds = [];
        $positionsVoted = [];
        
        foreach ($selectedCandidates as $candidateId) {
            $candidateId = InputValidator::validateId($candidateId);
            
            if (!$candidateId) {
                $message = "Invalid candidate selection.";
                break;
            }
            
            // Find the candidate in our data
            foreach ($candidates as $position) {
                foreach ($position['candidates'] as $candidate) {
                    if ($candidate['id'] == $candidateId) {
                        // Check if we've already voted for this position
                        if (in_array($position['position_id'], $positionsVoted)) {
                            $message = "You can only vote for one candidate per position.";
                            break 2; // Break out of both loops
                        }
                        
                        $validCandidateIds[] = $candidateId;
                        $positionsVoted[] = $position['position_id'];
                        break;
                    }
                }
            }
        }
        
        // If all candidates are valid, cast the votes
        if (empty($message)) {
            $voterName = $_SESSION['full_name'] ?? 'Unknown Voter';
            $result = $voteManager->castVotes($userId, $voterName, $electionId, $validCandidateIds);
            
            if ($result === true) {
                // Vote was successful, redirect to confirmation page
                header("Location: confirmation.php?election_id=$electionId");
                exit;
            } else {
                // Vote failed, show error message
                $message = $result;
            }
        }
    }
}

// Set page title
$pageTitle = "Vote - " . htmlspecialchars($election['title']);

// Generate CSRF token for security
$csrf_token = $securityManager->generateCSRFToken();

// Get all eligible elections for this user
$eligibleElections = $voteManager->getEligibleElections($userId);

// Build an array of allowed election IDs
$allowedElectionIds = array_column($eligibleElections, 'id');

// Check if the requested election_id is in the allowed list
if (!in_array($electionId, $allowedElectionIds)) {
    // Not allowed to vote in this election
    echo "<div style='color:red;'>You are not eligible to vote in this election.</div>";
    header("Refresh: 3; URL=available_elections.php");
    exit;
}
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
    <link rel="stylesheet" href="../css/vote.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
</head>
<body>
    <?php require_once '../includes/voter_header.php'; ?>
    <?php voterHeader('vote'); ?>
    
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
        <h1><?= $pageTitle ?></h1>
        
        <?php if (!empty($message)): ?>
            <div class="message error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="election-info">
            <h2><?= htmlspecialchars($election['title']) ?></h2>
            <p><?= htmlspecialchars($election['description']) ?></p>
            <p><strong>Start Date:</strong> <?= date('F j, Y, g:i a', strtotime($election['start_date'])) ?></p>
            <p><strong>End Date:</strong> <?= date('F j, Y, g:i a', strtotime($election['end_date'])) ?></p>
            <p class="note">Select one candidate per position. You can leave positions unselected if you don't want to vote for any candidate.</p>
            
            <?php if (!empty($candidates)): ?>
                <p class="note">
                    <strong>Party Voting:</strong> If you prefer to vote by party instead of individual candidates, 
                    <a href="party_vote.php?election_id=<?= $electionId ?>">click here</a>.
                </p>
            <?php endif; ?>
        </div>
        
        <?php if (empty($candidates)): ?>
            <div class="message error">
                No candidates found for this election.
            </div>
        <?php else: ?>
            <form method="post" action="" id="voteForm">
                <div class="positions-container">
                    <?php foreach ($candidates as $position): ?>
                        <div class="position-section">
                            <h3><?= htmlspecialchars($position['position_name']) ?></h3>
                            
                            <?php if (empty($position['candidates'])): ?>
                                <p>No candidates for this position.</p>
                            <?php else: ?>
                                <div class="candidates-list">
                                    <?php foreach ($position['candidates'] as $candidate): ?>
                                        <div class="candidate-card" data-candidate-id="<?= $candidate['id'] ?>">
                                            <?php if (!empty($candidate['photo']) && file_exists("../uploads/candidates/{$candidate['photo']}")): ?>
                                                <img src="../uploads/candidates/<?= htmlspecialchars($candidate['photo']) ?>" alt="<?= htmlspecialchars($candidate['name']) ?>" class="candidate-photo">
                                            <?php endif; ?>
                                            
                                            <h4><?= htmlspecialchars($candidate['name']) ?></h4>
                                            
                                            <?php if (!empty($candidate['party_name'])): ?>
                                                <div class="party-name"><?= htmlspecialchars($candidate['party_name']) ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($candidate['bio'])): ?>
                                                <div class="bio"><?= htmlspecialchars($candidate['bio']) ?></div>
                                            <?php endif; ?>
                                            
                                            <div class="candidate-selection">
                                                <input type="radio" name="candidates[<?= $position['position_id'] ?>]" value="<?= $candidate['id'] ?>" id="candidate_<?= $candidate['id'] ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="vote-actions">
                    <button type="submit" name="vote" value="1" class="submit-vote-btn" id="submitVoteBtn">
                        Submit Your Vote
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="available_elections.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Available Elections
            </a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make the entire candidate card clickable for selecting
            const candidateCards = document.querySelectorAll('.candidate-card');
            
            candidateCards.forEach(function(card) {
                card.addEventListener('click', function(e) {
                    // Don't trigger if they clicked directly on the radio button
                    if (e.target.type !== 'radio') {
                        const radio = this.querySelector('input[type="radio"]');
                        radio.checked = true;
                        
                        // Add selected class to this card and remove from siblings
                        const positionSection = this.closest('.position-section');
                        const cardsInPosition = positionSection.querySelectorAll('.candidate-card');
                        
                        cardsInPosition.forEach(function(c) {
                            c.classList.remove('selected');
                        });
                        
                        this.classList.add('selected');
                    }
                });
            });
            
            // Add confirmation before submitting vote
            const voteForm = document.getElementById('voteForm');
            const submitBtn = document.getElementById('submitVoteBtn');
            
            if (voteForm && submitBtn) {
                voteForm.addEventListener('submit', function(e) {
                    // Count selected candidates
                    const selectedCandidates = document.querySelectorAll('input[type="radio"]:checked').length;
                    
                    if (selectedCandidates === 0) {
                        e.preventDefault();
                        alert('Please select at least one candidate to vote for.');
                        return false;
                    }
                    
                    // Confirm submission
                    if (!confirm('Are you sure you want to submit your vote? This action cannot be undone.')) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Prepare the actual data for submission - convert from position-based to flat array
                    const formData = new FormData();
                    const radioButtons = document.querySelectorAll('input[type="radio"]:checked');
                    
                    radioButtons.forEach(function(radio, index) {
                        formData.append('candidates[]', radio.value);
                    });
                    
                    // Add other form fields
                    formData.append('vote', '1');
                    
                    // Submit the form with the modified data
                    e.preventDefault();
                    
                    fetch(voteForm.action || window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text().then(html => {
                                document.open();
                                document.write(html);
                                document.close();
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting vote:', error);
                        alert('An error occurred while submitting your vote. Please try again.');
                    });
                });
            }
        });
    </script>
</body>
</html>
