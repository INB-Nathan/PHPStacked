<?php
require_once '../includes/autoload.php';
session_start();

$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout('../login.php');

if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    http_response_code(403);
    echo "<div style='color:red;'>Access denied. Only voters can access this page.</div>";
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo "<div style='color:red;'>User ID not found in session.</div>";
    exit;
}

$voteManager = new VoteManager($pdo);

$pageTitle = "Test Vote Manager Functions";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .result {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .election-card {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .position-section {
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .candidate-item {
            padding: 8px;
            margin: 5px 0;
            background-color: #eee;
            border-radius: 3px;
        }
        .percentage-bar {
            height: 15px;
            background-color: #4CAF50;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        
        <div class="test-section">
            <div class="test-title">1. Check if User Has Voted</div>
            <?php
            $eligibleElections = $voteManager->getEligibleElections($userId);
            $electionId = $eligibleElections[0]['id'] ?? 0;
            
            if ($electionId) {
                $hasVoted = $voteManager->hasUserVoted($userId, $electionId);
                echo "<p>Has user voted in election #$electionId? " . ($hasVoted ? 'Yes' : 'No') . "</p>";
            } else {
                echo "<p>No eligible elections found to test this function.</p>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Eligible Elections</div>
            <?php
            $eligibleElections = $voteManager->getEligibleElections($userId);
            
            if (empty($eligibleElections)) {
                echo "<p>No eligible elections found.</p>";
            } else {
                echo "<p>Found " . count($eligibleElections) . " eligible election(s):</p>";
                
                foreach ($eligibleElections as $election) {
                    echo "<div class='election-card'>";
                    echo "<h3>" . htmlspecialchars($election['title']) . "</h3>";
                    echo "<p><strong>Status:</strong> " . htmlspecialchars($election['status']) . 
                         " (Display Status: " . htmlspecialchars($election['display_status'] ?? 'N/A') . ")</p>";
                    echo "<p><strong>Dates:</strong> " . htmlspecialchars($election['start_date']) . 
                         " to " . htmlspecialchars($election['end_date']) . "</p>";
                    
                    if (isset($election['has_voted'])) {
                        echo "<p><strong>Has Voted:</strong> " . ($election['has_voted'] ? 'Yes' : 'No') . "</p>";
                    }
                    
                    if (isset($election['time_until_start'])) {
                        echo "<p><strong>Time Until Start:</strong> " . htmlspecialchars($election['time_until_start']) . "</p>";
                    }
                    
                    echo "</div>";
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Completed Elections</div>
            <?php
            $completedElections = $voteManager->getCompletedElections($userId);
            
            if (empty($completedElections)) {
                echo "<p>No completed elections found.</p>";
            } else {
                echo "<p>Found " . count($completedElections) . " completed election(s):</p>";
                
                foreach ($completedElections as $election) {
                    echo "<div class='election-card'>";
                    echo "<h3>" . htmlspecialchars($election['title']) . "</h3>";
                    echo "<p><strong>Status:</strong> " . htmlspecialchars($election['status']) . "</p>";
                    echo "<p><strong>Dates:</strong> " . htmlspecialchars($election['start_date']) . 
                         " to " . htmlspecialchars($election['end_date']) . "</p>";
                    
                    if (isset($election['has_voted'])) {
                        echo "<p><strong>Has Voted:</strong> " . ($election['has_voted'] ? 'Yes' : 'No') . "</p>";
                    }
                    
                    echo "</div>";
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Election Candidates</div>
            <?php
            $testElectionId = 0;
            if (!empty($eligibleElections)) {
                $testElectionId = $eligibleElections[0]['id'];
            } elseif (!empty($completedElections)) {
                $testElectionId = $completedElections[0]['id'];
            }
            
            if ($testElectionId) {
                $electionCandidates = $voteManager->getElectionCandidates($testElectionId);
                
                if (empty($electionCandidates)) {
                    echo "<p>No candidates found for election #$testElectionId.</p>";
                } else {
                    echo "<p>Found candidates for " . count($electionCandidates) . " position(s):</p>";
                    
                    foreach ($electionCandidates as $position) {
                        echo "<div class='position-section'>";
                        echo "<h3>" . htmlspecialchars($position['position_name']) . "</h3>";
                        
                        if (empty($position['candidates'])) {
                            echo "<p>No candidates for this position.</p>";
                        } else {
                            foreach ($position['candidates'] as $candidate) {
                                echo "<div class='candidate-item'>";
                                echo "<strong>" . htmlspecialchars($candidate['name']) . "</strong>";
                                
                                if (!empty($candidate['party_name'])) {
                                    echo " - " . htmlspecialchars($candidate['party_name']);
                                }
                                
                                if (!empty($candidate['bio'])) {
                                    echo "<br><small>" . htmlspecialchars($candidate['bio']) . "</small>";
                                }
                                
                                echo "</div>";
                            }
                        }
                        
                        echo "</div>";
                    }
                }
            } else {
                echo "<p>No elections found to test this function.</p>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">5. Election Results</div>
            <?php
            $testResultsElectionId = 0;
            if (!empty($completedElections)) {
                $testResultsElectionId = $completedElections[0]['id'];
            }
            
            if ($testResultsElectionId) {
                $electionResults = $voteManager->getElectionResults($testResultsElectionId);
                
                if (is_string($electionResults)) {
                    echo "<p>Message: " . htmlspecialchars($electionResults) . "</p>";
                } elseif (empty($electionResults)) {
                    echo "<p>No results found for election #$testResultsElectionId.</p>";
                } else {
                    echo "<p>Results for Election #$testResultsElectionId:</p>";
                    
                    foreach ($electionResults as $position) {
                        echo "<div class='position-section'>";
                        echo "<h3>" . htmlspecialchars($position['position_name']) . "</h3>";
                        echo "<p>Total votes: " . $position['total_votes'] . "</p>";
                        
                        if (empty($position['candidates'])) {
                            echo "<p>No candidates for this position.</p>";
                        } else {
                            foreach ($position['candidates'] as $candidate) {
                                echo "<div class='candidate-item'>";
                                echo "<strong>" . htmlspecialchars($candidate['name']) . "</strong>";
                                
                                if (!empty($candidate['party_name'])) {
                                    echo " - " . htmlspecialchars($candidate['party_name']);
                                }
                                
                                echo "<br>Votes: " . $candidate['vote_count'] . 
                                     " (" . $candidate['percentage'] . "%)";
                                
                                echo "<div class='percentage-bar' style='width: " . 
                                     $candidate['percentage'] . "%'></div>";
                                
                                echo "</div>";
                            }
                        }
                        
                        echo "</div>";
                    }
                    
                    $totalVotes = $voteManager->getTotalVoteCount($testResultsElectionId);
                    echo "<p><strong>Total unique voters:</strong> $totalVotes</p>";
                }
            } else {
                echo "<p>No completed elections found to test this function.</p>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">6. Voter Receipt</div>
            <?php
            $receiptElectionId = 0;
            foreach ($completedElections as $election) {
                if ($election['has_voted'] ?? false) {
                    $receiptElectionId = $election['id'];
                    break;
                }
            }
            
            if ($receiptElectionId) {
                $receipt = $voteManager->getVoterReceipt($userId, $receiptElectionId);
                
                if ($receipt === false) {
                    echo "<p>No receipt found for election #$receiptElectionId.</p>";
                } else {
                    echo "<div class='result'>";
                    echo "Receipt Code: " . htmlspecialchars($receipt['receipt_code']) . "\n";
                    echo "Election ID: " . $receipt['election_id'] . "\n";
                    echo "Timestamp: " . htmlspecialchars($receipt['timestamp']) . "\n";
                    echo "</div>";
                }
            } else {
                echo "<p>No elections with votes found to test this function.</p>";
            }
            ?>
        </div>

        <p><a href="index.php">Back to Voter Dashboard</a></p>
    </div>
</body>
</html>
