<?php
/**
 * Key History
 *
 * Displays the full history of a specific key (all assignments over time).
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

$key_type = isset($_GET['key_type']) ? $_GET['key_type'] : '';
$serial = isset($_GET['serial']) ? $_GET['serial'] : '';

if ($key_type === '' || $serial === '') {
    header('Location: operator_keys.php');
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT
        ok.seq_nmbr,
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
    WHERE ok.key_type = ? AND ok.serial_number = ?
    ORDER BY ok.seq_nmbr DESC"
);
$stmt->bind_param("ss", $key_type, $serial);
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Key History</title>
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
            <a href="operator_keys_by_type.php">By Key Type</a>
            <a href="operator_keys_by_status.php">By Status</a>
            <a href="index.php">To main page</a>
        </div>
    </div>

    <h1>Key History: <?php echo htmlspecialchars($key_type); ?> #<?php echo htmlspecialchars($serial); ?></h1>

    <table id="key-history" class="display">
        <thead>
            <tr>
                <th>Status</th>
                <th>Operator</th>
                <th>Issued</th>
                <th>Returned</th>
                <th>Notes</th>
                <th>Entered</th>
                <th>Entered By</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>
                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </span>
                </td>
                <td>
                    <a href="personnel_edit.php?id=<?php echo urlencode($row['operator_id']); ?>">
                        <?php echo htmlspecialchars($row['operator_name']); ?>
                    </a>
                </td>
                <td><?php echo $row['issued_date'] ? htmlspecialchars($row['issued_date']) : '-'; ?></td>
                <td><?php echo $row['returned_date'] ? htmlspecialchars($row['returned_date']) : '-'; ?></td>
                <td><?php echo $row['notes'] ? htmlspecialchars($row['notes']) : '-'; ?></td>
                <td><?php echo htmlspecialchars($row['entered']); ?></td>
                <td><?php echo htmlspecialchars($row['entered_by']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            new DataTable('#key-history', {
                scrollX: true,
                pageLength: 25,
                lengthMenu: [10, 15, 25, 50, 75, 100],
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>
