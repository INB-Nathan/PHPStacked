<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'admin') exit;

$party_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$party_id) { echo "<div>No party selected.</div>"; exit; }

// Fetch candidates for this party
try {
    $stmt = $pdo->prepare("SELECT name, position, bio, photo FROM candidates WHERE party_id = ? ORDER BY name ASC");
    $stmt->execute([$party_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div style='color:red;'>Database error: ".htmlspecialchars($e->getMessage())."</div>";
    exit;
}

if (!$members) {
    echo "<div style='text-align:center;'>No members for this party.</div>";
    exit;
}
?>
<div style="display:flex;flex-direction:column;gap:18px;">
<?php foreach ($members as $m): ?>
  <div style="display:flex;gap:20px;align-items:flex-start;padding:12px 0;border-bottom:1px solid #eaeaea;">
    <div>
      <?php if (!empty($m['photo'])): ?>
        <img src="../<?= htmlspecialchars($m['photo']) ?>" alt="<?= htmlspecialchars($m['name']) ?>" style="width:70px;height:70px;object-fit:cover;border-radius:50%;border:1px solid #bbb;">
      <?php else: ?>
        <div style="width:70px;height:70px;background:#f0f0f0;border-radius:50%;border:1px solid #bbb;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:2em;">
          ?
        </div>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-weight:bold;font-size:1.15em;"><?= htmlspecialchars($m['name']) ?></div>
      <div style="color:#27ae60;font-size:.96em;"><?= htmlspecialchars($m['position']) ?></div>
      <div style="margin-top:8px;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($m['bio'])) ?></div>
    </div>
  </div>
<?php endforeach; ?>
</div>