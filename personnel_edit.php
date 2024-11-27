<?php
// Start session to access success/error messages
session_start();
// Include the database connection file
include_once("config.php");

// Check if the user is logged in and has the required access level (1 or 2)
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] < 1 || $_SESSION['role_id'] > 2)) {
    // Redirect to login page if not authorized
    header("Location: login.php");
    exit();
}

$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Check if 'id' is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request. No operator ID provided.");
}

$id = intval($_GET['id']); // Sanitize the ID

// Prepare the SQL statement to fetch operator details
$query = "SELECT * FROM operators WHERE seq_nmbr = ?";
$operator_stmt = $mysqli->prepare($query);

if (!$operator_stmt) {
    die("Database error: " . $mysqli->error); // Debugging helper
}

// Bind the parameter and execute the statement
$operator_stmt->bind_param("i", $id);
$operator_stmt->execute();

// Bind the result fields to variables
$operator_stmt->bind_result($seq_nmbr, $name, $fname, $email, $altemail, $phones, $status, $office, $home, $updated, $comments, $entered, $addedby);

// Fetch the result
if ($operator_stmt->fetch()) {
    $operator = [
        'seq_nmbr' => $seq_nmbr,
        'name' => $name,
        'fname' => $fname,
        'email' => $email,
        'altemail' => $altemail,
        'phones' => $phones,
        'status' => $status,
        'office' => $office,
        'home' => $home,
        'updated' => $updated,
        'comments' => $comments,
        'entered' => $entered,
        'addedby' => $addedby
    ];
} else {
    die("No operator found with the provided ID.");
}

// Close the statement
$operator_stmt->close();

// Fetch certifications for the operator
$certifications_query = "
    SELECT c.certification 
    FROM optraining ot
    JOIN certifications c ON ot.certification = c.seq_nmbr
    WHERE ot.operator = ?
";
$certifications_stmt = $mysqli->prepare($certifications_query);

if (!$certifications_stmt) {
    die("Database error: " . $mysqli->error);
}

$certifications_stmt->bind_param("i", $id);
$certifications_stmt->execute();

// Bind results to variables
$certifications_stmt->bind_result($certification);

// Retrieve the certifications
$certifications = [];
while ($certifications_stmt->fetch()) {
    $certifications[] = $certification;
}

$certifications_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Operator</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
	<link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="common.js" defer></script>
    <script>
        // Pass the session expiration time to the JavaScript function
        document.addEventListener('DOMContentLoaded', () => {
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
        });
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="personnel_list.php">Back to Personnel List</a>
            <a href="index.php">Back to main page</a>
        </div>
        <h1>Edit Operator Details</h1>
        <form method="post" action="personnel_save.php">
            <div class="form-row">
                <label>Seq Number:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['seq_nmbr']); ?>" readonly>
                <input type="hidden" name="seq_nmbr" value="<?php echo htmlspecialchars($operator['seq_nmbr']); ?>">
            </div>
            <div class="form-row">
                <label>Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($operator['name']); ?>">
            </div>
            <div class="form-row">
                <label>Full Name:</label>
                <input type="text" name="fname" value="<?php echo htmlspecialchars($operator['fname']); ?>">
            </div>
            <div class="form-row">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($operator['email']); ?>">
            </div>
            <div class="form-row">
                <label>Alt Email:</label>
                <input type="email" name="altemail" value="<?php echo htmlspecialchars($operator['altemail']); ?>">
            </div>
            <div class="form-row">
                <label>Phones:</label>
                <input type="text" name="phones" value="<?php echo htmlspecialchars($operator['phones']); ?>">
            </div>
            <div class="form-row">
                <label>Status:</label>
                <select name="status">
                    <option value="Active" <?php echo $operator['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $operator['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Other" <?php echo $operator['status'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="form-row">
                <label>Office:</label>
                <input type="text" name="office" value="<?php echo htmlspecialchars($operator['office']); ?>">
            </div>
            <div class="form-row">
                <label>Home:</label>
                <input type="text" name="home" value="<?php echo htmlspecialchars($operator['home']); ?>">
            </div>
            <div class="form-row">
                <label>Updated:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['updated']); ?>" readonly>
            </div>
            <div class="form-row">
                <label>Entered:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['entered']); ?>" readonly>
            </div>
            <div class="form-row">
                <label>Added By:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['addedby']); ?>" readonly>
            </div>
            <div class="form-row">
                <label>Comments:</label>
                <textarea name="comments"><?php echo htmlspecialchars($operator['comments']); ?></textarea>
            </div>
            <button type="submit" class="full-width-button">Save Changes</button>
        </form>
        <div class="certifications">
            <h2>Certifications</h2>
            <?php if (count($certifications) > 0): ?>
                <ul>
                    <?php foreach ($certifications as $cert): ?>
                        <li><?php echo htmlspecialchars($cert); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No certifications found for this operator.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="back-button-container">
        <a href="certification_add.php?id=<?php echo urlencode($operator['seq_nmbr']); ?>">Add Certification</a>
    </div>
</body>
</html>
