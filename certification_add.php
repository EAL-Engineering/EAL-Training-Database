<?php
/**
 * Add / Manage Certifications for an Operator
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

checkLogin(1, $_SERVER['REQUEST_URI']);

$timeUntilSessionExpires = getTimeUntilSessionExpires();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid operator ID. <a href='personnel_list.php'>Back to List</a>");
}

$operator_id = intval($_GET['id']);
$logged_in_user_id = $_SESSION['user_id'] ?? 0;

// Fetch operator details
$query = "SELECT fname, name FROM operators WHERE seq_nmbr = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $operator_id);
$stmt->execute();
$stmt->bind_result($op_fname, $op_name);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Operator not found. <a href='personnel_list.php'>Back to List</a>");
}
$stmt->close();

// Fetch current active certifications (Issue #33 UI)
$query = "
    SELECT ot.seq_nmbr AS optraining_id, c.certification, ot.entered, ot.expires, o.fname AS trainer_fname
    FROM optraining ot
    JOIN certifications c ON ot.certification = c.seq_nmbr
    LEFT JOIN trainers t ON ot.trainer = t.seq_nmbr
    LEFT JOIN operators o ON t.optbl_ptr = o.seq_nmbr
    WHERE ot.operator = ? AND ot.status = 'Active'
    ORDER BY c.certification ASC
";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $operator_id);
$stmt->execute();
$stmt->bind_result($optraining_id, $cert_name, $entered, $expires, $trainer_fname);

$current_certs = [];
while ($stmt->fetch()) {
    $current_certs[] = [
        'id' => $optraining_id,
        'name' => $cert_name,
        'entered' => $entered,
        'expires' => $expires,
        'trainer' => $trainer_fname
    ];
}
$stmt->close();

// Fetch available certifications
$query = "SELECT seq_nmbr, certification FROM certifications ORDER BY certification ASC";
$cert_result = $mysqli->query($query);

// Fetch active trainers for pulldown
$query = "
    SELECT t.seq_nmbr AS trainer_id, o.fname, o.name 
    FROM trainers t 
    JOIN operators o ON t.optbl_ptr = o.seq_nmbr 
    WHERE o.status = 'Active' 
    ORDER BY o.fname ASC
";
$trainer_result = $mysqli->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Certifications - <?php echo htmlspecialchars("$op_fname $op_name"); ?></title>
    <link rel="stylesheet" href="common.css">
    <script src="common.js" defer></script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="personnel_edit.php?id=<?php echo $operator_id; ?>">Back to Edit Operator</a>
            <a href="personnel_list.php">Back to Personnel List</a>
        </div>

        <h1>Certifications for <?php echo htmlspecialchars("$op_fname $op_name"); ?></h1>

        <!-- Status Feedback Messages -->
        <?php if (isset($_GET['success'])) : ?>
            <p style="color: green; font-weight: bold;">Certification added successfully.</p>
        <?php endif; ?>
        <?php if (isset($_GET['removed'])) : ?>
            <p style="color: green; font-weight: bold;">Certification removed successfully.</p>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate') : ?>
            <p style="color: red; font-weight: bold;">Error: Operator already has an active record for this certification.</p>
        <?php endif; ?>

        <!-- Active Certifications List & Removal Form (Issue #33) -->
        <h2>Active Certifications</h2>
        <?php if (!empty($current_certs)) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Certification</th>
                        <th>Entered</th>
                        <th>Expires</th>
                        <th>Completed By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_certs as $cert) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cert['name']); ?></td>
                            <td><?php echo htmlspecialchars($cert['entered'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($cert['expires'] ?? 'Never'); ?></td>
                            <td><?php echo htmlspecialchars($cert['trainer'] ?? 'Unknown'); ?></td>
                            <td>
                                <form method="post" action="certification_remove.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this certification?');">
                                    <input type="hidden" name="optraining_id" value="<?php echo $cert['id']; ?>">
                                    <input type="hidden" name="operator_id" value="<?php echo $operator_id; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                                    <button type="submit" style="color: red;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No active certifications recorded.</p>
        <?php endif; ?>

        <hr style="margin: 2em 0;">

        <!-- Add Certification Form -->
        <h2>Add New Certification</h2>
        <form method="post" action="certification_save.php">
            <input type="hidden" name="operator_id" value="<?php echo $operator_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">

            <label for="cert_id">Certification:</label>
            <select name="cert_id" id="cert_id" required>
                <option value="">-- Select Certification --</option>
                <?php while ($row = $cert_result->fetch_assoc()) : ?>
                    <option value="<?php echo $row['seq_nmbr']; ?>"><?php echo htmlspecialchars($row['certification']); ?></option>
                <?php endwhile; ?>
            </select><br><br>

            <label for="completed_by">Completed By (Trainer):</label>
            <select name="completed_by" id="completed_by" required>
                <option value="">-- Select Trainer --</option>
                <?php while ($row = $trainer_result->fetch_assoc()) : ?>
                    <!-- FIX (Issue #25): Default selection to currently logged-in user -->
                    <option value="<?php echo $row['trainer_id']; ?>" <?php echo ($row['trainer_id'] == $logged_in_user_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['fname'] . ' ' . $row['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select><br><br>

            <button type="submit">Add Certification</button>
        </form>
    </div>
</body>
</html>
