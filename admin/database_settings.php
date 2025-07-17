<?php
require_once '../includes/autoload.php';
session_start();

$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$dbManager = new DatabaseManager($pdo);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCSRFToken($_POST['csrf_token'])) {
        $message = "Security validation failed. Please try again.";
        $message_type = 'error';
    }
    else if (isset($_POST['admin_password'])) {
        if ($dbManager->verifyAdminPassword($_POST['admin_password'])) {
            $action = $_POST['action'] ?? '';
            
            try {
                $result = null;
                
                switch ($action) {
                    case 'clear_users':
                        $result = $dbManager->clearUsers();
                        break;
                        
                    case 'clear_elections':
                        $result = $dbManager->clearElections();
                        break;
                        
                    case 'clear_positions':
                        $result = $dbManager->clearPositions();
                        break;
                        
                    case 'clear_parties':
                        $result = $dbManager->clearParties();
                        break;
                        
                    case 'clear_votes':
                        $result = $dbManager->clearVotes();
                        break;
                        
                    case 'reset_database':
                        $result = $dbManager->resetDatabase();
                        break;
                        
                    default:
                        throw new Exception("Invalid action requested.");
                }
                
                if ($result && $result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'] ?? "Unknown error occurred";
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Invalid administrator password. Action aborted.";
            $message_type = 'error';
        }
    }
}

// Get record counts
$counts = $dbManager->getRecordCounts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Database Maintenance</title>
    <link rel="stylesheet" href="../css/admin_header.css?v=1.1">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/party.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/admin_database.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
</head>
<body>
    <?php adminHeader('database', $csrf_token); ?>
    
    <div id="confirmationModal" class="confirmation-modal">
        <div class="modal-content">
            <h3>Confirm Action</h3>
            <p id="modalMessage">Are you sure you want to perform this action? This cannot be undone.</p>
            <form id="confirmForm" method="post">
                <input type="hidden" id="actionInput" name="action" value="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label for="adminPassword">Enter Administrator Password:</label>
                    <input type="password" id="adminPassword" name="admin_password" class="password-field" required>
                </div>
                <div class="btn-group">
                    <button type="button" id="cancelActionBtn" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <div class="maintenance-container">
        <div class="warning-header">
            <i class="fas fa-exclamation-triangle"></i> WARNING: Database Maintenance Operations
        </div>

        <?php if ($message): ?>
            <div class="message-container message-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <p>This page contains administrative options for clearing data from the election system. All operations are <strong>irreversible</strong> and should be used with extreme caution.</p>
        
        <h2>Database Operations</h2>
        
        <div class="action-grid">
            <div class="action-card">
                <span class="record-count"><?= htmlspecialchars($counts['users']) ?></span>
                <h3>Clear Voter Accounts</h3>
                <p>Removes all voter accounts from the system. Administrator accounts will be preserved.</p>
                <button class="btn-danger action-btn" data-action="clear_users">Clear Voters</button>
            </div>
            
            <div class="action-card">
                <span class="record-count"><?= htmlspecialchars($counts['elections']) ?></span>
                <h3>Clear Elections</h3>
                <p>Removes all elections and their associated data (candidates, votes, etc).</p>
                <button class="btn-danger action-btn" data-action="clear_elections">Clear Elections</button>
            </div>
            
            <div class="action-card">
                <span class="record-count"><?= htmlspecialchars($counts['positions']) ?></span>
                <h3>Clear Positions</h3>
                <p>Removes all position definitions. This will also remove candidates associated with these positions.</p>
                <button class="btn-danger action-btn" data-action="clear_positions">Clear Positions</button>
            </div>
            
            <div class="action-card">
                <span class="record-count"><?= htmlspecialchars($counts['parties']) ?></span>
                <h3>Clear Parties</h3>
                <p>Removes all political parties except the "Independent" party.</p>
                <button class="btn-danger action-btn" data-action="clear_parties">Clear Parties</button>
            </div>
            
            <div class="action-card">
                <span class="record-count"><?= htmlspecialchars($counts['votes']) ?></span>
                <h3>Clear Votes</h3>
                <p>Removes all cast votes while preserving elections, candidates, and voters.</p>
                <button class="btn-danger action-btn" data-action="clear_votes">Clear Votes</button>
            </div>
        </div>
        
        <div class="danger-zone">
            <h3>DANGER ZONE</h3>
            <p>The following action will reset the entire database to its initial state. All data will be permanently deleted except for administrator accounts.</p>
            <button class="btn-danger action-btn" data-action="reset_database">Reset Entire Database</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const actionButtons = document.querySelectorAll('.action-btn');
            const confirmationModal = document.getElementById('confirmationModal');
            const modalMessage = document.getElementById('modalMessage');
            const actionInput = document.getElementById('actionInput');
            const cancelActionBtn = document.getElementById('cancelActionBtn');
            
            actionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const action = this.dataset.action;
                    
                    switch(action) {
                        case 'clear_users':
                            modalMessage.textContent = "Are you sure you want to delete all voter accounts? This action cannot be undone.";
                            break;
                        case 'clear_elections':
                            modalMessage.textContent = "Are you sure you want to delete all elections and their associated data? This action cannot be undone.";
                            break;
                        case 'clear_positions':
                            modalMessage.textContent = "Are you sure you want to delete all positions? This will also remove candidates. This action cannot be undone.";
                            break;
                        case 'clear_parties':
                            modalMessage.textContent = "Are you sure you want to delete all political parties (except Independent)? This action cannot be undone.";
                            break;
                        case 'clear_votes':
                            modalMessage.textContent = "Are you sure you want to delete all votes? Election results will be lost. This action cannot be undone.";
                            break;
                        case 'reset_database':
                            modalMessage.textContent = "WARNING: This will reset the ENTIRE database to its initial state. All data except administrator accounts will be permanently deleted. This action CANNOT be undone.";
                            break;
                    }
                    
                    actionInput.value = action;
                    confirmationModal.style.display = 'flex';
                    document.getElementById('adminPassword').focus();
                });
            });
            
            cancelActionBtn.addEventListener('click', function() {
                confirmationModal.style.display = 'none';
            });
            
            confirmationModal.addEventListener('click', function(e) {
                if (e.target === confirmationModal) {
                    confirmationModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>