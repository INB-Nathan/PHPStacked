<?php
require_once '../includes/autoload.php';

// Start the session para magamit ang session variables
session_start();

// Make sure na ang user ay naka-login at admin. If not, i-redirect sa login page.
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$securityManager = new SecurityManager($pdo);
$candidateManager = new CandidateManager($pdo);
$positionManager = new PositionManager($pdo);
$partyManager = new PartyManager($pdo);
$electionManager = new ElectionManager($pdo);


// Para sa mga success or error messages pagkatapos ng isang action
$message = '';
$message_type = ''; // 'success' or 'error'

// Variables for the form (for both add and edit)
$candidate_id = null;     // ID ng candidate, null if adding
$name         = '';       // Name ng candidate
$position_id  = null;     // Position ID na tinatakbuhan
$party_id     = null;     // Party ID kung meron
$description  = '';       // Description or platform
$photo        = null;     // Path ng photo ng candidate (for editing)
$election_id  = null;     // Election ID that this candidate belongs to

// Determine if we are adding or editing a candidate
$is_editing = false; // Default is not editing (meaning, adding)

// fetch list of positions and parties for dropdowns
$position_list = $positionManager->getAll();
$party_list = $partyManager->getAll();
$election_list = $electionManager->getAll();

// If may 'action' na 'edit' and may 'id' sa URL (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    // Sanitize and validate the candidate ID
    $candidate_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    // If valid ang ID
    if ($candidate_id) {
        // Get the data ng candidate using the ID
        $candidate_to_edit = $candidateManager->getCandidateById($candidate_id);

        // Check if getCandidateById returned an error string
        if (is_string($candidate_to_edit)) {
            $message      = $candidate_to_edit; // Display the specific error
            $message_type = 'error';
        } elseif ($candidate_to_edit) {
            $is_editing   = true; // Set to true because we are editing
            // Populate the form variables with the current candidate's data
            $name         = $candidate_to_edit['name'];
            $position_id  = $candidate_to_edit['position_id'];
            $party_id     = $candidate_to_edit['party_id'];
            $description  = $candidate_to_edit['description'];
            $photo        = $candidate_to_edit['photo'];
            $election_id  = $candidate_to_edit['election_id'];
            
            // Get positions specific to this election
            $position_list = $positionManager->getAll($election_id);
            // Get parties specific to this election
            $party_list = $partyManager->getAll($election_id);
        } else {
            // If hindi nahanap ang candidate
            $message      = 'Candidate not found.';
            $message_type = 'error';
            $candidate_id = null; // Reset ID to avoid showing empty edit form
            $is_editing   = false; // Reset to false
        }
    } else {
        // If invalid ang candidate ID
        $message      = 'Invalid candidate ID.';
        $message_type = 'error';
    }
}

// Handle POST requests (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the action from the POST data
    $action = $_POST['action'] ?? '';
    if ($action !== 'get_positions_by_election' && $action !== 'get_parties_by_election') {
        // Validate CSRF token
        if (!$securityManager->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $message = 'Security validation failed. Please try again.';
            $message_type = 'error';
            // Stop processing the form
            goto skip_form_processing;
        }
    }

    // Common form inputs
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Get selected IDs from dropdowns
    $position_id = filter_input(INPUT_POST, 'position_id', FILTER_VALIDATE_INT);
    $party_id    = filter_input(INPUT_POST, 'party_id',    FILTER_VALIDATE_INT);
    $election_id = filter_input(INPUT_POST, 'election_id', FILTER_VALIDATE_INT);

    // Get the current photo path (only for update)
    $current_photo  = $_POST['current_photo'] ?? null;
    $uploaded_photo = $current_photo;

    // If the action is 'add' or 'update'
    if ($action === 'add' || $action === 'update') {
        // Check if may kulang na field
        if (empty($name) || empty($position_id) || empty($description) || empty($election_id)) {
            $message      = 'All fields are required.';
            $message_type = 'error';
            // If it's an update and fields are empty, re-populate the form with existing data
            if ($action === 'update' && $candidate_id) {
                $candidate_to_edit = $candidateManager->getCandidateById($candidate_id);
                // Check if getCandidateById returned an error string
                if (is_string($candidate_to_edit)) {
                    $message      = $candidate_to_edit; // Display the specific error
                    $message_type = 'error';
                } elseif ($candidate_to_edit) {
                    $name        = $candidate_to_edit['name'];
                    $position_id = $candidate_to_edit['position_id'];
                    $party_id    = $candidate_to_edit['party_id'];
                    $description = $candidate_to_edit['description'];
                    $photo       = $candidate_to_edit['photo'];
                    $election_id = $candidate_to_edit['election_id'];
                }
            }
        } else {
            // Handle file upload
            // If may in-upload na photo at walang error sa pag-upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Use FileHandler class
                $upload_error = '';
                $new_path = FileHandler::uploadFile($_FILES['photo'], 'uploads/candidates/', $upload_error);
                if ($new_path === null) {
                    // If may error sa pag-upload
                    $message      = $upload_error;
                    $message_type = 'error';
                } else {
                    // If updating and there's an old photo, delete it
                    if ($action === 'update' && $current_photo) {
                        FileHandler::deleteFile($current_photo);
                    }
                    $uploaded_photo = $new_path;
                }
            }

            // If walang error sa message (meaning, no file upload errors)
            if (empty($message_type)) {
                // If the action is 'add'
                if ($action === 'add') {
                    // Call the addCandidate method with election_id, position_id, party_id
                    $result = $candidateManager->addCandidate(
                        $election_id,
                        $name,
                        $position_id,
                        $party_id,
                        $description,
                        $uploaded_photo
                    );
                    if ($result === true) {
                        $message      = 'Candidate added successfully!';
                        $message_type = 'success';
                        // Clear form fields after successful add
                        $name         = '';
                        $position_id  = null;
                        $party_id     = null;
                        $election_id  = null;
                        $description  = '';
                        $photo        = null;
                    } else {
                        // If hindi na-add ang candidate (result is an error string)
                        $message      = $result; // Display the specific error message
                        $message_type = 'error';
                    }
                } elseif ($action === 'update') { // If the action is 'update'
                    // Call the updateCandidate method
                    $result = $candidateManager->updateCandidate(
                        $candidate_id,
                        $name,
                        $position_id,
                        $party_id,
                        $description,
                        $uploaded_photo
                    );
                    if ($result === true) {
                        $message      = 'Candidate updated successfully!';
                        $message_type = 'success';
                        // Update the current photo variable for display
                        $photo        = $uploaded_photo;
                    } else {
                        // If hindi na-update ang candidate (result is an error string)
                        $message      = $result; // Display the specific error message
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
            // Call the deleteCandidate method
            $result = $candidateManager->deleteCandidate($candidate_id_to_delete);
            if ($result === true) {
                $message      = 'Candidate deleted successfully!';
                $message_type = 'success';
            } else {
                // If hindi na-delete ang candidate (result is an error string)
                $message      = $result; // Display the specific error message
                $message_type = 'error';
            }
        } else {
            // If invalid ang candidate ID
            $message      = 'Invalid candidate ID for deletion.';
            $message_type = 'error';
        }
    }
    
    if ($action === 'get_positions_by_election') {
        header('Content-Type: application/json');
        $election_id = filter_input(INPUT_POST, 'election_id', FILTER_VALIDATE_INT);
        if ($election_id) {
            $positions = $positionManager->getAll($election_id);
            echo json_encode($positions);
            exit;
        }
        echo json_encode([]);
        exit;
    }
    
    if ($action === 'get_parties_by_election') {
        header('Content-Type: application/json');
        $election_id = filter_input(INPUT_POST, 'election_id', FILTER_VALIDATE_INT);
        if ($election_id) {
            $parties = $partyManager->getAll($election_id);
            echo json_encode($parties);
            exit;
        }
        echo json_encode([]);
        exit;
    }

    skip_form_processing:
}

if (isset($_GET['action']) && $_GET['action'] === 'get_positions_by_election') {
    header('Content-Type: application/json');
    $election_id = filter_input(INPUT_GET, 'election_id', FILTER_VALIDATE_INT);
    if ($election_id) {
        $positions = $positionManager->getAll($election_id);
        echo json_encode($positions);
        exit;
    }
    echo json_encode([]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_parties_by_election') {
    header('Content-Type: application/json');
    $election_id = filter_input(INPUT_GET, 'election_id', FILTER_VALIDATE_INT);
    if ($election_id) {
        $parties = $partyManager->getAll($election_id);
        echo json_encode($parties);
        exit;
    }
    echo json_encode([]);
    exit;
}

// Get all candidates (after any modifications)
$candidates = $candidateManager->getCandidates();
// Check if getCandidates returned an error string
if (is_string($candidates)) {
    $message      = $candidates; // Display the specific error
    $message_type = 'error';
    $candidates   = []; // Ensure $candidates is an empty array to prevent issues in the loop
}

// SEKYU CIBERSEKURITY
$securityManager->checkSessionTimeout();
$securityManager->secureSession();

$csrf_token = $securityManager->generateCSRFToken();
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php adminHeader('candidates'); ?>
    <div id="logoutModal">
        <div id="logoutModalContent">
            <h3>Are you sure you want to log out?</h3>
            <form action="../logout.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="modal-btn confirm">Continue</button>
            </form>
            <button class="modal-btn cancel" id="cancelLogoutBtn" type="button">Cancel</button>
        </div>
    </div>
    <div class="container">
        <h1>Candidate Management</h1>

        <?php if ($message): // If may message, show ito ?>
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
                    <input type="hidden" name="current_photo" value="<?php echo htmlspecialchars($photo ?? ''); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="name">Candidate Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="election_id">Election:</label>
                        <select id="election_id" name="election_id" required onchange="updatePositionsAndParties()">
                            <?php foreach ($election_list as $election): ?>
                                <option value="<?php echo htmlspecialchars($election['id']); ?>"
                                    <?php echo ($election_id == $election['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($election['title']); ?> 
                                    (<?php echo htmlspecialchars($election['status']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="position_id">Position:</label>
                        <select id="position_id" name="position_id" required>
                            <?php foreach ($position_list as $pos_item): ?>
                                <option value="<?php echo htmlspecialchars($pos_item['id']); ?>"
                                    <?php echo ($position_id == $pos_item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos_item['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="party_id">Partylist:</label>
                        <select id="party_id" name="party_id" required>
                            <?php foreach ($party_list as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['id']); ?>"
                                    <?php echo ($party_id == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description/Platform:</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="photo">Candidate Photo:</label>
                        <?php if ($photo): // If may photo na ?>
                            <img src="../<?php echo htmlspecialchars($photo); ?>" alt="Current Candidate Photo" style="max-width: 200px; margin: 10px 0;">
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
                        <label for="election_id">Election:</label>
                        <select id="election_id" name="election_id" required onchange="updatePositionsAndParties()">
                            <option value="">-- Select Election --</option>
                            <?php foreach ($election_list as $election): ?>
                                <option value="<?php echo htmlspecialchars($election['id']); ?>">
                                    <?php echo htmlspecialchars($election['title']); ?> 
                                    (<?php echo htmlspecialchars($election['status']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="position_id">Position:</label>
                        <select id="position_id" name="position_id" required disabled>
                            <option value="">-- Select Election First --</option>
                        </select>
                        <div id="position-loading" style="display:none;">Loading positions...</div>
                    </div>

                    <div class="form-group">
                        <label for="party_id">Partylist:</label>
                        <select id="party_id" name="party_id" required>
                            <?php foreach ($party_list as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['id']); ?>">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="party-loading" style="display:none;">Loading parties...</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description/Platform:</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="photo">Candidate Photo (optional):</label>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

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
                        <th>Election</th>
                        <th>Position</th>
                        <th>Partylist</th>
                        <th>Description/Platform</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $candidate_row): // Loop through each candidate ?>
                        <tr>
                            <td>
                                <?php if ($candidate_row['photo']): // If may photo ang candidate ?>
                                    <img src="../<?php echo htmlspecialchars($candidate_row['photo']); ?>" alt="<?php echo htmlspecialchars($candidate_row['name']); ?>" class="candidate-photo">
                                <?php else: // If walang photo ?>
                                    No Photo
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($candidate_row['name']); ?></td>
                            <td>
                                <?php 
                                    $election_title = "Unknown";
                                    foreach ($election_list as $election) {
                                        if ($election['id'] == $candidate_row['election_id']) {
                                            $election_title = $election['title'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($election_title);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($candidate_row['position']); ?></td>
                            <td><?php echo htmlspecialchars($candidate_row['partylist']) ?: ''; ?></td>
                            <td><?php echo htmlspecialchars(substr($candidate_row['description'], 0, 100)) . (strlen($candidate_row['description']) > 100 ? '...' : ''); ?></td>
                            <td class="actions">
                                <a href="candidates.php?action=edit&id=<?php echo htmlspecialchars($candidate_row['id']); ?>" class="edit-btn">Edit</a>
                                <form action="candidates.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($candidate_row['id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token);?>">
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
        
        function updatePositionsAndParties() {
            const electionId = document.getElementById('election_id').value;
            const positionDropdown = document.getElementById('position_id');
            const partyDropdown = document.getElementById('party_id');
            const positionLoading = document.getElementById('position-loading');
            const partyLoading = document.getElementById('party-loading');
            
            if (!electionId) {
                positionDropdown.disabled = true;
                positionDropdown.innerHTML = '<option value="">-- Select Election First --</option>';
                return;
            }
            
            positionDropdown.disabled = true;
            positionLoading.style.display = 'block';
            
            fetch(`candidates.php?action=get_positions_by_election&election_id=${electionId}`)
                .then(response => response.json())
                .then(positions => {
                    positionLoading.style.display = 'none';
                    positionDropdown.disabled = false;
                    
                    positionDropdown.innerHTML = '';
                    
                    if (positions.length === 0) {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'No positions available for this election';
                        positionDropdown.appendChild(option);
                        positionDropdown.disabled = true;
                    } else {
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = '-- Select Position --';
                        positionDropdown.appendChild(defaultOption);
                        
                        positions.forEach(position => {
                            const option = document.createElement('option');
                            option.value = position.id;
                            option.textContent = position.position_name;
                            positionDropdown.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching positions:', error);
                    positionLoading.style.display = 'none';
                    positionDropdown.innerHTML = '<option value="">Error loading positions</option>';
                });
                
            partyLoading.style.display = 'block';
            fetch(`candidates.php?action=get_parties_by_election&election_id=${electionId}`)
                .then(response => response.json())
                .then(parties => {
                    partyLoading.style.display = 'none';
                    
                    partyDropdown.innerHTML = '';
                    
                    // Look for the Independent party in the list
                    let independentParty = parties.find(party => party.name.toLowerCase() === 'independent');
                    
                    // If Independent party exists, add it as first option
                    if (independentParty) {
                        const independentOption = document.createElement('option');
                        independentOption.value = independentParty.id;
                        independentOption.textContent = 'Independent';
                        partyDropdown.appendChild(independentOption);
                    } else {
                        // If no Independent party exists, add option for null party_id
                        const independentOption = document.createElement('option');
                        independentOption.value = '0'; // This will be treated as NULL in the backend
                        independentOption.textContent = 'Independent (No Party)';
                        partyDropdown.appendChild(independentOption);
                    }
                    
                    // Add all other parties
                    parties.forEach(party => {
                        if (party.name.toLowerCase() === 'independent') return; // Skip Independent as we already added it
                        
                        const option = document.createElement('option');
                        option.value = party.id;
                        option.textContent = party.name;
                        partyDropdown.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching parties:', error);
                    partyLoading.style.display = 'none';
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const electionDropdown = document.getElementById('election_id');
            if (electionDropdown.value) {
                updatePositionsAndParties();
            }
        });
    </script>
</body>
</html>