<?php include("cred.php") ?>
<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('timeofday', 'Hour');
	data.addColumn('number', 'Blocks');
	data.addRows([
<?php 
	$query = "SELECT hour, ROUND(AVG(numhits), 1) AS avghits FROM (SELECT DATE(`timestamp`) AS day, HOUR(`timestamp`) AS hour, COUNT(*) as numhits FROM hm_fwban_rh GROUP BY day, hour) d GROUP BY hour ORDER BY hour ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
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
