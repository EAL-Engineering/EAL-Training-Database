<?php
// auth.php
session_start();

// Session timeout duration in seconds (2 hours)
define('SESSION_TIMEOUT', 2 * 60 * 60);

function check_access($required_role) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Check if session has expired
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        // Destroy the session and redirect to login page
        session_unset();
        session_destroy();
        header("Location: login.php?message=session_expired");
        exit;
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();

    // Check role permissions
    if ($_SESSION['role_id'] < $required_role) {
        header("Location: unauthorized.php");
        exit;
    }
}
?>
