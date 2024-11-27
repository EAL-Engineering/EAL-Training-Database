<?php
session_start();

function getTimeUntilSessionExpires() {
    if (isset($_SESSION['last_activity'])) {
        $remaining = (2 * 60 * 60) - (time() - $_SESSION['last_activity']);
        return max($remaining, 0); // Ensure no negative time is returned
    }
    return 0;
}
?>
<html>
<head>	
    <title>OUAL Operator Training Information</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 0; }
        .header { background: #007bff; color: white; padding: 10px; text-align: center; position: relative; }
        .header span { display: inline-block; margin-right: 10px; }
        .logout-button {
            display: inline-block;
            background-color: white;
            color: #007bff;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        .logout-button:hover {
            background-color: #0056b3;
            color: white;
        }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 8px; }
        h1 { margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea, button { width: 100%; padding: 10px; font-size: 16px; }
        button { background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
    </style>
    <script>
        let timeLeft = <?php echo getTimeUntilSessionExpires(); ?>;
        function updateCountdown() {
            if (timeLeft > 0) {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                document.getElementById('countdown').textContent = `${minutes}m ${seconds}s`;
                timeLeft--;
            } else {
                document.getElementById('countdown').textContent = "Session expired";
            }
        }
        setInterval(updateCountdown, 1000);
    </script>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="header">
            Logged in as: <?php echo htmlspecialchars($_SESSION['fname']); ?> |
            Session expires in: <span id="countdown"></span>
			<a href="logout.php" class="logout-button">Logout</a>
		</div>
    <?php endif; ?>
    <div class="container">
        <h1>OUAL Training Information</h1>
        <h2>Operator Training Information Database</h2>
        <p>This is a complete green field re-write of the operators database that was created by John 
            O'Donnel and maintained by Donald Carter.</p>
        <p>This version was started in November of 2024 by Gregory Leblanc.  It was developed using 
            ChatGPT, among other resources.</p>
        <p>The basic function of the operator training information database and related web pages 
            is to record an association between a person (operator) and a certification, indicating 
            that the person has completed all the training required to obtain the certification.</p>
        <p>The main list of current EAL personnel can be found at:
            <a href="personnel_list.php">personnel_list.php</a></p>
        <p>The list of all personnel including folks who are no longer active at EAL can be found at:
            <a href="personnel_list_all.php">personnel_list_all.php</a></p>
        <p>The list of personnel who can give each certification sorted by trainer can be found at:
            <a href="trainer_list.php">trainer_list.php</a></p>
        <p>The list of personnel who can give each certification sorted by certification can be found at:
            <a href="trainer_list_by_cert.php">trainer_list_by_cert.php</a></p>
        <p>New personnel can be added at:
            <a href="personnel_add.php">personnel_add.php</a></p>
		<p>Login at:
            <a href="login.php">login.php</a></p>
		<p>Logout at:
            <a href="logout.php">logout.php</a></p>


        <p>New trainings can be registered using: certification_add.php, but don't access that page directly.  
            Instead, go to edit a user and add a certification from there.</p>
        <p>This information is recorded in a database table named <b>optraining</b> and kept on the 
        localhost system using MySQL.  The optraining table links an operator and a certification, and 
        also includes additional information on who is responsible for the training, a status (usually 
        <i>Active</i>) an expiration date (usually not used), and a time stamp showing when the completed 
        training was entered in the table. This information is usually accessed using the <i>List 
        Completed Certifications</i> button on the main operator training web page, and certifications 
        are added to the database using the <i>Add completed training certification</i> button (actually
        you select an operator from a list of names, then press a button).</p>
        <p>Another table, <b>certifications</b>, contains a list of available certifications.  This 
        table includes a long and short form name for the certification, a comment field, and an 
        expiration field showing the number of months a certification is valid (usually not used, 
        indicating the certification does not expire).</p>
        <p>Another table, <b>operators</b>, contains information about the people being trained, 
        including name, phone numbers, and office information. Other tables referring to operators contain 
        pointers into this table (rather than operator names).</p>
        <p>A table, <b>trainers</b>, contains a list of browser login names (which are different from 
        the names used in the operators table) allowed to record completion of training 
        certification.</p>
        <p>A table, <b>can_certify</b>, links trainers to certifications, showing which trainers are 
        allowed to certify what training.</p>
        <p>Most everyday transactions should be accomplished using the appropriate web pages.  Some 
        advanced operations are done manually using the MySQL database interface on localhost.</p>
        <p>Please report problems or suggestions to Gregory Leblanc</p>
    </div>
</body>
</html>
