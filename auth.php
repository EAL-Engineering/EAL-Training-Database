<?php

/**
 * Session Bootstrapping & Authentication Middleware
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

require_once "auth_functions.php";

// Configure secure session settings before starting session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 7200);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}

// Session activity timeout handling
$maxLifetime = ini_get('session.gc_maxlifetime') ?: 7200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxLifetime)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Real-time privilege synchronization
if (isset($_SESSION['user_id'])) {
    global $mysqli;
    $user_id = $_SESSION['user_id'];
    $role_query = $mysqli->prepare("SELECT role_id FROM trainers WHERE seq_nmbr = ?");
    if ($role_query) {
        $role_query->bind_param("i", $user_id);
        $role_query->execute();
        $role_result = $role_query->get_result();
        
        if ($row = $role_result->fetch_assoc()) {
            $_SESSION['role_id'] = (int)$row['role_id']; // Sync role
        } else {
            // User was deleted from the trainers table
            session_unset();
            session_destroy();
            header("Location: login.php?error=account_deleted");
            exit;
        }
        $role_query->close();
    }
}
