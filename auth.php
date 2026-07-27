<?php

/**
 * Authentication and authorization checks for the application.
 *
 * PHP Version 8.0+
 *
 * @category Certification
 * @package TrainingManagementSystem
 * @author Gregory Leblanc <leblanc+php@ohio.edu>
 * @license AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Check if a session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Session timeout duration in seconds (2 hours).
 */
define('SESSION_TIMEOUT', 2 * 60 * 60);

/**
 * FIX (Issue #13 / Bug #27): Enforce session idle timeout globally on load.
 * Purges expired session variables immediately so header.php and checkLogin()
 * see an unauthenticated state regardless of which page is loaded.
 */
if (isset($_SESSION['user_id'], $_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start(); // Start fresh empty session
    }
}

/**
 * Check if the user is logged in and has the required access level.
 * Redirects unauthorized users to the login page.
 *
 * @param int    $requiredRole The required access level.
 * @param string $redirectUrl  The current page URI to redirect back after login.
 *
 * @return void
 */
function checkLogin($requiredRole, $redirectUrl)
{
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in. Redirecting to login page.");
        header("Location: login.php?return=" . urlencode($redirectUrl));
        exit();
    }

    // Refresh the idle timer on every valid request
    $_SESSION['last_activity'] = time();

    $userRole = getUserRole();

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
 * Get the role of the currently logged-in user.
 *
 * @return int|null The role of the user.
 */
function getUserRole()
{
    global $mysqli;

    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id === null) {
        return null;
    }

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
 * FIX (Issue #8): Replaced hardcoded (2 * 60 * 60) with SESSION_TIMEOUT constant.
 *
 * @return int The number of seconds remaining until the session expires.
 */
function getTimeUntilSessionExpires()
{
    if (isset($_SESSION['last_activity'])) {
        $remaining = SESSION_TIMEOUT - (time() - $_SESSION['last_activity']);
        return max($remaining, 0); // Ensure no negative time is returned
    }
    return 0;
}

/**
 * Validate that a redirect URL is safe to use (i.e. relative to this app).
 *
 * @param string $url The candidate redirect URL.
 * @return bool True if the URL is safe to redirect to, false otherwise.
 */
function isSafeRedirect($url)
{
    if (!is_string($url) || $url === '') {
        return false;
    }

    if (preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*:/', $url)) {
        return false;
    }

    if (strpos($url, '//') === 0) {
        return false;
    }

    if (strpos($url, '/') !== 0) {
        return false;
    }

    return true;
}

/**
 * Generates or retrieves a CSRF token for the current session.
 *
 * @return string The CSRF token.
 */
function getCSRFToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Regenerate the CSRF token for the current session.
 *
 * @return string The new CSRF token.
 */
function regenerateCSRFToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Validates the CSRF token provided in a POST request.
 *
 * @param string|null $token The token from the form submission.
 * @return bool True if valid, false otherwise.
 */
function verifyCSRFToken(?string $token)
{
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    if ($sessionToken === null || $token === null) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}
