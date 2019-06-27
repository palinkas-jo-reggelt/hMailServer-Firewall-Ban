<?php include("cred.php") ?>
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
	var data = google.visualization.arrayToDataTable([
	['Date','Hits'],
<?php 
	$query = "SELECT DATE(timestamp) Date, COUNT(id) AS ipperday FROM hm_fwban GROUP BY DATE(timestamp)";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "['".$row['Date']."',".$row['ipperday']."],";
	}
?> 

	]);

	  var options = {
		hAxis: {
		  title: 'Time'
		},
		vAxis: {
		  title: 'Hits/Day'
		},
		series: {
		  1: {curveType: 'function'}
		}
	  };
	var chart = new google.visualization.LineChart(document.getElementById('chart_hitsperday'));
	  chart.draw(data, options);
}	
</script>

</head>
<body>

<div class="header">
	<div class="banner"><h1>hMailServer Firewall Ban</h1></div>
	<div class="headlinks">
		<div class="headlinkswidth">
			<a href="./">stats</a> | <a href="search.php">search</a> | <a href="history.php">history</a> | <a href="release.php">release</a> | <a href="reban.php">reban</a>
		</div>
	</div>
</div>

<div class="wrapper">