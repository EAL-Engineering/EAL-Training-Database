<?php
// Include the database connection file
include_once("config.php");

// Start session to access success/error messages
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if 'id' is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request. No trainer ID provided.");
}

$trainer_id = intval($_GET['id']); // Sanitize the ID

// Fetch trainer's name
$query = "SELECT fname FROM operators WHERE seq_nmbr = ?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error);
}
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($trainer_name);
if (!$stmt->fetch()) {
    die("Trainer not found.");
}
$stmt->close();

// Fetch existing certifications from the can_certify table
$query = "
    SELECT c.certification, c.seq_nmbr AS cert_id
    FROM can_certify cc
    JOIN certifications c ON cc.cert_ptr = c.seq_nmbr
    WHERE cc.trainer_ptr = ?
";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($certification, $cert_id);

$current_certifications = [];
while ($stmt->fetch()) {
    $current_certifications[] = [
        'certification' => $certification,
        'cert_id' => $cert_id
    ];
}
$stmt->close();

// Fetch all available certifications (that are not already assigned to the trainer)
$query = "
    SELECT c.certification, c.seq_nmbr AS cert_id
    FROM certifications c
    WHERE c.seq_nmbr NOT IN (
        SELECT cert_ptr FROM can_certify WHERE trainer_ptr = ?
    )
";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$stmt->bind_result($available_certification, $available_cert_id);

$available_certifications = [];
while ($stmt->fetch()) {
    $available_certifications[] = [
        'certification' => $available_certification,
        'cert_id' => $available_cert_id
    ];
}
$stmt->close();

// Display any session messages (success or error)
$message = '';
if (isset($_SESSION['message'])) {
    $message = '<p style="color: ' . ($_SESSION['message']['type'] == 'success' ? 'green' : 'red') . ';">' . htmlspecialchars($_SESSION['message']['text']) . '</p>';
    
    // Clear the message after it has been displayed
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Trainer Certifications</title>
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body { font-family: Arial, sans-serif; }
        .form-container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        .form-row { display: flex; align-items: center; margin-bottom: 8px; }
        .form-row label { width: 150px; text-align: right; margin-right: 10px; }
        .form-row input, 
        .form-row select, 
        .form-row textarea { flex: 1; padding: 5px; }
        button { padding: 8px 16px; margin-top: 8px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background-color: #0056b3; }
        .readonly-field { background-color: #f9f9f9; border: none; }
        .certifications-list { margin-top: 10px; }
        .certifications-list ul { list-style: none; padding: 0; margin: 0; }
        .certifications-list li { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .certifications-list button { margin-left: 10px; }
        .back-button-container { margin-bottom: 15px; text-align: center; }
        .back-button-container a { 
            display: inline-block; 
            padding: 8px 16px; 
            text-decoration: none; 
            color: white; 
            background-color: #007bff; 
            border-radius: 4px; 
            transition: background-color 0.2s ease; 
            margin-left: 15px;
            margin-right: 15px;
        }
        .back-button-container a:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="back-button-container">
            <a href="trainer_list.php">Back to Trainer List</a>
            <a href="index.php">Back to Main Page</a>
        </div>
        <h1>Edit Certifications for Trainer: <?php echo htmlspecialchars($trainer_name); ?></h1>

        <!-- Display success or error message -->
        <?php echo $message; ?>

        <!-- Current Certifications -->
        <h2>Current Certifications</h2>
        <?php if (!empty($current_certifications)): ?>
            <div class="certifications-list">
                <ul>
                    <?php foreach ($current_certifications as $cert): ?>
                        <li>
                            <span><?php echo htmlspecialchars($cert['certification']); ?></span>
                            <form method="post" action="trainer_certification_remove.php" style="display:inline;">
                                <input type="hidden" name="trainer_id" value="<?php echo htmlspecialchars($trainer_id); ?>">
                                <input type="hidden" name="cert_id" value="<?php echo htmlspecialchars($cert['cert_id']); ?>">
                                <button type="submit">Remove Certification</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>No certifications assigned to this trainer.</p>
        <?php endif; ?>

        <!-- Available Certifications -->
        <h2>Available Certifications</h2>
        <?php if (!empty($available_certifications)): ?>
            <div class="certifications-list">
                <ul>
                    <?php foreach ($available_certifications as $cert): ?>
                        <li>
                            <span><?php echo htmlspecialchars($cert['certification']); ?></span>
                            <form method="post" action="trainer_certification_add.php" style="display:inline;">
                                <input type="hidden" name="trainer_id" value="<?php echo htmlspecialchars($trainer_id); ?>">
                                <input type="hidden" name="cert_id" value="<?php echo htmlspecialchars($cert['cert_id']); ?>">
                                <button type="submit">Add Certification</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>No available certifications to assign to this trainer.</p>
        <?php endif; ?>
    </div>
</body>
</html>
