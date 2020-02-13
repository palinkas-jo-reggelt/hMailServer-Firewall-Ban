<?php include_once("config.php") ?>
<?php include_once("functions.php") ?>
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
	$sql = $pdo->prepare("
		SELECT 
			a.week_beginning,
			a.year,
			a.month,
			a.day,
			a.ipperweek,
			b.blockperweek
		FROM
		(
			SELECT 
				FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7)) AS week_beginning,
				DATE_FORMAT(FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7)), '%Y') AS year,
				(DATE_FORMAT(FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7)), '%c') - 1) AS month,
				DATE_FORMAT(FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7)), '%e') AS day,
				COUNT(id) AS ipperweek 
			FROM hm_fwban
			GROUP BY FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7))
			ORDER BY FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7))
		) AS a
		LEFT JOIN
		(
			SELECT 
				FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7)) AS week_beginning,
				COUNT(DISTINCT(ipaddress)) AS blockperweek
			FROM hm_fwban_rh 
			GROUP BY FROM_DAYS(TO_DAYS(timestamp) -MOD(TO_DAYS(timestamp) -1, 7))
		) AS b
		ON a.week_beginning = b.week_beginning
		ORDER BY a.week_beginning
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if (is_null($row['blockperweek'])){$blockperweek = 0;} else {$blockperweek = $row['blockperweek'];}
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperweek'].", ".$blockperweek."],";
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
