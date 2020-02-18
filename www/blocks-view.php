<?php include("head.php") ?>

<div class="section">
<h2>Repeat Hits (Connections Dropped at Firewall)</h2>

<?php
	include("config.php");
	include("functions.php");

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		$display_pagination = 1;
	} else {
		$page = 1;
		$total_pages = 1;
		$display_pagination = 0;
	}
	if (isset($_GET['submit'])) {
		$button = $_GET ['submit'];
	} else {
		$button = "";
	}
	if (isset($_GET['days'])) {$days = $_GET['days'];} else {$days = 0;}
	if ($days==""){$days_page="";}else{$days_page="&days=".$days;}

	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;

	$total_pages_sql = $pdo->prepare("
		SELECT COUNT(*) AS countips 
		FROM (
			SELECT
				a.ipaddress,
				b.ban_reason,
				b.country,
				a.countip
			FROM
			(
				SELECT 
					ipaddress, 
					COUNT(ipaddress) AS countip,
					COUNT(DISTINCT(".DBCastDateTimeFieldAsDate('timestamp').")) AS countdate 
				FROM hm_fwban_rh 
				GROUP BY ipaddress 
				HAVING COUNT(DISTINCT(".DBCastDateTimeFieldAsDate('timestamp').")) > ".($days - 1)."
			) AS a
			LEFT JOIN
			(
				SELECT 
					ipaddress, 
					country, 
					ban_reason
				FROM hm_fwban
			) AS b
			ON a.ipaddress = b.ipaddress
		) AS returnhits
	");
	$total_pages_sql->execute();
	$total_rows = $total_pages_sql->fetchColumn();
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = $pdo->prepare("
		SELECT
			a.ipaddress,
			b.ban_reason,
			b.country,
            a.countip
		FROM
		(
			SELECT 
				ipaddress, 
            	COUNT(ipaddress) AS countip,
				COUNT(DISTINCT(".DBCastDateTimeFieldAsDate('timestamp').")) AS countdate 
			FROM hm_fwban_rh 
			GROUP BY ipaddress 
			HAVING COUNT(DISTINCT(".DBCastDateTimeFieldAsDate('timestamp').")) > ".($days - 1)."
		) AS a
		LEFT JOIN
		(
			SELECT 
				ipaddress, 
				country, 
				ban_reason
			FROM hm_fwban
		) AS b
		ON a.ipaddress = b.ipaddress
		".DBLimitRowsWithOffset('countip','DESC',0,0,$offset,$no_of_records_per_page)
	);
	$sql->execute();

	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
	if ($total_rows == 0){
		echo "<br>There are no IPs repeatedly dropped at the firewall at least <b>".$days."</b> days.";
	} else {
		echo number_format($total_rows)." IP".$singular." repeatedly dropped at firewall on at least ".$days." distinct days. (Page: ".number_format($page)." of ".number_format($total_pages).")<br>";
		echo "<table class='section'>
			<tr>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>Blocks</th>
			</tr>";

		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr>";
			echo "<td>".$row['ipaddress']."</td>";
			echo "<td>".$row['ban_reason']."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			if($row['countip']==0){echo "<td style=\"text-align:right;\">0</td>";}
			else{echo "<td style=\"text-align:right;\"><a href=\"repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".number_format($row['countip'])."</a></td>";}
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "<br><br>";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search".$days_page."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search".$days_page."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search".$days_page."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search".$days_page."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
	}

	echo "<br>";
?>
</div>

<?php include("foot.php") ?>