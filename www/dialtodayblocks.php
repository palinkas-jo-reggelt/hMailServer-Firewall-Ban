<script type="text/javascript">
	google.charts.load('current', {'packages':['gauge']});
	google.charts.setOnLoadCallback(drawChart);

	function drawChart() {

	var data = google.visualization.arrayToDataTable([
		['Label', 'Value'],
<?php
	include_once("config.php");
	include_once("functions.php");
	include_once("blocksdata.php");

	//Set guage color marker points
	$redFrom = ($redToBlock / 1.2);
	$yellowTo = $redFrom;
	$yellowFrom = ($yellowTo * 0.75);

	//Get current (today's) bans
	$sql = $pdo->prepare("
		SELECT	
			COUNT(ipaddress)
		FROM (
			SELECT * 
			FROM hm_fwban_rh 
			WHERE '".date('Y-m-d')." 00:00:00' <= timestamp
		) AS A 
		WHERE timestamp <= '".date('Y-m-d')." 23:59:59'
	");
	$sql->execute();
	$hits = $sql->fetchColumn();
	echo "['Blocks', ".$hits."]";
	echo "]);";

	echo "var options = { ";
	echo "width: 100, height: 100, ";
	echo "min: 0, max: ".$redToBlock.", ";
	echo "redFrom: ".$redFrom.", redTo: ".$redToBlock.", ";
	echo "yellowFrom: ".$yellowFrom.", yellowTo: ".$yellowTo.", ";
?>
		minorTicks: 10
	};

	var chart = new google.visualization.Gauge(document.getElementById('todays_blocks_dial'));

	chart.draw(data, options);

	}
</script>
