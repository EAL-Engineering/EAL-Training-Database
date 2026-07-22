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

// Check if user is logged in (session has user_id)
$isLoggedIn = isset($_SESSION['user_id']);
?>
<div class="header">
    <div class="header-logo">
        <a href="index.php">
            <img src="EALlogoZM.svg" alt="EAL Logo" class="header-logo-img">
        </a>
    </div>
    <div class="header-info">
        <?php if ($isLoggedIn) : ?>
            <!-- Display user information and session countdown for logged-in users -->

            <span>Logged in as: <?php echo htmlspecialchars($_SESSION['fname']); ?></span>
            <span>|</span>
            <span>Session expires in: <span id="countdown"></span></span>
            <a href="logout.php" class="logout-button">Logout</a>
        <?php else: ?>
            <!-- Display welcome message and login button for guests -->
            <span>Session expired</span>
            <?php if ($currentScript !== 'login.php') : ?>
                <a href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="logout-button">Login</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>