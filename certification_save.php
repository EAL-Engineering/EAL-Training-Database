<?php
/**
 * Processes the addition of a certification to an operator in the database.
 *
 * This script validates the form input submitted via POST, retrieves the expiration period
 * for the selected certification, and adds a new record to the `optraining` table. On success,
 * it redirects back to the `certification_add.php` page with a success message. If an error occurs,
 * it redirects back with an error message.
 * 
 * PHP version 5.4+
 *
 * Requirements:
 * - Database connection via `config.php`.
 * - `POST` request containing the following required fields:
 *   - `operator_id` (int): The ID of the operator receiving the certification.
 *   - `cert_id` (int): The ID of the certification being assigned.
 *   - `completed_by` (int): The ID of the trainer completing the certification.
 *
 * Error Handling:
 * - If required fields are missing or invalid, the script terminates with an error message.
 * - If database queries fail, the script terminates with a database error message.
 *
 * Redirect Behavior:
 * - On success: Redirects to `certification_add.php` with `success=1`.
 * - On failure: Redirects to `certification_add.php` with `error=1`.
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Include the database connection file
require_once "config.php";

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (!isset($_POST['operator_id'])) {
        die("Error: Operator ID is missing. <a href='index.php'>Go to Main Page</a>");
    } elseif (!is_numeric($_POST['operator_id'])) {
        die("Error: Operator ID must be numeric. <a href='index.php'>Go to Main Page</a>");
    }
    
    if (!isset($_POST['cert_id'])) {
        die("Error: Certification ID is missing. <a href='index.php'>Go to Main Page</a>");
    } elseif (!is_numeric($_POST['cert_id'])) {
        die("Error: Certification ID must be numeric. <a href='index.php'>Go to Main Page</a>");
    }
    
    if (!isset($_POST['completed_by'])) {
        die("Error: Trainer ID (Completed By) is missing. <a href='index.php'>Go to Main Page</a>");
    } elseif (!is_numeric($_POST['completed_by'])) {
        die("Error: Trainer ID (Completed By) must be numeric. <a href='index.php'>Go to Main Page</a>");
    }

    $operator_id = intval($_POST['operator_id']);
    $cert_id = intval($_POST['cert_id']);
    $completed_by = intval($_POST['completed_by']);

    // Default values for status, entered date, and expiration
    $status = 'Active';
    $entered = date('Y-m-d H:i:s');
    
    // Fetch expiration period for the selected certification
    $query = "SELECT exp_months FROM certifications WHERE seq_nmbr = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
    }
    $stmt->bind_param("i", $cert_id);
    $stmt->execute();
    $stmt->bind_result($exp_months);
    $stmt->fetch();
    $stmt->close();

    // Calculate expiration date if applicable
    $expires = null;
    if ($exp_months && is_numeric($exp_months)) {
        $expires = date('Y-m-d', strtotime("+$exp_months months"));
    }

    // Insert the new certification into the optraining table
    $query = "INSERT INTO optraining (operator, certification, trainer, status, entered, expires)
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
    }
    $stmt->bind_param("iiisss", $operator_id, $cert_id, $completed_by, $status, $entered, $expires);

    if ($stmt->execute()) {
        // Redirect back to the certification_add.php page with a success message
        header("Location: certification_add.php?id=$operator_id&success=1");
        exit;
    } else {
        // Redirect back with an error message
        header("Location: certification_add.php?id=$operator_id&error=1");
        exit;
    }
} else {
    die("Invalid request method. <a href='index.php'>Go to Main Page</a>");
}
?>
