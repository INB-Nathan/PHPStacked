<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
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
$electionId = InputValidator::validateId($_GET['election_id'] ?? '');

// Check if the election ID is valid
if (!$electionId) {
    header("Location: available_elections.php");
    exit;
}

// Create an instance of VoteManager
$voteManager = new VoteManager($pdo);

// Verify that the user has actually voted in this election
if (!$voteManager->hasUserVoted($userId, $electionId)) {
    echo "<div style='color:red;'>No vote record found for this election.</div>";
    header("Refresh: 3; URL=available_elections.php");
    exit;
}

// Get the vote receipt
$receipt = $voteManager->getVoterReceipt($userId, $electionId);

// Create an instance of ElectionManager
$electionManager = new ElectionManager($pdo);

// Get election details
$election = $electionManager->getById($electionId);

// Get user details if full_name is not in session
if (empty($_SESSION['full_name'])) {
    $userManager = new UserManager($pdo);
    $userDetails = $userManager->getUserById($userId);
    $_SESSION['full_name'] = $userDetails['full_name'] ?? 'Unknown Voter';
}

// Format timestamp if receipt exists
$formattedTimestamp = 'N/A';
$receiptCode = 'ERROR-GENERATING-CODE';
$voterName = $_SESSION['full_name'] ?? 'N/A';

if ($receipt && isset($receipt['timestamp'])) {
    $formattedTimestamp = date('F j, Y \a\t g:i A', strtotime($receipt['timestamp']));
    $receiptCode = $receipt['receipt_code'] ?? 'ERROR-GENERATING-CODE';
}

// Set page title
$pageTitle = "Vote Confirmation - " . ($election['title'] ?? 'Election');

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
    <link rel="stylesheet" href="../css/vote.css">
    <link rel="stylesheet" href="../css/vote_confirmation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
</head>
<body>
    <?php voterHeader(''); ?>
    
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    
    <div class="container">
        
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
                    <strong>Date & Time:</strong> <?= htmlspecialchars($formattedTimestamp) ?>
                </div>
                <div class="receipt-item">
                    <strong>Voter:</strong> <?= htmlspecialchars($voterName) ?>
                </div>
                
                <div class="receipt-code">
                    <?= htmlspecialchars($receiptCode) ?>
                </div>
                
                <div class="receipt-item">
                    <small>This receipt does not reveal your voting choices. It only confirms that you participated in the election.</small>
                </div>
            </div>
            
            <div class="no-print">
                <button class="print-button" onclick="handlePrint();">Print Receipt</button>
                <a href="index.php" class="home-button">Return to Dashboard</a>
            </div>
        </div>
    </div>
    
    <script>
        function handlePrint() {
            try {
                const button = document.querySelector('.print-button');
                const originalText = button.textContent;
                button.textContent = 'Printing...';
                button.style.backgroundColor = '#1976D2';
                
                if (typeof window.print !== 'function') {
                    alert('Printing is not supported in this browser. Please save the page or take a screenshot.');
                    button.textContent = originalText;
                    button.style.backgroundColor = '#2196F3';
                    return;
                }
                
                setTimeout(function() {
                    window.print();
                    setTimeout(function() {
                        button.textContent = originalText;
                        button.style.backgroundColor = '#2196F3';
                    }, 1000);
                }, 100);
            } catch (error) {
                console.error('Print failed:', error);
                alert('Unable to print. Please use your browser\'s print function (Ctrl+P or Cmd+P) or save the page.');
                const button = document.querySelector('.print-button');
                button.textContent = 'Print Receipt';
                button.style.backgroundColor = '#2196F3';
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                handlePrint();
            }
        });
    </script>
</body>
</html>
