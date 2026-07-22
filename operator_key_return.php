<?php
/**
 * Return Operator Key
 *
 * Returns a key from an operator and assigns it to a spare pool.
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

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'operator_keys.php';
$error_message = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . $redirect);
    exit();
}

$key_id = intval($_GET['id']);

// Fetch the key details
$stmt = $mysqli->prepare(
    "SELECT ok.key_type, ok.serial_number, o.fname AS operator_name, ok.operator_id
     FROM operator_keys ok
     JOIN operators o ON ok.operator_id = o.seq_nmbr
     WHERE ok.seq_nmbr = ? AND ok.status = 'Active'"
);
$stmt->bind_param("i", $key_id);
$stmt->execute();
$stmt->bind_result($key_type, $serial_number, $operator_name, $operator_id);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    header("Location: " . $redirect . "?error=not_found");
    exit();
}

// Fetch spare pool operators
$pools_result = $mysqli->query(
    "SELECT seq_nmbr, fname FROM operators WHERE fname LIKE '%- Spares' OR fname LIKE '%Spares%' ORDER BY fname"
);
$pools = [];
while ($row = $pools_result->fetch_assoc()) {
    $pools[] = $row;
}

// Default pool: Crystal Brooks - Spares
$default_pool_id = null;
foreach ($pools as $pool) {
    if (stripos($pool['fname'], 'Crystal') !== false || stripos($pool['fname'], 'Brooks') !== false) {
        $default_pool_id = $pool['seq_nmbr'];
        break;
    }
}
if (!$default_pool_id && count($pools) > 0) {
    $default_pool_id = $pools[0]['seq_nmbr'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        $pool_id = isset($_POST['pool_id']) ? intval($_POST['pool_id']) : 0;

        if ($pool_id <= 0) {
            $error_message = "Please select a pool.";
        } else {
            $mysqli->begin_transaction();

            try {
                // Mark original key as returned
                $returned_date = date('Y-m-d');
                $stmt1 = $mysqli->prepare(
                    "UPDATE operator_keys SET status = 'Returned', returned_date = ? WHERE seq_nmbr = ?"
                );
                $stmt1->bind_param("si", $returned_date, $key_id);
                $stmt1->execute();
                $stmt1->close();

                // Create new active assignment to pool
                $issued_date = date('Y-m-d');
                $stmt2 = $mysqli->prepare(
                    "INSERT INTO operator_keys (operator_id, key_type, serial_number, status, issued_date, entered_by)
                     VALUES (?, ?, ?, 'Active', ?, ?)"
                );
                $stmt2->bind_param("issss", $pool_id, $key_type, $serial_number, $issued_date, $entered_by);
                $stmt2->execute();
                $stmt2->close();

                $mysqli->commit();
                header("Location: " . $redirect . "?message=key_returned");
                exit();
            } catch (Exception $e) {
                $mysqli->rollback();
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Key</title>
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
            <a href="<?php echo htmlspecialchars($redirect); ?>">Back</a>
            <a href="index.php">To main page</a>
        </div>
    </div>

    <div class="container">
        <h1>Return Key</h1>

        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <p>Returning <strong><?php echo htmlspecialchars($key_type); ?></strong> key <strong>#<?php echo htmlspecialchars($serial_number); ?></strong> from <strong><?php echo htmlspecialchars($operator_name); ?></strong>.</p>

        <form method="post" action="operator_key_return.php?id=<?php echo urlencode($key_id); ?>&redirect=<?php echo urlencode($redirect); ?>">
            <div class="form-group">
                <label for="pool_id">Return to pool:</label>
                <select name="pool_id" id="pool_id" required>
                    <option value="">-- Select Pool --</option>
                    <?php foreach ($pools as $pool): ?>
                        <option value="<?php echo htmlspecialchars($pool['seq_nmbr']); ?>"
                            <?php echo ($default_pool_id && intval($pool['seq_nmbr']) === $default_pool_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pool['fname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            <button type="submit">Return Key</button>
        </form>
    </div>
</body>
</html>
