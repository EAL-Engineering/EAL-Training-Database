<?php
/**
 * Authentication and authorization checks for the application.
 * 
 * PHP Version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Check if a session is already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Session timeout duration in seconds (2 hours).
 */
define('SESSION_TIMEOUT', 2 * 60 * 60);

/**
 * Check if the user is logged in and has the required access level.
 * Redirects unauthorized users to the login page.
 *
 * @param int    $requiredRole The required access level.
 * @param string $redirectUrl  The current page URI to redirect back after login.
 * 
 * @return void 
 */
function checkLogin($requiredRole, $redirectUrl = '')
{
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in. Redirecting to login page.");
        $loginUrl = 'login.php';
        if (!empty($redirectUrl)) {
            $loginUrl .= '?return=' . urlencode($redirectUrl);
        }
        header("Location: " . $loginUrl);
        exit();
    }

    $userRole = getUserRole();

    if ($userRole === null) {
        error_log("User role not set. Ensure role_id is correctly set in the database.");
        header("Location: index.php");
        exit();
    }

    if ($userRole > $requiredRole) {
        error_log("User does not have the required role. Redirecting to main page.");
        header("Location: index.php");
        exit();
    }
}

/**
 * Get the role of the currently logged-in user.
 *
 * @return int The role of the user.
 */
function getUserRole()
{
    global $mysqli;
    $user_id = $_SESSION['user_id'];
    $query = $mysqli->prepare("SELECT role_id FROM trainers WHERE seq_nmbr = ?");
    if (!$query) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        die("Database error. Please try again later.");
    }
    $query->bind_param("i", $user_id);
    $query->execute();
    $query->bind_result($role);
    $query->fetch();
    $query->close();
    return $role;
}

/**
 * Calculate the time remaining until the user's session expires.
 *
 * This function checks the `last_activity` timestamp stored in the session
 * and calculates how much time is left before the session expires. 
 * Sessions are set to expire after 2 hours of inactivity.
 *
 * @return int The number of seconds remaining until the session expires. 
 *             Returns 0 if the session has already expired or if `last_activity` is not set.
 */
function getTimeUntilSessionExpires() 
{
    if (isset($_SESSION['last_activity'])) {
        $remaining = (2 * 60 * 60) - (time() - $_SESSION['last_activity']);
        return max($remaining, 0); // Ensure no negative time is returned
    }
    return 0;
}
?>
