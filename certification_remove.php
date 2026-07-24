<?php
/**
 * Processes the removal or revocation of a certification record from optraining.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

session_start();

require_once "config.php";
require_once "auth.php";

// Enforce login requirement (Role >= 1)
checkLogin(1, $_SERVER['REQUEST_URI']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Protection
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        error_log("CSRF token validation failed in certification_remove.php");
        http_response_code(403);
        die("Invalid security token. Please refresh the page and try again.");
    }

    $optraining_id = isset($_POST['optraining_id']) ? intval($_POST['optraining_id']) : 0;
    $operator_id   = isset($_POST['operator_id'])   ? intval($_POST['operator_id'])   : 0;

    if ($optraining_id <= 0 || $operator_id <= 0) {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Invalid operator or certification record ID.'
        ];
        header("Location: certification_add.php?id=" . urlencode((string)$operator_id));
        exit();
    }

    // Option A: Hard Delete (Permanently delete record from database)
    $query = "DELETE FROM optraining WHERE seq_nmbr = ? AND operator = ?";

    // Option B: Soft Delete / Revoke (Uncomment to preserve audit/training history)
    // $query = "UPDATE optraining SET status = 'Revoked' WHERE seq_nmbr = ? AND operator = ?";

    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $optraining_id, $operator_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Certification record updated successfully.'
                ];
                $stmt->close();
                header("Location: certification_add.php?id=$operator_id&removed=1");
                exit();
            } else {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'No matching active certification record found to remove.'
                ];
            }
            $stmt->close();
        } else {
            error_log("Database execution failed in certification_remove.php: " . $stmt->error);
            $stmt->close();
        }
    } else {
        error_log("Database prepare failed in certification_remove.php: " . $mysqli->error);
    }

    // Fallthrough error redirect
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => 'A database error occurred while attempting to remove the certification.'
    ];
    header("Location: certification_add.php?id=$operator_id&error=1");
    exit();
} else {
    die("Invalid request method. <a href='personnel_list.php'>Go to Personnel List</a>");
}
