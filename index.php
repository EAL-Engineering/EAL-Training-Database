<html>
<head>	
	<title>OUAL Operator Training Information</title>
	<link rel="stylesheet" href="dataTables.dataTables.css">
	<!-- <link rel="stylesheet" href="styles.css" /> -->
</head>
<body>
	<H1>OUAL Training Information</h1>
	<H2>Operator Training Information Database</H2>
	<p>This is a complete green field re-write of the operators databased that was created by John 
		O'Donnel and maintained by Donald Carter.</p>
	<p>This version was started in November of 2024 by Gregory Leblanc.  It was developed using 
		ChatGPT, among other resources.
	</p>

	<p>The basic function of the operator training information database and related web pages 
		is to record an association between a person (operator) and a certification, indicating 
		that the person has completed all the training required to obtain the certification.
	</p>
		
	<p>The main list of current EAL personnel can be found at:
		<a href="personnel_list.php">personnel_list.php</a>
	</p>
	<p>The list of all personnel including folks who are no longer active at EAL can be found at:
		<a href="personnel_list_all.php">personnel_list_all.php</a>
	</p>
 
	<p>This information is recorded in a database table named <b>optraining</b> and kept on the 
	localhost system using MySQL.  The optraining table links an operator and a certification, and 
	also includes additional information on who is responsible for the training, a status (usually 
	<i>Active</i>) an expiration date (usually not used), and a time stamp showing when the completed 
	training was entered in the table. This information is usually accessed using the <i>List 
	Completed Certifications</i> button on the main operator training web page, and certifications 
	are added to the database using the <i>Add completed training certification</i> button (actually
	you select an operator from a list of names, then press a button). </p>
 
	<p>A separate table, <b>certifications</b>, contains a list of available certifications.  This 
	table includes a long and short form name for the certification, a comment field, and an 
	expiration field showing the number of months a certification is valid (usually not used, 
	indicating the certification does not expire). </p>

	<p>Another table, <b>operators</b>, contains information about the people being trained, 
	including name, phone numbers and office information. Other tables refering to operators contain 
	pointers into this table (rather than operator names).</p>
 
	<p>A table, <b>trainers</b>, contains a list of browser login names (which are different from 
	the  names used in the operators table) allowed to record completion of training 
	certification.<p>
 
	<p>A table, <b>can_certify</b> links trainers to certifications, showing which trainers are 
	allowed to certify what training.</p>

	<p>Most everyday transactions should be accomplished using the appropriate web pages.  Some 
	advanced operations are done manually using the mysql database interface on localhost. </p>

	<p>Please report problems or suggestions to Gregory Leblanc</p>

</body>
</html>