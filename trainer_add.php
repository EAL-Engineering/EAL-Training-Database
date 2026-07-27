<?php
/**
 * Add New Trainer
 *
 * This script allows authorized trainers to add new trainers to the system.
 * It validates the operator's eligibility and sends a password reset email
 * upon successful addition.
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

/**
 * Check if the user is logged in and authorized to edit personnel details.
 * Redirects unauthorized users to the login page.
 */
checkLogin(1, $_SERVER['REQUEST_URI'] ?? '');

/**
 * Remaining session time in seconds.
 *
 * @var int $timeUntilSessionExpires
 */
$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Verify currently logged-in user is a trainer
$user_id = $_SESSION['user_id'] ?? 0;
$trainerCheckQuery = $mysqli->prepare("SELECT COUNT(*) FROM trainers WHERE seq_nmbr = ?");
if (!$trainerCheckQuery) {
    error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
    die("Database error. Please try again later.");
}
$trainerCheckQuery->bind_param("i", $user_id);
$trainerCheckQuery->execute();
$trainerCheckQuery->bind_result($isTrainer);
$trainerCheckQuery->fetch();
$trainerCheckQuery->close();

if (!$isTrainer) {
    error_log("Access denied: Only existing trainers can add new trainers.");
    die("Access denied: Only existing trainers can add new trainers.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX (Issue #4): CSRF guard clause
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } elseif (!isset($_POST['operator_id'], $_POST['cert_id'])) {
        $error = "Operator and initial certification selection are required.";
    } else {
        $operator_id = intval($_POST['operator_id']);
        $cert_id     = intval($_POST['cert_id']);

        // Check if operator exists and is active
        $operatorCheckQuery = $mysqli->prepare("SELECT fname, email FROM operators WHERE seq_nmbr = ? AND status = 'Active'");
        if (!$operatorCheckQuery) {
            error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
            die("Database error. Please try again later.");
        }
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
            $login_name = substr($email, 0, strpos($email, '@'));

            $mysqli->autocommit(false);

            try {
                // FIX (Issue #17): Include required cert_ptr column in can_certify query
                $addCanCertifyQuery = $mysqli->prepare("INSERT INTO can_certify (trainer_ptr, cert_ptr) VALUES (?, ?)");
                if (!$addCanCertifyQuery) {
                    throw new Exception("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
                }
                $addCanCertifyQuery->bind_param("ii", $operator_id, $cert_id);
                $addCanCertifyQuery->execute();
                $addCanCertifyQuery->close();

                // Generate password reset token
                $token = bin2hex(random_bytes(16));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $addTrainerEntry = $mysqli->prepare("INSERT INTO trainers (optbl_ptr, login_name, reset_token, reset_expiration) VALUES (?, ?, ?, ?)");
                if (!$addTrainerEntry) {
                    throw new Exception("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
                }
                $addTrainerEntry->bind_param("isss", $operator_id, $login_name, $token, $expires);
                $addTrainerEntry->execute();
                $addTrainerEntry->close();

                $mysqli->commit();
                $mysqli->autocommit(true);

                $resetLink = "https://inpp.ohio.edu/~leblanc/eal_2024/password_reset.php?token=" . urlencode($token);
                $subject = "Set Your Password for the Training Portal";
                $message = "Hello $fname,\n\nYou have been added as a trainer in the Training Information Portal.\nPlease set your password using the following link:\n\n$resetLink\n\nThank you.";

                mail($email, $subject, $message, "From: no-reply@ohio.edu");

                $success = "Trainer added successfully, and an email has been sent.";
            } catch (Exception $e) {
                $mysqli->rollback();
                $mysqli->autocommit(true);
                error_log("Transaction failed: " . $e->getMessage());
                $error = "Failed to add trainer. Please try again.";
            }
        }
    }
}

// Fetch eligible operators
$eligibleOperators = $mysqli->query(
    "SELECT o.seq_nmbr, o.fname, o.email
     FROM operators o
     JOIN optraining ot ON o.seq_nmbr = ot.operator
     WHERE o.status = 'Active'
       AND o.email IS NOT NULL
       AND o.email != ''
       AND ot.certification = 3
       AND o.seq_nmbr NOT IN (SELECT optbl_ptr FROM trainers)
     ORDER BY o.fname ASC"
);

// Fetch available certifications for initial assignment
$allCertifications = $mysqli->query(
    "SELECT seq_nmbr, certification FROM certifications ORDER BY certification ASC"
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
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha384-wsqsSADZR1YRBEZ4/kKHNSmU+aX8ojbnKUMN4RyD3jDkxw5mHtoe2z/T/n4l56U/" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js" integrity="sha384-cDXquhvkdBprgcpTQsrhfhxXRN4wfwmWauQ3wR5ZTyYtGrET2jd68wvJ1LlDqlQG" crossorigin="anonymous"></script>
    <script src="common.js" defer></script>
</head>
<body>
<?php require 'header.php'; ?>
<div>
    <div class="back-button-container">
        <a href="personnel_list.php">To Personnel List</a>
        <a href="index.php">To main page</a>
    </div>
</div>
<div class="form-container">
    <h1>Add New Trainer</h1>

    <?php if (isset($success)) : ?>
        <p class="success" style="color: green; font-weight: bold;"><?php echo htmlspecialchars($success); ?></p>
    <?php elseif (isset($error)) : ?>
        <p class="error" style="color: red; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p>
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
        </select><br><br>

        <!-- FIX (Issue #17): Initial certification selection -->
        <label for="cert_id">Initial Certification to Teach:</label>
        <select name="cert_id" id="cert_id" required>
            <option value="">-- Select Initial Certification --</option>
            <?php while ($cert = $allCertifications->fetch_assoc()): ?>
                <option value="<?php echo $cert['seq_nmbr']; ?>">
                    <?php echo htmlspecialchars($cert['certification']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit" class="primary-button">Add Trainer</button>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
    </form>
</div>
</body>
</html>
