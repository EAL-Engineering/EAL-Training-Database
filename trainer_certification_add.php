<?php
// Include the database connection file
include_once("config.php");

// Start session to store success/error messages
session_start();

// Check if POST data is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trainer_id'], $_POST['cert_id'])) {
    $trainer_id = intval($_POST['trainer_id']);
    $cert_id = intval($_POST['cert_id']);

    // Check if trainer_id and cert_id are valid
    if ($trainer_id > 0 && $cert_id > 0) {
        // Add the certification to the can_certify table
        $query = "INSERT INTO can_certify (trainer_ptr, cert_ptr) VALUES (?, ?)";
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
