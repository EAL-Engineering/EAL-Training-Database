<?php

/**
 * Page header for the Training Management System.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure auth.php functions are available if not already included
if (function_exists('getTimeUntilSessionExpires')) {
    $secondsRemaining = getTimeUntilSessionExpires();
} else {
    $secondsRemaining = 0;
}

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isLoggedIn = isset($_SESSION['user_id']);
?>
<div class="header">
    <div class="header-logo">
        <a href="index.php">
            <img src="EALlogoZM.svg" alt="EAL Logo" class="header-logo-img">
        </a>
    </div>
    <div class="header-info" id="header-info-container">
    <?php if ($isLoggedIn) : ?>
        <!-- Display user information and session countdown for logged-in users -->
        <span id="user-display">
            Logged in as: <?php echo htmlspecialchars($_SESSION['fname'] ?? 'User'); ?>
            <span class="divider">|</span>
        </span>
        <span id="session-status-text">Session expires in: <span id="countdown"></span></span>
        <a href="logout.php" id="session-button" class="logout-button">Logout</a>

        <script>
        (function() {
            let timeRemaining = <?php echo (int)$secondsRemaining; ?>;
            const countdownElem = document.getElementById('countdown');
            const sessionBtn = document.getElementById('session-button');
            const statusText = document.getElementById('session-status-text');
            const userDisplay = document.getElementById('user-display');

            function updateTimer() {
                if (timeRemaining <= 0) {
                    if (countdownElem) countdownElem.textContent = "00:00:00";
                    if (statusText) statusText.textContent = "Session expired";
                    if (userDisplay) userDisplay.style.display = "none"; // Hides username AND pipe together
                    if (sessionBtn) {
                        sessionBtn.textContent = "Login";
                        sessionBtn.href = "login.php?return=" + encodeURIComponent(window.location.pathname);
                    }
                    return;
                }

                let hours = Math.floor(timeRemaining / 3600);
                let minutes = Math.floor((timeRemaining % 3600) / 60);
                let seconds = timeRemaining % 60;

                let formatted = 
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');

                if (countdownElem) countdownElem.textContent = formatted;
                timeRemaining--;
            }

            updateTimer();
            setInterval(updateTimer, 1000);
        })();
        </script>
    <?php else : ?>
        <!-- Display welcome message and login button for guests -->
        <span>Session expired</span>
        <?php if ($currentScript !== 'login.php') : ?>
            <a href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? ''); ?>" class="logout-button">Login</a>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>
