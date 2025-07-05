<?php
require_once '../includes/autoload.php';
require_once '../includes/voter_header.php';
session_start();

if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'voter') {
    header("Location: ../login.php");
    exit;
}

$electionManager = new ElectionManager($pdo);
// $activeElection = $electionManager->getActiveElection();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Election</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
</head>
<body>
    <?php voterHeader('view_election'); ?>
    <h1>View Election</h1>
    <?php if ($activeElection): ?>
        <h2><?php echo htmlspecialchars($activeElection['title']); ?></h2>
        <p><?php echo htmlspecialchars($activeElection['description']); ?></p>
        <table class="party-table">
            <tr><th>Candidate</th><th>Votes</th></tr>
            <?php foreach ($activeElection['candidates'] as $candidate): ?>
                <tr>
                    <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                    <td><?php echo (int)$candidate['votes']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No active election at the moment.</p>
    <?php endif; ?>
</body>
</html>