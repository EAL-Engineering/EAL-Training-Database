<?php
/**
 * Operator Keys by Type
 *
 * Lists all keys grouped by key type, showing which operator holds each.
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

$timeUntilSessionExpires = isset($_SESSION['user_id']) ? getTimeUntilSessionExpires() : 0;

$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Optional status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_sql = '';
$params = [];
$types = '';

if ($status_filter !== '') {
    $where_sql = "WHERE ok.status = ?";
    $params[] = $status_filter;
    $types = 's';
}

$query = "
    SELECT
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
    INNER JOIN (
        SELECT key_type, serial_number, MAX(seq_nmbr) AS max_seq
        FROM operator_keys
        GROUP BY key_type, serial_number
    ) latest ON ok.key_type = latest.key_type
             AND ok.serial_number = latest.serial_number
             AND ok.seq_nmbr = latest.max_seq
    $where_sql
    ORDER BY ok.key_type, ok.status = 'Active' DESC, o.fname, ok.serial_number
";

if (!empty($params)) {
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
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
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.5.0/css/rowGroup.dataTables.css">
    <script src="https://cdn.datatables.net/rowgroup/1.5.0/js/dataTables.rowGroup.js"></script>
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
            <a href="index.php">To main page</a>
        </div>
    </div>

    <h1>Keys by Type</h1>

    <form method="get" action="operator_keys_by_status.php" class="filter-form" style="margin-bottom: 20px;">
        <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div>
                <label for="status">Status:</label>
                <select name="status" id="status" onchange="this.form.submit();">
                    <option value="">All</option>
                    <option value="Active" <?php echo ($status_filter === 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Lost" <?php echo ($status_filter === 'Lost') ? 'selected' : ''; ?>>Lost</option>
                    <option value="Returned" <?php echo ($status_filter === 'Returned') ? 'selected' : ''; ?>>Returned</option>
                    <option value="Obsolete" <?php echo ($status_filter === 'Obsolete') ? 'selected' : ''; ?>>Obsolete</option>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end;">
                <a href="operator_keys_by_status.php">Clear</a>
            </div>
        </div>
    </form>

    <table id="keys-by-type" class="display">
        <thead>
            <tr>
                <th>Key Type</th>
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
                <td><?php echo htmlspecialchars($row['key_type']); ?></td>
                <td>
                    <?php if (strtolower($row['key_type']) === 'badge'): ?>
                        <?php echo htmlspecialchars($row['serial_number']); ?>
                    <?php else: ?>
                        <a href="key_history.php?key_type=<?php echo urlencode($row['key_type']); ?>&serial=<?php echo urlencode($row['serial_number']); ?>">
                            <?php echo htmlspecialchars($row['serial_number']); ?>
                        </a>
                    <?php endif; ?>
                </td>
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
                order: [[0, 'asc'], [3, 'desc']],
                rowGroup: {
                    dataSrc: 0
                },
                columnDefs: [
                    { targets: 0, visible: false }
                ]
            });
        });
    </script>
</body>
</html>
