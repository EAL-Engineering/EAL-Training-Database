<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
include_once("config.php");
include_once("auth.php");

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

$timeUntilSessionExpires = getTimeUntilSessionExpires();
$currentUrl = urlencode($_SERVER['REQUEST_URI']); // Encodes the URL for safe use in GET parameters

// Check authorization
$authorizedTrainer = isset($_SESSION['user_id']) && checkCertification($_SESSION['user_id'], 18);
if (!$authorizedTrainer) {
    header("Location: login.php?return=$currentUrl");
    exit();
}

$message = ''; // Initialize message

// Form submission logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateOfTraining = isset($_POST['date_of_training']) ? trim($_POST['date_of_training']) : '';
    $selectedOperators = isset($_POST['operators']) ? $_POST['operators'] : [];

    if (!empty($dateOfTraining) && !empty($selectedOperators)) {
        $date = date('Y-m-d', strtotime($dateOfTraining)); // Validate and format the date
        $successCount = 0;

        foreach ($selectedOperators as $operator) {
            $operator = (int)$operator; // Ensure the ID is numeric
            if (!$stmt = $mysqli->prepare("INSERT INTO optraining (operator, certification, date_completed) VALUES (?, 18, ?)")) {
                die("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param("is", $operator, $date);
            if ($stmt->execute()) {
                $successCount++;
            }
            $stmt->close();
        }

        $message = $successCount > 0 ? "Successfully registered $successCount operators." : "No operators were registered.";
    } else {
        $message = "Please select at least one operator and enter a valid date.";
    }
}

// Fetch operators eligible for training
$operatorsResult = $mysqli->query("
    SELECT o.seq_nmbr AS id, o.name AS name
    FROM operators o
    WHERE o.status = 'Active'
");

if (!$operatorsResult) {
    die("Query failed: " . $mysqli->error);
}

// Fetch data using a loop since `fetch_all` is unavailable in PHP 5.4
$operators = [];
while ($row = $operatorsResult->fetch_assoc()) {
    $operators[] = $row;
}

// Function to check authorization
function checkCertification($trainerId, $certificationId)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT optbl_ptr FROM trainers WHERE seq_nmbr = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    $stmt->bind_result($operatorId);
    $stmt->fetch();
    $stmt->close();

    if (!$operatorId || $operatorId == -1) {
        echo "No valid operator associated with trainer ID $trainerId.";
        return false;
    }

    $stmt = $mysqli->prepare("
        SELECT COUNT(*) 
        FROM can_certify 
        WHERE trainer_ptr = ? AND cert_ptr = ?
    ");
    $stmt->bind_param("ii", $operatorId, $certificationId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    // echo "Authorization check: operator ID $operatorId, cert count $count.";
    return $count > 0;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Radiation Safety Training</title>
    <link rel="stylesheet" href="common.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="form-container">
        <h1>Register Radiation Safety Training</h1>
        
        <?php if (!empty($message)): ?>
            <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="operators">Select Operators:</label>
            <select id="operators" name="operators[]" multiple>
                <?php foreach ($operators as $operator): ?>
                    <option value="<?php echo htmlspecialchars($operator['seq_nmbr']); ?>">
                        <?php echo htmlspecialchars($operator['fname'] . " " . $operator['lname']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="training_date">Training Date:</label>
            <input type="date" id="training_date" name="training_date" required>

            <button type="submit">Register Training</button>
        </form>
    </div>
</body>
</html>