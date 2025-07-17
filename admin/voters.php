<?php
require_once '../includes/autoload.php';
session_start();

$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$userManager = new UserManager($pdo);
$electionManager = new ElectionManager($pdo);

$elections = $electionManager->getAll();

$message = "";
$messageType = "";

if (isset($_GET['edit_permissions']) && is_numeric($_GET['edit_permissions'])) {
    $voter_id = (int)$_GET['edit_permissions'];
    $voter_details = $userManager->getUserById($voter_id);
    $voter_elections = $userManager->getVoterElections($voter_id);
    
    // If POST form submitted to update permissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$securityManager->validateCSRFToken($_POST['csrf_token'])) {
            $message = 'Security validation failed. Please try again.';
            $messageType = 'error';
        } else {
            // Get selected elections from the form
            $selected_elections = $_POST['voter_elections'] ?? [];
            
            // Update voter's election permissions
            $result = $userManager->updateVoterElections($voter_id, $selected_elections);
            
            if ($result === true) {
                $message = "Voter election permissions updated successfully.";
                $messageType = "success";
                // Refresh the voter elections
                $voter_elections = $userManager->getVoterElections($voter_id);
            } else {
                $message = $result;
                $messageType = "error";
            }
        }
    }
}

// Delete voter
if (isset($_POST['delete_voter']) && isset($_POST['voter_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCSRFToken($_POST['csrf_token'])) {
        $message = 'Security validation failed. Please try again.';
        $messageType = 'error';
    } else {
        $id = intval($_POST['voter_id']);
        $result = $userManager->deleteUser($id, 'voter');
        
        if ($result === true) {
            $message = "Voter deleted successfully.";
            $messageType = "success";
        } else {
            $message = $result;
            $messageType = "error";
        }
    }
}

// Add new voter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_voter'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCSRFToken($_POST['csrf_token'])) {
        $message = 'Security validation failed. Please try again.';
        $messageType = 'error';
    } else {
        $name = $_POST['name'] ?? '';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $selected_elections = $_POST['voter_elections'] ?? [];

        // Validate inputs using InputValidator
        $nameValidation = InputValidator::validateName($name);
        $usernameValidation = InputValidator::validateUsername($username);
        $emailValidation = InputValidator::validateEmail($email);
        $passwordValidation = InputValidator::validatePassword($password);

        if (!$nameValidation['valid']) {
            $message = $nameValidation['message'];
            $messageType = "error";
        } elseif (!$usernameValidation['valid']) {
            $message = $usernameValidation['message'];
            $messageType = "error";
        } elseif (!$emailValidation['valid']) {
            $message = $emailValidation['message'];
            $messageType = "error";
        } elseif (!$passwordValidation['valid']) {
            $message = $passwordValidation['message'];
            $messageType = "error";
        } else {
            // Sanitize inputs
            $name = ucwords(InputValidator::sanitizeString($name));
            $username = InputValidator::sanitizeString($username);
            $email = InputValidator::sanitizeString($email);

            // Use the addUser method to handle validation and insertion
            $result = $userManager->addUser($username, $email, $password, $name, 'voter', true);
            
            if ($result === true) {
                // If successfully added, get the new voter's ID
                $new_voter_id = $userManager->getLastInsertId();
                
                // Assign selected elections to the voter
                if (!empty($selected_elections)) {
                    $userManager->updateVoterElections($new_voter_id, $selected_elections);
                }
                
                $message = "New voter added successfully.";
                $messageType = "success";
            } else {
                $message = $result;
                $messageType = "error";
            }
        }
    }
}

$voters = $userManager->getAllUsersByType('voter');
foreach ($voters as &$voter) {
    $voter['elections'] = $userManager->getVoterElections($voter['id']);
}
unset($voter);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Voters Management</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/voter.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/party_table.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
</head>

<body>
    <?php adminHeader('voters'); ?>
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
        <h1>Voters Management</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>" style="margin: 20px auto; max-width: 800px; padding: 10px 15px; border-radius: 5px; text-align: center; 
                                    background-color: <?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>; 
                                    color: <?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['edit_permissions']) && isset($voter_details)): ?>
            <!-- Edit Voter Election Permissions Form -->
            <div class="add-container" style="max-width: 600px;">
                <h2>Edit Election Permissions for <?php echo htmlspecialchars($voter_details['full_name']); ?></h2>
                <form action="?edit_permissions=<?php echo (int)$_GET['edit_permissions']; ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div style="margin: 20px 0;">
                        <p><strong>Select elections this voter can participate in:</strong></p>
                        
                        <?php if (empty($elections)): ?>
                            <p>No elections available.</p>
                        <?php else: ?>
                            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 8px;">
                                <?php foreach ($elections as $election): ?>
                                    <?php 
                                        $is_checked = false;
                                        foreach ($voter_elections as $ve) {
                                            if ($ve['id'] == $election['id']) {
                                                $is_checked = true;
                                                break;
                                            }
                                        }
                                    ?>
                                    <div style="margin: 8px 0;">
                                        <label style="font-weight: normal; display: flex; align-items: center;">
                                            <input type="checkbox" name="voter_elections[]" value="<?php echo $election['id']; ?>" 
                                                <?php echo $is_checked ? 'checked' : ''; ?>>
                                            <span style="margin-left: 10px;">
                                                <?php echo htmlspecialchars($election['title']); ?> 
                                                <span class="status-badge status-<?php echo strtolower($election['status']); ?>" 
                                                      style="width: auto; margin-left: 10px; padding: 2px 8px; display: inline-block;">
                                                    <?php echo htmlspecialchars($election['status']); ?>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                        <button type="submit" name="update_permissions" style="flex: 1; margin-right: 10px;">Update Permissions</button>
                        <a href="voters.php" style="flex: 1; display: block; background: #6c757d; color: white; text-align: center; 
                                                  padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Add Voter Form -->
            <div class="add-container">
                <h2>Add New Voter</h2>
                <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="add_voter" value="1">
                    
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required>
                    
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required minlength="3">
                    
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    
                    <label for="voter_elections">Elections this voter can participate in:</label>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #f5f6fa; margin: 10px 0;">
                        <?php if (empty($elections)): ?>
                            <p>No elections available.</p>
                        <?php else: ?>
                            <?php foreach ($elections as $election): ?>
                                <div style="margin: 8px 0;">
                                    <label style="font-weight: normal; display: flex; align-items: center;">
                                        <input type="checkbox" name="voter_elections[]" value="<?php echo $election['id']; ?>">
                                        <span style="margin-left: 10px;">
                                            <?php echo htmlspecialchars($election['title']); ?> 
                                            <span class="status-badge status-<?php echo strtolower($election['status']); ?>" 
                                                  style="width: auto; margin-left: 10px; padding: 2px 8px; display: inline-block;">
                                                <?php echo htmlspecialchars($election['status']); ?>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit">Add Voter</button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Voters Table -->
        <div class="data-container">
            <h2>Registered Voters</h2>
            <?php if (!empty($voters)): ?>
                <table class="party-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Elections</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($voters as $voter): ?>
                            <tr>
                                <td><?= htmlspecialchars($voter['full_name']) ?></td>
                                <td><?= htmlspecialchars($voter['username']) ?></td>
                                <td><?= htmlspecialchars($voter['email']) ?></td>
                                <td>
                                    <?php if (!empty($voter['elections'])): ?>
                                        <ul style="margin: 0; padding-left: 20px;">
                                            <?php foreach ($voter['elections'] as $election): ?>
                                                <li>
                                                    <?= htmlspecialchars($election['title']) ?>
                                                    <span class="status-badge status-<?= strtolower($election['status']) ?>" 
                                                          style="width: auto; margin-left: 5px; padding: 2px 8px; display: inline-block; font-size: 0.8em;">
                                                        <?= htmlspecialchars($election['status']) ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span style="color: #777;">No elections assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="voters.php?edit_permissions=<?= $voter['id'] ?>" class="btn-edit" style="text-decoration: none; padding: 5px 10px; background-color: #007bff; color: white; border-radius: 4px; display: inline-block; margin-right: 5px;">
                                        Edit Elections
                                    </a>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete voter <?= htmlspecialchars($voter['full_name']) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="voter_id" value="<?= $voter['id'] ?>">
                                        <button type="submit" name="delete_voter" class="btn-delete" style="border: none; padding: 5px 10px; background-color: #dc3545; color: white; border-radius: 4px; cursor: pointer;">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No voters found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof logoutNavBtnClickHandler === 'undefined') {
            const logoutNavBtn = document.getElementById('logoutNavBtn');
            const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
            const logoutModal = document.getElementById('logoutModal');
            
            if (logoutNavBtn) {
                logoutNavBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    logoutModal.classList.add('active');
                });
            }
            
            if (cancelLogoutBtn) {
                cancelLogoutBtn.addEventListener('click', function() {
                    logoutModal.classList.remove('active');
                });
            }
            
            if (logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.remove('active');
                    }
                });
            }
        }
    });
    </script>
</body>
</html>