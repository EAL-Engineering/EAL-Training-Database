<?php
/**
 * Add New Personnel Script
 *
 * This script handles the addition of new personnel into the database.
 * It validates the form inputs, performs database insertion, and handles errors.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Start the session
session_start();

/**
 * Include the database connection file
 */
require_once "config.php";
require_once "auth.php";

// Check if the user is logged in and authorized to edit personnel details
checkLogin(1, $_SERVER['REQUEST_URI']);

/**
 * Create the added by variable to enter into the database.
 */
$addedby = isset($_SESSION['fname']) ? $_SESSION['fname'] : 'Unknown';

/**
 * Time until the session expires
 *
 * @var int $timeUntilSessionExpires
 */
$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Initialize variables for error handling
/**
 * Error message to display to the user
 *
 * @var string $error_message
 */
$error_message = "";

/**
 * Success message to display to the user
 *
 * @var string $success_message
 */
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    }
    /**
     * Form input variables
     *
     * @var string $fname Full name of the personnel
     * @var string $name Full name (duplicate of $fname for legacy reasons)
     * @var string $email Email address of the personnel
     * @var string $altemail Alternate email address
     * @var string $phone Phone number
     * @var string $office Office number
     * @var string $home Home address
     * @var string $comments Additional comments
     * @var string $status Status of the personnel (Active/Inactive)
     * @var string $entered Timestamp of when the personnel was added
     */
    $fname = isset($_POST['fname']) ? trim($_POST['fname']) : '';
    $name = isset($_POST['fname']) ? trim($_POST['fname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $altemail = isset($_POST['altemail']) ? trim($_POST['altemail']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $office = isset($_POST['office']) ? trim($_POST['office']) : '';
    $home = isset($_POST['home']) ? trim($_POST['home']) : '';
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    $status = isset($_POST['status']) && $_POST['status'] !== '' ? trim($_POST['status']) : 'Active';
    $entered = date('Y-m-d H:i:s'); // Get current date and time in 'YYYY-MM-DD HH:MM:SS' format

    // Validate inputs
    if (empty($fname) || empty($email)) {
        $error_message = "Full Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email address.";
    } elseif (!empty($altemail) && !filter_var($altemail, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid alternate email address.";
    } else {
        // Insert into the database
        $stmt = $mysqli->prepare(
            "
            INSERT INTO operators (
	            fname, name, email, altemail, phones, 
                office, home, comments, status, entered, 
                addedby
            ) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            "
        );
        if ($stmt) {
            $stmt->bind_param(
                "sssssssssss",
                $fname,
                $name,
                $email,
                $altemail,
                $phone,
                $office,
                $home,
                $comments,
                $status,
                $entered,
                $addedby
            );
            if ($stmt->execute()) {
                $operator_id = $stmt->insert_id;

                // Insert default certification with trainer and status
                $default_certification = 1; // Replace with actual certification ID
                $trainer_id = $_SESSION['user_id']; // Currently logged-in user
                $status = 'Active';

                $stmt_cert = $mysqli->prepare(
                    "
                    INSERT INTO optraining (operator, certification, trainer, status) 
                    VALUES (?, ?, ?, ?)
                    "
                );
                if ($stmt_cert) {
                    $stmt_cert->bind_param("iiis", $operator_id, $default_certification, $trainer_id, $status);
                    $stmt_cert->execute();
                    $stmt_cert->close();
                }

                // Redirect to personnel_list.php
                header("Location: personnel_list.php");
                exit;
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Database error: " . $mysqli->error;
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Personnel</title>
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="common.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize the countdown with the session expiration time from PHP
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
        });
    </script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="personnel_list.php">To Personnel List</a>
            <a href="index.php">To main page</a>
        </div>
    </div>
    <div class="container">
        <h1>Add New Personnel</h1>
        <p>Note: New personnel will automatically be added with <b>key holder</b> Training</p>

        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php elseif (!empty($success_message)) : ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="personnel_add.php">
            <div class="form-group">
                <label for="fname">Full Name:</label>
                <input type="text" name="fname" id="fname" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="altemail">Alternate Email Address:</label>
                <input type="email" name="altemail" id="altemail">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" name="phone" id="phone">
            </div>
            <div class="form-group">
                <label for="office">Office Number:</label>
                <input type="text" name="office" id="office">
            </div>
            <div class="form-group">
                <label for="home">Home Address:</label>
                <textarea name="home" id="home" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="comments">Comments:</label>
                <textarea name="comments" id="comments" rows="1"></textarea>
            </div>
            <script>
                // Target the textarea with id 'comments'
                const commentsTextarea = document.getElementById('comments');

                if (commentsTextarea) {
                    commentsTextarea.addEventListener('input', function () {
                        // Reset height to recalculate based on scrollHeight
                        this.style.height = 'auto';
                        this.style.height = this.scrollHeight + 'px';
                    });
                }
            </script>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            <button type="submit">Add Personnel</button>
        </form>
    </div>
</body>
</html>
