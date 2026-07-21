<?php
/**
 * Add Operator Key
 *
 * Assigns a new key to an operator.
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

require_once "config.php";
require_once "auth.php";

checkLogin(1, $_SERVER['REQUEST_URI']);

$timeUntilSessionExpires = getTimeUntilSessionExpires();
$entered_by = isset($_SESSION['fname']) ? $_SESSION['fname'] : 'Unknown';

$error_message = "";
$success_message = "";

// Pre-fill operator if passed via URL
$prefill_operator_id = isset($_GET['operator_id']) ? intval($_GET['operator_id']) : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        $operator_id = isset($_POST['operator_id']) ? intval($_POST['operator_id']) : 0;
        $key_type = isset($_POST['key_type']) ? trim($_POST['key_type']) : '';
        $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : '';
        $issued_date = isset($_POST['issued_date']) && $_POST['issued_date'] !== '' ? $_POST['issued_date'] : null;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        // Validate
        if ($operator_id <= 0) {
            $error_message = "Please select an operator.";
        } elseif (empty($key_type)) {
            $error_message = "Key type is required.";
        } elseif (empty($serial_number)) {
            $error_message = "Serial number is required.";
        } else {
            // Check if this key is already active for another operator
            $check_stmt = $mysqli->prepare(
                "SELECT ok.seq_nmbr, o.fname
                 FROM operator_keys ok
                 JOIN operators o ON ok.operator_id = o.seq_nmbr
                 WHERE ok.key_type = ? AND ok.serial_number = ? AND ok.status = 'Active'"
            );
            $check_stmt->bind_param("ss", $key_type, $serial_number);
            $check_stmt->execute();
            $check_stmt->bind_result($existing_key_id, $existing_operator_name);
            $has_existing = $check_stmt->fetch();
            $check_stmt->close();

            if ($has_existing) {
                $error_message = "This key is already active and assigned to " . htmlspecialchars($existing_operator_name) .
                    ". Return it first before reassigning.";
            } else {
                $stmt = $mysqli->prepare(
                    "INSERT INTO operator_keys
                     (operator_id, key_type, serial_number, status, issued_date, notes, entered_by)
                     VALUES (?, ?, ?, 'Active', ?, ?, ?)"
                );
                if ($stmt) {
                    $stmt->bind_param("isssss", $operator_id, $key_type, $serial_number, $issued_date, $notes, $entered_by);
                    if ($stmt->execute()) {
                        $success_message = "Key assigned successfully.";
                        // Clear form for next entry
                        $prefill_operator_id = $operator_id; // Keep same operator for batch entry
                    } else {
                        $error_message = "Database error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Database error: " . $mysqli->error;
                }
            }
        }
    }
}

// Fetch active operators for dropdown
$operators_result = $mysqli->query("SELECT seq_nmbr, fname FROM operators WHERE status = 'Active' ORDER BY fname");
$operators = [];
while ($row = $operators_result->fetch_assoc()) {
    $operators[] = $row;
}

// Get existing key types for datalist
$key_types_result = $mysqli->query("SELECT DISTINCT key_type FROM operator_keys ORDER BY key_type");
$key_types = [];
while ($row = $key_types_result->fetch_assoc()) {
    $key_types[] = $row['key_type'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Operator Key</title>
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="common.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
        });
    </script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="operator_keys.php">To Keys List</a>
            <?php if ($prefill_operator_id): ?>
                <a href="personnel_edit.php?id=<?php echo urlencode($prefill_operator_id); ?>">Back to Operator</a>
            <?php endif; ?>
            <a href="index.php">To main page</a>
        </div>
    </div>

    <div class="container">
        <h1>Add Operator Key</h1>

        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php elseif (!empty($success_message)) : ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="operator_key_add.php<?php echo $prefill_operator_id ? '?operator_id=' . urlencode($prefill_operator_id) : ''; ?>">
            <div class="form-group">
                <label for="operator_id">Operator:</label>
                <select name="operator_id" id="operator_id" required>
                    <option value="">-- Select Operator --</option>
                    <?php foreach ($operators as $op): ?>
                        <option value="<?php echo htmlspecialchars($op['seq_nmbr']); ?>"
                            <?php echo ($prefill_operator_id && intval($op['seq_nmbr']) === $prefill_operator_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($op['fname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="key_type">Key Type:</label>
                <input type="text" name="key_type" id="key_type" list="key_types" required
                    placeholder="e.g., badge, 200A2, 200A21">
                <datalist id="key_types">
                    <?php foreach ($key_types as $kt): ?>
                        <option value="<?php echo htmlspecialchars($kt); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label for="serial_number">Serial Number:</label>
                <input type="text" name="serial_number" id="serial_number" required
                    placeholder="e.g., 101019511 or 05">
            </div>

            <div class="form-group">
                <label for="issued_date">Issued Date:</label>
                <input type="date" name="issued_date" id="issued_date"
                    value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="notes">Notes:</label>
                <textarea name="notes" id="notes" rows="2"></textarea>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            <button type="submit">Add Key</button>
        </form>
    </div>
</body>
</html>
