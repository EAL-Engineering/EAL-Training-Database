<?php
/**
 * Trainer Certification Management
 *
 * This script allows authorized users to edit certifications for a specific trainer.
 * Users can view and remove current certifications or add new ones from the list
 * of available certifications.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Start session to access success/error messages
session_start();

/**
 * Include the database connection file to connect to the database.
 */
require_once "config.php";

// Capture the current page URL
$currentUrl = urlencode($_SERVER['REQUEST_URI']); // Encodes the URL for safe use in GET parameters

/**
 * Check if the user is logged in and has the required access level (1 or 2).
 * Redirects unauthorized users to the login page.
 */
checkLogin(1);

// Get the session expiration time
$timeUntilSessionExpires = getTimeUntilSessionExpires();

/**
 * Check if 'id' is provided in the URL.
 * If not, terminate the script with an error message.
 */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request. No trainer ID provided. <a href='index.php'>Go to Main Page</a>");
}

$trainer_id = intval($_GET['id']); // Sanitize the trainer ID

/**
 * Verify if the trainer ID matches a pre-existing optbl_ptr in the trainers table.
 */
$query = "SELECT COUNT(*) FROM trainers WHERE optbl_ptr = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count == 0) {
    die("Invalid request. Trainer ID does not exist. <a href='index.php'>Go to Main Page</a>");
}

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
    <title>Edit Trainer Certifications</title>
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="common.css">
    <script src="common.js" defer></script>
    <script>
        // Pass the session expiration time to the JavaScript function
        document.addEventListener('DOMContentLoaded', () => {
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
        });
    </script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="trainer_list.php">Back to Trainer List</a>
            <a href="index.php">Back to Main Page</a>
        </div>
        <h1>Edit Certifications for Trainer: <?php echo htmlspecialchars($trainer_name); ?></h1>

        <!-- Display success or error message -->
        <?php echo $message; ?>

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
