<?php
require_once '../includes/autoload.php';
session_start();

// Include security checks
$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout('../login.php');

// Ensure the user is logged in and is a voter
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    http_response_code(403);
    echo "<div style='color:red;'>Access denied. Only voters can access this page.</div>";
    exit;
}

// Get the current user's ID
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo "<div style='color:red;'>User ID not found in session.</div>";
    exit;
}

// Get the election ID from the query string
$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

// Check if the election ID is valid
if (!$electionId) {
    header("Location: all_elections.php");
    exit;
}

// Create an instance of VoteManager
$voteManager = new VoteManager($pdo);

// Verify that the user has actually voted in this election
if (!$voteManager->hasUserVoted($userId, $electionId)) {
    echo "<div style='color:red;'>No vote record found for this election.</div>";
    header("Refresh: 3; URL=all_elections.php");
    exit;
}

// Get the vote receipt
$receipt = $voteManager->getVoterReceipt($userId, $electionId);

// Create an instance of ElectionManager
$electionManager = new ElectionManager($pdo);

// Get election details
$election = $electionManager->getById($electionId);

// Set page title
$pageTitle = "Vote Confirmation - " . ($election['title'] ?? 'Election');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/voter.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #4CAF50;
            margin-top: 30px;
            text-align: center;
        }
        
        .confirmation-container h2 {
            color: #4CAF50;
            margin-top: 0;
        }
        
        .receipt-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin: 20px 0;
            text-align: left;
        }
        
        .receipt-item {
            margin-bottom: 10px;
        }
        
        .receipt-item strong {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }
        
        .receipt-code {
            font-family: monospace;
            font-size: 20px;
            letter-spacing: 2px;
            color: #333;
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
        
        .confirmation-message {
            margin: 20px 0;
            line-height: 1.6;
            color: #555;
        }
        
        .print-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            cursor: pointer;
        }
        
        .home-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            margin-left: 10px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                font-size: 12pt;
            }
            
            .confirmation-container {
                border: none;
                padding: 0;
            }
            
            .receipt-box {
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="no-print">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <nav>
                <a href="index.php">Dashboard</a> |
                <a href="all_elections.php">All Elections</a> |
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="confirmation-container">
            <h2>Your Vote Has Been Recorded</h2>
            
            <div class="confirmation-message">
                <p>Thank you for participating in this election. Your vote has been successfully recorded and will be counted.</p>
                <p>Please save or print this receipt for your records. You will need the receipt code if you need to verify your vote later.</p>
            </div>
            
            <div class="receipt-box">
                <div class="receipt-item">
                    <strong>Election:</strong> <?= htmlspecialchars($election['title'] ?? 'N/A') ?>
                </div>
                <div class="receipt-item">
                    <strong>Date & Time:</strong> <?= htmlspecialchars($receipt['timestamp'] ?? 'N/A') ?>
                </div>
                <div class="receipt-item">
                    <strong>Voter:</strong> <?= htmlspecialchars($_SESSION['full_name'] ?? 'N/A') ?>
                </div>
                
                <div class="receipt-code">
                    <?= htmlspecialchars($receipt['receipt_code'] ?? 'ERROR-GENERATING-CODE') ?>
                </div>
                
                <div class="receipt-item">
                    <small>This receipt does not reveal your voting choices. It only confirms that you participated in the election.</small>
                </div>
            </div>
            
            <div class="no-print">
                <button class="print-button" onclick="window.print();">Print Receipt</button>
                <a href="index.php" class="home-button">Return to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
