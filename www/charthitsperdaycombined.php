<?php include_once("config.php") ?>
<?php include_once("functions.php") ?>
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
 
	$sql = $pdo->prepare("
		SELECT 
			a.daily,
			a.year,
			a.month,
			a.day,
			a.ipperday,
			b.blockperday
		FROM
		(
			SELECT 
				".DBCastDateTimeFieldAsDate('timestamp')." AS daily,
				".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%Y')." AS year,
				(".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%c')." ".($Database['dbtype'] == 'mysql' ? "- 1" : "").") AS month,
				".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%e')." AS day,
				COUNT(id) AS ipperday 
			FROM hm_fwban 
			WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())."
			GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')."
			".($Database['dbtype'] == 'mysql' ? "ORDER BY ".DBCastDateTimeFieldAsDate('timestamp')." ASC" : "")."
		) AS a
		LEFT JOIN
		(
			SELECT 
				".DBCastDateTimeFieldAsDate('timestamp')." AS daily, 
				COUNT(DISTINCT(ipaddress)) AS blockperday  
			FROM hm_fwban_rh 
			WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())." 
			GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')."
		) AS b
		ON a.daily = b.daily
		ORDER BY a.daily
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if (is_null($row['blockperday'])){$blockperday = 0;} else {$blockperday = $row['blockperday'];}
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday'].", ".$blockperday."],";
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
				},
		},
	  });
}	
</script>
