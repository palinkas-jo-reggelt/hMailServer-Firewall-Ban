<?php include_once("config.php") ?>
<?php include_once("functions.php") ?>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Blocks');
	data.addRows([
<?php 
	$sql = $pdo->prepare("
		SELECT 
			".DBCastDateTimeFieldAsDate('timestamp')." AS daily, 
			".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%Y')." AS year,
			(".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%c')." ".(IsMySQL() ? "- 1" : "").") AS month,
			".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%e')." AS day,
			COUNT(ipaddress) AS ipperday 
		FROM hm_fwban_rh 
		WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())." 
		GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')." 
		ORDER BY daily ASC
		");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday']."],";
	}
?>
	]);

	var chart = new google.visualization.LineChart(document.getElementById('chart_totalblocksperday'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		colors: ['#000000'],
		legend: 'none',
		trendlines: { 0: { 
			type: 'polynomial',
			degree: 2,
			visibleInLegend: true,
			},
		},
	  });
}	
</script>
