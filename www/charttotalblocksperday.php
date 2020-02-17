<?php include_once("config.php") ?>
<?php include_once("functions.php") ?>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Blocks');
	data.addRows([<?php include_once("charttotalblocksperdaydata.php") ?>]);

	var chart = new google.visualization.LineChart(document.getElementById('chart_totalblocksperday_staticdata'));
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
