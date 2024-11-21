<?php
// Include the database connection file
include_once("config.php");
// include("common_functions.php");

// Fetch active operators 
$active_operators = mysqli_query($mysqli, "SELECT * FROM operators WHERE status='Active' ORDER BY name");

function build_oper_pulldown () {
    echo "<select name='selname'>";
    while ($operator = $active_operators->fetch_array(MYSQLI_ASSOC)) {
        echo '<option value="' .$operator['seq_nmbr'] . '">' . $operator['fname'] . "\n";
    }
    echo "</select>\n";
}
?>
<html>
<head>	
	<title>OUAL Operator Training Information</title>
	<link rel="stylesheet" href="styles.css" />
</head>
<body>
    <h1>EAL Personnel Information</h1>
    <h2>Operator Training Information</h2>
    <br/>
    <p>Select operator:<br>
        <?php
            echo "<select name='selname'>";
            while ($operator = $active_operators->fetch_array(MYSQLI_ASSOC)) {
                echo '<option value="' .$operator['seq_nmbr'] . '">' . $operator['fname'] . "\n";
            }
            echo "</select>\n";
        ?>
        <form method=post name="add_certification" action="add_certification.php">
            <button type=submit name="add_certification" value="add_certification">
                Add Certification
            </button>
        </form>
        <form method=post name="delete" action="delete_certification.php">
            <button type=submit name="remove_certification" value="remove_certification">
                Remove Certification
            </button>
        </form>
    </p>
    
</body>
</html>