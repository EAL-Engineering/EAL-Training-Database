<?php
include("auth.php");
include("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch current password hash
    $query = "SELECT password_hash FROM trainers WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($password_hash);

    if ($stmt->fetch() && password_verify($current_password, $password_hash)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_query = "UPDATE trainers SET password_hash = ? WHERE id = ?";
            $update_stmt = $mysqli->prepare($update_query);
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            $update_stmt->execute();

            echo "Password successfully changed.";
        } else {
            echo "New passwords do not match.";
        }
    } else {
        echo "Current password is incorrect.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
</head>
<body>
    <h1>Change Password</h1>
    <form method="post">
        <label for="current_password">Current Password:</label>
        <input type="password" id="current_password" name="current_password" required><br>
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required><br>
        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required><br>
        <button type="submit">Change Password</button>
    </form>
</body>
</html>
