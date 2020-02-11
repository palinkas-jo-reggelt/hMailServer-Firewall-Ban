<?php include_once("config.php") ?>
<?php include_once("functions.php") ?>
<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('timeofday', 'Hour');
	data.addColumn('number', 'Blocks');
	data.addRows([
<?php 
	$sql = $pdo->prepare("
		SELECT 
			hour, 
			ROUND(AVG(numhits), 1) AS avghits 
		FROM (
			SELECT 
				".DBCastDateTimeFieldAsDate('timestamp')." AS day, 
				".DBCastDateTimeFieldAsHour('timestamp')." AS hour, 
				COUNT(*) as numhits 
			FROM hm_fwban_rh 
			GROUP BY ".DBCastDateTimeFieldAsDate('timestamp').", ".DBCastDateTimeFieldAsHour('timestamp')."
		) d 
		GROUP BY hour 
		ORDER BY hour ASC
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "[[".$row['hour'].", 0, 0], ".$row['avghits']."],";
	}
?>
	]);

	var chart = new google.visualization.ColumnChart(document.getElementById('chart_blocksperhour'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		legend: 'none',
		colors: ['#000000']
	  });
}	
</script>
