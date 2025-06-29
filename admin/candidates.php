<?php
require_once '../includes/admin_header.php';
require_once '../includes/db_connect.php';
// Need i-require ang functions.php for CRUD (Create, Read, Update, Delete) functions ng candidates
require_once '../includes/functions.php'; // Include the functions file

// Start the session para magamit ang session variables
session_start();

// Make sure na ang user ay naka-login at admin. If not, i-redirect sa login page.
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Para sa mga success or error messages pagkatapos ng isang action
$message = '';
$message_type = ''; // 'success' or 'error'

// Variables for the form (for both add and edit)
$candidate_id = null; // ID ng candidate, null if adding
$name = ''; // Name ng candidate
$position = ''; // Position na tinatakbuhan
$description = ''; // Description or platform
$photo_path = null; // Path ng photo ng candidate (for editing)

// Determine if we are adding or editing a candidate
$is_editing = false; // Default is not editing (meaning, adding)
// If may 'action' na 'edit' and may 'id' sa URL (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    // Sanitize and validate the candidate ID
    $candidate_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    // If valid ang ID
    if ($candidate_id) {
        // Get the data ng candidate using the ID
        $candidate_to_edit = getCandidateById($candidate_id);

        // Check if getCandidateById returned an error string
        if (is_string($candidate_to_edit)) {
            $message = $candidate_to_edit; // Display the specific error
            $message_type = 'error';
            $candidate_id = null;
            $is_editing = false;
        } elseif ($candidate_to_edit) {
            $is_editing = true; // Set to true because we are editing
            // Populate the form variables with the current candidate's data
            $name = $candidate_to_edit['name'];
            $position = $candidate_to_edit['position'];
            $description = $candidate_to_edit['description'];
            $photo_path = $candidate_to_edit['photo_path'];
        } else {
            // If hindi nahanap ang candidate
            $message = 'Candidate not found.';
            $message_type = 'error';
            $candidate_id = null; // Reset ID to avoid showing empty edit form
            $is_editing = false; // Reset to false
        }
    } else {
        // If invalid ang candidate ID
        $message = 'Invalid candidate ID.';
        $message_type = 'error';
    }
}

// Handle POST requests (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the action from the POST data
    $action = $_POST['action'] ?? '';

    // Define a default election ID for now.
    $default_election_id = 1; 

    // If the action is 'add' or 'update'
    if ($action === 'add' || $action === 'update') {
        // Get and trim the data from the form
        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $description = trim($_POST['description'] ?? '');
        // Get the current photo path (only for update)
        $current_photo_path = $_POST['current_photo_path'] ?? null; 

        // Check if may kulang na field
        if (empty($name) || empty($position) || empty($description)) {
            $message = 'All fields are required.';
            $message_type = 'error';
            // If it's an update and fields are empty, re-populate the form with existing data
            if ($action === 'update' && $candidate_id) {
                $candidate_to_edit = getCandidateById($candidate_id);
                // Check if getCandidateById returned an error string
                if (is_string($candidate_to_edit)) {
                    $message = $candidate_to_edit; // Display the specific error
                    $message_type = 'error';
                } elseif ($candidate_to_edit) {
                    $name = $candidate_to_edit['name'];
                    $position = $candidate_to_edit['position'];
                    $description = $candidate_to_edit['description'];
                    $photo_path = $candidate_to_edit['photo_path'];
                }
            }
        } else {
            // Default photo path is the current path (for update)
            $uploaded_photo_path = $current_photo_path; 

            // Handle file upload
            // If may in-upload na photo and walang error sa pag-upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Target directory for candidate photos
                $target_dir = '../uploads/candidates/';
                // If the directory doesn't exist yet, create it
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                // Get the file extension
                $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                // Create a unique filename to avoid conflicts
                $new_file_name = uniqid('candidate_') . '.' . $file_extension;
                // Build the complete path of the target file
                $target_file = $target_dir . $new_file_name;

                // Check if the file is a real image
                $check = getimagesize($_FILES['photo']['tmp_name']);
                if ($check !== false) {
                    // Move the uploaded file to the target directory
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                        // If updating and there's an old photo, delete it
                        if ($action === 'update' && $current_photo_path && file_exists(__DIR__ . '/../' . $current_photo_path)) {
                            unlink(__DIR__ . '/../' . $current_photo_path);
                        }
                        // Set the new photo path for the database
                        $uploaded_photo_path = 'uploads/candidates/' . $new_file_name;
                    } else {
                        // If may error sa pag-upload
                        $message = 'Error uploading photo.';
                        $message_type = 'error';
                    }
                } else {
                    // If the uploaded file is not an image
                    $message = 'Uploaded file is not an image.';
                    $message_type = 'error';
                }
            }

            // If walang error sa message (meaning, no file upload errors)
            if (empty($message_type)) { 
                // If the action is 'add'
                if ($action === 'add') {
                    // Call the addCandidate function with election_id
                    $result = addCandidate($default_election_id, $name, $position, $description, $uploaded_photo_path);
                    if ($result === true) {
                        $message = 'Candidate added successfully!';
                        $message_type = 'success';
                        // Clear form fields after successful add
                        $name = '';
                        $position = '';
                        $description = '';
                        $photo_path = null;
                    } else {
                        // If hindi na-add ang candidate (result is an error string)
                        $message = $result; // Display the specific error message
                        $message_type = 'error';
                    }
                } elseif ($action === 'update') { // If the action is 'update'
                    // Call the updateCandidate function
                    $result = updateCandidate($candidate_id, $name, $position, $description, $uploaded_photo_path);
                    if ($result === true) {
                        $message = 'Candidate updated successfully!';
                        $message_type = 'success';
                        // Update the current photo_path variable for display
                        $photo_path = $uploaded_photo_path;
                    } else {
                        // If hindi na-update ang candidate (result is an error string)
                        $message = $result; // Display the specific error message
                        $message_type = 'error';
                    }
                }
            }
        }
    } elseif ($action === 'delete') { // If the action is 'delete'
        // Sanitize and validate the candidate ID for deletion
        $candidate_id_to_delete = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        // If valid ang ID
        if ($candidate_id_to_delete) {
            // Call the deleteCandidate function
            $result = deleteCandidate($candidate_id_to_delete);
            if ($result === true) {
                $message = 'Candidate deleted successfully!';
                $message_type = 'success';
            } else {
                // If hindi na-delete ang candidate (result is an error string)
                $message = $result; // Display the specific error message
                $message_type = 'error';
            }
        } else {
            // If invalid ang candidate ID
            $message = 'Invalid candidate ID for deletion.';
            $message_type = 'error';
        }
    }
}

// Get all candidates (after any modifications)
$candidates = getCandidates(); 
// Check if getCandidates returned an error string
if (is_string($candidates)) {
    $message = $candidates; // Display the specific error
    $message_type = 'error';
    $candidates = []; // Ensure $candidates is an empty array to prevent issues in the loop
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Management</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/candidates.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
</head>
<body>
    <?php adminHeader('candidates'); ?>
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    <div class="container">
        <h1>Candidate Management</h1>

        <?php if ($message): // If may message, show it ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($is_editing): // If editing ?>
            <div class="form-container">
                <h2>Edit Candidate</h2>
                <form action="candidates.php?action=edit&id=<?php echo htmlspecialchars($candidate_id); ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($candidate_id); ?>">
                    <input type="hidden" name="current_photo_path" value="<?php echo htmlspecialchars($photo_path ?? ''); ?>">

                    <div class="form-group">
                        <label for="name">Candidate Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="position">Position:</label>
                        <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description/Platform:</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="photo">Candidate Photo:</label>
                        <?php if ($photo_path): // If may photo na ?>
                            <img src="../<?php echo htmlspecialchars($photo_path); ?>" alt="Current Candidate Photo">
                            <p>Current photo. Upload new to change.</p>
                        <?php else: // If walang photo ?>
                            <p>No photo uploaded yet.</p>
                        <?php endif; ?>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="update-btn">Update Candidate</button>
                    </div>
                </form>
            </div>
        <?php else: // If adding ?>
            <div class="form-container">
                <h2>Add New Candidate</h2>
                <form action="candidates.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="name">Candidate Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="position">Position:</label>
                        <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description/Platform:</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="photo">Candidate Photo (optional):</label>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>
                    <div class="form-actions">
                        <button type="submit">Add Candidate</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Horizontal rule para paghiwalayin ang form at table -->
        <hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

        <h2>Existing Candidates</h2>
        <div class="action-buttons" style="text-align: left; margin-bottom: 20px;">
             <!-- Link para bumalik sa add form if currently editing and user wants to add new -->
            <?php if ($is_editing): ?>
                <a href="candidates.php">Add New Candidate</a>
            <?php endif; ?>
        </div>


        <?php if (!empty($candidates)): // If may mga candidates, show sa table ?>
            <table class="candidates-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Description/Platform</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $candidate_row): // Loop through each candidate ?>
                        <tr>
                            <td>
                                <?php if ($candidate_row['photo_path']): // If may photo ang candidate ?>
                                    <img src="../<?php echo htmlspecialchars($candidate_row['photo_path']); ?>" alt="<?php echo htmlspecialchars($candidate_row['name']); ?>" class="candidate-photo">
                                <?php else: // If walang photo ?>
                                    No Photo
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($candidate_row['name']); ?></td>
                            <td><?php echo htmlspecialchars($candidate_row['position']); ?></td>
                            <td><?php echo htmlspecialchars(substr($candidate_row['description'], 0, 100)) . (strlen($candidate_row['description']) > 100 ? '...' : ''); ?></td>
                            <td class="actions">
                                <a href="candidates.php?action=edit&id=<?php echo htmlspecialchars($candidate_row['id']); ?>" class="edit-btn">Edit</a>
                                <form action="candidates.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($candidate_row['id']); ?>">
                                    <button type="submit" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: // If walang candidates ?>
            <p style="text-align: center; margin-top: 50px;">No candidates found.</p>
        <?php endif; ?>
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
