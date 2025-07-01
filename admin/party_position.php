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
$electionObj = new Election($pdo);

// Get all elections for dropdown
$elections = $electionObj->getAll();

// Party messages & state
$addError = $partyAddSuccess = $partyEditError = $partyEditSuccess = '';
$partyDeleteError = $partyDeleteSuccess = '';
$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// Position messages & state
$posAddError = $posAddSuccess = $posEditError = $posEditSuccess = '';
$posDeleteError = $posDeleteSuccess = '';
$pos_editing_id = isset($_GET['edit_position']) ? (int)$_GET['edit_position'] : 0;

// Selected election filter
$selected_election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : (isset($_POST['election_id']) ? (int)$_POST['election_id'] : null);

// --- ADD PARTY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_party'])) {
    $party_name = trim($_POST['party_name'] ?? '');
    $party_desc = trim($_POST['party_desc'] ?? '');
    $election_id = isset($_POST['party_election_id']) ? (int)$_POST['party_election_id'] : null;
    
    if ($party_name === '' || empty($election_id)) {
        $addError = 'Party name and election selection are required.';
    } else {
        try {
            $partyObj->add($party_name, $party_desc, $election_id);
            $partyAddSuccess = 'Party added!';
        } catch (PDOException $e) {
            $addError = $e->getCode() == 23000
                ? 'Party name must be unique within an election.'
                : 'DB error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// --- UPDATE PARTY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_party'])) {
    $party_id = (int)($_POST['party_id'] ?? 0);
    $party_name = trim($_POST['party_name'] ?? '');
    $party_desc = trim($_POST['party_desc'] ?? '');
    $election_id = isset($_POST['party_election_id']) ? (int)$_POST['party_election_id'] : null;
    
    if ($party_name === '' || empty($election_id)) {
        $partyEditError = 'Party name and election selection are required.';
        $editing_id = $party_id;
    } else {
        try {
            $partyObj->update($party_id, $party_name, $party_desc, $election_id);
            $partyEditSuccess = 'Party updated!';
            $editing_id = 0;
        } catch (PDOException $e) {
            $partyEditError = $e->getCode() == 23000
                ? 'Party name must be unique within an election.'
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
    $parties = $partyObj->getAll($selected_election_id);
} catch (PDOException $e) {
    $parties = [];
    $fetchError = "Could not fetch parties: " . htmlspecialchars($e->getMessage());
}

// --- ADD POSITION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_position'])) {
    $position_name = trim($_POST['position_name'] ?? '');
    $election_id = isset($_POST['position_election_id']) ? (int)$_POST['position_election_id'] : null;
    
    if ($position_name === '' || empty($election_id)) {
        $posAddError = 'Position name and election selection are required.';
    } else {
        try {
            $positionObj->add($position_name, $election_id);
            $posAddSuccess = 'Position added!';
        } catch (PDOException $e) {
            $posAddError = $e->getCode() == 23000
                ? 'Position name must be unique within an election.'
                : 'DB error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// --- UPDATE POSITION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_position'])) {
    $position_id = (int)($_POST['position_id'] ?? 0);
    $position_name = trim($_POST['position_name'] ?? '');
    $election_id = isset($_POST['position_election_id']) ? (int)$_POST['position_election_id'] : null;
    
    if ($position_name === '' || empty($election_id)) {
        $posEditError = 'Position name and election selection are required.';
        $pos_editing_id = $position_id;
    } else {
        try {
            $positionObj->update($position_id, $position_name, $election_id);
            $posEditSuccess = 'Position updated!';
            $pos_editing_id = 0;
        } catch (PDOException $e) {
            $posEditError = $e->getCode() == 23000
                ? 'Position name must be unique within an election.'
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
    $positions = $positionObj->getAll($selected_election_id);
} catch (PDOException $e) {
    $positions = [];
    $posFetchError = "Could not fetch positions: " . htmlspecialchars($e->getMessage());
}

// --- FETCH INDEPENDENT CANDIDATES ---
try {
    // Query for candidates with null party_id or party_id of the Independent party
    $independentCandidates = [];
    $sql = "
        SELECT 
            c.id, 
            c.name, 
            c.bio, 
            c.photo,
            p.position_name, 
            e.title as election_title,
            e.status as election_status
        FROM 
            candidates c
        JOIN 
            positions p ON c.position_id = p.id
        JOIN 
            elections e ON c.election_id = e.id
        WHERE 
            c.party_id IS NULL OR c.party_id = (SELECT id FROM parties WHERE name = 'Independent' LIMIT 1)
    ";
    
    if ($selected_election_id) {
        $sql .= " AND c.election_id = :election_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':election_id', $selected_election_id, PDO::PARAM_INT);
    } else {
        $stmt = $pdo->prepare($sql);
    }
    
    $stmt->execute();
    $independentCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $independentFetchError = "Could not fetch independent candidates: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Party & Position Management</title>
    <link rel="stylesheet" href="../css/admin_header.css?v=1.1">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/party.css">
    <link rel="stylesheet" href="../css/container.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="../css/party_popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

    <!-- Election Filter -->
    <div class="election-filter-container">
        <form method="get" action="party_position.php" class="election-filter">
            <label for="election_filter">Filter by Election:</label>
            <select name="election_id" id="election_filter" onchange="this.form.submit()">
                <option value="">All Elections</option>
                <?php foreach ($elections as $election): ?>
                    <option value="<?= $election['id'] ?>" <?= $selected_election_id == $election['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($election['title']) ?> (<?= htmlspecialchars($election['status']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="container-flex">
        <!-- Party Management -->
        <div class="management-box">
            <h1>Party Management</h1>
            <div class="add-party-form" style="max-width:400px;">
                <form method="post" autocomplete="off">
                    <div class="form_row"><strong><?= $editing_id ? 'Edit Party' : 'Add New Party' ?></strong></div>
                    <?php if (!empty($addError)): ?>
                        <div class="msg-error"><?= htmlspecialchars($addError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($partyAddSuccess)): ?>
                        <div class="msg-success"><?= htmlspecialchars($partyAddSuccess) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($partyEditError)): ?>
                        <div class="msg-error"><?= htmlspecialchars($partyEditError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($partyEditSuccess)): ?>
                        <div class="msg-success"><?= htmlspecialchars($partyEditSuccess) ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    if ($editing_id) {
                        $editing_party = null;
                        foreach ($parties as $p) {
                            if ($p['id'] == $editing_id) {
                                $editing_party = $p;
                                break;
                            }
                        }
                    }
                    ?>
                    
                    <div class="form-row">
                        <label for="party_name">Party Name:</label><br>
                        <input type="text" name="party_name" id="party_name" maxlength="100" required style="width:97%;" 
                               value="<?= $editing_id && $editing_party ? htmlspecialchars($editing_party['name']) : '' ?>">
                    </div>
                    <div class="form-row">
                        <label for="party_desc">Description (optional):</label><br>
                        <textarea name="party_desc" id="party_desc" rows="2" maxlength="255" style="width:97%;"><?= $editing_id && $editing_party ? htmlspecialchars($editing_party['description']) : '' ?></textarea>
                    </div>
                    <div class="form-row">
                        <label for="party_election_id">Election:</label><br>
                        <select name="party_election_id" id="party_election_id" required style="width:97%; padding:10px 12px; border-radius:8px; border:1px solid #e3e3e3;">
                            <option value="">-- Select Election --</option>
                            <?php foreach ($elections as $election): ?>
                                <option value="<?= $election['id'] ?>" <?= ($editing_id && $editing_party && $editing_party['election_id'] == $election['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($election['title']) ?> (<?= htmlspecialchars($election['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <?php if ($editing_id): ?>
                            <input type="hidden" name="party_id" value="<?= $editing_id ?>">
                            <button type="submit" name="update_party" class="btn-save">Update Party</button>
                            <a href="party_position.php<?= $selected_election_id ? '?election_id=' . $selected_election_id : '' ?>" class="btn-cancel">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_party" class="btn-save">Add Party</button>
                        <?php endif; ?>
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

            <table class="party-table">
                <thead>
                    <tr>
                        <th style="width:24%;">Name</th>
                        <th>Description</th>
                        <th style="width:20%;">Election</th>
                        <th style="width:22%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($parties)): ?>
                        <?php foreach ($parties as $party): ?>
                            <?php 
                            // Skip the Independent party - it will be shown in the Independent Candidates section
                            if (strtolower($party['name']) === 'independent') continue;
                            // Skip parties being edited - they show in the form
                            if ($editing_id === (int)$party['id']) continue;
                            ?>
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
                                    <?php 
                                    $election_name = "Unknown";
                                    foreach ($elections as $e) {
                                        if ($e['id'] == $party['election_id']) {
                                            $election_name = $e['title'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($election_name); 
                                    ?>
                                </td>
                                <td>
                                    <a href="party_position.php?edit=<?= $party['id'] ?><?= $selected_election_id ? '&election_id=' . $selected_election_id : '' ?>" class="btn-edit">Edit</a>
                                    <form method="post" style="display:inline;"
                                        onsubmit="return confirm('Delete this party?');">
                                        <input type="hidden" name="party_id" value="<?= $party['id'] ?>">
                                        <button type="submit" name="delete_party" class="btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
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
                    <div class="form_row"><strong><?= $pos_editing_id ? 'Edit Position' : 'Add New Position' ?></strong></div>
                    <?php if (!empty($posAddError)): ?>
                        <div class="msg-error"><?= htmlspecialchars($posAddError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($posAddSuccess)): ?>
                        <div class="msg-success"><?= htmlspecialchars($posAddSuccess) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($posEditError)): ?>
                        <div class="msg-error"><?= htmlspecialchars($posEditError) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($posEditSuccess)): ?>
                        <div class="msg-success"><?= htmlspecialchars($posEditSuccess) ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    if ($pos_editing_id) {
                        $editing_position = null;
                        foreach ($positions as $p) {
                            if ($p['id'] == $pos_editing_id) {
                                $editing_position = $p;
                                break;
                            }
                        }
                    }
                    ?>
                    
                    <div class="form-row">
                        <label for="position_name">Position Name:</label><br>
                        <input type="text" name="position_name" id="position_name" maxlength="100" required style="width:97%;"
                               value="<?= $pos_editing_id && $editing_position ? htmlspecialchars($editing_position['position_name']) : '' ?>">
                    </div>
                    <div class="form-row">
                        <label for="position_election_id">Election:</label><br>
                        <select name="position_election_id" id="position_election_id" required style="width:97%; padding:10px 12px; border-radius:8px; border:1px solid #e3e3e3;">
                            <option value="">-- Select Election --</option>
                            <?php foreach ($elections as $election): ?>
                                <option value="<?= $election['id'] ?>" <?= ($pos_editing_id && $editing_position && $editing_position['election_id'] == $election['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($election['title']) ?> (<?= htmlspecialchars($election['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <?php if ($pos_editing_id): ?>
                            <input type="hidden" name="position_id" value="<?= $pos_editing_id ?>">
                            <button type="submit" name="update_position" class="btn-save">Update Position</button>
                            <a href="party_position.php<?= $selected_election_id ? '?election_id=' . $selected_election_id : '' ?>" class="btn-cancel">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_position" class="btn-save">Add Position</button>
                        <?php endif; ?>
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

            <table class="party-table">
                <thead>
                    <tr>
                        <th style="width:42%;">Name</th>
                        <th style="width:30%;">Election</th>
                        <th style="width:22%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($positions)): ?>
                        <?php foreach ($positions as $position): ?>
                            <?php if ($pos_editing_id === (int)$position['id']): continue; endif; ?>
                            <tr>
                                <td><?= htmlspecialchars($position['position_name']) ?></td>
                                <td>
                                    <?php 
                                    $election_name = "Unknown";
                                    foreach ($elections as $e) {
                                        if ($e['id'] == $position['election_id']) {
                                            $election_name = $e['title'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($election_name); 
                                    ?>
                                </td>
                                <td>
                                    <a href="party_position.php?edit_position=<?= $position['id'] ?><?= $selected_election_id ? '&election_id=' . $selected_election_id : '' ?>" class="btn-edit">Edit</a>
                                    <form method="post" style="display:inline;"
                                        onsubmit="return confirm('Delete this position?');">
                                        <input type="hidden" name="position_id" value="<?= $position['id'] ?>">
                                        <button type="submit" name="delete_position" class="btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
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

    <!-- Independent Candidates Section -->
    <div class="independent-candidates-container">
        <div class="management-box full-width">
            <h1>Independent Candidates</h1>
            <?php if (!empty($independentFetchError)): ?>
                <div class="msg-error"><?= htmlspecialchars($independentFetchError) ?></div>
            <?php endif; ?>
            
            <table class="party-table">
                <thead>
                    <tr>
                        <th style="width:10%;">Photo</th>
                        <th style="width:20%;">Name</th>
                        <th style="width:15%;">Position</th>
                        <th style="width:20%;">Election</th>
                        <th>Description</th>
                        <th style="width:20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($independentCandidates)): ?>
                        <?php foreach ($independentCandidates as $candidate): ?>
                            <tr>
                                <td class="candidate-photo-cell">
                                    <?php if (!empty($candidate['photo'])): ?>
                                        <img src="../<?= htmlspecialchars($candidate['photo']) ?>" alt="<?= htmlspecialchars($candidate['name']) ?>" class="candidate-thumbnail">
                                    <?php else: ?>
                                        <div class="no-photo">No Photo</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($candidate['name']) ?></td>
                                <td><?= htmlspecialchars($candidate['position_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($candidate['election_title']) ?> 
                                    <span class="status-badge status-<?= strtolower($candidate['election_status']) ?>">
                                        <?= ucfirst(htmlspecialchars($candidate['election_status'])) ?>
                                    </span>
                                </td>
                                <td><?= nl2br(htmlspecialchars(substr($candidate['bio'], 0, 100) . (strlen($candidate['bio']) > 100 ? '...' : ''))) ?></td>
                                <td>
                                    <a href="candidates.php?action=edit&id=<?= $candidate['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="candidates.php?action=edit&id=<?= $candidate['id'] ?>" class="btn-delete">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No independent candidates found.</td>
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