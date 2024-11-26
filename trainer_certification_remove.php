<?php
// Include the database connection file
include_once("config.php");

// Start session to store success or error messages
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if 'trainer_id' and 'cert_id' are provided in the POST request
if (!isset($_POST['trainer_id']) || !isset($_POST['cert_id']) || !is_numeric($_POST['trainer_id']) || !is_numeric($_POST['cert_id'])) {
    die("Invalid request. Missing trainer or certification information.");
}

$trainer_id = intval($_POST['trainer_id']); // Sanitize the trainer ID
$cert_id = intval($_POST['cert_id']); // Sanitize the certification ID

// Perform the removal operation
$query = "DELETE FROM can_certify WHERE trainer_ptr = ? AND cert_ptr = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("ii", $trainer_id, $cert_id);
$stmt->execute();

// Check if the query was successful
if ($stmt->affected_rows > 0) {
    // Store success message in session and redirect back to trainer_edit.php
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Certification removed successfully.'];
} else {
    // Store error message in session and redirect back to trainer_edit.php
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to remove certification.'];
}

$stmt->close();

// Redirect back to the trainer_edit.php page
header("Location: trainer_edit.php?id=$trainer_id");
exit();
