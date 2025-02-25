<?php
/**
 * Login page for the OUAL Training Management System
 * 
 * This script handles user authentication by validating usernames and passwords
 * against the `trainers` table in the database. Successful login initializes
 * session variables and redirects the user to the desired page.
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
session_start();

if (isset($_GET['return'])) {
    $_SESSION['return_url'] = $_GET['return'];
}

// Session timeout duration in seconds (2 hours)
define('SESSION_TIMEOUT', 2 * 60 * 60); // 7200 seconds

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    // Check if session has expired
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        // Destroy the session and redirect to login page
        session_unset();
        session_destroy();
        $error = "Your session has expired. Please log in again.";
        header("Location: login.php");
        exit;
    }
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch trainer data
    $query = "SELECT seq_nmbr, login_name, password_hash, role_id FROM trainers WHERE login_name = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $fname, $password_hash, $role_id);
    
    if ($stmt->fetch()) {
        // Verify the password by comparing the stored hash with the password provided
        if (crypt($password, $password_hash) === $password_hash) {
            // Successful login
            $_SESSION['user_id'] = $id;
            $_SESSION['fname'] = $fname;
            $_SESSION['role_id'] = $role_id;
            $_SESSION['last_activity'] = time(); // Record the login time
            $redirectUrl = isset($_GET['return']) ? urldecode($_GET['return']) : 'index.php';
            header("Location: $redirectUrl");
        }
    }

    // Always return the same error message
    $error = "Invalid username or password.";
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
    <h1>Login</h1>
    <?php if (isset($error)) : ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <p><a href="password_reset.php">Forgot your password?</a></p>
</body>
</html>
