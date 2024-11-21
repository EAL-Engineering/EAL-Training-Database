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

$id = intval($_GET['id']); // Sanitize the ID

// Prepare the SQL statement to fetch operator details
$query = "SELECT * FROM operators WHERE seq_nmbr = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    die("Database error: " . $mysqli->error); // Debugging helper
}

// Bind the parameter and execute the statement
$stmt->bind_param("i", $id);
$stmt->execute();

// Bind the result fields to variables
$stmt->bind_result($seq_nmbr, $name, $fname, $email, $altemail, $phones, $status, $office, $home, $updated, $comments, $entered, $addedby);

// Fetch the result
if ($stmt->fetch()) {
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
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Operator</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <style>
        .form-container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        /* label { display: inline-block; width: 150px; margin-top: 10px; } */
        input, select, textarea { width: calc(100% - 160px); padding: 5px; }
        .readonly-field { background-color: #f9f9f9; border: none; }
        .form-row { margin-bottom: 10px; }
        button { margin-top: 20px; padding: 10px 20px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Edit Operator Details</h1>
        <form method="post" action="save_operator.php">
            <div class="form-row">
                <label>Seq Number:</label>
                <input type="text" class="readonly-field" value="<?php echo htmlspecialchars($operator['seq_nmbr']); ?>" readonly>
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
            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>
