<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Ensure the session is started
}
?>
<div class="header">
    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Logged in as: <?php echo htmlspecialchars($_SESSION['fname']); ?></span> |
        <span>Session expires in: <span id="countdown"></span></span>
        <a href="logout.php" class="logout-button">Logout</a>
    <?php else: ?>
        <span>Welcome to the OUAL Training Information Portal</span>
        <a href="login.php" class="logout-button">Login</a>
    <?php endif; ?>
</div>
