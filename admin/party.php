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

$addError = $addSuccess = $editError = $editSuccess = $deleteError = $deleteSuccess = '';
$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// ADD PARTY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_party'])) {
    $party_name = trim($_POST['party_name'] ?? '');
    $party_desc = trim($_POST['party_desc'] ?? '');
    if ($party_name === '') {
        $addError = 'Party name required.';
    } else {
        try {
            $partyObj->add($party_name, $party_desc);
            $addSuccess = 'Party added!';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $addError = 'Party name must be unique.';
            } else {
                $addError = 'DB error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// UPDATE PARTY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_party'])) {
    $party_id = (int)$_POST['party_id'];
    $party_name = trim($_POST['party_name'] ?? '');
    $party_desc = trim($_POST['party_desc'] ?? '');
    if ($party_name === '') {
        $editError = 'Party name required.';
        $editing_id = $party_id;
    } else {
        try {
            $partyObj->update($party_id, $party_name, $party_desc);
            $editSuccess = 'Party updated!';
            $editing_id = 0;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $editError = 'Party name must be unique.';
            } else {
                $editError = 'DB error: ' . htmlspecialchars($e->getMessage());
            }
            $editing_id = $party_id;
        }
    }
}

// DELETE PARTY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_party'])) {
    $party_id = (int)$_POST['party_id'];
    try {
        $partyObj->delete($party_id);
        $deleteSuccess = 'Party deleted.';
        if ($editing_id == $party_id) $editing_id = 0;
    } catch (PDOException $e) {
        $deleteError = 'Delete failed: ' . htmlspecialchars($e->getMessage());
    }
}

try {
    $parties = $partyObj->getAll();
} catch (PDOException $e) {
    $parties = [];
    $fetchError = "Could not fetch parties: " . htmlspecialchars($e->getMessage()) . '.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Party Management</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/party.css">
</head>
<body>
<?php adminHeader('party'); ?>
<div class="container" style="max-width:900px; margin:0 auto;">
<h1>Party Management</h1>

<div class="add-party-form" style="max-width:400px;">
    <form method="post" autocomplete="off">
        <div class="form_row"><strong>Add New Party</strong></div>
        <?php if ($addError): ?><div class="msg-error"><?= htmlspecialchars($addError) ?></div><?php endif; ?>
        <?php if ($addSuccess): ?><div class="msg-success"><?= htmlspecialchars($addSuccess) ?></div><?php endif; ?>
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
<?php if ($deleteError): ?><div class="msg-error"><?= htmlspecialchars($deleteError) ?></div><?php endif; ?>
<?php if ($deleteSuccess): ?><div class="msg-success"><?= htmlspecialchars($deleteSuccess) ?></div><?php endif; ?>
<?php if ($editError): ?><div class="msg-error"><?= htmlspecialchars($editError) ?></div><?php endif; ?>
<?php if ($editSuccess): ?><div class="msg-success"><?= htmlspecialchars($editSuccess) ?></div><?php endif; ?>

<table class="party-table">
    <thead>
        <tr>
            <th style="width:6%;">ID</th>
            <th style="width:24%;">Name</th>
            <th>Description</th>
            <th style="width:22%;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($parties): ?>
            <?php foreach ($parties as $party): ?>
                <?php if ($editing_id == $party['id']): ?>
                <tr style="background:#f9f9f9;">
                    <form method="post" autocomplete="off">
                        <td><?= $party['id'] ?><input type="hidden" name="party_id" value="<?= $party['id'] ?>"></td>
                        <td><input type="text" name="party_name" maxlength="100" style="width:96%;" value="<?= htmlspecialchars($party['name']) ?>" required></td>
                        <td><textarea name="party_desc" maxlength="255" rows="1" style="width:98%;"><?= htmlspecialchars($party['description']) ?></textarea></td>
                        <td>
                            <button type="submit" name="update_party" class="btn-save">Save</button>
                            <a href="party.php" class="btn-cancel">Cancel</a>
                        </td>
                    </form>
                </tr>
                <?php else: ?>
                <tr>
                    <td><?= $party['id'] ?></td>
                    <td>
                        <span class="party-name-link" data-partyid="<?= $party['id'] ?>" data-partyname="<?= htmlspecialchars($party['name']) ?>">
                            <?= htmlspecialchars($party['name']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($party['description']) ?></td>
                    <td>
                        <a href="party.php?edit=<?= $party['id'] ?>" class="btn-edit">Edit</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this party?');">
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

<!-- Modal Structure -->
<div id="partyMembersModal">
    <div id="modalBlurBG"></div>
    <div id="partyMembersContent">
        <button id="closeModalBtn">&times;</button>
        <div id="partyMembersInner"></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Use event delegation for dynamic elements
  document.querySelectorAll('.party-name-link').forEach(function(link) {
    link.addEventListener('click', function() {
      var partyId = this.getAttribute('data-partyid');
      var partyName = this.getAttribute('data-partyname');
      var modal = document.getElementById('partyMembersModal');
      var inner = document.getElementById('partyMembersInner');
      inner.innerHTML = '<div style="text-align:center;padding:30px;">Loading...</div>';
      modal.classList.add('active');

      // AJAX request to load members
      fetch('party_members.php?id=' + encodeURIComponent(partyId))
        .then(resp => resp.text())
        .then(html => {
          inner.innerHTML = '<h2 style="margin-top:0;">' + partyName + ' Members</h2>' + html;
        })
        .catch(() => {
          inner.innerHTML = '<div style="color:red;text-align:center;">Failed to load members. Try again.</div>';
        });
    });
  });

  // Close modal
  document.getElementById('closeModalBtn').onclick = function() {
    document.getElementById('partyMembersModal').classList.remove('active');
  };
  document.getElementById('modalBlurBG').onclick = function() {
    document.getElementById('partyMembersModal').classList.remove('active');
  };
});
</script>
</body>
</html>