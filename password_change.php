<?php

/**
 * Password Change Script
 *
 * Allows any authenticated user to change their password.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

require_once "auth.php";
require_once "config.php";

// FIX (Issue #15): Require role >= 0 so any logged-in user can change their password
checkLogin(0, $_SERVER['REQUEST_URI'] ?? '');

$timeUntilSessionExpires = getTimeUntilSessionExpires();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $error_message = "Invalid security token. Please refresh the page and try again.";
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $user_id = $_SESSION['user_id'] ?? null;
            if ($user_id === null) {
                $error_message = "User session expired or invalid.";
            } else {
                // Fetch current password hash for the logged-in user
                $query = "SELECT password_hash FROM trainers WHERE seq_nmbr = ?";
                $stmt = $mysqli->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($row) {
                        if (password_verify($current_password, $row['password_hash'])) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                            $update_query = "UPDATE trainers SET password_hash = ? WHERE seq_nmbr = ?";
                            $update_stmt = $mysqli->prepare($update_query);
                            if ($update_stmt) {
                                $update_stmt->bind_param("si", $hashed_password, $user_id);
                                if ($update_stmt->execute()) {
                                    $success_message = "Password successfully changed.";
                                } else {
                                    $error_message = "Database error updating password.";
                                }
                                $update_stmt->close();
                            } else {
                                $error_message = "Database error preparing update statement.";
                            }
                        } else {
                            $error_message = "Current password is incorrect.";
                        }
                    } else {
                        $error_message = "User account not found.";
                    }
                } else {
                    $error_message = "Database error verifying current password.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="common.js" defer></script>
</head>
<body>
    <?php require 'header.php'; ?>
    <h1>Change Password</h1>

    <?php if (!empty($error_message)) : ?>
        <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <?php if (!empty($success_message)) : ?>
        <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <form method="post" class="pw_change">
        <label for="current_password">Current Password:</label>
        <input type="password" id="current_password" name="current_password" required><br>
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required><br>
        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required><br>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
        <button type="submit">Change Password</button>
    </form>
</body>
</html>
