<?php
// Include database connection
include_once("config.php");
session_start();  // Make sure this is called before any output

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch trainer data
    $query = "SELECT seq_nmbr, login_name, password_hash, role_id FROM trainers WHERE login_name = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $fname, $password_hash, $role_id);

    // Check if the username exists in the database
    if ($stmt->fetch()) {
        // Verify the password by comparing the stored hash with the password provided
        if (crypt($password, $password_hash) === $password_hash) {
            // Successful login
            $_SESSION['user_id'] = $id;
            $_SESSION['fname'] = $fname;
            $_SESSION['role_id'] = $role_id;
    
            header("Location: trainer_list.php");
            exit;
        }
    }
    // If username or password is invalid, show a generic error message
    $error = "Invalid username or password.";    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
