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

$redirect = $_GET['redirect'] ?? 'operator_keys.php';

$key_id_param = $_GET['id'] ?? null;
if ($key_id_param === null || !is_numeric($key_id_param)) {
    header("Location: " . $redirect);
    exit();
}

$key_id = intval($key_id_param);

$stmt = $mysqli->prepare(
    "UPDATE operator_keys SET status = 'Lost' WHERE seq_nmbr = ? AND status = 'Active'"
);
$stmt->bind_param("i", $key_id);
$stmt->execute();
$stmt->close();

header("Location: " . $redirect);
exit();
