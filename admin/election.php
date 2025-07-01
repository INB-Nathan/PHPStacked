<?php
require_once '../includes/admin_header.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$electionObj = new Election($pdo);

$addError = $addSuccess = $editError = $editSuccess = $deleteError = $deleteSuccess = '';
$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// --- ADD ELECTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_election'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'upcoming';
    $max_votes = (int)($_POST['max_votes_per_user'] ?? 1);

    if ($title === '' || $start_date === '' || $end_date === '' || $max_votes < 1) {
        $addError = 'All required fields must be filled out.';
    } else {
        $data = compact('title', 'description', 'start_date', 'end_date', 'status', 'max_votes');
        $data['max_votes_per_user'] = $max_votes;
        $r = $electionObj->addElection($data);
        $addSuccess = $r === true ? 'Election created!' : $r;
    }
}

// --- UPDATE ELECTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_election'])) {
    $id = (int)$_POST['election_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'upcoming';
    $max_votes = (int)($_POST['max_votes_per_user'] ?? 1);

    if ($title === '' || $start_date === '' || $end_date === '' || $max_votes < 1) {
        $editError = 'All required fields must be filled out.';
        $editing_id = $id;
    } else {
        $data = compact('title', 'description', 'start_date', 'end_date', 'status', 'max_votes');
        $data['max_votes_per_user'] = $max_votes;
        $r = $electionObj->updateElection($id, $data);
        if ($r === true) {
            $editSuccess = 'Election updated!';
            $editing_id = 0;
        } else {
            $editError = $r;
            $editing_id = $id;
        }
    }
}

// --- DELETE ELECTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_election'])) {
    $id = (int)$_POST['election_id'];
    $r = $electionObj->deleteElection($id);
    if ($r === true) {
        $deleteSuccess = 'Election deleted.';
        if ($editing_id === $id) $editing_id = 0;
    } else {
        $deleteError = $r;
    }
}

// --- FETCH ELECTIONS ---
try {
    $elections = $electionObj->getAll();
} catch (PDOException $e) {
    $elections = [];
    $fetchError = "Could not fetch elections: " . htmlspecialchars($e->getMessage());
}

// --- Fetch single for edit if needed
$editing_election = null;
if ($editing_id) {
    $row = $electionObj->getById($editing_id);
    if (is_array($row)) $editing_election = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Management</title>
    <link rel="stylesheet" href="../css/admin_header.css?v=1.1">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/party.css">
    <link rel="stylesheet" href="../css/container.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/party_popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body>
    <?php adminHeader('elections'); ?>

    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>

    <div class="container-flex">
        <div class="management-box">
            <h1>Election Management</h1>
            <div class="add-party-form">
                <form method="post" autocomplete="off">
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

    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
            text-align: center;
            width: 90%;
            margin: 0 auto;
        }
        .status-upcoming {
            background-color: #f39c12;
            color: #fff;
        }
        .status-active {
            background-color: #27ae60;
            color: #fff;
        }
        .status-completed {
            background-color: #3498db;
            color: #fff;
        }
        .status-cancelled {
            background-color: #e74c3c;
            color: #fff;
        }
        
        /* Override for datetime inputs to match other inputs */
        input[type="datetime-local"] {
            padding: 10px 12px;
            border: 1px solid #e3e3e3;
            border-radius: 8px;
            font-size: 1.07em;
            background: #f5f6fa;
            color: #1d2b20;
            transition: border-color 0.2s;
        }
        input[type="datetime-local"]:focus {
            border-color: #27ae60;
            outline: none;
        }
        input[type="number"] {
            padding: 10px 12px;
            border: 1px solid #e3e3e3;
            border-radius: 8px;
            font-size: 1.07em;
            background: #f5f6fa;
            color: #1d2b20;
            transition: border-color 0.2s;
        }
        input[type="number"]:focus {
            border-color: #27ae60;
            outline: none;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('logoutNavBtn').onclick = e => {
                e.preventDefault();
                document.getElementById('logoutModal').classList.add('active');
            };
            document.getElementById('cancelLogoutBtn').onclick = () => {
                document.getElementById('logoutModal').classList.remove('active');
            };
            document.getElementById('logoutModal').onclick = e => {
                if (e.target === e.currentTarget) {
                    document.getElementById('logoutModal').classList.remove('active');
                }
            };
        });
    </script>
</body>
</html>