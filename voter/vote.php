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

// Get the election ID from the query string
$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

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
$now = date('Y-m-d H:i:s');

// Convert all dates to timestamps for reliable comparison
$nowTimestamp = strtotime($now);
$startTimestamp = strtotime($election['start_date']);
$endTimestamp = strtotime($election['end_date']);

// Better date comparison using timestamps
if ($election['status'] !== 'active' || $nowTimestamp < $startTimestamp || $nowTimestamp > $endTimestamp) {
    // For debugging
    $reason = '';
    if ($election['status'] !== 'active') {
        $reason = "Election status is '{$election['status']}', not 'active'";
    } elseif ($nowTimestamp < $startTimestamp) {
        $reason = "Election hasn't started yet (starts: " . date('Y-m-d H:i:s', $startTimestamp) . ")";
    } elseif ($nowTimestamp > $endTimestamp) {
        $reason = "Election already ended (ended: " . date('Y-m-d H:i:s', $endTimestamp) . ")";
    }
    
    echo "<div style='color:red;'>This election is not currently active.</div>";
    echo "<div style='color:#666;font-size:0.9em;margin-top:10px;'>Reason: $reason</div>";
    echo "<div style='color:#666;font-size:0.9em;margin-top:5px;'>Current time: $now</div>";
    header("Refresh: 5; URL=available_elections.php"); // Increased refresh time to see the debug info
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
        // Convert to integers and validate each candidate ID
        $validCandidateIds = [];
        $positionsVoted = [];
        
        foreach ($selectedCandidates as $candidateId) {
            $candidateId = (int)$candidateId;
            
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
    <style>
        .election-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #4CAF50;
        }
        
        .election-info h2 {
            margin-top: 0;
            color: #333;
        }
        
        .positions-container {
            margin-bottom: 30px;
        }
        
        .position-section {
            margin-bottom: 40px;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .position-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .candidates-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .candidate-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .candidate-card.selected {
            border-color: #4CAF50;
            background-color: #f0fff0;
        }
        
        .candidate-card h4 {
            margin-top: 0;
            color: #333;
        }
        
        .candidate-card .party-name {
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }
        
        .candidate-card .bio {
            color: #555;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .candidate-selection {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .candidate-selection input {
            transform: scale(1.5);
            cursor: pointer;
        }
        
        .candidate-photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .vote-actions {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        .submit-vote-btn {
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-vote-btn:hover {
            background-color: #45a049;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 5px solid #c62828;
        }
        
        .note {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2196F3;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Logout Modal Styles */
        .logout-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        
        .logout-modal.active {
            display: flex;
        }
        
        .logout-modal-content {
            background-color: #fff;
            padding: 24px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
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
