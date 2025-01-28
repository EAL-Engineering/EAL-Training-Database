<?php
/**
 * Logout Script
 *
 * This script handles user logout by clearing session data and optionally
 * removing session cookies. After logout, the user is redirected to the
 * home page or login page.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

session_start();

// Destroy all session data
$_SESSION = []; // Clear session array
session_destroy(); // Destroy the session itself

// Optionally, clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Redirect to login page or home page
header("Location: index.php");
exit;
?>
