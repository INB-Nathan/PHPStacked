<?php
require_once '../includes/admin_header.php';
require_once '../includes/db_connect.php';
session_start();

// Only allow access if logged in and user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

//Pag may di gumana tanong nyo muna saken baka sa database field lang.
// Stores error message
$err_msg = "";

// Delete function
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'voter'");
    if ($stmt->execute([$id])) {
        $err_msg = "Voter deleted successfully.";
    } else {
        $err_msg = "Error deleting voter.";
    }
}

// Submit voter form handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = ucwords(trim($_POST['name'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic sanitization and validation
    if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $name)) {
        $err_msg = "Name contains invalid characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $err_msg = "Username can only contain letters, numbers, and underscores.";
    } elseif ($name === '' || $username === '' || $email === '' || $password === '') {
        $err_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err_msg = "Please enter a valid email address.";
    } elseif (strlen($username) < 3) {
        $err_msg = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 6) {
        $err_msg = "Password must be at least 6 characters.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `users`(`username`, `email`, `pass_hash`, `full_name`, `user_type`, `is_active`) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $password_hash, $name, 'voter', 1])) {
            $err_msg = "New voter added successfully.";
        } else {
            $err_msg = "Error adding voter.";
        }
    }
}
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
        <?php
        // Fetch voters from the database
        $stmt = $pdo->prepare("SELECT * FROM users");
        $stmt->execute();
        $voters = $stmt->fetchAll();

        if ($voters) {
            echo "<table>";
            echo "<tr><th>Name</th><th>Username</th><th>Email</th><th>Action</th></tr>";
            foreach ($voters as $voter) {
                if ($voter['user_type'] !== 'voter') {
                    continue; 
                }
                echo "<tr>";
                echo "<td>" . $voter['full_name'] . "</td>";
                echo "<td>" . $voter['username'] . "</td>";
                echo "<td>" . $voter['email'] . "</td>";
                echo "<td><a href='voters.php?delete=" . $voter['id'] . "' onclick=\"return confirm('Are you sure you want to voter " . $voter['full_name'] . "?');\">Delete</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        ?>
    </div>
    <script>
        document.getElementById('logoutNavBtn').onclick = function(e) {
            e.preventDefault();
            document.getElementById('logoutModal').classList.add('active');
        };
        document.getElementById('cancelLogoutBtn').onclick = function() {
            document.getElementById('logoutModal').classList.remove('active');
        };
        document.getElementById('logoutModal').onclick = function(e) {
            if (e.target === this) this.classList.remove('active');
        };
    </script>
</body>

</html>