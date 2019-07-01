<?php include("cred.php") ?>
<?php 
	$query = "SELECT hour, ROUND(AVG(numhits), 1) AS avghits FROM (SELECT DATE(`timestamp`) AS day, HOUR(`timestamp`) AS hour, COUNT(*) as numhits FROM hm_fwban GROUP BY day, hour ) d GROUP BY hour ORDER BY hour ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "[[".$row['hour'].", 0, 0], ".$row['avghits']."],";
	}
?>