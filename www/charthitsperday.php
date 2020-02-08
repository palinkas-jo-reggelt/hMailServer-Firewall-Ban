<?php include_once("config.php") ?>
<?php include_once("functions.php") ?>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Hits');
	data.addRows([
<?php 
	$query = "
		SELECT 
			".DBCastDateTimeFieldAsDate('timestamp')." AS daily, 
			".DBFormatDate('timestamp', '%Y')." AS year,
			(".DBFormatDate('timestamp', '%c')." - 1) AS month,
			".DBFormatDate('timestamp', '%e')." AS day,
			COUNT(id) AS ipperday 
		FROM hm_fwban 
		WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())." 
		GROUP BY daily ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday']."],";
	}
?>
	]);

	var chart = new google.visualization.LineChart(document.getElementById('chart_hitsperday'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		colors: ['#ff0000'],
		legend: 'none',
		trendlines: { 0: { 
			type: 'polynomial',
			degree: 1,
			visibleInLegend: true,
			}
		}
	  });
}	
</script>
