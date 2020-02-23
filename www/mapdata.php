<?php 
	include_once("config.php");
	include_once("functions.php");
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(country)) AS count
		FROM hm_fwban
	");
	$sql->execute();
	$countcountries = $sql->fetchColumn();
	
	echo "data.addRows(".$countcountries.");";
	echo "data.addColumn('string', 'Country');";
	echo "data.addColumn('number', 'Blocked IPs');";

	$sql = $pdo->prepare("
		SELECT 
			country,
			COUNT(country) AS count
		FROM hm_fwban
		GROUP BY country
	");
	$sql->execute();
	$N=0;
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if (($row['country']===NULL)||($row['country']=="")||($row['country']=="NOT FOUND")) {
			echo "data.setValue(".$N.", 0, 'Nowhereland".$N."');";
			echo "data.setValue(".$N.", 1, 0);";
		} else {
			echo "data.setValue(".$N.", 0, '".$row['country']."');";
			echo "data.setValue(".$N.", 1, ".$row['count'].");";
		}
		$N++;
	}
?>
