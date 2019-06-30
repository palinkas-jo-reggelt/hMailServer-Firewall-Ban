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
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Hits');
	data.addRows([
<?php include("charthitsperday.php") ?>
	]);

	var chart = new google.visualization.LineChart(document.getElementById('chart_hitsperday'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		colors: ['#ff0000'],
		legend: 'none',
		trendlines: { 0: { 
             type: 'polynomial',
             degree: 3,
             visibleInLegend: false,
			}
		}
	  });
}	
</script>
<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('timeofday', 'Hour');
	data.addColumn('number', 'Hits');
	data.addRows([
<?php include("charthitsperhour.php") ?>
	]);

	var chart = new google.visualization.ColumnChart(document.getElementById('chart_hitsperhour'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		legend: 'none',
		colors: ['#ff0000']
	  });
}	
</script>
</head>
<body>

<div class="header">
	<div class="banner"><h1>hMailServer Firewall Ban</h1></div>
	<div class="headlinks">
		<div class="headlinkswidth">
			<a href="./">stats</a> | <a href="search.php">search</a> | <a href="release.php">release</a> | <a href="reban.php">reban</a>
		</div>
	</div>
</div>

<div class="wrapper">