<?php include("cred.php") ?>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'IPs Added');
	data.addColumn('number', 'IPs Blocked');
	data.addRows([
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
		LEFT JOIN
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
		if (is_null($row['blockperday'])){$blockperday = 0;} else {$blockperday = $row['blockperday'];}
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday'].", ".$blockperday."],";
	}
?>

	]);

	var chart = new google.visualization.LineChart(document.getElementById('chart_combined'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		colors: ['#ff0000','#000000'],
		legend: { position: 'bottom' },
		trendlines: { 
			0: { 
				type: 'polynomial',
				degree: 2,
				visibleInLegend: false,
				},
			1: { 
				type: 'polynomial',
				degree: 2,
				visibleInLegend: false,
				},
		},
	  });
}	
</script>
