<?php include("cred.php") ?>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'IPs Added');
	data.addColumn('number', 'IPs Blocked');
	data.addRows([
<?php 
	$query = "SELECT DATE(timestamp) AS daily, DATE_FORMAT(timestamp, '%Y') AS year, (DATE_FORMAT(timestamp, '%c') - 1) AS month, DATE_FORMAT(timestamp, '%e') AS day, COUNT(id) AS ipperday FROM hm_fwban WHERE DATE(timestamp) < DATE(NOW()) GROUP BY daily ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		$res_knocks = mysqli_query($con,"SELECT COUNT(DISTINCT(ipaddress)) FROM hm_fwban_rh WHERE DATE(timestamp)='".$row['daily']."'");
		$knocks = mysqli_fetch_array($res_knocks)[0];
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday'].", ".$knocks."],";
	}
?>
	]);

	var chart = new google.visualization.LineChart(document.getElementById('chart_combined'));
	  chart.draw(data, {
		width: 350,
		height: 200,
		colors: ['#ff0000','#000000'],
		legend: { position: 'bottom' },
		trendlines: { 
			0: { 
				type: 'polynomial',
				degree: 2,
				visibleInLegend: false,
				},
			1: { 
				type: 'polynomial',
				degree: 2,
				visibleInLegend: false,
				}
		}
	  });
}	
</script>
