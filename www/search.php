<?php include("head.php") ?>

<div class="section">
To search for a date range <a href="./search-date.php">click here</a>.
</div>

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
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['search'])) {$search = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['search'])));} else {$search = "";}
	if (isset($_GET['RS'])) {$RS = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['RS'])));} else {$RS = "";}

	echo "<div class='section'>";
	echo "<form action='search.php' method='GET'> ";
	echo	"<input type='text' size='20' name='search' placeholder='Search...' value='".$search."'>";
	echo	" ";
	echo	"<select name='RS'>";
	echo		"<option value=''>RS</option>";
	echo		"<option value='YES'>YES</option>";
	echo		"<option value='NO'>NO</option>";
	echo	"</select>";
	echo	" ";
	echo	"<input type='submit' name='submit' value='Search' >";
	echo "</form>";
	echo "</div>";
	echo "<div class='section'>";
  
	// $min_length = 2;
    // if(strlen($search) < $min_length){
		// echo "Please enter a search term of at least 2 characters.";
	// } else {

	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	
	if ($RS=="NO"){$RS_SQL = " AND (flag IS NULL OR flag=3)";}
	elseif ($RS=="YES"){$RS_SQL = " AND (flag=1 OR flag=2)";}
	else {$RS_SQL = "";}
	
	$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE (timestamp LIKE '%{$search}%' OR ipaddress LIKE '%{$search}%' OR ban_reason LIKE '%{$search}%' OR countrycode LIKE '%{$search}%' OR country LIKE '%{$search}%')".$RS_SQL."";
	$result = mysqli_query($con,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban WHERE (timestamp LIKE '%{$search}%' OR ipaddress LIKE '%{$search}%' OR ban_reason LIKE '%{$search}%'OR countrycode LIKE '%{$search}%'OR country LIKE '%{$search}%')".$RS_SQL." ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";
	$res_data = mysqli_query($con,$sql);
	
	if ($RS=="YES"){$RSres=" with release status \"<b>YES</b>\"";} 
	elseif ($RS=="NO"){$RSres=" with release status \"<b>NO</b>\"";} 
	else {$RSres = "";} 
	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
	if ($total_rows == 0){
		echo "No results for \"<b>".$search."</b>\"".$RSres;
	} else {
		if(strlen($search)==''){
		echo "Please enter a search term. <br /><br />";
		echo "All results".$RSres.": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
		} else {
		echo "Results for \"<b>".$search."</b>\"".$RSres.": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
		}
		echo "<table class='section'>
			<tr>
				<th>Timestamp</th>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>RH</th>
				<th>RS</th>
			</tr>";
		while($row = mysqli_fetch_array($res_data)){
			$res_repeat = mysqli_query($con,"SELECT COUNT(ipaddress) FROM hm_fwban_rh WHERE ipaddress='".$row['ipaddress']."'");
			$repeats = mysqli_fetch_array($res_repeat)[0];
			echo "<tr>";
			echo "<td>".$row['TimeStamp']."</td>";
			echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$row['ban_reason']."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			echo "<td style=\"text-align:right;\">".number_format($repeats)."</td>";
			if($row['flag'] === NULL || $row['flag'] == 3 || $row['flag'] == 7) echo "<td style=\"text-align:center;\"><a href=\"./release-ip.php?submit=Release&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to release ".$row['ipaddress']."?')\">No</a></td>";
			elseif($row['flag'] == 1 || $row['flag'] == 2) echo "<td style=\"text-align:center;\">YES</td>";
			elseif($row['flag'] == 4) echo "<td style=\"text-align:center;\">NEW</td>";
			elseif($row['flag'] == 6 || $row['flag'] == 5) echo "<td style=\"text-align:center;\">SAF</td>";
			else echo "<td style=\"text-align:center;\">ERR</td>";
			echo "</tr>";
		}
		echo "</table>";
		if ($total_pages == 1){echo "";}
		else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&search=".$search."&RS=".$RS."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&search=".$search."&RS=".$RS."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&search=".$search."&RS=".$RS."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&search=".$search."&RS=".$RS."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
		if ($total_pages > 0){
			echo "<br />
			RH = Repeat Hits<br />
			RS = Release Status<br /><br />";
		}
	mysqli_close($con);
	}
	echo "<br />";
	echo "</div>";
?>
<?php include("foot.php") ?>