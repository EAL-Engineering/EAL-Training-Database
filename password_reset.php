<?php
/**
 * Password Reset Script
 *
 * This script handles the resetting of a user's password using a token.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */
require_once "config.php";

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the reset token is provided
if (!isset($_GET['token'])) {
    die("Invalid or expired token.");
}

$reset_token = $_GET['token'];

// Verify the reset token in the database
$query = "SELECT seq_nmbr, reset_expiration FROM trainers WHERE reset_token = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("s", $reset_token);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($trainer_id, $reset_expiration);

// Check if a valid record exists and the token hasn't expired
if ($stmt->num_rows === 0) {
    die("Invalid or expired token.");
}

$stmt->fetch();

if (time() > strtotime($reset_expiration)) {
    die("This token has expired.");
}

$stmt->close();

// If the form is submitted, process the password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Salt generation for bcrypt (bcrypt requires a salt to be passed along)
    $salt = substr(sha1(rand()), 0, 22);  // Generate a salt with 22 characters
    
    // Hash the password with bcrypt
    $hashed_password = crypt($new_password, '$2y$10$' . $salt);
    
    // Store the hashed password in the database
    $update_query = "UPDATE trainers SET password_hash = ?, reset_token = NULL, reset_expiration = NULL WHERE seq_nmbr = ?";
    $update_stmt = $mysqli->prepare($update_query);
    if (!$update_stmt) {
        die("Database error: " . $mysqli->error);
    }
    
    $update_stmt->bind_param("si", $hashed_password, $trainer_id);
    if ($update_stmt->execute()) {
        // Redirect to login page after successful password change
        header("Location: login.php?password_reset=success");
        exit();
    } else {
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
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
