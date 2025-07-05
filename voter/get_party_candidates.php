<?php
require_once '../includes/autoload.php';
session_start();

// Security checks
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    http_response_code(403);
    echo "<p>Access denied. Only logged-in voters can access this page.</p>";
    exit;
}

// Get parameters
$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
$partyId = isset($_GET['party_id']) ? (int)$_GET['party_id'] : 0;

// Validate parameters
if (!$electionId || !$partyId) {
    http_response_code(400);
    echo "<p>Missing required parameters.</p>";
    exit;
}

// Create an instance of VoteManager
$voteManager = new VoteManager($pdo);

// Get party candidates
$partyCandidates = $voteManager->getPartyCandidates($electionId, $partyId);

if (empty($partyCandidates)) {
    echo "<p>No candidates found for this party.</p>";
    exit;
}

// Output the candidates by position
foreach ($partyCandidates as $position) {
    echo "<div class='position-section'>";
    echo "<h4>" . htmlspecialchars($position['position_name']) . "</h4>";
    
    foreach ($position['candidates'] as $candidate) {
        echo "<div class='candidate-item'>";
        echo "<strong>" . htmlspecialchars($candidate['name']) . "</strong>";
        
        if (!empty($candidate['bio'])) {
            echo "<br><small>" . htmlspecialchars($candidate['bio']) . "</small>";
        }
        
        echo "</div>";
    }
    
    echo "</div>";
}
?>
