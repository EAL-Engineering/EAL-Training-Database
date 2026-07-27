<?php

/**
 * Processes the addition of a certification to an operator in the database.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        error_log("CSRF token validation failed in certification_save.php");
        http_response_code(403);
        die("Invalid security token. Please refresh the page and try again.");
    }

    $raw_operator_id = $_POST['operator_id'] ?? null;
    if ($raw_operator_id === null || !is_numeric($raw_operator_id)) {
        die("Error: Operator ID is missing or invalid. <a href='index.php'>Go to Main Page</a>");
    }

    $raw_cert_id = $_POST['cert_id'] ?? null;
    if ($raw_cert_id === null || !is_numeric($raw_cert_id)) {
        die("Error: Certification ID is missing or invalid. <a href='index.php'>Go to Main Page</a>");
    }

    $raw_completed_by = $_POST['completed_by'] ?? null;
    if ($raw_completed_by === null || !is_numeric($raw_completed_by)) {
        die("Error: Trainer ID (Completed By) is missing or invalid. <a href='index.php'>Go to Main Page</a>");
    }

    $operator_id = intval($raw_operator_id);
    $cert_id = intval($raw_cert_id);
    $completed_by = intval($raw_completed_by);

    // FIX (Issue #22): Check if user already holds an active certification of this type
    $check_query = "SELECT COUNT(*) AS total "
        . "FROM optraining "
        . "WHERE operator = ? AND certification = ? AND status = 'Active'";
    $check_stmt = $mysqli->prepare($check_query);
    if ($check_stmt) {
        $check_stmt->bind_param("ii", $operator_id, $cert_id);
        $check_stmt->execute();
        $check_row = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($check_row && intval($check_row['total']) > 0) {
            header("Location: certification_add.php?id=$operator_id&error=duplicate");
            exit;
        }
    }

    $status = 'Active';
    $entered = date('Y-m-d H:i:s');

    // Fetch expiration period
    $query = "SELECT exp_months FROM certifications WHERE seq_nmbr = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
    }
    $stmt->bind_param("i", $cert_id);
    $stmt->execute();
    $cert_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $exp_months = $cert_row['exp_months'] ?? null;

    $expires = null;
    if ($exp_months && is_numeric($exp_months)) {
        $expires = date('Y-m-d', strtotime("+$exp_months months"));
    }

    $query = "INSERT INTO optraining (operator, certification, trainer, status, entered, expires)
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
    }
    $stmt->bind_param("iiisss", $operator_id, $cert_id, $completed_by, $status, $entered, $expires);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: certification_add.php?id=$operator_id&success=1");
        exit;
    } else {
        $stmt->close();
        header("Location: certification_add.php?id=$operator_id&error=1");
        exit;
    }
} else {
    die("Invalid request method. <a href='index.php'>Go to Main Page</a>");
}
