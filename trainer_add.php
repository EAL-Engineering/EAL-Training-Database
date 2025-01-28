<?php
/**
 * Add New Trainer
 *
 * This script allows authorized trainers to add new trainers to the system.
 * It validates the operator's eligibility and sends a password reset email
 * upon successful addition.
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

// Include configuration and helper files
require_once "config.php";

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?return=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

/**
 * Remaining session time in seconds.
 *
 * @var int $timeUntilSessionExpires
 */
$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Check if the user is an existing trainer
/**
 * Check if the currently logged-in user is an existing trainer.
 *
 * @var bool $isTrainer True if the user is a trainer, false otherwise.
 */
$trainerCheckQuery = $mysqli->prepare(
    "
    SELECT 
        COUNT(*) 
    FROM 
        trainers 
    WHERE 
        seq_nmbr = ?"
);
$trainerCheckQuery->bind_param("i", $_SESSION['user_id']);
$trainerCheckQuery->execute();
$trainerCheckQuery->bind_result($isTrainer);
$trainerCheckQuery->fetch();
$trainerCheckQuery->close();

if (!$isTrainer) {
    die("Access denied: Only existing trainers can add new trainers.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['operator_id'])) {
    /**
     * ID of the operator to be added as a trainer.
     *
     * @var int $operator_id
     */
    $operator_id = intval($_POST['operator_id']);

    // Check if the operator exists
    $operatorCheckQuery = $mysqli->prepare("SELECT fname, email FROM operators WHERE seq_nmbr = ? AND status = 'Active'");
    $operatorCheckQuery->bind_param("i", $operator_id);
    $operatorCheckQuery->execute();
    $operatorCheckQuery->store_result();

    if ($operatorCheckQuery->num_rows === 0) {
        $operatorCheckQuery->close();
        $error = "The selected operator does not exist or is inactive.";
    } else {
        $operatorCheckQuery->bind_result($fname, $email);
        $operatorCheckQuery->fetch();
        $operatorCheckQuery->close();

        // Add the operator as a trainer
        $addTrainerQuery = $mysqli->prepare("INSERT INTO can_certify (trainer_ptr) VALUES (?)");
        $addTrainerQuery->bind_param("i", $operator_id);
        if ($addTrainerQuery->execute()) {
            $addTrainerQuery->close();

            // Send password reset email
            $resetLink = "http://yourdomain.com/reset_password.php?email=" . urlencode($email);
            $subject = "Set Your Password for the Training Portal";
            $message = "Hello $fname,\n\nYou have been added as a trainer in the Training Information Portal. 
            Please set your password using the following link:\n\n$resetLink\n\nThank you.";
            mail($email, $subject, $message, "From: no-reply@yourdomain.com");

            $success = "Trainer added successfully, and an email has been sent.";
        } else {
            $addTrainerQuery->close();
            $error = "Failed to add trainer. Please try again.";
        }
    }
}

// Fetch eligible operators
/**
 * Query to fetch eligible operators for trainer addition.
 *
 * @var mysqli_result|false $eligibleOperators Result set of eligible operators.
 */
$eligibleOperators = $mysqli->query(
    "
SELECT 
    o.seq_nmbr, 
    o.fname, 
    o.email 
FROM 
    operators o 
    JOIN optraining ot ON o.seq_nmbr = ot.operator 
WHERE 
    o.status = 'Active' 
    AND o.email IS NOT NULL 
    AND o.email != '' 
    AND ot.certification = 3 
    AND o.seq_nmbr NOT IN (
        SELECT 
            trainer_ptr 
        FROM 
            can_certify
    ) 
ORDER BY 
    `o`.`fname` ASC
"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Trainer</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
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
        <h1>Add New Trainer</h1>
        <?php if (isset($success)) : ?>
            <p class="success"><?php echo $success; ?></p>
        <?php elseif (isset($error)) : ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="operator_id">Select Operator:</label>
            <select name="operator_id" id="operator_id" required>
                <option value="">-- Select an Operator --</option>
                <?php while ($operator = $eligibleOperators->fetch_assoc()): ?>
                    <option value="<?php echo $operator['seq_nmbr']; ?>">
                        <?php echo htmlspecialchars($operator['fname'] . " (" . $operator['email'] . ")"); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="primary-button">Add Trainer</button>
        </form>
    </div>
</body>
</html>
