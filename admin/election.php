<?php
require_once '../includes/autoload.php';

$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();

session_start();

$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

// Election Manager
$electionManager = new ElectionManager($pdo);
$electionManager->updateElectionStatuses();

if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$electionManager = new ElectionManager($pdo);

$addError = $addSuccess = $editError = $editSuccess = $deleteError = $deleteSuccess = '';
$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCSRFToken($_POST['csrf_token'])) {
        $csrf_error = 'Security validation failed. Please try again.';
        goto render_page;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_election'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'upcoming';
    $max_votes = (int)($_POST['max_votes_per_user'] ?? 1);

    // Validate inputs using InputValidator
    $titleValidation = InputValidator::validateName($title);
    $startDateValidation = InputValidator::validateDate($start_date);
    $endDateValidation = InputValidator::validateDate($end_date);

    if (!$titleValidation['valid']) {
        $addError = $titleValidation['message'];
    } elseif (!$startDateValidation['valid']) {
        $addError = $startDateValidation['message'];
    } elseif (!$endDateValidation['valid']) {
        $addError = $endDateValidation['message'];
    } elseif ($max_votes < 1) {
        $addError = 'Maximum votes per user must be at least 1.';
    } else {
        // Sanitize inputs
        $title = InputValidator::sanitizeString($title);
        $description = InputValidator::sanitizeString($description);
        
        $data = compact('title', 'description', 'start_date', 'end_date', 'status', 'max_votes');
        $data['max_votes_per_user'] = $max_votes;
        $result = $electionManager->addElection($data);
        $addSuccess = $result === true ? 'Election created!' : $result;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_election'])) {
    $id = (int)$_POST['election_id'];
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'upcoming';
    $max_votes = (int)($_POST['max_votes_per_user'] ?? 1);

    // Validate inputs using InputValidator
    $titleValidation = InputValidator::validateName($title);
    $startDateValidation = InputValidator::validateDate($start_date);
    $endDateValidation = InputValidator::validateDate($end_date);

    if (!$titleValidation['valid']) {
        $editError = $titleValidation['message'];
        $editing_id = $id;
    } elseif (!$startDateValidation['valid']) {
        $editError = $startDateValidation['message'];
        $editing_id = $id;
    } elseif (!$endDateValidation['valid']) {
        $editError = $endDateValidation['message'];
        $editing_id = $id;
    } elseif ($max_votes < 1) {
        $editError = 'Maximum votes per user must be at least 1.';
        $editing_id = $id;
    } else {
        // Sanitize inputs
        $title = InputValidator::sanitizeString($title);
        $description = InputValidator::sanitizeString($description);
        
        $data = compact('title', 'description', 'start_date', 'end_date', 'status', 'max_votes');
        $data['max_votes_per_user'] = $max_votes;
        $result = $electionManager->updateElection($id, $data);
        if ($result === true) {
            $editSuccess = 'Election updated!';
            $editing_id = 0;
        } else {
            $editError = $result;
            $editing_id = $id;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_election'])) {
    $id = (int)$_POST['election_id'];
    $result = $electionManager->deleteElection($id);
    if ($result === true) {
        $deleteSuccess = 'Election deleted.';
        if ($editing_id === $id) $editing_id = 0;
    } else {
        $deleteError = $result;
    }
}

try {
    $elections = $electionManager->getAll();
} catch (PDOException $e) {
    $elections = [];
    $fetchError = "Could not fetch elections: " . htmlspecialchars($e->getMessage());
}

$editing_election = null;
if ($editing_id) {
    $row = $electionManager->getById($editing_id);
    if (is_array($row)) $editing_election = $row;
}

render_page:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Election Management</title>
    <link rel="stylesheet" href="../css/admin_header.css?v=1.1">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/party.css">
    <link rel="stylesheet" href="../css/container.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/party_popup.css">
    <link rel="stylesheet" href="../css/admin_election.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
</head>
<body>
    <?php adminHeader('election', $csrf_token); ?>

    <?php if (isset($csrf_error)): ?>
    <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px; text-align: center; max-width: 800px; margin-left: auto; margin-right: auto;">
        <?php echo htmlspecialchars($csrf_error); ?>
    </div>
    <?php endif; ?>

    <div class="container-flex">
        <div class="management-box">
            <h1>Election Management</h1>
            <div class="add-party-form">
                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="form_row"><strong><?= $editing_election ? 'Edit Election' : 'Add New Election' ?></strong></div>
                    <?php if ($addError): ?><div class="msg-error"><?= htmlspecialchars($addError) ?></div><?php endif; ?>
                    <?php if ($addSuccess): ?><div class="msg-success"><?= htmlspecialchars($addSuccess) ?></div><?php endif; ?>
                    <?php if ($editError): ?><div class="msg-error"><?= htmlspecialchars($editError) ?></div><?php endif; ?>
                    <?php if ($editSuccess): ?><div class="msg-success"><?= htmlspecialchars($editSuccess) ?></div><?php endif; ?>
                    
                    <div class="form-row">
                        <label for="title">Title:</label><br>
                        <input type="text" name="title" id="title" maxlength="255" required style="width:97%;"
                            value="<?= htmlspecialchars($editing_election['title'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <label for="description">Description:</label><br>
                        <textarea name="description" id="description" rows="2" maxlength="500" style="width:97%;"><?= htmlspecialchars($editing_election['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <label for="start_date">Start Date:</label><br>
                        <input type="datetime-local" name="start_date" id="start_date" required style="width:97%;"
                            value="<?= isset($editing_election['start_date']) ? date('Y-m-d\TH:i', strtotime($editing_election['start_date'])) : '' ?>">
                    </div>
                    <div class="form-row">
                        <label for="end_date">End Date:</label><br>
                        <input type="datetime-local" name="end_date" id="end_date" required style="width:97%;"
                            value="<?= isset($editing_election['end_date']) ? date('Y-m-d\TH:i', strtotime($editing_election['end_date'])) : '' ?>">
                    </div>
                    <div class="form-row">
                        <label for="status">Status:</label><br>
                        <select name="status" id="status" required style="width:97%; padding:10px 12px; border-radius:8px; border:1px solid #e3e3e3;">
                            <?php
                            $statuses = ['upcoming' => 'Upcoming', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
                            $selected = $editing_election['status'] ?? 'upcoming';
                            foreach ($statuses as $val => $label) {
                                $sel = $val === $selected ? 'selected' : '';
                                echo "<option value=\"$val\" $sel>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="max_votes_per_user">Max Votes Per User:</label><br>
                        <input type="number" name="max_votes_per_user" id="max_votes_per_user" min="1" required style="width:97%;"
                            value="<?= htmlspecialchars($editing_election['max_votes_per_user'] ?? 1) ?>">
                    </div>
                    <div class="form-row">
                        <?php if ($editing_election): ?>
                            <input type="hidden" name="election_id" value="<?= $editing_election['id'] ?>">
                            <button type="submit" name="update_election" class="btn-save">Update Election</button>
                            <a href="election.php" class="btn-cancel">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_election" class="btn-save">Add Election</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <h2 style="margin-top:36px;">Existing Elections</h2>
            <?php if (!empty($fetchError)): ?>
                <div class="msg-error"><?= htmlspecialchars($fetchError) ?></div>
            <?php endif; ?>
            <?php if ($deleteError): ?><div class="msg-error"><?= htmlspecialchars($deleteError) ?></div><?php endif; ?>
            <?php if ($deleteSuccess): ?><div class="msg-success"><?= htmlspecialchars($deleteSuccess) ?></div><?php endif; ?>

            <table class="party-table">
                <thead>
                    <tr>
                        <th style="width:24%;">Title</th>
                        <th style="width:22%;">Dates</th>
                        <th style="width:14%;">Status</th>
                        <th style="width:12%;">Max Votes</th>
                        <th style="width:18%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($elections)): ?>
                        <?php foreach ($elections as $election): ?>
                            <?php if ($editing_id === (int)$election['id']): continue; endif; ?>
                            <tr>
                                <td><?= htmlspecialchars($election['title']) ?></td>
                                <td>
                                    <?= date('Y-m-d H:i', strtotime($election['start_date'])) ?><br>
                                    <span style="display:block; text-align:center; margin:2px 0;">↓</span>
                                    <?= date('Y-m-d H:i', strtotime($election['end_date'])) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($election['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($election['status'])) ?>
                                    </span>
                                </td>
                                <td style="text-align:center;"><?= htmlspecialchars($election['max_votes_per_user']) ?></td>
                                <td>
                                    <a href="election.php?edit=<?= $election['id'] ?>" class="btn-edit">Edit</a>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this election?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="election_id" value="<?= $election['id'] ?>">
                                        <button type="submit" name="delete_election" class="btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No elections found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        });
    </script>
</body>
</html>