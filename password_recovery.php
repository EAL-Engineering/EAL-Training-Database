<?php
/**
 * Password Recovery Script
 *
 * This script handles user password recovery requests.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

require "config.php";
require_once "auth.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']); 

    // Query to find the operator and ensure linkage to a trainer
    $query = "
        SELECT t.seq_nmbr, t.login_name 
        FROM trainers t
        INNER JOIN operators o ON t.optbl_ptr = o.seq_nmbr
        WHERE o.email = ? OR o.altemail = ?
    ";

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        // Log actual error for admin, show generic error to user
        error_log("Database error in password_recovery.php: " . $mysqli->error);
        die("Internal server error. <a href='index.php'>Go to Main Page</a>");
    }

    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $stmt->bind_result($trainer_id, $username);

    // If an account is found, proceed with token generation and email
    if ($stmt->fetch()) {
        $stmt->close(); // Close immediately to allow the subsequent update

        // Generate a secure reset token and expiration time
        $reset_token = bin2hex(openssl_random_pseudo_bytes(16));
        $reset_expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store the reset token and expiration time in the database
        $update_query = "UPDATE trainers SET reset_token = ?, reset_expiration = ? WHERE seq_nmbr = ?";
        if ($update_stmt = $mysqli->prepare($update_query)) {
            $update_stmt->bind_param("ssi", $reset_token, $reset_expiration, $trainer_id);
            
            if ($update_stmt->execute()) {
                // Prepare reset link and email content
                $reset_link = "https://inpp.ohio.edu/~leblanc/eal_2024/password_reset.php?token=" . urlencode($reset_token);
                $subject = "Password Recovery Request";
                $email_body = "Hello, $username,\n\nClick the following link to reset your password:\n\n$reset_link\n\nThis link is valid for 1 hour.";
                $headers = "From: no-reply@ohio.edu";

                // Send email, logging any failures internally
                if (!mail($email, $subject, $email_body, $headers)) {
                    error_log("Failed to send password recovery email to $email");
                }
            }
            $update_stmt->close();
        }
    } else {
        // If not found, close the statement and proceed
        $stmt->close();
        // To mitigate timing attacks, you could perform a dummy hash or sleep here
    }

    // FIX (Issue #5): Always return the same message to avoid email enumeration
    $message = "If an account is associated with " . htmlspecialchars($email) . ", a password recovery email has been sent.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Recovery</title>
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="container">
        <h1>Password Recovery</h1>
        
        <?php if (!empty($message)) : ?>
            <p class="alert alert-success"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Enter your email address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" style="width:100%; padding:10px; margin-top:10px;">Submit Request</button>
        </form>
        <p style="text-align:center; margin-top:20px;"><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>
