<?php
/**
 * Edit Operator Details Page
 * 
 * This script retrieves and displays the details of an operator for editing. 
 * It ensures proper authorization and handles operator data securely.
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
 * Database connection and configuration.
 * Includes the `config.php` file to establish a connection with the database.
 */
require_once "config.php";
require_once "auth.php";

/**
 * Encoded URL string of the current page for safe use in GET parameters.
 * 
 * @var string $currentUrl
 */
$currentUrl = urlencode($_SERVER['REQUEST_URI']);

/**
 * Check if the user is logged in and authorized to edit personnel details.
 * Redirects unauthorized users to the login page.
 */
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] < 1 || $_SESSION['role_id'] > 2)) {
    header("Location: login.php?return=$currentUrl");
    exit();
}

/**
 * Time until the session expires
 *
 * @var int $timeUntilSessionExpires
 */
$timeUntilSessionExpires = getTimeUntilSessionExpires();

/**
 * Validate and sanitize the `id` parameter from the URL.
 * 
 * @throws Exception If the ID is not set or not numeric.
 */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request. No operator ID provided. <a href='index.php'>Go to Main Page</a>");
}

$id = intval($_GET['id']); // Sanitize the ID

/**
 * Retrieve operator details from the database.
 * 
 * @var string $query SQL query to fetch operator details.
 * @var mysqli_stmt $operator_stmt Prepared statement for fetching operator details.
 */
$query = "SELECT * FROM operators WHERE seq_nmbr = ?";
$operator_stmt = $mysqli->prepare($query);

if (!$operator_stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}

$operator_stmt->bind_param("i", $id);
$operator_stmt->execute();
$operator_stmt->bind_result($seq_nmbr, $name, $fname, $email, $altemail, $phones, $status, $office, $home, $updated, $comments, $entered, $addedby);

/**
 * Fetch the operator's details into an array for display and editing.
 * 
 * @var array $operator Associative array holding operator details.
 */
if ($operator_stmt->fetch()) {
    $operator = [
        'seq_nmbr' => $seq_nmbr,
        'name' => $name,
        'fname' => $fname,
        'email' => $email,
        'altemail' => $altemail,
        'phones' => $phones,
        'status' => $status,
        'office' => $office,
        'home' => $home,
        'updated' => $updated,
        'comments' => $comments,
        'entered' => $entered,
        'addedby' => $addedby
    ];
} else {
    die("No operator found with the provided ID.");
}

$operator_stmt->close();

/**
 * Fetch certifications for the operator.
 * 
 * @var string $certifications_query SQL query to retrieve operator certifications.
 * @var mysqli_stmt $certifications_stmt Prepared statement for fetching certifications.
 */
$certifications_query = "
    SELECT c.certification 
    FROM optraining ot
    JOIN certifications c ON ot.certification = c.seq_nmbr
    WHERE ot.operator = ?
";
$certifications_stmt = $mysqli->prepare($certifications_query);

if (!$certifications_stmt) {
    die("Database error: " . $mysqli->error);
}

$certifications_stmt->bind_param("i", $id);
$certifications_stmt->execute();
$certifications_stmt->bind_result($certification);

/**
 * Retrieve certifications into an array.
 * 
 * @var array $certifications List of certifications for the operator.
 */
$certifications = [];
while ($certifications_stmt->fetch()) {
    $certifications[] = $certification;
}

$certifications_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Operator</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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
        <!-- HTML form and interface for editing operator details -->
        <div class="back-button-container">
            <a href="personnel_list.php">Back to Personnel List</a>
            <a href="index.php">Back to main page</a>
        </div>
        <h1>Edit Operator Details</h1>
        <form method="post" action="personnel_save.php">
            <div class="form-row">
                <label>Seq Number:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['seq_nmbr']); ?>" readonly>
                <input type="hidden" name="seq_nmbr" value="<?php echo htmlspecialchars($operator['seq_nmbr']); ?>">
            </div>
            <div class="form-row">
                <label>Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($operator['name']); ?>">
            </div>
            <div class="form-row">
                <label>Full Name:</label>
                <input type="text" name="fname" value="<?php echo htmlspecialchars($operator['fname']); ?>">
            </div>
            <div class="form-row">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($operator['email']); ?>">
            </div>
            <div class="form-row">
                <label>Alt Email:</label>
                <input type="email" name="altemail" value="<?php echo htmlspecialchars($operator['altemail']); ?>">
            </div>
            <div class="form-row">
                <label>Phones:</label>
                <input type="text" name="phones" value="<?php echo htmlspecialchars($operator['phones']); ?>">
            </div>
            <div class="form-row">
                <label>Status:</label>
                <select name="status">
                    <option value="Active" <?php echo $operator['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $operator['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Other" <?php echo $operator['status'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="form-row">
                <label>Office:</label>
                <input type="text" name="office" value="<?php echo htmlspecialchars($operator['office']); ?>">
            </div>
            <div class="form-row">
                <label>Home:</label>
                <input type="text" name="home" value="<?php echo htmlspecialchars($operator['home']); ?>">
            </div>
            <div class="form-row">
                <label>Updated:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['updated']); ?>" readonly>
            </div>
            <div class="form-row">
                <label>Entered:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['entered']); ?>" readonly>
            </div>
            <div class="form-row">
                <label>Added By:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['addedby']); ?>" readonly>
            </div>
            <div class="form-row">
                <label>Comments:</label>
                <textarea name="comments"><?php echo htmlspecialchars($operator['comments']); ?></textarea>
            </div>
            <button type="submit" class="full-width-button">Save Changes</button>
        </form>
        <div class="certifications">
            <h2>Certifications</h2>
            <?php if (count($certifications) > 0) : ?>
                <ul>
                    <?php foreach ($certifications as $cert): ?>
                        <li><?php echo htmlspecialchars($cert); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No certifications found for this operator.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="back-button-container">
        <a href="certification_add.php?id=<?php echo urlencode($operator['seq_nmbr']); ?>">Add Certification</a>
    </div>
</body>
</html>
