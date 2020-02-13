<!--
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

******                                         ******
******              STATS.PHP                  ******
******                                         ******

Information file for public display. Contains no links 
nor any way of manipulating the firewall. For informational
purposes only. 

Fill in the database variables and place in any PHP/web accessible
folder.

-->
<?php

/* Database Variables 

	'dbtype' = database server type
	
		For MySQL use dbtype = 'mysql'
		For MSSQL use dbtype = 'mssql'

	'driver' = connection type
	
		For MySQL use driver = 'mysql'
		For MSSQL use driver = 'mssql'
		For ODBC  use driver = 'odbc'
		
		* When opting for ODBC use correct DSN! *
		* Example: "MariaDB ODBC 3.0 Driver".   *
		* Exact spelling is critical!           *
	
*/

$Database = array (
	'dbtype'      => 'mysql',
	'host'        => 'localhost',
	'username'    => 'hmailserver',
	'password'    => 'supersecretpassword',
	'dbname'      => 'hmailserver',
	'driver'      => 'mysql',
	'port'        => '3306',
	'dsn'         => 'MariaDB ODBC 3.0 Driver'
);

?>

<?php

	If ($Database['driver'] == 'mysql') {
		$pdo = new PDO("mysql:host=".$Database['host'].";port=".$Database['port'].";dbname=".$Database['dbname'], $Database['username'], $Database['password']);
	} ElseIf ($Database['driver'] == 'mssql') {
		$pdo = new PDO("sqlsrv:Server=".$Database['host'].",".$Database['port'].";Database=".$Database['dbname'], $Database['username'], $Database['password']);
	} ElseIf ($Database['driver'] == 'odbc') {
		$pdo = new PDO("odbc:Driver={".$Database['dsn']."};Server=".$Database['host'].";Port=".$Database['port'].";Database=".$Database['dbname'].";User=".$Database['username'].";Password=".$Database['password'].";");
	} Else {
		echo "Configuration Error - No database driver specified";
	}

	function ipRangeFinder($cidr) {
	   $range = array();
	   $cidr = explode('/', $cidr);
	   $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
	   $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
	   return $range;
	}

	// $orderBy2 is sub-order to $orderBy1
	Function DBLimitRowsWithOffset($orderBy1, $orderByDir1, $orderBy2, $orderByDir2, $offset, $numRows){
		global $Database;
		$QueryLimit = "";
		
		if (($orderBy1 === 0) && ($orderByDir1 === 0) && ($orderBy2 === 0) && ($orderByDir2 === 0)){
			$orderStmt = " ORDER BY 1";
		} elseif (($orderBy2 === 0) && ($orderByDir2 === 0)){
			$orderStmt = " ORDER BY ".$orderBy1." ".$orderByDir1;
		} else {
			$orderStmt = " ORDER BY ".$orderBy1." ".$orderByDir1.", ".$orderBy2." ".$orderByDir2;
		}
		
		if ($Database['dbtype'] == 'mysql') {
			$QueryLimit = " ".$orderStmt." LIMIT ".$offset.", ".$numRows;
		} elseif ($Database['dbtype'] == 'mssql') {
			$QueryLimit = " ".$orderStmt." OFFSET ".$offset." ROWS FETCH NEXT ".$numRows." ROWS ONLY";
		} else {
			$QueryLimit = "";
		}
		return $QueryLimit;
	}

	Function DBGetCurrentDateTime(){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "NOW()";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "GETDATE()";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsDate($fieldName){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "DATE(".$fieldName.")";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "CAST(".$fieldName." AS DATE)";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsHour($fieldName){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "HOUR(".$fieldName.")";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = DBFormatDate($fieldName, '%H');
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsMonth($fieldName){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "MONTH(".$fieldName.")";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = DBFormatDate($fieldName, '%c');
		}
		return $Return;
	}

	Function DBFormatDate($fieldName, $formatSpecifier){
		global $Database;
		$Return = "";

		$dateFormatSpecifiers = array (
			'%Y'                => 'yyyy',
			'%c'                => 'MM',
			'%e'                => 'dd',
			'Y-m-d'             => 'yyyy-MM-dd',
			'%y/%m/%d'          => 'yyyy/MM/dd',
			'Y-m'               => 'yyyy-MM',
			'%Y-%m'             => 'yyyy-MM',
			'%y/%m/%d %T'		=> 'yyyy-MM-dd HH:mm:ss',
			'%Y/%m/%d %T'       => 'yyyy-MM-dd HH:mm:ss',
			'%Y/%m/01'          => 'yyyy-MM-01',
			'%y/%c/%e'          => 'yyyy/MM/dd',
			'%H'				=> 'HH',
		);

		if ($Database['dbtype'] == 'mysql') {
			$Return = "DATE_FORMAT(".$fieldName.", '".$formatSpecifier."')";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "FORMAT(".$fieldName.", '".$dateFormatSpecifiers[$formatSpecifier]."', 'en-US')";
		}
		return $Return;
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
				(".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%c')." ".($Database['dbtype'] == 'mysql' ? "- 1" : ""). ") AS month,
				".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%e')." AS day,
				COUNT(id) AS ipperday 
			FROM hm_fwban 
			WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())."
			GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')."
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
<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('timeofday', 'Hour');
	data.addColumn('number', 'Avg Hits');
	data.addRows([
<?php 

	$sql = $pdo->prepare("
		SELECT 
			hour, 
			ROUND(AVG(numhits), 1) AS avghits 
		FROM (
			SELECT 
				".DBCastDateTimeFieldAsDate('timestamp')." AS day, 
				".DBCastDateTimeFieldAsHour('timestamp')." AS hour, 
				COUNT(*) as numhits 
			FROM hm_fwban 
			GROUP BY ".DBCastDateTimeFieldAsDate('timestamp').", ".DBCastDateTimeFieldAsHour('timestamp')." 
		) d 
		GROUP BY hour 
		ORDER BY hour ASC
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
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

	$sql = $pdo->prepare("
		SELECT 
			hour, 
			ROUND(AVG(numhits), 1) AS avghits 
		FROM (
			SELECT 
				".DBCastDateTimeFieldAsDate('timestamp')." AS day, 
				".DBCastDateTimeFieldAsHour('timestamp')." AS hour, 
				COUNT(*) as numhits 
			FROM hm_fwban_rh 
			GROUP BY ".DBCastDateTimeFieldAsDate('timestamp').", ".DBCastDateTimeFieldAsHour('timestamp')."
		) d 
		GROUP BY hour 
		ORDER BY hour ASC
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
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

	$sql = $pdo->prepare("
		SELECT 
			".DBCastDateTimeFieldAsDate('timestamp')." AS daily, 
			".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%Y')." AS year,
			(".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%c')." - 1) AS month,
			".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%e')." AS day,
			COUNT(DISTINCT(ipaddress)) AS ipperday 
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
<style type="text/css" media="screen">
body {
	background: #fefefe;
	font-family: "Roboto";
	font-size: 12pt;
	}

a:link, a:active, a:visited {
	color: #FF0000;
	text-transform: underline;
	}

a:hover {
	color: #FF0000;
	text-transform: none;
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

.header h2 {
	font-size:15px;
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

.headlinks {
	max-width: 720px;
	position:relative;
	margin: 0px auto;
	}

.headlinkwidth {
	width: 100%;
	min-width: 300px;
	position:relative;
	margin: 0 auto;
	}

.headlinks a:link, a:active, a:visited {
	color: #FF0000;
	text-transform: underline;
	}

.headlinks a:hover {
	color: #FF0000;
	text-transform: none;
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

.section h3 {
	font-size:16px;
    font-weight:bold;
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
	
ul {
	list-style-type: none;
	padding: 0;
	}

li {
	padding: 0;
	display: inline;
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
	<div class="secright">
		<h2>Total blocks per day (block frequency):</h2>
		<div id="chart_totalblocksperday"></div>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Average hits per hour from inception:</h2>
		<div id="chart_hitsperhour"></div>
	</div>
	<div class="secright">
		<h2>Average blocks per hour from inception:</h2>
		<div id="chart_blocksperhour"></div>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<!-- START OF DAILY HITS -->
	<div class="secleft">
		<h2>This Week's Daily Hits:</h2>

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

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits Today<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits Yesterday<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits on ".date("l", strtotime($twodaysago))."<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits on ".date("l", strtotime($threedaysago))."<br />";
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits on ".date("l", strtotime($fourdaysago))."<br />"; 
		}

		echo "<br />";
		$mindate_sql = $pdo->prepare("
			SELECT 
				MIN(". DBCastDateTimeFieldAsDate('timestamp') .") AS mindate 
			FROM hm_fwban
		");
		$mindate_sql->execute();
		$mindate = $mindate_sql->fetchColumn();

		if ($mindate > date('Y-m-d', strtotime(date('Y-m-d')." -7 day"))){
			echo "";
		} else {

			$sql = $pdo->prepare("
				SELECT 
					ROUND(AVG(numhits), 0) AS avghits 
				FROM (
					SELECT 
						COUNT(id) as numhits,
						".DBCastDateTimeFieldAsDate('timestamp')." as timestamp
					FROM hm_fwban 
					WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())."
					GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')."
					".DBLimitRowsWithOffset(DBCastDateTimeFieldAsDate('timestamp'),'DESC',0,0,0,7)."
				) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Daily average last 7 days: ".number_format($row['avghits'])." hits<br />"; 
			}
		}

		if ($mindate > date('Y-m-d', strtotime(date('Y-m-d')." -30 day"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
			SELECT 
				ROUND(AVG(numhits), 0) AS avghits 
			FROM (
				SELECT 
					COUNT(id) as numhits,
					".DBCastDateTimeFieldAsDate('timestamp')." timestamp
				FROM hm_fwban 
				WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())."
				GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')."
				".DBLimitRowsWithOffset(DBCastDateTimeFieldAsDate('timestamp'),'DESC',0,0,0,30)."
			) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Daily average last 30 days: ".number_format($row['avghits'])." hits<br />"; 
			}
		}
	?>
	<br />
	</div> 
	<!-- END OF DAILY HITS -->
	
	
	<!-- START MONTHLY HITS -->
	<div class="secright">
		<h2>This Year's Monthly Hits:</h2>

	<?php

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$thismonth}-01 00:00:00' AND ".DBGetCurrentDateTime()."
			GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits so far this month<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$lastmonth}-01 00:00:00' AND '{$thismonth}-01 00:00:00'
			GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($lastmonth))."<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$twomonthsago}-01 00:00:00' AND '{$lastmonth}-01 00:00:00'
			GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($twomonthsago))."<br />"; 
		}

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$threemonthsago}-01 00:00:00' AND '{$twomonthsago}-01 00:00:00'
			GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($threemonthsago))."<br />";
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$fourmonthsago}-01 00:00:00' AND '{$threemonthsago}-01 00:00:00'
			GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo number_format($row['value_occurrence'])." Hits in ".date("F", strtotime($fourmonthsago))."<br />"; 
		}

		echo "<br />";
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-1')." -3 month"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
				SELECT 
					ROUND(AVG(numhits), 0) AS avghits 
				FROM (
					SELECT 
						COUNT(id) as numhits,
						".DBCastDateTimeFieldAsMonth('timestamp')." AS timestamp
					FROM hm_fwban 
					WHERE ".DBCastDateTimeFieldAsMonth('timestamp')." < ".DBFormatDate(DBGetCurrentDateTime(), '%Y/%m/01')."
					GROUP BY ".DBCastDateTimeFieldAsMonth('timestamp')."
					".DBLimitRowsWithOffset(DBCastDateTimeFieldAsMonth('timestamp'),'DESC',0,0,0,3)."
				) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Monthly average last 3 months: ".number_format($row['avghits'])." hits<br />"; 
			}
		}

		if ($mindate > date('Y-m-d', strtotime(date('Y-m-1')." -6 month"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
				SELECT 
					ROUND(AVG(numhits), 0) AS avghits 
				FROM (
					SELECT 
						COUNT(id) as numhits,
						".DBCastDateTimeFieldAsMonth('timestamp')." AS timestamp
					FROM hm_fwban 
					WHERE ".DBCastDateTimeFieldAsMonth('timestamp')." < ".DBFormatDate(DBGetCurrentDateTime(), '%Y/%m/01')."
					GROUP BY ".DBCastDateTimeFieldAsMonth('timestamp')."
					".DBLimitRowsWithOffset(DBCastDateTimeFieldAsMonth('timestamp'),'DESC',0,0,0,6)."
				) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Monthly average last 6 months: ".number_format($row['avghits'])." hits<br />"; 
			}
		}
	?>
	<br />
	</div> 
	<div class="clear"></div>
	<!-- END OF MONTHLY HITS -->
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF TOP 5 SPAMMER COUNTRIES -->
	<div class="secleft">
		<h2>Top 5 spammer countries:</h2>

	<?php
		$sql = $pdo->prepare("
			SELECT 
				country, 
				COUNT(country) AS value_occurrence 
			FROM hm_fwban 
			GROUP BY country 
			".DBLimitRowsWithOffset('value_occurrence','DESC',0,0,0,5)."
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
			echo $row['country']." with ".number_format($row['value_occurrence'])." hit".$singular.".<br />";
		}
	?>
	<br />
	</div> 
	<!-- END OF TOP 5 SPAMMER COUNTRIES -->
	

	<!-- START OF LAST 5 DUPLICATES -->
	<div class="secright">
		<h2>Last 5 duplicate IPs:</h2>

	<?php
		$num_dups_sql = $pdo->prepare("
			SELECT 
				COUNT(*) AS duplicate_count 
			FROM ( 
				SELECT ipaddress 
				FROM hm_fwban 
				GROUP BY ipaddress 
				HAVING COUNT(ipaddress) > 1 
			) AS t
		");
		$num_dups_sql->execute();
		$num_dups = $num_dups_sql->fetchColumn();

		$sql = $pdo->prepare("
			SELECT 
				ipaddress, 
				COUNT(ipaddress) AS dupip, 
				MAX(".DBFormatDate('timestamp', '%y/%c/%e').") AS dupdate, 
				country 
			FROM hm_fwban 
			GROUP BY ipaddress, country
			HAVING dupip > 1 
			".DBLimitRowsWithOffset('dupdate','DESC',0,0,0,5)
		);
		$sql->execute();
		if ($num_dups == 0){
			echo "There are no duplicate IPs to report.<br /><br />";
		}else{
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo $row['ipaddress']." with ".$row['dupip']." hits last seen ".$row['dupdate']."<br />";
			}
		}
	?>
	<br />
	</div> 
	<!-- END OF LAST 5 DUPLICATES -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->
	

<div class="section">
	<!-- START OF BAN REASONS -->
	<div class="secleft">
		<h2>Ban Reasons:</h2>

	<?php
		$sql = $pdo->prepare("
			SELECT 
				ban_reason, 
				COUNT(ban_reason) AS value_occurrence 
				FROM hm_fwban 
				GROUP BY ban_reason 
				ORDER BY value_occurrence DESC
			");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
			echo number_format($row['value_occurrence'])." hit".$singular." for ".$row['ban_reason']."<br />";
		}
	?>
	<br />
	</div>
	<!-- END OF BAN REASONS -->


	<!-- START OF RELEASED IPS -->
	<div class="secright">
		<h2>IPs Released From Firewall:</h2>

	<?php
		$sqlcount = $pdo->prepare("
			SELECT 
				COUNT(*) 
			FROM hm_fwban 
			WHERE (flag=1 OR flag=2 OR flag=5 OR flag=6)
		");
		$sqlcount->execute();
		$total_rows = $sqlcount->fetchColumn();
		if ($total_rows > 0) { 
			$sql = $pdo->prepare("
				SELECT 
					ban_reason, 
					COUNT(ban_reason) AS value_occurrence, 
					flag 
				FROM hm_fwban 
				WHERE (flag=1 OR flag=2 OR flag=5 OR flag=6) 
				GROUP BY ban_reason, flag
				ORDER BY value_occurrence DESC
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
				echo number_format($row['value_occurrence'])." IP".$singular." triggered by ".$row['ban_reason']." released.<br />";
			}
		} else {
			echo "There are no released IPs to report.";
		}
	?>
	<br />
	</div> 
	<!-- END OF RELEASED IPS -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF BAN ENFORCEMENT -->
	<div class="secleft">
		<h2>Ban Enforcement:</h2>

	<?php
		echo "<table>";
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
				FROM hm_fwban
			");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Total number of IPs banned</td></tr>"; 
		}

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=1 OR flag=2 OR flag=5 OR flag=6
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr><td style=\"text-align:right\">-".number_format($row['value_occurrence'])."</td><td>Number of IPs released from firewall</td></tr>"; 
		}

		echo "<tr><td style=\"text-align:right\">--------</td><td></td></tr>";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
				FROM hm_fwban 
				WHERE flag IS NULL OR flag=3 OR flag=4 OR flag=7
			");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Number of IPs currently banned by firewall rule</td></tr>"; 
		}
		
		echo "</table>";
	?>
	<br />
	</div>
	<!-- END OF BAN ENFORCEMENT -->


	<!-- START OF IPS MARKED SAFE -->
	<div class="secright">
		<h2>IPs Marked Safe:</h2>

	<?php
		$sql = $pdo->prepare("
			SELECT 
				COUNT(ipaddress) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=5 OR flag=6
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo $total_rows." hit".$singular." for permanently released (SAFE) IPs.<br />";
	?>
	<br />
	</div> 
	<!-- END OF IPS MARKED SAFE -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF UNPROCESSED IPS -->
	<div class="secleft">
		<h2>Unprocessed IPs:</h2>
		IPs that have been recently added or marked for release or reban that have not yet been processed by the scheduled task to have their firewall rule added or deleted.<br /><br />

	<?php
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=4
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo number_format($total_rows)." IP".$singular." recently added<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=2
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo number_format($total_rows)." IP".$singular." marked for release<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=3
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo number_format($total_rows)." IP".$singular." marked for reban<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=5
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo number_format($total_rows)." IP".$singular." marked for SAFE list<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=7
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo number_format($total_rows)." IP".$singular." marked for SAFE list removal<br />";
	?>
	<br />
	</div>
	<!-- END OF UNPROCESSED IPS -->


	<!-- START OF TOP 5 REPEAT SPAMMERS -->
	<div class="secright">
		<h2>Top 5 Repeat Spammers:</h2>
		Parsed from the firewall log dropped connections: IPs that knocked on the door but couldn't get in.<br /><br />

	<?php
		$num_repeats_sql = $pdo->prepare("
			SELECT 
				COUNT(DISTINCT(ipaddress)) 
			FROM hm_fwban_rh
		");
		$num_repeats_sql->execute();
		$num_repeats = $num_repeats_sql->fetchColumn();

		$sql_repeats = $pdo->prepare("
			SELECT
				a.ipaddress,
				a.countip,
				b.country
			FROM
			(
				SELECT 
					COUNT(ipaddress) AS countip, 
					ipaddress
				FROM hm_fwban_rh 
				GROUP BY ipaddress 
			) AS a
			JOIN
			(
				SELECT ipaddress, country 
				FROM hm_fwban
			) AS b
			ON a.ipaddress = b.ipaddress
			".DBLimitRowsWithOffset('countip','DESC',0,0,0,5)."
		");
		$sql_repeats->execute();
		if ($num_repeats == 0){
			echo "There are no repeat firewall drops to report.<br /><br />";
		}else{
			while($row = $sql_repeats->fetch(PDO::FETCH_ASSOC)){
				if ($row['countip']==1){$singular="";}else{$singular="s";}
				echo number_format($row['countip'])." knock".$singular." by ".$row['ipaddress']." from ".$row['country']."<br />";
			}
			if ($num_repeats > 5){
				$sql_num_repeats = $pdo->prepare("
					SELECT 
						COUNT(ipaddress) 
					FROM hm_fwban_rh
				");
				$sql_num_repeats->execute();
				$total_repeats = $sql_num_repeats->fetchColumn();
				echo "<br />".number_format($num_repeats)." IPs have repeatedly attempted to gain access unsuccessfully a total of ".number_format($total_repeats)." times.<br /><br />";}
		}
	?>
	<br />
	</div> 
	<!-- END OF TOP 5 REPEAT SPAMMERS -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->

<br /><br />

<div class="footer">

</div>

</div> <!-- end WRAPPER -->
</body>
</html>