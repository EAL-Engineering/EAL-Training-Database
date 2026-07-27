<?php

/**
 * Login page for the OUAL Training Management System
 *
 * This script handles user authentication by validating usernames and passwords
 * against the `trainers` table in the database. Successful login initializes
 * session variables and redirects the user to the desired page.
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

if (isset($_GET['return'])) {
    $_SESSION['return_url'] = $_GET['return'];
}

// If user is already logged in, send them to the main page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$username = htmlspecialchars($_GET['login_name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX (Issue #4): Guard clause — halt execution immediately if CSRF fails
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Fetch trainer data
        $query = "SELECT seq_nmbr, login_name, password_hash, role_id FROM trainers WHERE login_name = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $trainer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($trainer) {
            // FIX (Issue #3): Use modern password_verify() instead of legacy crypt()
            if (password_verify($password, $trainer['password_hash'])) {
                // Regenerate session ID and rotate CSRF token on login
                session_regenerate_id(true);
                regenerateCSRFToken();

                $_SESSION['user_id'] = $trainer['seq_nmbr'];
                $_SESSION['fname'] = $trainer['login_name'];
                $_SESSION['role_id'] = $trainer['role_id'];
                $_SESSION['last_activity'] = time();

                $redirectUrl = 'index.php';
                $returnParam = $_GET['return'] ?? null;
                if ($returnParam !== null) {
                    $candidate = urldecode($returnParam);
                    if (isSafeRedirect($candidate)) {
                        $redirectUrl = $candidate;
                    }
                }

                header("Location: $redirectUrl");
                exit;
            }
        }

        // Always return the same error message to avoid username enumeration
        $error = "Invalid username or password.";
    }
}

$timeUntilSessionExpires = 0;
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
    <script src="https://code.jquery.com/jquery-3.7.1.js"
            integrity="sha384-wsqsSADZR1YRBEZ4/kKHNSmU+aX8ojbnKUMN4RyD3jDkxw5mHtoe2z/T/n4l56U/"
            crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"
            integrity="sha384-cDXquhvkdBprgcpTQsrhfhxXRN4wfwmWauQ3wR5ZTyYtGrET2jd68wvJ1LlDqlQG"
            crossorigin="anonymous"></script>
    <script src="common.js" defer></script>
</head>
<body>
<?php require 'header.php'; ?>
<h1>Login</h1>

<?php if (isset($error)) : ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="post">
    <label for="username">Username:</label>
    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" /><br><br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required><br><br>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
    <button type="submit">Login</button>
</form>
<p><a href="password_recovery.php">Forgot your password?</a></p>
</body>
</html>
