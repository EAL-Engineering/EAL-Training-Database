<?php
/**
 * Add Trainer Certification
 *
 * This script processes the addition of a certification for a trainer. 
 * It validates the input data, performs the database insertion, and stores
 * success or error messages in the session.
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
require_once "auth.php";

/**
 * Check if the user is logged in and authorized to edit personnel details.
 * Redirects unauthorized users to the login page.
 */
checkLogin(1, 'REQUEST_URI')

// Check if POST data is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trainer_id'], $_POST['cert_id'])) {
    /**
     * Trainer ID from the POST request.
     *
     * @var int $trainer_id
     */
    $trainer_id = intval($_POST['trainer_id']);

    /**
     * Certification ID from the POST request.
     *
     * @var int $cert_id
     */
    $cert_id = intval($_POST['cert_id']);

    // Check if trainer_id and cert_id are valid
    if ($trainer_id > 0 && $cert_id > 0) {
        /**
         * SQL query to insert the trainer certification.
         *
         * @var string $query
         */
        $query = "INSERT INTO can_certify (trainer_ptr, cert_ptr) VALUES (?, ?)";

        /**
         * Prepared statement for the database query.
         *
         * @var mysqli_stmt|false $stmt
         */
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ii", $trainer_id, $cert_id);
            if ($stmt->execute()) {
                // Success: Store success message in session
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Certification added successfully.'
                ];
            } else {
                // Error: Store error message in session
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Failed to add certification: ' . $stmt->error
                ];
            }
            $stmt->close();
        } else {
            // Error: Store error message in session
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Database error: ' . $mysqli->error
            ];
        }
    } else {
        // Error: Invalid input
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Invalid trainer or certification ID.'
        ];
    }
} else {
    // Error: Missing POST data
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => 'Invalid request. Trainer and certification IDs are required.'
    ];
}

// Redirect back to the trainer_edit.php page
header("Location: trainer_edit.php?id=" . urlencode($trainer_id));
exit;
