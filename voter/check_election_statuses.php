<?php
require_once '../includes/autoload.php';
session_start();

// Ensure the user is logged in
if (empty($_SESSION['loggedin']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Update election statuses
$electionManager = new ElectionManager($pdo);
$updatedCount = $electionManager->updateElectionStatuses();

// Get updated election information
$voteManager = new VoteManager($pdo);
$eligibleElections = $voteManager->getEligibleElections($userId);

// Process elections to include only the needed information
$electionsData = [];
foreach ($eligibleElections as $election) {
    // Skip completed elections - they should be viewed in election_results.php
    if ($election['display_status'] === 'completed' || $election['status'] === 'completed') {
        continue;
    }
    
    // Check if the voter has already voted in this election
    $hasVoted = $voteManager->hasUserVoted($userId, $election['id']);
    
    $electionsData[] = [
        'id' => $election['id'],
        'title' => $election['title'],
        'status' => $election['display_status'] ?? $election['status'],
        'start_date' => $election['start_date'],
        'end_date' => $election['end_date'],
        'has_voted' => $hasVoted
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'elections' => $electionsData,
    'updated_count' => $updatedCount
]);
