<?php include("head.php") ?>

<div class="section">
<h2>Search Repeat Hits (Connections Dropped at Firewall)</h2>

<?php include("cred.php") ?>
<?php

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
	if (isset($_GET['days'])) {$days = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['days'])));} else {$days = 0;}
	if (isset($_GET['search'])) {$search = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['search'])));} else {$search = "";}
	if ($search==""){$search_sql="";}else{$search_sql=" WHERE a.ipaddress LIKE '{$search}%' OR b.country LIKE '{$search}%' OR b.ban_reason LIKE '{$search}%'";}
	if ($search==""){$search_page="";}else{$search_page="&search=".$search;}
	if ($search==""){$search_list="";}else{$search_list=" matching <b>\"".$search."\"</b>";}
	if ($search==""){$search_all="All ";}else{$search_all="";}
  
	// echo "<div class='section'>";
	// echo "<form action='blocks-view.php' method='GET'> ";
	// echo	"<input type='text' size='20' name='search' placeholder='Search...' value='".$search."'>";
	// echo	" ";
	// echo	"<input type='submit' name='submit' value='Search' >";
	// echo "</form>";
	// echo "</div>";
	// echo "<div class='section'>";

	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	$total_pages_sql = "
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
					COUNT(DISTINCT(DATE(timestamp))) AS countdate 
				FROM hm_fwban_rh 
				GROUP BY ipaddress 
				HAVING countdate > ".($days - 1)."
			) AS a
			LEFT JOIN
			(
				SELECT ipaddress, country, ban_reason
				FROM hm_fwban
			) AS b
			ON a.ipaddress = b.ipaddress
		) AS returnhits
	";
	$result = mysqli_query($con,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = "
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
				COUNT(DISTINCT(DATE(timestamp))) AS countdate 
			FROM hm_fwban_rh 
			GROUP BY ipaddress 
			HAVING countdate > ".($days - 1)."
		) AS a
		LEFT JOIN
		(
			SELECT ipaddress, country, ban_reason
			FROM hm_fwban
		) AS b
		ON a.ipaddress = b.ipaddress
		LIMIT ".$offset.", ".$no_of_records_per_page;

	$res_data = mysqli_query($con,$sql);

	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
	if ($total_rows == 0){
		echo "<br />There are no repeat drops to report for search term <b>\"".$search."\"</b>. Please enter only IP address or date.";
	} else {
		echo $search_all."".number_format($total_rows)." IP".$singular." repeatedly dropped at firewall on at least ".$days." distinct days. (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
		echo "<table class='section'>
			<tr>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>Blocks</th>
			</tr>";

		while($row = mysqli_fetch_array($res_data)){
			echo "<tr>";
			echo "<td><a href=\"./repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$row['ban_reason']."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			if($row['countip']==0){echo "<td style=\"text-align:right;\">0</td>";}
			else{echo "<td style=\"text-align:right;\"><a href=\"repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".number_format($row['countip'])."</a></td>";}
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "<br /><br />";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
	}

	mysqli_close($con);

	echo "<br />";
?>
</div>

<?php include("foot.php") ?>