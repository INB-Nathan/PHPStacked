<?php
require_once '../includes/autoload.php';
session_start();

// Only allow access if logged in and user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$userManager = new UserManager($pdo);

//Pag may di gumana tanong nyo muna saken baka sa database field lang.
$message = "";
$messageType = "";

// Delete function
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $userManager->deleteUser($id, 'voter');
    
    if ($result === true) {
        $message = "Voter deleted successfully.";
        $messageType = "success";
    } else {
        $message = $result;
        $messageType = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = ucwords(trim($_POST['name'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // nilipat ko ung sanitization mo sa user_manager.php
    // Use the addUser method to handle validation and insertion
    $result = $userManager->addUser($username, $email, $password, $name, 'voter', true);
    
    if ($result === true) {
        $message = "New voter added successfully.";
        $messageType = "success";
    } else {
        $message = $result;
        $messageType = "error";
    }
}

$voters = $userManager->getAllUsersByType('voter');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Voters Management</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/voter.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
</head>

<body>
    <?php adminHeader('voters'); ?>
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    <h1>Voters Management</h1>
    <div class="add-container">
        <h2>Add New Voter</h2>
        <?php if (!empty($err_msg)): ?>
            <div class="form-error"><?php echo $err_msg; ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>
            <br>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required minlength="3">
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required minlength="6">
            <br>
            <button type="submit">Add Voter</button>
        </form>
    </div>
    <div class="data-container">
        <h2>Registered Voters</h2>
        <?php if (!empty($voters)): ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($voters as $voter): ?>
                    <tr>
                        <td><?= htmlspecialchars($voter['full_name']) ?></td>
                        <td><?= htmlspecialchars($voter['username']) ?></td>
                        <td><?= htmlspecialchars($voter['email']) ?></td>
                        <td>
                            <a href="voters.php?delete=<?= $voter['id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete voter <?= htmlspecialchars($voter['full_name']) ?>?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No voters found.</p>
        <?php endif; ?>
    </div>
</body>

</html>