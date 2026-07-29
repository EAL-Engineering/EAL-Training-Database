<?php

/**
 * Operator Keys List
 *
 * Displays all keys assigned to operators with filtering and DataTables.
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

$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Build query with optional filters
$where_clauses = [];
$params = [];
$types = '';

$status_filter = $_GET['status'] ?? '';
if ($status_filter !== '') {
    $where_clauses[] = "ok.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$key_type_filter = $_GET['key_type'] ?? '';
if ($key_type_filter !== '') {
    $where_clauses[] = "ok.key_type = ?";
    $params[] = $key_type_filter;
    $types .= 's';
}

$operator_id_filter = $_GET['operator_id'] ?? null;
if ($operator_id_filter !== null && is_numeric($operator_id_filter)) {
    $where_clauses[] = "ok.operator_id = ?";
    $params[] = intval($operator_id_filter);
    $types .= 'i';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "
    SELECT
        ok.seq_nmbr,
        ok.key_type,
        ok.serial_number,
        ok.status,
        ok.issued_date,
        ok.returned_date,
        ok.notes,
        ok.entered,
        ok.entered_by,
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
    ORDER BY ok.status = 'Active' DESC, o.fname, ok.key_type, ok.serial_number
";

if (!empty($params)) {
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
}

// Get distinct key types for filter dropdown
$key_types_result = $mysqli->query("SELECT DISTINCT key_type FROM operator_keys ORDER BY key_type");
$key_types = [];
if ($key_types_result) {
    while ($row = $key_types_result->fetch_assoc()) {
        $key_types[] = $row['key_type'];
    }
}

// Get active operators for filter dropdown
$operators_result = $mysqli->query("SELECT seq_nmbr, fname FROM operators WHERE status = 'Active' ORDER BY fname");
$operators = [];
if ($operators_result) {
    while ($row = $operators_result->fetch_assoc()) {
        $operators[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operator Keys</title>
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
    <link rel="stylesheet"
          href="https://cdn.datatables.net/rowgroup/1.5.0/css/rowGroup.dataTables.css"
          integrity="sha384-bei2bpb7orBDGkDdrxxnvqdYpRnpXwKe5JxL3It/9i0qLCepqOWVYsKn0NSb47um"
          crossorigin="anonymous">
    <script src="https://cdn.datatables.net/rowgroup/1.5.0/js/dataTables.rowGroup.js"
            integrity="sha384-/T8n5YuWFPR9FBZ0KGlsnRvNM5QnSRKP+mxyfECfEyTneU3QJBdNnV5q4jgCFWxH"
            crossorigin="anonymous"></script>
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
            <a href="personnel_list.php">To Personnel List</a>
            <a href="operator_keys_by_type.php">By Key Type</a>
            <a href="index.php">To main page</a>
        </div>
    </div>

    <h1>Operator Keys</h1>

        <form method="get" action="operator_keys.php" class="filter-form" style="margin-bottom: 20px;">
            <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div>
                    <label for="status">Status:</label>
                    <select name="status" id="status" onchange="this.form.submit();">
                        <option value="">All</option>
                        <option value="Active" <?php echo ($status_filter === 'Active') ? 'selected' : ''; ?>>
                            Active
                        </option>
                        <option value="Lost" <?php echo ($status_filter === 'Lost') ? 'selected' : ''; ?>>
                            Lost
                        </option>
                        <option value="Returned" <?php echo ($status_filter === 'Returned') ? 'selected' : ''; ?>>
                            Returned
                        </option>
                        <option value="Obsolete" <?php echo ($status_filter === 'Obsolete') ? 'selected' : ''; ?>>
                            Obsolete
                        </option>
                    </select>
                </div>
                <div>
                    <label for="key_type">Key Type:</label>
                    <select name="key_type" id="key_type" onchange="this.form.submit();">
                        <option value="">All</option>
                        <?php foreach ($key_types as $kt) : ?>
                            <option value="<?php echo htmlspecialchars($kt); ?>"
                                <?php echo ($key_type_filter === $kt) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kt); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="operator_id">Operator:</label>
                    <select name="operator_id" id="operator_id" onchange="this.form.submit();">
                        <option value="">All</option>
                        <?php foreach ($operators as $op) : ?>
                            <?php
                            $isOpSelected = ($operator_id_filter !== null
                                && intval($operator_id_filter) === intval($op['seq_nmbr']))
                                ? 'selected'
                                : '';
                            ?>
                            <option value="<?php echo htmlspecialchars($op['seq_nmbr']); ?>"
                                <?php echo $isOpSelected; ?>>
                                <?php echo htmlspecialchars($op['fname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <a href="operator_keys.php">Clear</a>
                </div>
            </div>
        </form>

        <table id="keys" class="display">
            <thead>
                <tr>
                    <th>Operator</th>
                    <th>Key Type</th>
                    <th>Serial #</th>
                    <th>Status</th>
                    <th>Issued</th>
                    <th>Returned</th>
                    <th>Entered</th>
                    <?php if (($_SESSION['role_id'] ?? 99) <= 2) : ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td>
                        <a href="personnel_edit.php?id=<?php echo urlencode($row['operator_id']); ?>">
                            <?php echo htmlspecialchars($row['operator_name']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($row['key_type']); ?></td>
                    <td>
                        <?php if (strtolower($row['key_type']) === 'badge') : ?>
                            <?php echo htmlspecialchars($row['serial_number']); ?>
                        <?php else : ?>
                            <?php
                            $history_url = 'key_history.php?key_type=' . urlencode($row['key_type'])
                                . '&serial=' . urlencode($row['serial_number']);
                            ?>
                            <a href="<?php echo htmlspecialchars($history_url); ?>">
                                <?php echo htmlspecialchars($row['serial_number']); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <td><?php echo $row['issued_date'] ? htmlspecialchars($row['issued_date']) : '-'; ?></td>
                    <td><?php echo $row['returned_date'] ? htmlspecialchars($row['returned_date']) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($row['entered']); ?></td>
                    <?php if (($_SESSION['role_id'] ?? 99) <= 2) : ?>
                    <td>
                        <?php if ($row['status'] === 'Active') : ?>
                            <?php
                            $ret_url = 'operator_key_return.php?id=' . urlencode($row['seq_nmbr'])
                                . '&redirect=operator_keys.php';
                            ?>
                            <a href="<?php echo htmlspecialchars($ret_url); ?>">Return</a>
                            
                            <form method="post" action="operator_key_lost.php" style="display:inline;">
                                <input type="hidden" name="id"
                                    value="<?php echo htmlspecialchars($row['seq_nmbr']); ?>">
                                <input type="hidden" name="redirect" value="operator_keys.php">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                                <button 
                                    type="submit" 
                                    class="link-button" 
                                    onclick="return confirm('Mark this key as lost?');">
                                    Lost
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <script>
        $(document).ready(function() {
            new DataTable('#keys', {
                scrollX: true,
                pageLength: 25,
                lengthMenu: [10, 15, 25, 50, 75, 100],
                order: [[0, 'asc'], [3, 'desc']],
                autoWidth: false,
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
