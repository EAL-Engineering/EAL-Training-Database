<?php

/**
 * Save Personnel Data
 *
 * This script handles saving personnel data to the database. It updates the
 * operators table with the provided information from a submitted form.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Include the database connection file
require_once "config.php";
require_once "auth.php";

checkLogin(1, $_SERVER['REQUEST_URI'] ?? '');

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX (Issue #4): CSRF Token Validation
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        error_log("CSRF token validation failed in personnel_save.php");
        http_response_code(403);
        die("Invalid security token. Please refresh the page and try again.");
    }

    $raw_seq_nmbr = $_POST['seq_nmbr'] ?? null;
    $seq_nmbr = ($raw_seq_nmbr !== null && is_numeric($raw_seq_nmbr)) ? intval($raw_seq_nmbr) : null;
    $name     = trim($_POST['name'] ?? '');
    $fname    = trim($_POST['fname'] ?? '');
    // Safely enforce the 64-character schema limit on $name
    $name     = mb_substr($name, 0, 64);
    $email    = trim($_POST['email'] ?? '');
    $altemail = trim($_POST['altemail'] ?? '');
    $phones   = trim($_POST['phones'] ?? '');
    $status   = trim($_POST['status'] ?? '');

    $is_senior_staff = isset($_POST['is_senior_staff']) ? 1 : 0;
    $is_eal_staff    = isset($_POST['is_eal_staff']) ? 1 : 0;

    // Validate raw input directly
    if (!$seq_nmbr || empty($name) || empty($fname) || empty($email) || empty($status)) {
        die("Missing required fields. <a href='index.php'>Go to Main Page</a>");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address. <a href='index.php'>Go to Main Page</a>");
    }

    if (!empty($altemail) && !filter_var($altemail, FILTER_VALIDATE_EMAIL)) {
        die("Invalid alternate email address. <a href='index.php'>Go to Main Page</a>");
    }

    // Senior staff is a subset of EAL staff
    if ($is_senior_staff) {
        $is_eal_staff = 1;
    }

    $office   = trim($_POST['office'] ?? '');
    $home     = trim($_POST['home'] ?? '');
    $comments = trim($_POST['comments'] ?? '');

    /**
     * Prepare the SQL statement to update the operator's information in the database.
     *
     * @var string $query SQL update query.
     */
    $query = "
        UPDATE operators
        SET 
            name = ?,
            fname = ?,
            email = ?,
            altemail = ?,
            phones = ?,
            status = ?,
            is_eal_staff = ?,
            is_senior_staff = ?,
            office = ?,
            home = ?,
            comments = ?
        WHERE seq_nmbr = ?
    ";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        error_log("Database prepare error in personnel_save.php: " . $mysqli->error);
        die("A database error occurred. <a href='index.php'>Go to Main Page</a>");
    }

    $stmt->bind_param(
        "ssssssiisssi",
        $name,
        $fname,
        $email,
        $altemail,
        $phones,
        $status,
        $is_eal_staff,
        $is_senior_staff,
        $office,
        $home,
        $comments,
        $seq_nmbr
    );

    // Execute the query
    if ($stmt->execute()) {
        // Redirect back to the main page or a success page
        header("Location: personnel_list.php?message=update_success");
        exit();
    } else {
        error_log("Database execution error in personnel_save.php: " . $stmt->error);
        die("Failed to update operator due to a database error. <a href='index.php'>Go to Main Page</a>");
    }
}
