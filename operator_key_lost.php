<?php

/**
 * Mark Operator Key as Lost
 *
 * Changes a key's status to 'Lost'.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

require_once "config.php";
require_once "auth.php";

checkLogin(1, $_SERVER['REQUEST_URI'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
    die("Invalid CSRF token.");
}

$raw_redirect = $_POST['redirect'] ?? 'operator_keys.php';
$redirect = isSafeRedirect($raw_redirect) ? $raw_redirect : 'operator_keys.php';

$key_id = isset($_POST['id']) && is_numeric($_POST['id']) ? intval($_POST['id']) : null;
if (!$key_id) {
    header("Location: " . $redirect);
    exit();
}

$stmt = $mysqli->prepare("UPDATE operator_keys SET status = 'Lost' WHERE seq_nmbr = ? AND status = 'Active'");
if ($stmt) {
    $stmt->bind_param("i", $key_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: " . $redirect);
exit();
