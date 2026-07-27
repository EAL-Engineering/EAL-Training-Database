<?php

/**
 * Password Reset Script
 *
 * This script handles the resetting of a user's password using a token.
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

// Disable inline error display in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Check if the reset token is provided
$reset_token = $_GET['token'] ?? null;
if ($reset_token === null) {
    die("Invalid or expired token.");
}

// Verify the reset token in the database
$query = "SELECT seq_nmbr, reset_expiration, login_name FROM trainers WHERE reset_token = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("s", $reset_token);
$stmt->execute();
$trainer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trainer) {
    die("Invalid or expired token.");
}

$trainer_id       = $trainer['seq_nmbr'];
$reset_expiration = $trainer['reset_expiration'];
$login_name       = $trainer['login_name'];

if (time() > strtotime($reset_expiration)) {
    die("This token has expired.");
}

// If the form is submitted, process the password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        die("Invalid CSRF token.");
    }
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Store the hashed password in the database
    $update_query = "UPDATE trainers SET password_hash = ?, reset_token = NULL, reset_expiration = NULL WHERE seq_nmbr = ?";
    $update_stmt = $mysqli->prepare($update_query);
    if (!$update_stmt) {
        die("Database error: " . $mysqli->error);
    }

    $update_stmt->bind_param("si", $hashed_password, $trainer_id);
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header("Location: login.php?password_reset=success&login_name=" . urlencode($login_name));
        exit();
    } else {
        $update_stmt->close();
        die("Error updating password.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Your Password</h1>

    <form action="password_reset.php?token=<?php echo htmlspecialchars($reset_token); ?>" method="POST">
        <label for="password">New Password:</label>
        <input type="password" name="password" required>
        <br><br>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" required>
        <br><br>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
