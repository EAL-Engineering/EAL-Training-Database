<?php
// Include the database connection file
include_once("config.php");

// Initialize variables for error handling
$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input fields
    $fname = isset($_POST['fname']) ? trim($_POST['fname']) : '';
    $name = isset($_POST['fname']) ? trim($_POST['fname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $altemail = isset($_POST['altemail']) ? trim($_POST['altemail']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $office = isset($_POST['office']) ? trim($_POST['office']) : '';
    $home = isset($_POST['home']) ? trim($_POST['home']) : '';
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    $status = isset($_POST['status']) && $_POST['status'] !== '' ? trim($_POST['status']) : 'Active';
    $entered = date('Y-m-d H:i:s');  // Get current date and time in 'YYYY-MM-DD HH:MM:SS' format

    // Check for empty required fields
    if (empty($fname) || empty($email)) {
        $error_message = "Full Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email address.";
    } elseif (!empty($altemail) && !filter_var($altemail, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid alternate email address.";
    } else {
        // Insert new user into the database, including the 'entered' field
        $stmt = $mysqli->prepare("
            INSERT INTO operators (fname, name, email, altemail, phones, office, home, comments, status, entered) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param("ssssssssss", $fname, $name, $email, $altemail, $phone, $office, $home, $comments, $status, $entered);
            if ($stmt->execute()) {
                $operator_id = $stmt->insert_id;

                // Insert default certification into certifications table
                $default_certification = 1; // Replace with an actual default certification ID
                $stmt_cert = $mysqli->prepare("
                    INSERT INTO optraining (operator, certification) 
                    VALUES (?, ?)
                ");
                if ($stmt_cert) {
                    $stmt_cert->bind_param("ii", $operator_id, $default_certification);
                    $stmt_cert->execute();
                    $stmt_cert->close();
                }

                // Redirect to personnel_list.php
                header("Location: personnel_list.php");
                exit;
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Database error: " . $mysqli->error;
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Personnel</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 650px; margin: 20px auto; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 8px; }
        h1 { margin-bottom: 10px; margin-top: 1px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input,  textarea, button { width: 96.5%; padding: 10px; font-size: 16px; }
        select { width: 100%; padding: 10px; font-size: 16px; }
        button { background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .form-container { max-width: 600px; margin: 5px auto; padding: 5px;}
        .back-button-container { margin-top: 5px; text-align: center; }
        .back-button-container a { 
            display: inline-block; 
            padding: 10px 20px; 
            text-decoration: none; 
            color: white; 
            background-color: #007bff; 
            border-radius: 4px; 
            transition: background-color 0.2s ease; 
            margin-left: 20px;
            margin-right: 20px; 
        }
        textarea {
            min-height: 20px; /* Initial height */
            padding: 10px;
            font-size: 16px;
            line-height: 1.5;
            resize: none; /* Prevent manual resizing */
            overflow: hidden; /* Hide overflow when resizing */
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="back-button-container">
            <a href="personnel_list.php">To Personnel List</a>
            <a href="index.php">To main page</a>
        </div>
    </div>
    <div class="container">
        <h1>Add New Personnel</h1>
        <p>Note: New personnel will automatically be added with <b>key holder</b> Training</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php elseif (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="personnel_add.php">
            <div class="form-group">
                <label for="fname">Full Name:</label>
                <input type="text" name="fname" id="fname" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="altemail">Alternate Email Address:</label>
                <input type="email" name="altemail" id="altemail">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" name="phone" id="phone">
            </div>
            <div class="form-group">
                <label for="office">Office Number:</label>
                <input type="text" name="office" id="office">
            </div>
            <div class="form-group">
                <label for="home">Home Address:</label>
                <textarea name="home" id="home" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="comments">Comments:</label>
                <textarea name="comments" id="comments" rows="1"></textarea>
            </div>
            <script>
                // Target the textarea with id 'comments'
                const commentsTextarea = document.getElementById('comments');

                if (commentsTextarea) {
                    commentsTextarea.addEventListener('input', function () {
                        // Reset height to recalculate based on scrollHeight
                        this.style.height = 'auto';
                        this.style.height = this.scrollHeight + 'px';
                    });
                }
            </script>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <button type="submit">Add Personnel</button>
        </form>
    </div>
</body>
</html>
