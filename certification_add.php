<?php
/**
 * Certification Add Script
 * 
 * This script provides functionality for adding certifications to a specific
 * operator. It retrieves existing certifications and trainers for the operator, 
 * displays them in a form, and allows users to add new certifications.
 * 
 * PHP version 5.4+
 * 
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Start the session
session_start();

// Include the database connection file
require_once "config.php";
require_once "auth.php"; // Ensure this line is included

// Check if the user is logged in and authorized to edit personnel details
checkLogin(1);

$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if 'id' is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request. No operator ID provided. <a href='index.php'>Go to Main Page</a>");
}

$operator_id = intval($_GET['id']); // Sanitize the ID

// Fetch operator details
$query = "SELECT fname FROM operators WHERE seq_nmbr = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("i", $operator_id);
$stmt->execute();
$stmt->bind_result($fname);
if (!$stmt->fetch()) {
    die("Operator not found.");
}
$stmt->close();

// Fetch operator's existing certifications
$query = "SELECT 
	c.certification, 
	ot.status, 
	ot.entered, 
	ot.expires, 
	o.fname AS completed_by_name 
FROM 
	optraining ot 
	JOIN certifications c ON ot.certification = c.seq_nmbr 
	LEFT JOIN operators o ON ot.trainer = o.seq_nmbr 
WHERE 
	ot.operator = ?
";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("i", $operator_id);
$stmt->execute();
$stmt->bind_result(
    $cert_name, $status, $date_entered, $expiration_date, $completed_by_name
);

$certifications = [];
while ($stmt->fetch()) {
    $certifications[] = [
        'cert_name' => $cert_name,
        'status' => $status,
        'date_entered' => $date_entered,
        'expiration_date' => $expiration_date,
        'completed_by_name' => $completed_by_name
    ];
}
$stmt->close();

// Fetch certifications not yet completed by the operator and their trainers
$query = "
SELECT 
    c.seq_nmbr AS cert_id, 
    c.certification AS cert_name, 
    GROUP_CONCAT(
        CONCAT(o.fname, ' ', o.seq_nmbr)
    ) AS trainers 
FROM 
    certifications c 
    LEFT JOIN can_certify cc ON c.seq_nmbr = cc.cert_ptr 
    LEFT JOIN operators o ON cc.trainer_ptr = o.seq_nmbr 
WHERE 
    o.status = 'Active'
    AND c.seq_nmbr NOT IN (
        SELECT 
            ot.certification 
        FROM 
            optraining ot 
        WHERE 
            ot.operator = ?
    )
    AND (
        (SELECT COUNT(*) FROM optraining WHERE operator = ? AND certification = 1) = 0
        OR (c.seq_nmbr = 2 AND (SELECT COUNT(*) FROM optraining WHERE operator = ? AND certification = 1) > 0)
        OR (c.seq_nmbr = 3 AND (SELECT COUNT(*) FROM optraining WHERE operator = ? AND certification = 2) > 0)
        OR (c.seq_nmbr NOT IN (1, 2, 3) AND (SELECT COUNT(*) FROM optraining WHERE operator = ? AND certification = 3) > 0)
    )
GROUP BY 
    c.seq_nmbr
";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error . " <a href='index.php'>Go to Main Page</a>");
}
$stmt->bind_param("iiiii", $operator_id, $operator_id, $operator_id, $operator_id, $operator_id);
$stmt->execute();
$stmt->bind_result($cert_id, $cert_name, $trainers);

$available_certifications = [];
while ($stmt->fetch()) {
    $available_certifications[] = [
        'cert_id' => $cert_id,
        'cert_name' => $cert_name,
        'trainers' => $trainers
    ];
}
$stmt->close();

// Build the certifications_with_trainers mapping
$certifications_with_trainers = [];
foreach ($available_certifications as $cert) {
    $certifications_with_trainers[$cert['cert_id']] = [
        'cert_name' => $cert['cert_name'],
        'trainers' => []
    ];

    $trainer_list = explode(",", $cert['trainers']); // Split trainer data by comma
    foreach ($trainer_list as $trainer_data) {
        $trainer_data = trim($trainer_data); // Remove extra whitespace

        if (preg_match('/^(.*?) (\d+)$/', $trainer_data, $matches)) {
            $trainer_fname = $matches[1]; // Trainer name
            $trainer_id = $matches[2];    // Trainer ID

            // Add structured trainer data to the array
            $certifications_with_trainers[$cert['cert_id']]['trainers'][] = [
                'trainer_id' => $trainer_id,
                'trainer_fname' => $trainer_fname
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Certification</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="common.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize the countdown with the session expiration time from PHP
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
        });
    </script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div>
        <div class="back-button-container">
            <a href="personnel_list.php">To Personnel List</a>
            <a href="personnel_edit.php?id=<?php echo htmlspecialchars($operator_id); ?>" class="back-button">
                Back to Edit Operator
            </a>
            <a href="index.php">To main page</a>
        </div>
    </div>
    <div class="certification-container">
        <h1>Add Certification for <?php echo htmlspecialchars($fname); ?></h1>

        <!-- Display Existing Certifications -->
        <h2>Existing Certifications</h2>
        <?php if (!empty($certifications)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Certification Name</th>
                        <th>Status</th>
                        <th>Date Entered</th>
                        <th>Expiration Date</th>
                        <th>Completed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certifications as $cert): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cert['cert_name']); ?></td>
                            <td><?php echo htmlspecialchars($cert['status']); ?></td>
                            <td><?php echo htmlspecialchars($cert['date_entered']); ?></td>
                            <td><?php echo htmlspecialchars($cert['expiration_date']); ?></td>
                            <td><?php echo htmlspecialchars($cert['completed_by_name'] ?: 'Unknown'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No certifications found for this operator.</p>
        <?php endif; ?>

        <!-- Add New Certification -->
        <h2>Add New Certification</h2>
        <form method="post" action="certification_save.php">
            <!-- Certification Dropdown -->
            <div class="form-row">
                <label for="cert_id">Certification:</label>
                <select name="cert_id" id="cert_id" required onchange="updateTrainers()">
                    <option value="">Select a Certification</option>
                    <?php foreach ($certifications_with_trainers as $cert_id => $data): ?>
                        <option value="<?php echo htmlspecialchars($cert_id); ?>">
                            <?php echo htmlspecialchars($data['cert_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Trainers Dropdown -->
            <div class="form-row">
                <label for="completed_by">Completed By:</label>
                <select name="completed_by" id="completed_by" required disabled>
                    <option value="">Select a Trainer</option>
                </select>
            </div>
            <input type="hidden" name="operator_id" value="<?php echo htmlspecialchars($operator_id); ?>">

            <div class="button-container">
                <!-- Add Certification Button -->
                <button type="submit">Add Certification</button>
                <!-- Back to Edit Operator Button -->
                <a href="personnel_edit.php?id=<?php echo htmlspecialchars($operator_id); ?>" class="back-button">Back to Edit Operator</a>
            </div>
        </form>
    </div>
    <script>
        // Define global object with certification-trainer mappings
        const certificationsWithTrainers = <?php echo json_encode($certifications_with_trainers); ?>;

        // Function to update the trainers' dropdown based on selected certification
        function updateTrainers() {
            const certSelect = document.getElementById('cert_id'); // Certification dropdown
            const trainerSelect = document.getElementById('completed_by'); // Trainers dropdown
            const certId = certSelect.value; // Get selected certification ID

            trainerSelect.innerHTML = ''; // Clear existing trainer options

            if (certificationsWithTrainers[certId]) {
                // Fetch trainers for the selected certification
                const trainerList = certificationsWithTrainers[certId].trainers;

                if (trainerList.length > 0) {
                    trainerList.forEach(trainer => {
                        const option = document.createElement('option');
                        option.value = trainer.trainer_id;
                        option.textContent = trainer.trainer_fname;
                        trainerSelect.appendChild(option);
                    });
                    trainerSelect.disabled = false; // Enable dropdown if trainers are available
                } else {
                    trainerSelect.disabled = true; // Disable dropdown if no trainers
                    const noTrainersOption = document.createElement('option');
                    noTrainersOption.textContent = 'No trainers available';
                    noTrainersOption.disabled = true;
                    trainerSelect.appendChild(noTrainersOption);
                }
            } else {
                trainerSelect.disabled = true; // Disable dropdown if certification is invalid
                const defaultOption = document.createElement('option');
                defaultOption.textContent = 'Select a certification first';
                defaultOption.disabled = true;
                trainerSelect.appendChild(defaultOption);
            }
        }


        // Attach event listener to the certification dropdown
        document.getElementById('cert_id').addEventListener('change', () => {
            const trainerSelect = document.getElementById('completed_by');
            trainerSelect.disabled = false; // Enable trainer dropdown
            updateTrainers(); // Call updateTrainers when certification changes
        });
    </script>
</body>
</html>