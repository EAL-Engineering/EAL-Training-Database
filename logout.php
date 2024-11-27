<?php
// logout.php
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
