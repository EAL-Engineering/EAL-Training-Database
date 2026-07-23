<?php
/**
 * Operator Keys by Type
 *
 * Select a key type and view all keys of that type with operator details.
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

$key_type_options = [
    'badge'   => 'Badge',
    '200A2'   => '200A2 (Operator Key)',
    '200A21'  => '200A21 (Student Lab Key)',
    '4CA'     => '4CA (Faculty Key)',
    '4CAB'    => '4CAB (Student Key)',
];

$selected_type = isset($_GET['key_type']) ? $_GET['key_type'] : '';

if ($selected_type !== '' && array_key_exists($selected_type, $key_type_options)) {
    // Filter by specific key type
    $stmt = $mysqli->prepare(
        "SELECT
            ok.seq_nmbr,
            ok.key_type,
            ok.serial_number,
            ok.status,
            ok.issued_date,
            ok.returned_date,
            ok.entered,
            o.seq_nmbr AS operator_id,
            o.fname AS operator_name
        FROM operator_keys ok
        JOIN operators o ON ok.operator_id = o.seq_nmbr
        WHERE ok.key_type = ?
        ORDER BY ok.status = 'Active' DESC, o.fname, ok.serial_number"
    );
    $stmt->bind_param("s", $selected_type);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($selected_type === '') {
    // No key type selected — show all keys
    $result = $mysqli->query(
        "SELECT
            ok.seq_nmbr,
            ok.key_type,
            ok.serial_number,
            ok.status,
            ok.issued_date,
            ok.returned_date,
            ok.entered,
            o.seq_nmbr AS operator_id,
            o.fname AS operator_name
        FROM operator_keys ok
        JOIN operators o ON ok.operator_id = o.seq_nmbr
        ORDER BY ok.status = 'Active' DESC, o.fname, ok.serial_number"
    );
} else {
    // Invalid key type selected
    $result = null;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Keys by Type</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
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
            <a href="operator_keys.php">By Operator</a>
            <a href="operator_keys_by_status.php">By Status</a>
            <a href="index.php">To main page</a>
        </div>
    </div>

    <h1>Keys by Type<?php if ($selected_type !== '') echo ' — ' . htmlspecialchars($key_type_options[$selected_type]); ?></h1>

    <form method="get" action="operator_keys_by_type.php" class="filter-form" style="margin-bottom: 20px;">
        <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div>
                <label for="key_type">Key Type:</label>
                <select name="key_type" id="key_type" onchange="this.form.submit();">
                    <option value="">-- Select Key Type --</option>
                    <?php foreach ($key_type_options as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>"
                            <?php echo ($selected_type === $value) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end;">
                <a href="operator_keys_by_type.php">Clear</a>
            </div>
        </div>
    </form>

    <?php if ($result !== null): ?>
    <table id="keys-by-type" class="display">
        <thead>
            <tr>
                <?php if ($selected_type === '') : ?>
                    <th>Key Type</th>
                <?php endif; ?>
                <th>Serial #</th>
                <th>Operator</th>
                <th>Status</th>
                <th>Issued</th>
                <th>Returned</th>
                <th>Entered</th>
                <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] <= 2) : ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <?php if ($selected_type === '') : ?>
                    <td><?php echo htmlspecialchars($row['key_type']); ?></td>
                <?php endif; ?>
                <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                <td>
                    <a href="personnel_edit.php?id=<?php echo urlencode($row['operator_id']); ?>">
                        <?php echo htmlspecialchars($row['operator_name']); ?>
                    </a>
                </td>
                <td>
                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </span>
                </td>
                <td><?php echo $row['issued_date'] ? htmlspecialchars($row['issued_date']) : '-'; ?></td>
                <td><?php echo $row['returned_date'] ? htmlspecialchars($row['returned_date']) : '-'; ?></td>
                <td><?php echo htmlspecialchars($row['entered']); ?></td>
                <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] <= 2) : ?>
                <td>
                    <?php if ($row['status'] === 'Active'): ?>
                        <a href="operator_key_return.php?id=<?php echo urlencode($row['seq_nmbr']); ?>&redirect=operator_keys_by_type.php">Return</a>
                        <a href="operator_key_lost.php?id=<?php echo urlencode($row['seq_nmbr']); ?>&redirect=operator_keys_by_type.php">Lost</a>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            new DataTable('#keys-by-type', {
                scrollX: true,
                pageLength: 25,
                lengthMenu: [10, 15, 25, 50, 75, 100],
                <?php if ($selected_type === '') : ?>
                order: [[3, 'desc'], [2, 'asc']]
                <?php else: ?>
                order: [[2, 'desc'], [1, 'asc']]
                <?php endif; ?>
            });
        });
    </script>
    <?php elseif ($selected_type !== ''): ?>
        <p>Invalid key type selected.</p>
    <?php endif; ?>
</body>
</html>
