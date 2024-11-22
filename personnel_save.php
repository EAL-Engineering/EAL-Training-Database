<?php
// Include the database connection file
include_once("config.php");

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
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
        echo "<pre>";
print_r($_POST);
echo "</pre>";
die();
        die("Missing required fields.");
    }

    // Check email validity
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }
    if (!empty($altemail) && !filter_var($altemail, FILTER_VALIDATE_EMAIL)) {
        die("Invalid alternate email address.");
    }

    // Prepare the SQL statement to update the operator
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
        die("Database error: " . $mysqli->error); // Debugging helper
    }

    // Bind the parameters to the query
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
        die("Failed to update operator: " . $stmt->error);
    }
} else {
    die("Invalid request method.");
}
