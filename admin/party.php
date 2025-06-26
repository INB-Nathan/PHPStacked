<?php
require_once '../includes/admin_header.php';
require_once '../includes/db_connect.php';
session_start();

// quick require admin login check -- plan to add a error page that will say not admin.
if (empty($_SESSION['loggedin']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// add party POST ewan ko kung pano ko to gagawin wish me luck
$add_error = '';
$add_success = '';
// check niya muna if ung request method is post kasi kapag post nag sesend ng data si webserver to the database
// then checheck nya if add_party exists sa loob ni $_POST array. isset para required ung field para kapag nag request na meron dapat.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_party'])) {
    // bali eto pang kuha lang ng data sa post array, bali party name and party desc itritrim para walang space sa harap or sa likod.
    // then what happens is checheck niya if nag eexist nga ung variable na party_name and party_desc sa loob ni post array.
    // if nag eexist siya gagamitin nya ung nasa loob ni post array
    // if inde, empty string. dahil sa "?? ''". it means if missing or null eto bibigay na value nya ''; which is nothing.
    $party_name = trim($_POST['party_name'] ?? '');
    $party_desc = trim($_POST['party_desc'] ?? '');
    // if statement para ma check if may party name, if wala edi lagay natin sa $add_error message natin na need nila ng party name
    if ($party_name === '') {
        $add_error = 'Party name required.';
        // inuna nalang natin icheck if wala para kapag meron laman diretso tayo, since mas mabilis logic neto
        // ata? di ko lam, its currently 2:37 and im dying!
        // sa else naman dito na tayo mag lalagay ng sql statements and then execute it to the database!
    } else {
        try {
            // -> means prepareing an sql statement, kahit ano yan na sql statement
            // we use pdo rin kasi dahil nag seseperate siya ng values sa execute, bali mahihirapan sila mag sql injection gamit ng prepared statements.
            // bali kasi kapag nag sql-exec ka lang or someshit, iaano niya lang yon ipapaste niya, so kapag may laman ung input fields na malicious sql commands
            // napasok ka na idol.
            $stmt = $pdo->prepare('INSERT INTO parties (name, description) VALUES (?, ?)');
            $stmt->execute([$party_name, $party_desc]);
            $add_success = "Party Added";
        } catch (PDOException $e) {
            // error code 23000 : https://help.nextcloud.com/t/sqlstate-23000-integrity-constraint-violation-1062-duplicate-entry-7457-1687094389-for-key-gf-versions-uniq-index/177890
            // basically may duplicate eh bawal yon!
            if ($e->getCode() === 23000) {
                $add_error = "Party name must be unique.";
            } else {
                $add_error = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// fetch all parties
try {
    // since eto naman di na need ng input fields diretso ->fetchall na pag ka load ng page.
    $parties = $pdo->query('SELECT * FROM parties ORDER BY name ASC')->fetchAll();
} catch (PDOException $e) {
    $parties = [];
    $fetch_error = "Could not fetch parties: " . htmlspecialchars($e->getMessage()) . '.';
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
    <h1>Party Management</h1>
    <div class="add-party-form">
        <form method="post" autocomplete="off">
            <div class="form_row"><strong>ADD NEW PARTY</strong></div>
            <?php if ($addError): ?><div class="msg-error"><?= htmlspecialchars($addError) ?></div><?php endif; ?>
            <?php if ($addSuccess): ?><div class="msg-success"><?= htmlspecialchars($addSuccess) ?></div><?php endif; ?>
            <div class="form-row">
                <label for="party_name">Party Name:</label><br>
                <input type="text" name="party_name" id="party_name" maxlength="100" required style="width:95%;">
            </div>
            <div class="form-row">
                <label for="party_desc">Description (optional):</label><br>
                <textarea name="party_desc" id="party_desc" rows="2" maxlength="255" style="width:95%;"></textarea>
            </div>
            <div class="form-row">
                <button type="submit" name="add_party">Add Party</button>
            </div>
        </form>
    </div>

    <h2>Existing Parties</h2>
    <?php if (!empty($fetchError)): ?>
        <div class="msg-error"><?= htmlspecialchars($fetchError) ?></div>
    <?php endif; ?>
    <table class="party-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($parties): ?>
                <?php foreach ($parties as $party): ?>
                    <tr>
                        <td><?= $party['id'] ?></td>
                        <td>
                            <a href="party_members.php?id=<?= $party['id'] ?>" style="color:#27ae60;text-decoration:underline;">
                                <?= htmlspecialchars($party['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($party['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center;">No parties found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>