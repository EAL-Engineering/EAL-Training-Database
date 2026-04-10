<?php
/**
 * Save Personnel Data
 * 
 * This script handles saving personnel data to the database. It updates the
 * operators table with the provided information from a submitted form.
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

// Include the database connection file
require_once "config.php";
require_once "auth.php";

checkLogin(1, $_SERVER['REQUEST_URI']);

// // Enable error reporting for debugging (remove in production)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die("Invalid CSRF token. <a href='index.php'>Go to Main Page</a>");
    }
    /**
     * Sanitize and validate inputs from the form submission.
     *
     * @var int    $seq_nmbr   Sequence number of the operator (required)
     * @var string $name       Name of the operator (required)
     * @var string $fname      First name of the operator (required)
     * @var string $email      Email address of the operator (required)
     * @var string $altemail   Alternate email address of the operator (optional)
     * @var string $phones     Phone numbers of the operator (optional)
     * @var string $status     Status of the operator (required)
     * @var string $office     Office address of the operator (optional)
     * @var string $home       Home address of the operator (optional)
     * @var string $comments   Additional comments about the operator (optional)
     */
    $seq_nmbr = isset($_POST['seq_nmbr']) ? intval($_POST['seq_nmbr']) : null;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $fname = isset($_POST['fname']) ? trim($_POST['fname']) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $altemail = isset($_POST['altemail']) ? filter_var(trim($_POST['altemail']), FILTER_SANITIZE_EMAIL) : '';
    $phones = isset($_POST['phones']) ? trim($_POST['phones']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $office = isset($_POST['office']) ? trim($_POST['office']) : '';
    $home = isset($_POST['home']) ? trim($_POST['home']) : '';
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

    // Validate required fields
    if (!$seq_nmbr || empty($name) || empty($fname) || empty($email) || empty($status)) {
        die("Missing required fields. <a href='index.php'>Go to Main Page</a>");
    }

    // Check email validity
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address. <a href='index.php'>Go to Main Page</a>");
    }
    if (!empty($altemail) && !filter_var($altemail, FILTER_VALIDATE_EMAIL)) {
        die("Invalid alternate email address. <a href='index.php'>Go to Main Page</a>");
    }

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
            office = ?, 
            home = ?, 
            comments = ?
        WHERE seq_nmbr = ?
    ";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
    }

    /**
     * Bind the parameters to the prepared SQL statement.
     *
     * @param string $name      Name of the operator.
     * @param string $fname     First name of the operator.
     * @param string $email     Email address of the operator.
     * @param string $altemail  Alternate email address of the operator.
     * @param string $phones    Phone numbers of the operator.
     * @param string $status    Status of the operator.
     * @param string $office    Office address of the operator.
     * @param string $home      Home address of the operator.
     * @param string $comments  Additional comments about the operator.
     * @param int    $seq_nmbr  Sequence number of the operator.
     */
    $stmt->bind_param(
        "sssssssssi",
        $name,
        $fname,
        $email,
        $altemail,
        $phones,
        $status,
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
        die("Failed to update operator: " . $stmt->error . " <a href='index.php'>Go to Main Page</a>");
    }
} else {
    die("Invalid request method. <a href='index.php'>Go to Main Page</a>");
}
