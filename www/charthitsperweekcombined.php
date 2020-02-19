<!DOCTYPE html> 
<html>
<head>
<title>hMailServer Firewall Ban</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" media="all" href="stylesheet.css">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet"> 
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<?php include("charthitsperdaycombined.php") ?>

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
	include_once("config.php");
	include_once("functions.php");
	
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

	var chart = new google.visualization.LineChart(document.getElementById('chart_combined_week'));
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

</head>
<body>
<div class="wrapper">

	<div class="section">
		<div class="secleft">
			<h2>Hits per day from inception:</h2>
			<div id="chart_combined_staticdata"></div>
		</div>
		<div class="secright">
			<h2>Hits per week from inception:</h2>
			<div id="chart_combined_week"></div>
		</div>
		<div class="clear"></div>

	</div>
</div>
</body>
</html>