<?php
/**
 * Mark Keys as Obsolete
 *
 * Batch operation to mark all keys of a given type as obsolete.
 * Used during building re-keying events.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        $key_type = isset($_POST['key_type']) ? trim($_POST['key_type']) : '';
        $new_key_type = isset($_POST['new_key_type']) ? trim($_POST['new_key_type']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        if (empty($key_type)) {
            $error_message = "Please select a key type to mark obsolete.";
        } elseif (empty($new_key_type)) {
            $error_message = "Please enter the new key type (re-key number).";
        } elseif ($key_type === $new_key_type) {
            $error_message = "New key type must be different from the old key type.";
        } else {
            $mysqli->begin_transaction();

            try {
                // Mark all active keys of this type as obsolete
                $stmt = $mysqli->prepare(
                    "UPDATE operator_keys
                     SET status = 'Obsolete', notes = CONCAT(IFNULL(notes, ''), '\nMarked obsolete during re-keying to ', ?, ' by ', ?, ' on ', NOW())
                     WHERE key_type = ? AND status = 'Active'"
                );
                $stmt->bind_param("sss", $new_key_type, $entered_by, $key_type);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                $mysqli->commit();
                $success_message = "Marked " . $affected . " key(s) of type '" . htmlspecialchars($key_type) . "' as obsolete.";
            } catch (Exception $e) {
                $mysqli->rollback();
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get existing key types
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
    <title>Mark Keys Obsolete</title>
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
            <a href="index.php">To main page</a>
        </div>
    </div>

    <div class="container">
        <h1>Mark Keys as Obsolete</h1>
        <p><strong>Warning:</strong> This will mark <em>all active keys</em> of the selected type as obsolete. Use this during building re-keying events.</p>

        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php elseif (!empty($success_message)) : ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="operator_key_mark_obsolete.php">
            <div class="form-group">
                <label for="key_type">Key Type to Mark Obsolete:</label>
                <select name="key_type" id="key_type" required>
                    <option value="">-- Select Key Type --</option>
                    <?php foreach ($key_types as $kt): ?>
                        <option value="<?php echo htmlspecialchars($kt); ?>">
                            <?php echo htmlspecialchars($kt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="new_key_type">New Key Type (Re-key Number):</label>
                <input type="text" name="new_key_type" id="new_key_type" required
                    placeholder="e.g., 200A22">
            </div>

            <div class="form-group">
                <label for="notes">Additional Notes:</label>
                <textarea name="notes" id="notes" rows="2"></textarea>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            <button type="submit" class="danger-button" onclick="return confirm('Are you sure? This will mark ALL active keys of this type as obsolete.');">
                Mark Obsolete
            </button>
        </form>
    </div>
</body>
</html>
