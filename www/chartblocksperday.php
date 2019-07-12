<?php include("cred.php") ?>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Blocks');
	data.addRows([
<?php 
	$query = "SELECT DATE(timestamp) AS daily, DATE_FORMAT(timestamp, '%Y') AS year, (DATE_FORMAT(timestamp, '%c') - 1) AS month, DATE_FORMAT(timestamp, '%e') AS day, COUNT(ipaddress) AS ipperday FROM hm_fwban_rh WHERE DATE(timestamp) < DATE(NOW()) GROUP BY daily ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday']."],";
	}
?>
	]);

	var chart = new google.visualization.LineChart(document.getElementById('chart_blocksperday'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		colors: ['#ff0000'],
		legend: 'none',
		trendlines: { 0: { 
			type: 'polynomial',
			degree: 2,
			visibleInLegend: true,
			}
		}
	  });
}	
</script>
