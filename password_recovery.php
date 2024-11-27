<?php
include("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']); // Sanitize email input

    // Query to find the operator and ensure linkage to a trainer
    $query = "
        SELECT t.seq_nmbr, t.login_name, t.optbl_ptr 
        FROM trainers t
        INNER JOIN operators o ON t.optbl_ptr = o.seq_nmbr
        WHERE o.email = ? OR o.altemail = ?
    ";

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Database error: " . $mysqli->error);
    }

    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $stmt->bind_result($trainer_id, $username, $optbl_ptr);

    if ($stmt->fetch()) {
        // Generate a secure reset token and expiration timestamp
        // Generate a secure reset token and expiration time
        $reset_token = bin2hex(openssl_random_pseudo_bytes(16)); // Generates 16 bytes of random data
        if (!$reset_token) {
            error_log("Error generating reset token.");
            die("Internal server error. Please try again later.");
        }
        $reset_expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store the reset token and expiration time in the database
        // Assuming previous SELECT statement was executed, make sure to close it properly
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close(); // Close the previous statement if exists
        }

        // Prepare the update query
        $update_query = "UPDATE trainers SET reset_token = ?, reset_expiration = ? WHERE seq_nmbr = ?";

        if (!$update_stmt = $mysqli->prepare($update_query)) {
            error_log("Error preparing update statement: " . $mysqli->error);
            die("Internal server error. Please try again later." . $mysqli->error);
        }

        // Bind parameters to the prepared statement
        $update_stmt->bind_param("ssi", $reset_token, $reset_expiration, $trainer_id);

        // Execute the statement
        if (!$update_stmt->execute()) {
            error_log("Error executing update statement: " . $update_stmt->error);
            die("Internal server error. Please try again later.");
        }

        // Close the update statement after use
        $update_stmt->close();

        // Send the password reset email
        $reset_link = "https://inpp.ohio.edu/~leblanc/eal_2024/password_reset.php?token=" . urlencode($reset_token);
        $subject = "Password Recovery Request";
        $message = "Hello, $username,\n\nClick the following link to reset your password:\n\n$reset_link\n\nThis link is valid for 1 hour.";
        $headers = "From: no-reply@yourdomain.com";

        if (!mail($email, $subject, $message, $headers)) {
            error_log("Failed to send email to $email");
            die("Failed to send the email. Please try again later.");
        }

        echo "A password recovery email has been sent to $email.";

    } else {
        echo "No account found associated with that email.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Recovery</title>
</head>
<body>
    <h1>Password Recovery</h1>
    <form method="post">
        <label for="email">Enter your email:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
