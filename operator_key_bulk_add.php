<?php
/**
 * Bulk Add Operator Keys
 *
 * Batch-assign keys to multiple operators. Starts with 5 rows
 * and auto-expands when the last row is filled.
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
$errors = [];
$success_count = 0;

$key_type_options = [
    'badge'   => 'Badge',
    '200A2'   => '200A2 (Operator Key)',
    '200A21'  => '200A21 (Student Lab Key)',
    '4CA'     => '4CA (Faculty Key)',
    '4CAB'    => '4CAB (Student Key)',
];

// Fetch active operators for dropdown
$operators_result = $mysqli->query("SELECT seq_nmbr, fname FROM operators WHERE status = 'Active' ORDER BY fname");
$operators = [];
while ($row = $operators_result->fetch_assoc()) {
    $operators[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        $key_type = isset($_POST['key_type']) ? trim($_POST['key_type']) : '';
        $issued_date = isset($_POST['issued_date']) && $_POST['issued_date'] !== '' ? $_POST['issued_date'] : date('Y-m-d');

        if (empty($key_type) || !array_key_exists($key_type, $key_type_options)) {
            $error_message = "Please select a valid key type.";
        } else {
            $mysqli->begin_transaction();
            $all_ok = true;

            $rows = isset($_POST['rows']) ? $_POST['rows'] : [];
            foreach ($rows as $idx => $row) {
                $operator_id = isset($row['operator_id']) ? intval($row['operator_id']) : 0;
                $serial_number = isset($row['serial_number']) ? trim($row['serial_number']) : '';

                // Skip completely empty rows
                if ($operator_id <= 0 && empty($serial_number)) {
                    continue;
                }

                if ($operator_id <= 0) {
                    $errors[] = "Row " . ($idx + 1) . ": Please select an operator.";
                    $all_ok = false;
                    continue;
                }
                if (empty($serial_number)) {
                    $errors[] = "Row " . ($idx + 1) . ": Serial number is required.";
                    $all_ok = false;
                    continue;
                }

                // Check for duplicate active key
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
                    $errors[] = "Row " . ($idx + 1) . ": Key already active and assigned to " . htmlspecialchars($existing_operator_name) . ".";
                    $all_ok = false;
                    continue;
                }

                $stmt = $mysqli->prepare(
                    "INSERT INTO operator_keys
                     (operator_id, key_type, serial_number, status, issued_date, entered_by)
                     VALUES (?, ?, ?, 'Active', ?, ?)"
                );
                if ($stmt) {
                    $stmt->bind_param("issss", $operator_id, $key_type, $serial_number, $issued_date, $entered_by);
                    if (!$stmt->execute()) {
                        $errors[] = "Row " . ($idx + 1) . ": Database error - " . $stmt->error;
                        $all_ok = false;
                    } else {
                        $success_count++;
                    }
                    $stmt->close();
                } else {
                    $errors[] = "Row " . ($idx + 1) . ": Database error - " . $mysqli->error;
                    $all_ok = false;
                }
            }

            if ($all_ok && $success_count > 0) {
                $mysqli->commit();
                $success_message = "Successfully assigned " . $success_count . " key(s).";
            } elseif ($success_count > 0) {
                $mysqli->commit();
                $success_message = "Assigned " . $success_count . " key(s) with " . count($errors) . " error(s).";
            } else {
                $mysqli->rollback();
                $error_message = "No keys were assigned. Please fix the errors below.";
            }
        }
    }
}

// Determine how many rows to show: at least 5, or enough to hold submitted data
$display_rows = 5;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rows'])) {
    $display_rows = max(5, count($_POST['rows']) + 1);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Add Operator Keys</title>
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="common.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
            initAutoExpand();
        });

        function initAutoExpand() {
            const tbody = document.getElementById('key-rows');
            if (!tbody) return;

            tbody.addEventListener('change', function(e) {
                if (e.target.matches('select[name^="rows"]') || e.target.matches('input[name^="rows"]')) {
                    checkLastRow();
                }
            });

            tbody.addEventListener('input', function(e) {
                if (e.target.matches('input[name^="rows"]')) {
                    checkLastRow();
                }
            });

            function checkLastRow() {
                const rows = tbody.querySelectorAll('tr');
                const lastRow = rows[rows.length - 1];
                const selects = lastRow.querySelectorAll('select');
                const inputs = lastRow.querySelectorAll('input[type="text"]');

                let hasContent = false;
                selects.forEach(s => { if (s.value) hasContent = true; });
                inputs.forEach(i => { if (i.value.trim()) hasContent = true; });

                if (hasContent) {
                    addRow(rows.length);
                }
            }

            function addRow(index) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <select name="rows[${index}][operator_id]">
                            <option value="">-- Select --</option>
                            <?php foreach ($operators as $op): ?>
                            <option value="<?php echo htmlspecialchars($op['seq_nmbr']); ?>">
                                <?php echo htmlspecialchars($op['fname']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="rows[${index}][serial_number]" placeholder="Serial #">
                    </td>
                `;
                tbody.appendChild(tr);
            }
        }
    </script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="operator_keys.php">To Keys List</a>
            <a href="operator_key_add.php">Single Add</a>
            <a href="index.php">To main page</a>
        </div>
    </div>

    <div class="container" style="max-width: 800px;">
        <h1>Bulk Add Operator Keys</h1>

        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)) : ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="operator_key_bulk_add.php">
            <div class="form-group">
                <label for="key_type">Key Type:</label>
                <select name="key_type" id="key_type" required>
                    <option value="">-- Select Key Type --</option>
                    <?php foreach ($key_type_options as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>"
                            <?php echo (isset($_POST['key_type']) && $_POST['key_type'] === $value) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="issued_date">Issued Date:</label>
                <input type="date" name="issued_date" id="issued_date"
                    value="<?php echo isset($_POST['issued_date']) ? htmlspecialchars($_POST['issued_date']) : date('Y-m-d'); ?>">
            </div>

            <table class="keys-table bulk-keys-table">
                <thead>
                    <tr>
                        <th>Operator</th>
                        <th>Serial Number</th>
                    </tr>
                </thead>
                <tbody id="key-rows">
                    <?php for ($i = 0; $i < $display_rows; $i++): ?>
                    <tr>
                        <td>
                            <select name="rows[<?php echo $i; ?>][operator_id]">
                                <option value="">-- Select --</option>
                                <?php foreach ($operators as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['seq_nmbr']); ?>"
                                        <?php echo (isset($_POST['rows'][$i]['operator_id']) && intval($_POST['rows'][$i]['operator_id']) === intval($op['seq_nmbr'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($op['fname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="rows[<?php echo $i; ?>][serial_number]"
                                placeholder="Serial #"
                                value="<?php echo isset($_POST['rows'][$i]['serial_number']) ? htmlspecialchars($_POST['rows'][$i]['serial_number']) : ''; ?>">
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
            <button type="submit" class="full-width-button" style="margin-top: 20px;">Assign Keys</button>
        </form>
    </div>

    <style>
        .bulk-keys-table select,
        .bulk-keys-table input[type="text"] {
            width: 100%;
            padding: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .bulk-keys-table th {
            text-align: left;
            padding: 8px 12px;
            background-color: #e9ecef;
        }
        .bulk-keys-table td {
            padding: 6px 12px;
        }
    </style>
</body>
</html>
