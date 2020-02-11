<?php include("head.php") ?>
<div class="section">
<?php
	include("config.php");
	include("functions.php");

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = 1;
	}
	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;

	$total_pages_sql = $pdo->prepare("
		SELECT 
			count(*) AS duplicate_count 
		FROM ( 
			SELECT 
				ipaddress 
			FROM hm_fwban 
			GROUP BY ipaddress 
			HAVING COUNT(ipaddress) > 1 
		) AS t
	");
	$total_pages_sql->execute();
	$total_rows = $total_pages_sql->fetchColumn();
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = $pdo->prepare("
		SELECT 
			ipaddress, 
			COUNT(ipaddress) AS dupip, 
			".DBFormatDate('timestamp', '%Y/%m/%d %T')." AS dupdate,
			country, 
			helo 
		FROM hm_fwban 
		GROUP BY ipaddress, ".DBFormatDate('timestamp', '%Y/%m/%d %T').", country, helo
		HAVING COUNT(ipaddress) > 1 
		".DBLimitRowsWithOffset('dupdate','DESC','dupip','DESC',$offset,$no_of_records_per_page)
	);
	$sql->execute();

	if ($total_rows == 0){
		echo "<br />No duplicate entries found.";
	} else {
		echo "<br />".number_format($total_rows)." Duplicate IP Reports (Page: ".number_format($page)." of ".number_format($total_pages) . ")<br />";
		echo "<table class='section'>
			<tr>
				<th>Last Seen</th>
				<th>IP Address</th>
				<th>Country</th>
				<th>HELO</th>
				<th>Duplicates</th>
			</tr>";
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr>";
			echo "<td>" . $row['dupdate'] . "</td>";
			echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			echo "<td>".$row['helo']."</td>";
			echo "<td style=\"text-align:center;\">" . $row['dupip'] . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		if ($total_pages < 2){
			echo "";
		} else {
			echo "<ul>";
				if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&page=1\">First </a><li>";}
				if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&page=".($page - 1)."\">Prev </a></li>";}
				if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&page=".($page + 1)."\">Next </a></li>";}
				if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
			echo "<br /><br />";
		}
	}
	//mysqli_close($con);
?>
</div>
<?php include("foot.php") ?>