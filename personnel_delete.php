<?php
/**
 * Delete a personnel entry from all related tables
 * 
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2020
 */

// Start the session
session_start();

// Include configuration and helper files
require_once "config.php";

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?return=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

/**
 * Remaining session time in seconds.
 *
 * @var int $timeUntilSessionExpires
 */
$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Check if the user is an existing trainer
/**
 * Check if the currently logged-in user is an existing trainer.
 *
 * @var bool $isTrainer True if the user is a trainer, false otherwise.
 */
$trainerCheckQuery = $mysqli->prepare(
    "
    SELECT 
        COUNT(*) 
    FROM 
        trainers 
    WHERE 
        seq_nmbr = ?"
);
$trainerCheckQuery->bind_param("i", $_SESSION['user_id']);
$trainerCheckQuery->execute();
$trainerCheckQuery->bind_result($isTrainer);
$trainerCheckQuery->fetch();
$trainerCheckQuery->close();

if (!$isTrainer) {
    die("Access denied: Only existing trainers can add new trainers.");
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$seq_nmbr = intval($_GET['id']);

// Begin transaction
mysqli_autocommit($conn, false);

$queries = [
    "DELETE FROM annualradsafety WHERE op_ptr = $seq_nmbr",
    "DELETE FROM optraining WHERE operator = $seq_nmbr",
    "DELETE FROM trainers WHERE optbl_ptr = $seq_nmbr",
    "DELETE FROM operators WHERE seq_nmbr = $seq_nmbr"
];

foreach ($queries as $query) {
    if (!mysqli_query($conn, $query)) {
        mysqli_rollback($conn);
        die("Error deleting record: " . mysqli_error($conn));
    }
}

mysqli_commit($conn);
mysqli_autocommit($conn, true);

header("Location: personnel_list.php?msg=deleted");
exit;
?>
