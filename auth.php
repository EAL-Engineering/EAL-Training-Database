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
session_start();

/**
 * Session timeout duration in seconds (2 hours).
 */
define('SESSION_TIMEOUT', 2 * 60 * 60);

/**
 * Checks if the user has the required access level.
 *
 * @param int $required_role The required role ID. 
 * 
 * @return void 
 */
function Check_access($required_role)
{ 
    // Check if the user is logged in. If not, redirect to the login page.
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Check if the session has expired. If so, destroy the session and redirect
    // to the login page.
    if (isset($_SESSION['last_activity']) 
        && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT
    ) { 
        session_unset();
        session_destroy();
        header("Location: login.php?message=session_expired");
        exit;
    }

    // Update last activity timestamp.
    $_SESSION['last_activity'] = time();

    // Check if the user has the required role. If not, redirect to the
    // unauthorized page.
    if ($_SESSION['role_id'] < $required_role) {
        header("Location: unauthorized.php");
        exit;
    }
}

/**
 * Check if the user is logged in and has the required access level.
 * Redirects unauthorized users to the login page.
 *
 * @param int $requiredRole The required access level.
 * @param string $currentPage The current page URI to redirect back after login.
 */
function checkLogin($requiredRole, $redirectUrl) {
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in. Redirecting to main page.");
        header("Location: index.php");
        exit();
    }

    // Assuming there's a function to get the user's role
    $userRole = getUserRole($_SESSION['user_id']);
    if ($userRole === null) {
        error_log("User role not set. Ensure role_id is correctly set in the database.");
        header("Location: index.php");
        exit();
    }

    if ($userRole < $requiredRole) {
        error_log("User does not have the required role. Redirecting to main page.");
        header("Location: index.php");
        exit();
    }
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
