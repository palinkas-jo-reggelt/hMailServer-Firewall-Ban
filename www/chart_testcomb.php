<?php include("cred.php") ?>
<?php 
	$query = "
		SELECT 
			a.daily,
			a.year,
			a.month,
			a.day,
			a.ipperday,
			b.blockperday
		FROM
		(
			SELECT DATE(timestamp) AS daily, DATE_FORMAT(timestamp, '%Y') AS year, (DATE_FORMAT(timestamp, '%c') - 1) AS month, DATE_FORMAT(timestamp, '%e') AS day, COUNT(id) AS ipperday 
				FROM hm_fwban 
				WHERE DATE(timestamp) < DATE(NOW())
				GROUP BY DATE(timestamp)
				ORDER BY DATE(timestamp) ASC
		) AS a
		INNER JOIN
		(
			SELECT DATE(timestamp) AS daily, COUNT(DISTINCT(ipaddress)) AS blockperday  
				FROM hm_fwban_rh 
				WHERE DATE(timestamp) < DATE(NOW()) 
				GROUP BY DATE(timestamp)
		) AS b
		ON a.daily = b.daily
		ORDER BY a.daily
	";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday'].", ".$row['blockperday']."],";
	}
?>
