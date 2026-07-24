<?php
/**
 * Trainer Certification & Role Management
 *
 * This script allows authorized users to edit certifications and role access levels
 * for a specific trainer.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

require_once "config.php";
require_once "auth.php"; 

$currentUrl = urlencode($_SERVER['REQUEST_URI']);

// Require login (Role >= 1 to view)
checkLogin(1, $_SERVER['REQUEST_URI']);

$timeUntilSessionExpires = getTimeUntilSessionExpires();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request. No trainer ID provided. <a href='index.php'>Go to Main Page</a>");
}

$trainer_id = intval($_GET['id']);

/**
 * Handle POST request to update trainer role_id (Issue #1)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    // Enforce Administrator access (Role 2) for role modification
    if (getUserRole() < 2) {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Unauthorized: Only Administrators can modify user access roles.'
        ];
        header("Location: trainer_edit.php?id=$trainer_id");
        exit();
    }

    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Invalid security token. Please try again.'
        ];
        header("Location: trainer_edit.php?id=$trainer_id");
        exit();
    }

    $new_role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 0;

    $update_query = "UPDATE trainers SET role_id = ? WHERE optbl_ptr = ?";
    $stmt = $mysqli->prepare($update_query);
    if ($stmt) {
        $stmt->bind_param("ii", $new_role_id, $trainer_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Trainer access role updated successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Database error: Unable to update role.'
            ];
        }
        $stmt->close();
    }
    header("Location: trainer_edit.php?id=$trainer_id");
    exit();
}

/**
 * Verify trainer existence and fetch role_id
 */
$query = "SELECT role_id FROM trainers WHERE optbl_ptr = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($current_role_id);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Invalid request. Trainer ID does not exist. <a href='index.php'>Go to Main Page</a>");
}
$stmt->close();

/**
 * Fetch the trainer's name using their ID.
 *
 * @var string $trainer_name The name of the trainer.
 */
$query = "SELECT fname FROM operators WHERE seq_nmbr = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($trainer_name);
if (!$stmt->fetch()) {
    die("Trainer not found. <a href='index.php'>Go to Main Page</a>");
}
$stmt->close();

/**
 * Fetch the trainer's current certifications.
 *
 * @var array $current_certifications Array of the trainer's current certifications.
 */
$query = "
    SELECT c.certification, c.seq_nmbr AS cert_id
    FROM can_certify cc
    JOIN certifications c ON cc.cert_ptr = c.seq_nmbr
    WHERE cc.trainer_ptr = ?
";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($certification, $cert_id);

$current_certifications = [];
while ($stmt->fetch()) {
    $current_certifications[] = [
        'certification' => $certification,
        'cert_id' => $cert_id
    ];
}
$stmt->close();

/**
 * Fetch all certifications available for assignment to the trainer.
 *
 * @var array $available_certifications Array of certifications not assigned to the trainer.
 */
$query = "
    SELECT c.certification, c.seq_nmbr AS cert_id
    FROM certifications c
    WHERE c.seq_nmbr NOT IN (
        SELECT cert_ptr FROM can_certify WHERE trainer_ptr = ?
    )
";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($available_certification, $available_cert_id);

$available_certifications = [];
while ($stmt->fetch()) {
    $available_certifications[] = [
        'certification' => $available_certification,
        'cert_id' => $available_cert_id
    ];
}
$stmt->close();

/**
 * Display any success or error messages stored in the session.
 *
 * @var string $message HTML string containing the message to display.
 */
$message = '';
if (isset($_SESSION['message'])) {
    $message = '<p style="color: ' 
    . ($_SESSION['message']['type'] == 'success' ? 'green' : 'red') 
    . ';">' 
    . htmlspecialchars($_SESSION['message']['text']) 
    . '</p>';
    unset($_SESSION['message']); // Clear the message after it has been displayed
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Trainer: <?php echo htmlspecialchars($trainer_name); ?></title>
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="common.css">
    <script src="common.js" defer></script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="trainer_list.php">Back to Trainer List</a>
            <a href="index.php">Back to Main Page</a>
        </div>
        <h1>Edit Trainer: <?php echo htmlspecialchars($trainer_name); ?></h1>

        <!-- Display success or error message -->
        <?php echo $message; ?>

        <!-- Role Management Section (Issue #1) -->
        <h2>Access Role</h2>
        <form method="post" action="trainer_edit.php?id=<?php echo htmlspecialchars($trainer_id); ?>" style="margin-bottom: 2em;">
            <input type="hidden" name="action" value="update_role">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            
            <label for="role_id">Role Level:</label>
            <select name="role_id" id="role_id" <?php echo (getUserRole() < 2) ? 'disabled' : ''; ?>>
                <option value="0" <?php echo ($current_role_id === 0 || $current_role_id === null) ? 'selected' : ''; ?>>0 - Unassigned / No Access</option>
                <option value="1" <?php echo ($current_role_id === 1) ? 'selected' : ''; ?>>1 - Standard Trainer</option>
                <option value="2" <?php echo ($current_role_id === 2) ? 'selected' : ''; ?>>2 - Administrator</option>
            </select>

            <?php if (getUserRole() >= 2): ?>
                <button type="submit">Update Role</button>
            <?php else: ?>
                <small style="color: gray;">(Administrator privilege required to change roles)</small>
            <?php endif; ?>
        </form>

        <!-- Current Certifications -->
        <h2>Current Certifications</h2>
        <?php if (!empty($current_certifications)) : ?>
            <div class="certifications-list">
                <ul>
                    <?php foreach ($current_certifications as $cert): ?>
                        <li>
                            <span><?php echo htmlspecialchars($cert['certification']); ?></span>
                            <div class="button-container">
                                <form method="post" action="trainer_certification_remove.php" style="display:inline;">
                                    <input type="hidden" name="trainer_id" value="<?php echo htmlspecialchars($trainer_id); ?>">
                                    <input type="hidden" name="cert_id" value="<?php echo htmlspecialchars($cert['cert_id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                                    <button type="submit">Remove Certification</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>No certifications assigned to this trainer.</p>
        <?php endif; ?>

        <!-- Available Certifications -->
        <h2>Available Certifications</h2>
        <?php if (!empty($available_certifications)) : ?>
            <div class="certifications-list">
                <ul>
                    <?php foreach ($available_certifications as $cert): ?>
                        <li>
                            <span><?php echo htmlspecialchars($cert['certification']); ?></span>
                            <div class="button-container">
                                <form method="post" action="trainer_certification_add.php" style="display:inline;">
                                    <input type="hidden" name="trainer_id" value="<?php echo htmlspecialchars($trainer_id); ?>">
                                    <input type="hidden" name="cert_id" value="<?php echo htmlspecialchars($cert['cert_id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                                    <button type="submit">Add Certification</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>No available certifications to assign to this trainer.</p>
        <?php endif; ?>
    </div>
</body>
</html>
