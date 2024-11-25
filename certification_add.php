<?php
// Include the database connection file
include_once("config.php");

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if 'id' is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request. No operator ID provided.");
}

$operator_id = intval($_GET['id']); // Sanitize the ID

// Fetch operator's existing certifications
$query = "SELECT c.certification, ot.status, ot.entered, ot.expires, ot.trainer
          FROM optraining ot
          JOIN certifications c ON ot.certification = c.seq_nmbr
          WHERE ot.operator = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("i", $operator_id);
$stmt->execute();
$stmt->bind_result($cert_name, $status, $date_entered, $expiration_date, $completed_by);

$certifications = [];
while ($stmt->fetch()) {
    $certifications[] = [
        'cert_name' => $cert_name,
        'status' => $status,
        'date_entered' => $date_entered,
        'expiration_date' => $expiration_date,
        'completed_by' => $completed_by
    ];
}
$stmt->close();

// Fetch certifications not yet completed by the operator
$query = "SELECT c.seq_nmbr, c.certification
          FROM certifications c
          WHERE c.certification NOT IN (
              SELECT ot.certification FROM optraining ot WHERE ot.operator = ?
          )";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("i", $operator_id);
$stmt->execute();
$stmt->bind_result($cert_id, $cert_name);

$available_certifications = [];
while ($stmt->fetch()) {
    $available_certifications[] = [
        'cert_id' => $cert_id,
        'cert_name' => $cert_name
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Certification</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        table th { background-color: #f4f4f4; }
        .form-row { margin-bottom: 10px; }
        label { display: block; margin-bottom: 5px; }
        select, input { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Certification for Operator <?php echo htmlspecialchars($operator_id); ?></h1>

        <!-- Display Existing Certifications -->
        <h2>Existing Certifications</h2>
        <?php if (!empty($certifications)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Certification Name</th>
                        <th>Status</th>
                        <th>Date Entered</th>
                        <th>Expiration Date</th>
                        <th>Completed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certifications as $cert): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cert['cert_name']); ?></td>
                            <td><?php echo htmlspecialchars($cert['status']); ?></td>
                            <td><?php echo htmlspecialchars($cert['date_entered']); ?></td>
                            <td><?php echo htmlspecialchars($cert['expiration_date']); ?></td>
                            <td><?php echo htmlspecialchars($cert['completed_by']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No certifications found for this operator.</p>
        <?php endif; ?>

        <!-- Add New Certification -->
        <h2>Add New Certification</h2>
        <form method="post" action="certification_save.php">
            <div class="form-row">
                <label for="cert_id">Certification:</label>
                <select name="cert_id" id="cert_id" required>
                    <option value="">Select a Certification</option>
                    <?php foreach ($available_certifications as $cert): ?>
                        <option value="<?php echo htmlspecialchars($cert['cert_id']); ?>">
                            <?php echo htmlspecialchars($cert['cert_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="completed_by">Completed By:</label>
                <input type="text" name="completed_by" id="completed_by" required>
            </div>
            <input type="hidden" name="operator_id" value="<?php echo htmlspecialchars($operator_id); ?>">
            <button type="submit">Add Certification</button>
        </form>
    </div>
</body>
</html>
