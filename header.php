<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Ensure the session is started
}

// Get the current script name to check if it's login.php
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<div class="header">
    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Logged in as: <?php echo htmlspecialchars($_SESSION['fname']); ?></span> |
        <span>Session expires in: <span id="countdown"></span></span>
        <a href="logout.php" class="logout-button">Logout</a>
    <?php else: ?>
        <span>Welcome to the OUAL Training Information Portal</span>
        <?php if ($currentScript !== 'login.php'): // Show login button only if not on login.php ?>
            <a href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="logout-button">Login</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
