<?php
/**
 * Return Operator Key
 *
 * Marks a key as returned.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

session_start();

require_once "config.php";
require_once "auth.php";

checkLogin(1, $_SERVER['REQUEST_URI']);

// Redirect target after operation
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'operator_keys.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . $redirect);
    exit();
}

$key_id = intval($_GET['id']);

// Verify the key exists and is active
$check_stmt = $mysqli->prepare(
    "SELECT ok.status, o.fname, ok.key_type, ok.serial_number
     FROM operator_keys ok
     JOIN operators o ON ok.operator_id = o.seq_nmbr
     WHERE ok.seq_nmbr = ?"
);
$check_stmt->bind_param("i", $key_id);
$check_stmt->execute();
$check_stmt->bind_result($status, $operator_name, $key_type, $serial_number);
$found = $check_stmt->fetch();
$check_stmt->close();

if (!$found) {
    header("Location: " . $redirect . "?error=not_found");
    exit();
}

if ($status !== 'Active') {
    header("Location: " . $redirect . "?error=already_returned");
    exit();
}

// Mark as returned
$returned_date = date('Y-m-d');
$update_stmt = $mysqli->prepare(
    "UPDATE operator_keys
     SET status = 'Returned', returned_date = ?
     WHERE seq_nmbr = ? AND status = 'Active'"
);
$update_stmt->bind_param("si", $returned_date, $key_id);
$update_stmt->execute();

if ($update_stmt->affected_rows > 0) {
    header("Location: " . $redirect . "?message=key_returned");
} else {
    header("Location: " . $redirect . "?error=update_failed");
}
$update_stmt->close();
exit();
