<?php

/**
 * Authentication and Session Utility Functions
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

/**
 * Validates a CSRF token from a form POST against the session token.
 *
 * @param string|null $token Submitted CSRF token.
 *
 * @return bool True if valid, false otherwise.
 */
function verifyCSRFToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * Generates or retrieves the current CSRF token.
 *
 * @return string CSRF token.
 */
function getCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Rotates the CSRF token on session elevation or login.
 *
 * @return void
 */
function regenerateCSRFToken(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Checks if the user is logged in and meets the minimum role requirement.
 *
 * @param int    $requiredRole   Minimum role_id required (e.g., 1 for trainer, 2 for admin).
 * @param string $currentUri     URI to return to after login.
 *
 * @return void
 */
function checkLogin(int $requiredRole = 1, string $currentUri = ''): void
{
    if (!isset($_SESSION['user_id'])) {
        $redirect = 'login.php';
        if (!empty($currentUri)) {
            $redirect .= '?return=' . urlencode($currentUri);
        }
        header("Location: $redirect");
        exit;
    }

    $userRole = $_SESSION['role_id'] ?? 0;
    if ($userRole < $requiredRole) {
        header("Location: index.php?error=unauthorized");
        exit;
    }
}

/**
 * Gets the current user's role ID.
 *
 * @return int Role ID or 0 if guest.
 */
function getUserRole(): int
{
    return $_SESSION['role_id'] ?? 0;
}

/**
 * Calculates remaining session time in seconds.
 *
 * @return int Seconds until session expiration.
 */
function getTimeUntilSessionExpires(): int
{
    $maxLifetime = ini_get('session.gc_maxlifetime') ?: 1440;
    $lastActivity = $_SESSION['last_activity'] ?? time();
    $elapsed = time() - $lastActivity;
    return max(0, (int)$maxLifetime - $elapsed);
}

/**
 * Validates whether a redirect URL is safe (internal/relative).
 *
 * @param string $url URL candidate.
 *
 * @return bool True if safe.
 */
function isSafeRedirect(string $url): bool
{
    if (empty($url)) {
        return false;
    }
    // Prevent open redirects (must start with / or be a relative page script)
    return str_starts_with($url, '/') || !preg_match('#^https?://#i', $url);
}

/**
 * Check if a trainer is authorized to certify a specific certification.
 *
 * @param int $trainerId       ID of the trainer (trainers.seq_nmbr)
 * @param int $certificationId Certification ID
 *
 * @return bool True if the trainer is authorized, false otherwise.
 */
function checkCertification(int $trainerId, int $certificationId): bool
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT optbl_ptr FROM trainers WHERE seq_nmbr = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    $trainer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$trainer || !isset($trainer['optbl_ptr']) || (int)$trainer['optbl_ptr'] === -1) {
        return false;
    }

    $operatorId = $trainer['optbl_ptr'];

    $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM can_certify WHERE trainer_ptr = ? AND cert_ptr = ?");
    $stmt->bind_param("ii", $operatorId, $certificationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row && intval($row['total']) > 0;
}
