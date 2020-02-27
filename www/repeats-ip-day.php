<?php include("head.php") ?>

<div class="section">

<?php
	include_once("config.php");
	include_once("functions.php");

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		$display_pagination = 1;
	} else {
		$page = 1;
		$total_pages = 1;
		$display_pagination = 0;
	}
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['repeatIP'])) {$repeatIP = $_GET['repeatIP'];} else {$repeatIP = "";}
  	if (isset($_GET['date'])) {$date = $_GET['date'];} else {$date = "";}

	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;

	$total_pages_sql = $pdo->prepare("
		SELECT 
			COUNT(ipaddress)
		FROM (
			SELECT * 
			FROM hm_fwban_rh 
			WHERE '".$date." 00:00:00' <= timestamp
		) AS A 
		WHERE timestamp <= '".$date." 23:59:59' AND ipaddress = '{$repeatIP}'
	");
	$total_pages_sql->execute();
	$total_rows = $total_pages_sql->fetchColumn();
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = $pdo->prepare("
		SELECT
			a.TimeStamp,
			a.ipaddress,
			b.ban_reason,
			b.country
		FROM
		(
			SELECT 
				ipaddress, 
				".DBFormatDate('timestamp', '%y/%m/%d %T')." as TimeStamp
			FROM (
				SELECT * 
				FROM hm_fwban_rh 
				WHERE '".$date." 00:00:00' <= timestamp
			) AS X 
			WHERE timestamp <= '".$date." 23:59:59' AND ipaddress = '{$repeatIP}'
		) AS a
		JOIN
		(
			SELECT 
				ipaddress, 
				country, 
				ban_reason
			FROM hm_fwban
		) AS b
		ON a.ipaddress = b.ipaddress
		".DBLimitRowsWithOffset('a.TimeStamp','DESC',0,0,$offset,$no_of_records_per_page)
	);
	$sql->execute();

	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
		
	if ($total_rows == 0){
		echo "<br><br>There are no repeat dropped IPs to report.";
	} else {
		echo "IP <b>".$repeatIP."</b> denied access ".number_format($total_rows)." time".$singular." on <b>".$date."</b>. (Page: ".number_format($page)." of ".number_format($total_pages).")<br>";
		echo "<table class='section'>
			<tr>
				<th>Date</th>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
			</tr>";

		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr>";
			echo "<td>".$row['TimeStamp']."</td>";
			echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$row['ban_reason']."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "<br><br>";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&date=".$date."&repeatIP=".$repeatIP."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&date=".$date."&repeatIP=".$repeatIP."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&date=".$date."&repeatIP=".$repeatIP."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&date=".$date."&repeatIP=".$repeatIP."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
		if ($total_pages > 0){
			echo	"FB = Firewall Blocks<br>
					RS = Released Status<br>";
		}
	}

	echo "<br>";
?>
</div>

<?php include("foot.php") ?>