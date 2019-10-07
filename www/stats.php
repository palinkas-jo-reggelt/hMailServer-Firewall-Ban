<?php
// Fill in variables
$m_host="localhost";
$m_dbuser="root";
$m_dbpass="supersecretpassword";
$m_db="hmailserver";

	$con=mysqli_connect($m_host,$m_dbuser,$m_dbpass,$m_db);
	if (mysqli_connect_errno()){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
		die();
	}
?>
<!DOCTYPE html> 
<html>
<head>
<title>hMailServer Firewall Ban</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet"> 
<style type="text/css">
body {
	background: #fefefe;
	font-family: "Roboto";
	font-size: 12pt;
	}

.header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    color: #000;
	background: #fefefe;
    z-index: 1;
    overflow: hidden;
    text-align:center;
	}

.header h1 {
	font-size:25px;
    font-weight:normal;
	margin:0 auto;
	}

.wrapper {
	max-width: 920px;
	position: relative;
	margin: 30px auto 30px auto;
	padding-top: 20px;
	}

.clear {
	clear: both;
	}

.banner {
	width: 100%;
	}

.section {
	padding: 5px 0 15px 0;
	margin: 0;
	}

.section h2 {
	font-size:16px;
    font-weight:bold;
	text-align:left;
	}

.secleft {
	float: left;
	width: 49%;
	padding-right: 3px;
	}

.secright {
	float: right;
	width: 49%;
	padding-left: 3px;
	}

table.section {
	border-collapse: collapse;
	border: 1px solid black;
	width: 100%;
	font-size: 10pt;
	}
	
table.section tr:nth-child(even) {
    background-color: #F8F8F8;
	}

table.section th, table.section td {
	border: 1px solid black;
	}

.footer {
	width: 100%;
	text-align: center;
	}
	
@media only screen and (max-width: 629px) {
	.secleft {
		float: none ;
		width: 100% ;
		padding: 0 0 10px 0;
		text-align: left;
	}
	.secright {
		float: none ;
		width: 100% ;
	}
}	
</style>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
	$query = "
		SELECT 
			a.daily,
			a.year,
			a.month,
			a.day,
			a.ipperday,
			b.blockperday
		FROM
		(
			SELECT DATE(timestamp) AS daily, DATE_FORMAT(timestamp, '%Y') AS year, (DATE_FORMAT(timestamp, '%c') - 1) AS month, DATE_FORMAT(timestamp, '%e') AS day, COUNT(id) AS ipperday 
				FROM hm_fwban 
				WHERE DATE(timestamp) < DATE(NOW())
				GROUP BY DATE(timestamp)
				ORDER BY DATE(timestamp) ASC
		) AS a
		LEFT JOIN
		(
			SELECT DATE(timestamp) AS daily, COUNT(DISTINCT(ipaddress)) AS blockperday  
				FROM hm_fwban_rh 
				WHERE DATE(timestamp) < DATE(NOW()) 
				GROUP BY DATE(timestamp)
		) AS b
		ON a.daily = b.daily
		ORDER BY a.daily
	";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
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
<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('timeofday', 'Hour');
	data.addColumn('number', 'Avg Hits');
	data.addRows([
<?php 
	$query = "SELECT hour, ROUND(AVG(numhits), 1) AS avghits FROM (SELECT DATE(`timestamp`) AS day, HOUR(`timestamp`) AS hour, COUNT(*) as numhits FROM hm_fwban GROUP BY day, hour ) d GROUP BY hour ORDER BY hour ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "[[".$row['hour'].", 0, 0], ".$row['avghits']."],";
	}
?>
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
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart", "line"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Blocks');
	data.addRows([
<?php 
	$query = "SELECT DATE(timestamp) AS daily, DATE_FORMAT(timestamp, '%Y') AS year, (DATE_FORMAT(timestamp, '%c') - 1) AS month, DATE_FORMAT(timestamp, '%e') AS day, COUNT(ipaddress) AS ipperday FROM hm_fwban_rh WHERE DATE(timestamp) < DATE(NOW()) GROUP BY daily ORDER BY daily ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
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
</head>
<body>

<div class="header">
	<div class="banner"><h1>hMailServer Firewall Ban</h1></div>
</div>

<div class="wrapper">
<div class="section">
	<div class="secleft">
		<h2>Hits per day from inception:</h2>
		<div id="chart_combined"></div>
	</div>
	<div class="secleft">
		<h2>Total blocks per day (block frequency):</h2>
		<div id="chart_totalblocksperday"></div>
	</div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Average hits per hour from inception:</h2>
		<div id="chart_hitsperhour"></div>
	</div>
	<div class="secleft">
		<h2>Average blocks per hour from inception:</h2>
		<div id="chart_blocksperhour"></div>
	</div>
</div>

<div class="section">

<?php
	$today = date('Y-m-d');
	$yesterday = date('Y-m-d', strtotime(date('Y-m-d')." -1 day"));
	$twodaysago = date('Y-m-d', strtotime(date('Y-m-d')." -2 day"));
	$threedaysago = date('Y-m-d', strtotime(date('Y-m-d')." -3 day"));
	$fourdaysago = date('Y-m-d', strtotime(date('Y-m-d')." -4 day"));
	$thismonth = date('Y-m');
	$lastmonth = date('Y-m', strtotime(date('Y-m')." -1 month"));
	$twomonthsago = date('Y-m', strtotime(date('Y-m')." -2 month"));
	$threemonthsago = date('Y-m', strtotime(date('Y-m')." -3 month"));
	$fourmonthsago = date('Y-m', strtotime(date('Y-m')." -4 month"));

	echo "<div class=\"secleft\">";
	echo "<h2>This Week's Daily Hits:</h2>";

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits Today<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits Yesterday<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits on ".date("l", strtotime($twodaysago))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits on ".date("l", strtotime($threedaysago))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits on ".date("l", strtotime($fourdaysago))."<br />"; }

	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";

	echo "<h2>This Year's Monthly Hits:</h2>";

	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$thismonth}-01 00:00:00' AND NOW()";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits so far this month<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$lastmonth}-01 00:00:00' AND '{$thismonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($lastmonth))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$twomonthsago}-01 00:00:00' AND '{$lastmonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($twomonthsago))."<br />"; }

	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$threemonthsago}-01 00:00:00' AND '{$twomonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($threemonthsago))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$fourmonthsago}-01 00:00:00' AND '{$threemonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($fourmonthsago))."<br />"; }

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";


	$sql = "SELECT `country`, COUNT(`country`) AS `value_occurrence` FROM `hm_fwban` GROUP BY `country` ORDER BY `value_occurrence` DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql);
	echo "<div class=\"secleft\">";
	echo "<h2>Top 5 spammer countries:</h2>";
	while($row = mysqli_fetch_array($res_data)){
		if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
		echo $row['country']." with ".number_format($row['value_occurrence'])." hit".$singular."<br />";
	}
	echo "<br />";
	echo "</div>";

	$num_dups_sql = "SELECT count(*) AS duplicate_count FROM ( SELECT ipaddress FROM hm_fwban GROUP BY ipaddress HAVING COUNT(ipaddress) > 1 ) AS t";
	$result = mysqli_query($con,$num_dups_sql);
	$num_dups = mysqli_fetch_array($result)[0];
	$sql = "SELECT ipaddress, COUNT(ipaddress) AS dupip, MAX(DATE_FORMAT(timestamp, '%y/%c/%e')) AS dupdate, country FROM hm_fwban GROUP BY ipaddress HAVING dupip > 1 ORDER BY dupdate DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql);
	echo "<div class=\"secright\">";
	echo "<h2>Last 5 duplicate IPs:</h2>";
	if ($num_dups == 0){
		echo "There are no duplicate IPs to report.<br /><br />";
	}else{
		while($row = mysqli_fetch_array($res_data)){
			echo $row['ipaddress']." with ".$row['dupip']." hits last seen ".$row['dupdate']."<br />";
		}
		if ($num_dups > 5){echo "<br />There are ".$num_dups." duplicate entries in total.<br /><br />";}
	}
	echo "</div><div class=\"clear\"></div>";
	

	echo "<div class=\"secleft\">";
	echo "<h2>Ban Reasons:</h2>";
	$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban` GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){
		if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
		echo number_format($row['value_occurrence'])." hit".$singular." for ".$row['ban_reason']."<br />";
	}
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>IPs Released From Firewall:</h2>";
	$sqlcount = "SELECT COUNT(*) FROM `hm_fwban` WHERE (flag=1 OR flag=2)";
	$res_count = mysqli_query($con,$sqlcount);
	$total_rows = mysqli_fetch_array($res_count)[0];
	if ($total_rows > 0) { 
		$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban` WHERE (flag=1 OR flag=2) GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
		$res_data = mysqli_query($con,$sql);
		while($row = mysqli_fetch_array($res_data)){
		if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
		echo number_format($row['value_occurrence'])." IP".$singular." triggered by ".$row['ban_reason']." released<br />";
		}
	} else {
		echo "There are no released IPs to report.";
	}
	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

	echo "<div class=\"secleft\">";
	echo "<h2>Ban Enforcement:</h2>";
	echo "<table>";
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban`WHERE flag IS NULL OR flag=1";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Total number of IPs banned</td></tr>"; }

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=1";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<tr><td style=\"text-align:right\">-".number_format($row['value_occurrence'])."</td><td>Number of IPs released from firewall</td></tr>"; }

	echo "<tr><td style=\"text-align:right\">--------</td><td></td></tr>";

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag IS NULL";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Number of IPs currently banned by firewall rule</td></tr>"; }
	
	echo "</table>";
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>Special:</h2>";
	echo "<h2>IP Ranges banned:</h2>";
	$sql = "SELECT COUNT(`ipaddress`) AS `value_occurrence` FROM `hm_fwban` WHERE `ipaddress` LIKE '%.0/24'";
	$res_data = mysqli_query($con,$sql);
	$total_rows = mysqli_fetch_array($res_data)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo $total_rows." hit".$singular." for CIDR bans (0.0.255.0/24 IP ranges).<br />";

	echo "<br />";

	echo "<h2>IPs Marked Safe:</h2>";
	$sql = "SELECT COUNT(`ipaddress`) AS `value_occurrence` FROM `hm_fwban` WHERE `flag`=5 OR `flag`=6";
	$res_data = mysqli_query($con,$sql);
	$total_rows = mysqli_fetch_array($res_data)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo $total_rows." hit".$singular." for permanently released (SAFE) IPs.<br />";
	

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

	echo "<div class=\"secleft\">";
	echo "<h2>Unprocessed IPs:</h2>";
	echo "IPs that have been recently added or marked for release or reban that have not yet been processed by the scheduled task to have their firewall rule added or deleted.<br /><br />";

	$sql_new = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=4";
	$res_data_new = mysqli_query($con,$sql_new);
	$total_rows = mysqli_fetch_array($res_data_new)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo number_format($total_rows)." IP".$singular." recently added<br />";

	$sql_rel = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=2";
	$res_data_rel = mysqli_query($con,$sql_rel);
	$total_rows = mysqli_fetch_array($res_data_rel)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo number_format($total_rows)." IP".$singular." marked for release<br />";

	$sql_reb = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=3";
	$res_data_reb = mysqli_query($con,$sql_reb);
	$total_rows = mysqli_fetch_array($res_data_reb)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo number_format($total_rows)." IP".$singular." marked for reban<br />";

	$sql_reb = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=5";
	$res_data_reb = mysqli_query($con,$sql_reb);
	$total_rows = mysqli_fetch_array($res_data_reb)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo number_format($total_rows)." IP".$singular." marked for SAFE list<br />";

	$sql_reb = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=7";
	$res_data_reb = mysqli_query($con,$sql_reb);
	$total_rows = mysqli_fetch_array($res_data_reb)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo number_format($total_rows)." IP".$singular." marked for SAFE list removal<br />";

	echo "<br />";
	echo "</div>";

	$num_repeats_sql = "SELECT COUNT(DISTINCT(ipaddress)) FROM hm_fwban_rh";
	$result = mysqli_query($con,$num_repeats_sql);
	$num_repeats = mysqli_fetch_array($result)[0];
	$sql_repeats = "SELECT COUNT(ipaddress) AS countip, ipaddress FROM hm_fwban_rh GROUP BY ipaddress ORDER BY countip DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql_repeats);
	echo "<div class=\"secright\">";
	echo "<h2>Top 5 Repeat Spammers:</h2>";
	echo "Parsed from the firewall log dropped connections: IPs that knocked on the door but couldn't get in.<br /><br />";
	if ($num_repeats == 0){
		echo "There are no repeat firewall drops to report.<br /><br />";
	}else{
		while($row = mysqli_fetch_array($res_data)){
			if ($row['countip']==1){$singular="";}else{$singular="s";}
			$sql_country = "SELECT country FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'";
			$res_country = mysqli_query($con,$sql_country);
			$country = mysqli_fetch_array($res_country)[0];
			echo number_format($row['countip'])." knock".$singular." by ".$row['ipaddress']." from ".$country."<br />";
		}
		if ($num_repeats > 5){
			$res_total_repeat_count = mysqli_query($con,"SELECT COUNT(ipaddress) FROM hm_fwban_rh");
			$total_repeats = mysqli_fetch_array($res_total_repeat_count)[0];
			echo "<br />".number_format($num_repeats)." IPs have repeatedly attempted to gain access unsuccessfully a total of ".number_format($total_repeats)." times.<br /><br />";}
	}

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

?>
</div>

<br /><br />

<div class="footer">

</div>

</div> <!-- end WRAPPER -->
</body>
</html>