<?php

/**
 * Register Radiation Safety Training
 *
 * This script handles the registration of radiation safety training for operators.
 * It provides an interface for authorized trainers to select operators and record
 * the training date in the database.
 *
 * PHP version 8.0+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

require_once "config.php";
require_once "auth.php";

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

/**
 * Encoded URL string of the current page for safe use in GET parameters.
 *
 * @var string $currentUrl
 */
$currentUrl = urlencode($_SERVER['REQUEST_URI'] ?? '');

checkLogin(1, $_SERVER['REQUEST_URI'] ?? '');

$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Check authorization: user must hold certification #18 to record this training
$user_id = $_SESSION['user_id'] ?? 0;
$authorizedTrainer = checkCertification($user_id, 18);

if (!$authorizedTrainer) {
    header("Location: login.php?return=$currentUrl");
    exit();
}

/**
 * Message to be displayed after form submission.
 *
 * @var string $message
 */
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX (Issue #4): Guard clause for CSRF validation
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $message = "Invalid security token. Please refresh the page and try again.";
    } else {
        $dateOfTraining = trim($_POST['date_of_training'] ?? '');
        $selectedOperators = is_array($_POST['operators'] ?? null) ? $_POST['operators'] : [];

        if (!empty($dateOfTraining) && !empty($selectedOperators)) {
            $date = date('Y-m-d', strtotime($dateOfTraining));
            $successCount = 0;

            foreach ($selectedOperators as $operator) {
                $operator = (int)$operator;
                $stmt = $mysqli->prepare(
                    "INSERT INTO optraining (operator, certification, trainer, entered) 
                    VALUES (?, 18, ?, ?) 
                    ON DUPLICATE KEY UPDATE entered = VALUES(entered), trainer = VALUES(trainer)"
                );
                if ($stmt) {
                    // FIX (Issue #21): Correct type binding string from "is" to "iis"
                    $stmt->bind_param("iis", $operator, $user_id, $date);
                    if ($stmt->execute()) {
                        $successCount++;
                    }
                    $stmt->close();
                }
            }

            $message = $successCount > 0
                ? "Successfully registered $successCount operators."
                : "No operators were registered.";
        } else {
            $message = "Please select at least one operator and enter a valid date.";
        }
    }
}

// Fetch operators eligible for training and their most recent training date
$operatorsResult = $mysqli->query(
    "SELECT o.seq_nmbr AS id, o.name AS name, MAX(t.entered) AS last_training 
     FROM operators o
     LEFT JOIN optraining t ON o.seq_nmbr = t.operator AND t.certification = 18
     WHERE o.status = 'Active'
     GROUP BY o.seq_nmbr"
);

if (!$operatorsResult) {
    die("Query failed: " . $mysqli->error);
}

$operators = [];
while ($row = $operatorsResult->fetch_assoc()) {
    $operators[] = $row;
}

/**
 * Check if a trainer is authorized to certify a specific certification.
 *
 * @param int $trainerId       ID of the trainer (trainers.seq_nmbr)
 * @param int $certificationId Certification ID
 *
 * @return bool True if the trainer is authorized, false otherwise.
 */
function checkCertification($trainerId, $certificationId)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT optbl_ptr FROM trainers WHERE seq_nmbr = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    $trainer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$trainer || !isset($trainer['optbl_ptr']) || (int)$trainer['optbl_ptr'] === -1) {
        return false;
    }

    $operatorId = $trainer['optbl_ptr'];

    $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM can_certify WHERE trainer_ptr = ? AND cert_ptr = ?");
    $stmt->bind_param("ii", $operatorId, $certificationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row && intval($row['total']) > 0;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Radiation Safety Training</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"
            integrity="sha384-wsqsSADZR1YRBEZ4/kKHNSmU+aX8ojbnKUMN4RyD3jDkxw5mHtoe2z/T/n4l56U/"
            crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"
            integrity="sha384-cDXquhvkdBprgcpTQsrhfhxXRN4wfwmWauQ3wR5ZTyYtGrET2jd68wvJ1LlDqlQG"
            crossorigin="anonymous"></script>
    <script src="common.js" defer></script>
</head>
<body>
    <?php require 'header.php'; ?>

    <h1>Record Radiation Safety Training</h1>

    <div class="form-container">
        <?php if (!empty($message)) : ?>
            <p class="message" style="font-weight: bold;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div>
                <label for="date_of_training">Date of Training:</label>
                <input type="date"
                    id="date_of_training"
                    name="date_of_training"
                    value="<?php echo date('Y-m-d'); ?>"
                    required>
            </div>

            <div>
                <table id="personnel" class="display">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Operator Name</th>
                            <th>Last Training Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operators as $operator) : ?>
                            <tr>
                                <td>
                                    <input 
                                        type="checkbox" 
                                        id="operator_<?php echo htmlspecialchars($operator['id']); ?>" 
                                        name="operators[]" 
                                        value="<?php echo htmlspecialchars($operator['id']); ?>"
                                    >
                                </td>
                                <td>
                                    <label for="operator_<?php echo htmlspecialchars($operator['id']); ?>">
                                        <?php echo htmlspecialchars($operator['name']); ?>
                                    </label>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($operator['last_training'] ?: 'Never'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit">Register Training</button>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
        </form>
    </div>
    <script>
        new DataTable('#personnel', {
            scrollX: true,
            pageLength: 50,
            lengthMenu: [10, 15, 25, 50, 75, 100]
        });
    </script>        
</body>
</html>
