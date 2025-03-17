<?php
/**
 * Remove Certification for Trainer
 *
 * Removes a certification from a trainer in the "can_certify" table.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Include the database connection file
require_once "config.php";

// Start session to store success or error messages
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Validate and retrieve the required POST parameters.
 *
 * @throws Exception If required parameters are missing or invalid.
 *  Trainer ID and Certification ID must be provided and numeric.
 *
 * @return array Associative array with sanitized 'trainer_id' and 'cert_id'.
 */
function validatePostParameters()
{
    if (!isset($_POST['trainer_id']) || !isset($_POST['cert_id']) 
        || !is_numeric($_POST['trainer_id']) || !is_numeric($_POST['cert_id'])
    ) {
        throw new Exception("Invalid request. Missing trainer or certification information.");
    }

    return [
        'trainer_id' => intval($_POST['trainer_id']),
        'cert_id' => intval($_POST['cert_id']),
    ];
}

try {
    // Validate input
    $params = validatePostParameters();
    $trainer_id = $params['trainer_id'];
    $cert_id = $params['cert_id'];

    // Perform the removal operation
    $query = "DELETE FROM can_certify WHERE trainer_ptr = ? AND cert_ptr = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
    }

    $stmt->bind_param("ii", $trainer_id, $cert_id);
    $stmt->execute();

    // Check if the query was successful
    if ($stmt->affected_rows > 0) {
        // Store success message in session
        $_SESSION['message'] = [
            'type' => 'success', 
            'text' => 'Certification removed successfully.'
        ];
    } else {
        // Store error message in session
        $_SESSION['message'] = [
            'type' => 'error', 
            'text' => 'Failed to remove certification.'
        ];
    }

    $stmt->close();
} catch (Exception $e) {
    // Handle errors and store error message in session
    $_SESSION['message'] = [
        'type' => 'error', 
        'text' => $e->getMessage()
    ];
}

// Redirect back to the trainer_edit.php page
header("Location: trainer_edit.php?id=$trainer_id");
exit();
