<?php
	include_once("config.php");
	include_once("functions.php");


	$sql = $pdo->prepare("
		SELECT 
			".DBCastDateTimeFieldAsDate('timestamp')." AS date 
		FROM hm_fwban 
		".DBLimitRowsWithOffset(DBCastDateTimeFieldAsDate('timestamp'),'ASC',0,0,0,1)
	);
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "'".$row['date']."',";
	}
?>