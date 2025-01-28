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
?>
