<?php
/**
 * Page header for the Training Management System.
 *
 * This file handles session management, displays user information,
 * and includes navigation links based on the user's authentication state.
 *
 * PHP version 5.x
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Start the session if it hasn't already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current script name to determine if it's login.php
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<div class="header">
    <?php if (isset($_SESSION['user_id'])) : ?>
        <!-- Display user information and session countdown for logged-in users -->
        <span>Logged in as: <?php echo htmlspecialchars($_SESSION['fname']); ?></span> |
        <span>Session expires in: <span id="countdown"></span></span>
        <a href="logout.php" class="logout-button">Logout</a>
    <?php else: ?>
        <!-- Display welcome message and login button for guests -->
        <span>Welcome to the OUAL Training Information Portal</span>
        <?php if ($currentScript !== 'login.php') : ?>
            <!-- Show login button only if not on login.php -->
            <a href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="logout-button">Login</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
