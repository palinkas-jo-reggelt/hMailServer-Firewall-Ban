<script type="text/javascript">
	google.charts.load('current', {'packages':['gauge']});
	google.charts.setOnLoadCallback(drawChart);

	function drawChart() {

	var data = google.visualization.arrayToDataTable([
		['Label', 'Value'],
<?php
	include_once("config.php");
	include_once("functions.php");

	//Get guage max
	$sql = $pdo->prepare("
		SELECT	
			ROUND(((COUNT(DISTINCT(ipaddress))) * 1.2), -1) AS dailymax,
			".DBCastDateTimeFieldAsDate('timestamp')." AS daily
		FROM hm_fwban
		GROUP BY daily
		".DBLimitRowsWithOffset('dailymax','DESC',0,0,0,1)
	);
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		$redTo = $row['dailymax'];
	}
	//Set guage color marker points
	$redFrom = ($redTo / 1.2);
	$yellowTo = $redFrom;
	$yellowFrom = ($yellowTo * 0.75);

	//Get current (today's) bans
	$sql = $pdo->prepare("
		SELECT	
			COUNT(DISTINCT(ipaddress)) AS hits
		FROM hm_fwban
		WHERE ".DBCastDateTimeFieldAsDate('timestamp')." LIKE ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())
	);
	$sql->execute();
	$hits = $sql->fetchColumn();
	echo "['Bans', ".$hits."]";
	echo "]);";

	echo "var options = { ";
	echo "width: 100, height: 100, ";
	echo "min: 0, max: ".$redTo.", ";
	echo "redFrom: ".$redFrom.", redTo: ".$redTo.", ";
	echo "yellowFrom: ".$yellowFrom.", yellowTo: ".$yellowTo.", ";
?>
		minorTicks: 10
	};

	var chart = new google.visualization.Gauge(document.getElementById('todays_hits_dial'));

	chart.draw(data, options);

	// setInterval(function() {
		// data.setValue(0, 1, 40 + Math.round(60 * Math.random()));
		// chart.draw(data, options);
	// }, 13000);
	}
</script>
