<?php
require_once '../includes/admin_header.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$partyObj = new Party($pdo);
$positionObj = new Position($pdo);

// Party messages & state
$addError = $partyAddSuccess = $partyEditError = $partyEditSuccess    = '';
$partyDeleteError = $partyDeleteSuccess = '';
$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// Position messages & state
$posAddError = $posAddSuccess = $posEditError = $posEditSuccess = '';
$posDeleteError = $posDeleteSuccess = '';
$pos_editing_id = isset($_GET['edit_position']) ? (int)$_GET['edit_position'] : 0;

// --- ADD PARTY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_party'])) {
    $party_name = trim($_POST['party_name'] ?? '');
    $party_desc = trim($_POST['party_desc'] ?? '');
    if ($party_name === '') {
        $addError = 'Party name required.';
    } else {
        try {
            $partyObj->add($party_name, $party_desc);
            $partyAddSuccess = 'Party added!';
        } catch (PDOException $e) {
            $addError = $e->getCode() == 23000
                ? 'Party name must be unique.'
                : 'DB error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// --- UPDATE PARTY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_party'])) {
    $party_id = (int)($_POST['party_id'] ?? 0);
    $party_name = trim($_POST['party_name'] ?? '');
    $party_desc = trim($_POST['party_desc'] ?? '');
    if ($party_name === '') {
        $partyEditError = 'Party name required.';
        $editing_id = $party_id;
    } else {
        try {
            $partyObj->update($party_id, $party_name, $party_desc);
            $partyEditSuccess = 'Party updated!';
            $editing_id = 0;
        } catch (PDOException $e) {
            $partyEditError = $e->getCode() == 23000
                ? 'Party name must be unique.'
                : 'DB error: ' . htmlspecialchars($e->getMessage());
            $editing_id = $party_id;
        }
    }
}

// --- DELETE PARTY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_party'])) {
    $party_id = (int)($_POST['party_id'] ?? 0);
    try {
        $partyObj->delete($party_id);
        $partyDeleteSuccess = 'Party deleted.';
        if ($editing_id === $party_id) {
            $editing_id = 0;
        }
    } catch (PDOException $e) {
        $partyDeleteError = 'Delete failed: ' . htmlspecialchars($e->getMessage());
    }
}

// --- FETCH PARTIES ---
try {
    $parties = $partyObj->getAll();
} catch (PDOException $e) {
    $parties = [];
    $fetchError = "Could not fetch parties: " . htmlspecialchars($e->getMessage());
}

// --- ADD POSITION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_position'])) {
    $position_name = trim($_POST['position_name'] ?? '');
    if ($position_name === '') {
        $posAddError = 'Position name required.';
    } else {
        try {
            $positionObj->add($position_name);
            $posAddSuccess = 'Position added!';
        } catch (PDOException $e) {
            $posAddError = $e->getCode() == 23000
                ? 'Position name must be unique.'
                : 'DB error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// --- UPDATE POSITION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_position'])) {
    $position_id = (int)($_POST['position_id'] ?? 0);
    $position_name = trim($_POST['position_name'] ?? '');
    if ($position_name === '') {
        $posEditError = 'Position name required.';
        $pos_editing_id = $position_id;
    } else {
        try {
            $positionObj->update($position_id, $position_name);
            $posEditSuccess = 'Position updated!';
            $pos_editing_id = 0;
        } catch (PDOException $e) {
            $posEditError = $e->getCode() == 23000
                ? 'Position name must be unique.'
                : 'DB error: ' . htmlspecialchars($e->getMessage());
            $pos_editing_id = $position_id;
        }
    }
}

// --- DELETE POSITION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_position'])) {
    $position_id = (int)($_POST['position_id'] ?? 0);
    try {
        $positionObj->delete($position_id);
        $posDeleteSuccess = 'Position deleted.';
        if ($pos_editing_id === $position_id) {
            $pos_editing_id = 0;
        }
    } catch (PDOException $e) {
        $posDeleteError = 'Delete failed: ' . htmlspecialchars($e->getMessage());
    }
}

// --- FETCH POSITIONS ---
try {
    $positions = $positionObj->getAll();
} catch (PDOException $e) {
    $positions = [];
    $posFetchError = "Could not fetch positions: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Party & Position Management</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/party.css">
    <link rel="stylesheet" href="../css/container.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/party_popup.css">
</head>

<body>
    <?php adminHeader('party'); ?>

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
        <!-- Party Management -->
        <div class="management-box">
            <h1>Party Management</h1>
            <div class="add-party-form" style="max-width:400px;">
                <form method="post" autocomplete="off">
                    <div class="form_row"><strong>Add New Party</strong></div>
                    <?php if (!empty($addError)): ?>
                        <div class="msg-error"><?= htmlspecialchars($addError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($partyAddSuccess)): ?>
                        <div class="msg-success"><?= htmlspecialchars($partyAddSuccess) ?></div>
                    <?php endif; ?>
                    <div class="form-row">
                        <label for="party_name">Party Name:</label><br>
                        <input type="text" name="party_name" id="party_name" maxlength="100" required style="width:97%;">
                    </div>
                    <div class="form-row">
                        <label for="party_desc">Description (optional):</label><br>
                        <textarea name="party_desc" id="party_desc" rows="2" maxlength="255" style="width:97%;"></textarea>
                    </div>
                    <div class="form-row">
                        <button type="submit" name="add_party" class="btn-save">Add Party</button>
                    </div>
                </form>
            </div>

            <h2 style="margin-top:36px;">Existing Parties</h2>
            <?php if (!empty($fetchError)): ?>
                <div class="msg-error"><?= htmlspecialchars($fetchError) ?></div>
            <?php endif; ?>
            <?php if (!empty($partyDeleteError)): ?>
                <div class="msg-error"><?= htmlspecialchars($partyDeleteError) ?></div>
            <?php endif; ?>
            <?php if (!empty($partyDeleteSuccess)): ?>
                <div class="msg-success"><?= htmlspecialchars($partyDeleteSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($partyEditError)): ?>
                <div class="msg-error"><?= htmlspecialchars($partyEditError) ?></div>
            <?php endif; ?>
            <?php if (!empty($partyEditSuccess)): ?>
                <div class="msg-success"><?= htmlspecialchars($partyEditSuccess) ?></div>
            <?php endif; ?>

            <table class="party-table">
                <thead>
                    <tr>
                        <th style="width:24%;">Name</th>
                        <th>Description</th>
                        <th style="width:22%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($parties)): ?>
                        <?php foreach ($parties as $party): ?>
                            <?php if ($editing_id === (int)$party['id']): ?>
                                <tr style="background:#f9f9f9;">
                                    <form method="post" autocomplete="off">
                                        <td>
                                            <input type="text" name="party_name" maxlength="100" style="width:96%;"
                                                value="<?= htmlspecialchars($party['name']) ?>" required>
                                        </td>
                                        <td>
                                            <textarea name="party_desc" maxlength="255" rows="1"
                                                style="width:98%;"><?= htmlspecialchars($party['description']) ?></textarea>
                                        </td>
                                        <td>
                                            <button type="submit" name="update_party" class="btn-save">Save</button>
                                            <a href="party_position.php" class="btn-cancel">Cancel</a>
                                        </td>
                                    </form>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    
                                    <td>
                                        <span class="party-name-link"
                                            data-partyid="<?= $party['id'] ?>"
                                            data-partyname="<?= htmlspecialchars($party['name']) ?>">
                                            <?= htmlspecialchars($party['name']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($party['description']) ?></td>
                                    <td>
                                        <a href="party_position.php?edit=<?= $party['id'] ?>" class="btn-edit">Edit</a>
                                        <form method="post" style="display:inline;"
                                            onsubmit="return confirm('Delete this party?');">
                                            <input type="hidden" name="party_id" value="<?= $party['id'] ?>">
                                            <button type="submit" name="delete_party" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">No parties found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Position Management -->
        <div class="management-box">
            <h1>Position Management</h1>
            <div class="add-party-form" style="max-width:400px;">
                <form method="post" autocomplete="off">
                    <div class="form_row"><strong>Add New Position</strong></div>
                    <?php if (!empty($posAddError)): ?>
                        <div class="msg-error"><?= htmlspecialchars($posAddError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($posAddSuccess)): ?>
                        <div class="msg-success"><?= htmlspecialchars($posAddSuccess) ?></div>
                    <?php endif; ?>
                    <div class="form-row">
                        <label for="position_name">Position Name:</label><br>
                        <input type="text" name="position_name" id="position_name" maxlength="100" required style="width:97%;">
                    </div>
                    <div class="form-row">
                        <button type="submit" name="add_position" class="btn-save">Add Position</button>
                    </div>
                </form>
            </div>

            <h2 style="margin-top:36px;">Existing Positions</h2>
            <?php if (!empty($posFetchError)): ?>
                <div class="msg-error"><?= htmlspecialchars($posFetchError) ?></div>
            <?php endif; ?>
            <?php if (!empty($posDeleteError)): ?>
                <div class="msg-error"><?= htmlspecialchars($posDeleteError) ?></div>
            <?php endif; ?>
            <?php if (!empty($posDeleteSuccess)): ?>
                <div class="msg-success"><?= htmlspecialchars($posDeleteSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($posEditError)): ?>
                <div class="msg-error"><?= htmlspecialchars($posEditError) ?></div>
            <?php endif; ?>
            <?php if (!empty($posEditSuccess)): ?>
                <div class="msg-success"><?= htmlspecialchars($posEditSuccess) ?></div>
            <?php endif; ?>

            <table class="party-table">
                <thead>
                    <tr>
                        <th style="width:72%;">Name</th>
                        <th style="width:22%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($positions)): ?>
                        <?php foreach ($positions as $position): ?>
                            <?php if ($pos_editing_id === (int)$position['id']): ?>
                                <tr style="background:#f9f9f9;">
                                    <form method="post" autocomplete="off">
                                        <td>
                                            <input type="text" name="position_name" maxlength="100" style="width:96%;"
                                                value="<?= htmlspecialchars($position['position_name']) ?>" required>
                                        </td>
                                        <td>
                                            <button type="submit" name="update_position" class="btn-save">Save</button>
                                            <a href="party_position.php" class="btn-cancel">Cancel</a>
                                        </td>
                                    </form>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><?= htmlspecialchars($position['position_name']) ?></td>
                                    <td>
                                        <a href="party_position.php?edit_position=<?= $position['id'] ?>" class="btn-edit">Edit</a>
                                        <form method="post" style="display:inline;"
                                            onsubmit="return confirm('Delete this position?');">
                                            <input type="hidden" name="position_id" value="<?= $position['id'] ?>">
                                            <button type="submit" name="delete_position" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align:center;">No positions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="partyMembersModal">
        <div id="modalBlurBG"></div>
        <div id="partyMembersContent">
            <button id="closeModalBtn">&times;</button>
            <div id="partyMembersInner"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.party-name-link').forEach(link => {
                link.addEventListener('click', function() {
                    const partyId = this.dataset.partyid;
                    const partyName = this.dataset.partyname;
                    const modal = document.getElementById('partyMembersModal');
                    const inner = document.getElementById('partyMembersInner');
                    inner.innerHTML = '<div style="text-align:center;padding:30px;">Loading...</div>';
                    modal.classList.add('active');

                    fetch('party_members.php?id=' + encodeURIComponent(partyId))
                        .then(resp => resp.text())
                        .then(html => {
                            inner.innerHTML = `<h2 style="margin-top:0;">${partyName} Members</h2>${html}`;
                        })
                        .catch(() => {
                            inner.innerHTML = '<div style="color:red;text-align:center;">Failed to load members.</div>';
                        });
                });
            });

            document.getElementById('closeModalBtn').onclick = () => {
                document.getElementById('partyMembersModal').classList.remove('active');
            };
            document.getElementById('modalBlurBG').onclick = () => {
                document.getElementById('partyMembersModal').classList.remove('active');
            };

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