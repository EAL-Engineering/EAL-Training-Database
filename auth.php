<?php
/**
 * Authentication and authorization checks for the application.
 *
 * PHP Version 5.4+
 *
 * @category Certification
 * @package TrainingManagementSystem
 * @author Gregory Leblanc <leblanc+php@ohio.edu>
 * @license AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link https://inpp.ohio.edu/~leblanc/eal_2024
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
 * Also enforces the session idle timeout (Issue #13): if last_activity is
 * older than SESSION_TIMEOUT, the session is destroyed and the user is sent
 * to the login page with a return URL so they land back where they were after
 * re-authenticating. last_activity is refreshed on every successful check so
 * the timeout is idle-based rather than absolute.
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

    // FIX (Issue #13): Enforce session idle timeout on every protected page.
    // Previously this check only existed in login.php, meaning an authenticated
    // user could stay active indefinitely on any other page.
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header("Location: login.php?return=" . urlencode($redirectUrl));
        exit();
    }

    // Refresh the idle timer on every valid request.
    $_SESSION['last_activity'] = time();

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
 * Returns 0 if the session has already expired or if `last_activity` is not set.
 */
function getTimeUntilSessionExpires()
{
    if (isset($_SESSION['last_activity'])) {
        $remaining = (2 * 60 * 60) - (time() - $_SESSION['last_activity']);
        return max($remaining, 0); // Ensure no negative time is returned
    }
    return 0;
}

/**
 * Validate that a redirect URL is safe to use (i.e. relative to this app).
 *
 * Accepts only paths that start with a single '/' but not '//' (which browsers
 * treat as protocol-relative and would allow off-site redirects), and rejects
 * anything containing a scheme (e.g. http://, https://).  Anything that does
 * not pass returns false so the caller can fall back to a safe default.
 *
 * @param string $url The candidate redirect URL.
 *
 * @return bool True if the URL is safe to redirect to, false otherwise.
 */
function isSafeRedirect($url)
{
    // Must be a non-empty string
    if (!is_string($url) || $url === '') {
        return false;
    }

    // Reject anything with a scheme (catches http://, https://, javascript:, etc.)
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*:/', $url)) {
        return false;
    }

    // Reject protocol-relative URLs (//evil.com)
    if (strpos($url, '//') === 0) {
        return false;
    }

    // Must start with a single slash — i.e. an absolute path on this server
    if (strpos($url, '/') !== 0) {
        return false;
    }

    return true;
}

/**
 * Generates or retrieves a CSRF token for the current session.
 * * @return string The CSRF token.
 */
function getCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        // Generate a secure random token
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the CSRF token provided in a POST request.
 * * @param string $token The token from the form submission.
 * @return bool True if valid, false otherwise.
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>
