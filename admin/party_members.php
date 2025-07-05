<?php
require_once '../includes/autoload.php';
session_start();

// create an instance of securitymanager class and call mo agad ung dalawang function which is securesession and checksessiontimeout.
$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout('../login.php');

// if statement lang para iensure na admins lang ung pwede mag acess ng page
// ung first conditional is whether na ugn user is logged in and the next conditional is whether their user type is set to admin
// if both mag fail, pupunta siya http 403 forbidden. ng error message na yon, the script is exited using exit
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "<div style='color:red;'>Access denied.</div>";
    exit;
}

// Validate if it's an AJAX request to prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$isAjax) {
    // Add referrer checking if not an AJAX request
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $allowedReferrers = [
        'http://localhost/PHPStacked/admin/party_position.php',
    ];
    
    $isValidReferrer = false;
    foreach ($allowedReferrers as $allowed) {
        if (strpos($referrer, $allowed) === 0) {
            $isValidReferrer = true;
            break;
        }
    }
    
    if (!$isValidReferrer) {
        http_response_code(403); // Forbidden
        echo "<div style='color:red;'>Direct access not allowed.</div>";
        exit;
    }
}

// Set security headers
header("Content-Security-Policy: default-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Validate and sanitize party_id
$party_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if (!$party_id || $party_id <= 0) {
    echo "<div style='color:red;'>Invalid party selected.</div>";
    exit;
}

try {
    // Check if the party exists first (to prevent information disclosure)
    $checkStmt = $pdo->prepare("SELECT id FROM parties WHERE id = ?");
    $checkStmt->execute([$party_id]);
    if (!$checkStmt->fetch()) {
        echo "<div style='color:red;'>Party not found.</div>";
        exit;
    }
    
    // Get members with prepared statement
    $stmt = $pdo->prepare("
      SELECT
        c.id,
        c.name,
        p.position_name AS position,
        c.bio,
        c.photo AS photo
      FROM candidates c
      LEFT JOIN positions p ON c.position_id = p.id
      WHERE c.party_id = ?
      ORDER BY c.name ASC
    ");
    $stmt->execute([$party_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $memberLimit = 100;
    if (count($members) > $memberLimit) {
        $members = array_slice($members, 0, $memberLimit);
    }
    
} catch (PDOException $e) {
    error_log("Party members query error: " . $e->getMessage());
    echo "<div style='color:red;'>An error occurred while retrieving party members.</div>";
    exit;
}

if (empty($members)) {
    echo "<div style='text-align:center;padding:20px 0;'>No members found for this party.</div>";
    exit;
}
// nonce is number used only once bali pang csrf parin to, randombytes means random lang talaga then bin2hex siyas which solves this by converting ung 16 random bytes to their hexaddecimal string reperesentation.
$nonce = bin2hex(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'nonce-$nonce'");
?>

<div style="display:flex;flex-direction:column;gap:18px;" nonce="<?= $nonce ?>">
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
      <div style="color:#27ae60;font-size:.96em;"><?= htmlspecialchars($m['position'] ?? 'No position') ?></div>
      <?php 
      $bioText = $m['bio'] ?? '';
      $maxLength = 500;
      if (strlen($bioText) > $maxLength) {
          $bioText = substr($bioText, 0, $maxLength) . '...';
      }
      ?>
      <div style="margin-top:8px;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($bioText)) ?></div>
    </div>
  </div>
<?php endforeach; ?>
</div>