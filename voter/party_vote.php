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

$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

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
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/voter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .party-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .party-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            transition: transform 0.2s;
        }
        
        .party-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .party-card h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .party-info {
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .party-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .party-action {
            text-align: center;
        }
        
        .view-candidates-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 10px;
            border: none;
            cursor: pointer;
        }
        
        .vote-party-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        
        .message {
            padding: 10px 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 5px solid #c62828;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-width: 800px;
            border-radius: 8px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .candidate-list {
            margin-top: 20px;
        }
        
        .position-section {
            margin-bottom: 20px;
        }
        
        .position-section h4 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .candidate-item {
            padding: 10px;
            margin: 5px 0;
            background-color: #f1f1f1;
            border-radius: 4px;
        }

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
    </style>
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
        
        <?php if (empty($parties)): ?>
            <div class="message error">
                No parties found for this election. Please use the <a href="vote.php?election_id=<?= $electionId ?>">regular voting</a> method.
            </div>
        <?php else: ?>
            <div class="election-info">
                <h2><?= htmlspecialchars($election['title'] ?? 'Election') ?></h2>
                <p><?= htmlspecialchars($election['description'] ?? '') ?></p>
                <p><strong>Start Date:</strong> <?= date('F j, Y, g:i a', strtotime($election['start_date'] ?? 'now')) ?></p>
                <p><strong>End Date:</strong> <?= date('F j, Y, g:i a', strtotime($election['end_date'] ?? 'now')) ?></p>
                <p class="note">Note: Voting for a party will automatically cast votes for all candidates from that party.</p>
            </div>
            
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
            <p><a href="available_elections.php">Back to Elections</a></p>
        </footer>
    </div>
    
    <script src="../js/logout.js"></script>
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
