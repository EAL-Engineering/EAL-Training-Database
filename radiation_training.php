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
            $stmt = $mysqli->prepare("INSERT INTO optraining (operator, certification, entered) VALUES (?, 18, ?) ON DUPLICATE KEY UPDATE entered = VALUES(entered)");
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

// Fetch operators eligible for training and their most recent training date
$operatorsResult = $mysqli->query("
    SELECT 
        o.seq_nmbr AS id, 
        o.name AS name, 
        MAX(t.entered) AS last_training 
    FROM 
        operators o
    LEFT JOIN 
        optraining t ON o.seq_nmbr = t.operator AND t.certification = 18
    WHERE 
        o.status = 'Active'
    GROUP BY 
        o.seq_nmbr
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

    return $count > 0;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Radiation Safety Training</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
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

    <h1>Register Radiation Safety Training</h1>

    <?php if (!empty($message)): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <div>
            <label for="date_of_training">Date of Training:</label>
            <input type="date" id="date_of_training" name="date_of_training" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div>
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Operator Name</th>
                        <th>Last Training Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operators as $operator): ?>
                        <tr>
                            <td>
                                <input 
                                    type="checkbox" 
                                    id="operator_<?php echo htmlspecialchars($operator['id']); ?>" 
                                    name="operators[]" 
                                    value="<?php echo htmlspecialchars($operator['id']); ?>"
                                >
                            </td>
                            <td>
                                <label for="operator_<?php echo htmlspecialchars($operator['id']); ?>">
                                    <?php echo htmlspecialchars($operator['name']); ?>
                                </label>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($operator['last_training'] ?: 'Never'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button type="submit">Register Training</button>
    </form>
</body>
</html>
