<html>
<head>
<title>hMailServer Firewall Ban</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" media="all" href="stylesheet.css">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet"> 
</head>
<body onBlur="window.focus()">

<div class="wrapper">
<div class="section">

<?php
	include_once("config.php");
	include_once("functions.php");

	if (isset($_GET['ip'])) {$ip = $_GET ['ip'];} else {$ip = "";}

	$sql = $pdo->prepare("
		SELECT 
			".DBCastDateTimeFieldAsDate('timestamp')." AS dateptr, 
			ban_reason, 
			helo, 
			ptr 
		FROM hm_fwban 
		WHERE ipaddress = '$ip'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "IP: ".$ip."<br />";
		echo "PTR: ".$row['ptr']."<br />";
		echo "HELO: ".$row['helo']."<br />";
		echo "Ban Reason: ".$row['ban_reason']."<br />";
		echo "Ban Date: ".$row['dateptr']."<br />";
	}
?>

</div>
</div>

</body>
</html>