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
 * @package TrainingManagementSystem
 * @author Gregory Leblanc <leblanc+php@ohio.edu>
 * @license AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link https://inpp.ohio.edu/~leblanc/eal_2024
 */

// session_start() must be called before any output and before includes
// that might themselves produce output.
session_start();

require_once "config.php";
require_once "auth.php";

if (isset($_GET['return'])) {
    $_SESSION['return_url'] = $_GET['return'];
}

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

$username = isset($_GET['login_name']) ? htmlspecialchars($_GET['login_name']) : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request.";
    }
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
            $stmt->close();

            // FIX (Issue #14): Regenerate the session ID on successful authentication
            // to prevent session fixation attacks. The `true` argument deletes the
            // old session data from the server so a pre-planted session ID becomes
            // useless after login.
            session_regenerate_id(true);

            $_SESSION['user_id'] = $id;
            $_SESSION['fname'] = $fname;
            $_SESSION['role_id'] = $role_id;
            $_SESSION['last_activity'] = time();

            $redirectUrl = 'index.php';
            if (isset($_GET['return'])) {
                $candidate = urldecode($_GET['return']);
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
    $stmt->close();
}

// $timeUntilSessionExpires is only meaningful for logged-in users. On the
// login page there is no session yet, so we pass 0 to avoid an undefined
// variable notice and keep the JS call safe.
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
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="common.js" defer></script>
    <script>
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
    <input type="text" name="username" value="<?php echo $username; ?>" /><br><br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required><br><br>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
    <button type="submit">Login</button>
</form>
<p><a href="password_recovery.php">Forgot your password?</a></p>
</body>
</html>
