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
            initSearchableSelects();
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
                tr.innerHTML = buildRowHtml(index);
                tbody.appendChild(tr);
                initSearchableSelect(tr.querySelector('.searchable-select'));
            }
        }

        function buildRowHtml(index) {
            return `
                <td>
                    <div class="searchable-select" data-index="${index}">
                        <input type="text" class="searchable-input" placeholder="Type to search..." autocomplete="off">
                        <div class="searchable-dropdown"></div>
                        <input type="hidden" name="rows[${index}][operator_id]" value="">
                    </div>
                </td>
                <td>
                    <input type="text" name="rows[${index}][serial_number]" placeholder="Serial #">
                </td>
            `;
        }

        // Operator data for JS filtering
        const operatorsData = [
            <?php foreach ($operators as $op): ?>
            { id: "<?php echo htmlspecialchars($op['seq_nmbr']); ?>", name: "<?php echo htmlspecialchars($op['fname']); ?>" },
            <?php endforeach; ?>
        ];

        function initSearchableSelects() {
            document.querySelectorAll('.searchable-select').forEach(el => {
                initSearchableSelect(el);
            });
        }

        function initSearchableSelect(container) {
            const input = container.querySelector('.searchable-input');
            const dropdown = container.querySelector('.searchable-dropdown');
            const hidden = container.querySelector('input[type="hidden"]');
            let selectedIndex = -1;

            input.addEventListener('focus', () => {
                renderDropdown(operatorsData);
                dropdown.style.display = 'block';
            });

            input.addEventListener('blur', () => {
                setTimeout(() => { dropdown.style.display = 'none'; }, 150);
            });

            input.addEventListener('input', () => {
                const term = input.value.toLowerCase();
                const filtered = operatorsData.filter(op => op.name.toLowerCase().includes(term));
                renderDropdown(filtered);
                dropdown.style.display = 'block';
                selectedIndex = -1;
            });

            input.addEventListener('keydown', (e) => {
                const items = dropdown.querySelectorAll('.searchable-item');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    highlightItem(items, selectedIndex);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    highlightItem(items, selectedIndex);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        selectOperator(items[selectedIndex], input, hidden);
                    }
                } else if (e.key === 'Escape') {
                    dropdown.style.display = 'none';
                }
            });

            function renderDropdown(items) {
                if (items.length === 0) {
                    dropdown.innerHTML = '<div class="searchable-no-results">No matches</div>';
                    return;
                }
                dropdown.innerHTML = items.map((op, i) =>
                    `<div class="searchable-item" data-id="${op.id}" data-name="${op.name}">${op.name}</div>`
                ).join('');

                dropdown.querySelectorAll('.searchable-item').forEach(item => {
                    item.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        selectOperator(item, input, hidden);
                    });
                });
            }

            function highlightItem(items, index) {
                items.forEach((item, i) => {
                    item.classList.toggle('searchable-highlight', i === index);
                });
            }

            function selectOperator(item, inp, hid) {
                inp.value = item.dataset.name;
                hid.value = item.dataset.id;
                dropdown.style.display = 'none';
                selectedIndex = -1;
                // Trigger auto-expand check
                const event = new Event('change', { bubbles: true });
                hid.dispatchEvent(event);
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
                            <div class="searchable-select" data-index="<?php echo $i; ?>">
                                <input type="text" class="searchable-input" placeholder="Type to search..."
                                    value="<?php
                                        if (isset($_POST['rows'][$i]['operator_id'])) {
                                            foreach ($operators as $op) {
                                                if (intval($op['seq_nmbr']) === intval($_POST['rows'][$i]['operator_id'])) {
                                                    echo htmlspecialchars($op['fname']);
                                                    break;
                                                }
                                            }
                                        }
                                    ?>"
                                    autocomplete="off">
                                <div class="searchable-dropdown"></div>
                                <input type="hidden" name="rows[<?php echo $i; ?>][operator_id]"
                                    value="<?php echo isset($_POST['rows'][$i]['operator_id']) ? htmlspecialchars($_POST['rows'][$i]['operator_id']) : ''; ?>">
                            </div>
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
</body>
</html>
