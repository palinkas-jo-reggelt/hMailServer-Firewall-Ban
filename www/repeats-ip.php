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

	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	$total_pages_sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(".DBCastDateTimeFieldAsDate('timestamp').")) 
		FROM hm_fwban_demo_rh 
		WHERE ipaddress='{$repeatIP}'
	");
	$total_pages_sql->execute();
	$total_rows = $total_pages_sql->fetchColumn();
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$total_hits_sql = $pdo->prepare("
		SELECT 
			COUNT(ipaddress) 
		FROM hm_fwban_demo_rh 
		WHERE ipaddress='{$repeatIP}'
	");
	$total_hits_sql->execute();
	$total_hits = $total_hits_sql->fetchColumn();
	
	$sql = $pdo->prepare("
		SELECT
			a.day,
			a.ipaddress,
			b.ban_reason,
			b.country,
			a.countip
		FROM
		(
			SELECT 
				ipaddress, 
				COUNT(ipaddress) AS countip, 
				".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%y/%m/%d')." AS day
			FROM hm_fwban_demo_rh
			WHERE ipaddress = '{$repeatIP}'
			GROUP BY ".DBFormatDate(DBCastDateTimeFieldAsDate('timestamp'), '%y/%m/%d').", ipaddress
		) AS a
		JOIN
		(
			SELECT 
				ipaddress, 
				country, 
				ban_reason
			FROM hm_fwban_demo
		) AS b
		ON a.ipaddress = b.ipaddress
		GROUP BY a.day, a.ipaddress, b.ban_reason, b.country, a.countip 
		".DBLimitRowsWithOffset('a.day','DESC',0,0,$offset,$no_of_records_per_page)
	);
	$sql->execute();

	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
		
	if ($total_rows == 0){
		echo "<br><br>There are no repeat dropped IPs to report.";
	} else {
		echo "IP <b>".$repeatIP."</b> denied access ".number_format($total_hits)." times over ".number_format($total_rows)." day".$singular.". (Page: ".number_format($page)." of ".number_format($total_pages).")<br>";
		echo "<table class='section'>
			<tr>
				<th>Date</th>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>FB</th>
			</tr>";

		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr>";
			echo "<td>".$row['day']."</td>";
			echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$row['ban_reason']."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			echo "<td style=\"text-align:right;\"><a href=\"./repeats-ip-day.php?submit=Search&date=".$row['day']."&repeatIP=".$row['ipaddress']."\">".number_format($row['countip'])."</td>";
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "<br><br>";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=".$total_pages."\">Last</a></li>";}
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