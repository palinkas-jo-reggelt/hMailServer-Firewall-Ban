<?php include("head-r.php") ?>
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
	if (isset($_GET['dateFrom'])) {$dateFrom = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateFrom'])));} else {$dateFrom = "";}
	if (isset($_GET['dateTo'])) {$dateTo = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateTo'])));} else {$dateTo = "";}
	if (isset($_GET['RS'])) {$RS = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['RS'])));} else {$RS = "";}
  
	echo "<div class='section'>";
	echo "<h2>Search a date range:</h2>";
	echo "Enter start & end dates and click to search.<br /><br />";
	echo "<form autocomplete='off' action='search-date.php' method='GET'>";
	echo "<table>";
	echo "<tr><td>Starting Date: </td><td><input type='text' id='dateFrom' name='dateFrom' placeholder='Starting Date...' value='".$dateFrom."' /></td></tr>";
	echo "<tr><td>Ending Date: </td><td><input type='text' id='dateTo' name='dateTo' placeholder='Ending Date...' value='".$dateTo."' /></td></tr>";
	echo "<tr><td>Release Status: </td><td>
			<select name='RS'>
			<option value=''>Both</option>
			<option value='YES'>YES</option>
			<option value='NO'>NO</option>
			</select></td></tr>";
	echo "<tr><td><input type='submit' name='submit' value='Search' /></td></tr>";
	echo "</table>";
	echo "</form>";
	//echo "<br />Note: Range can be a single day, but start and end dates must both be filled in.<br />";
	echo "</div>";
	echo "<div class='section'>";

	if (empty($dateFrom)){
		echo "Note: Range can be a single day, but start and end dates must both be filled in.<br /><br />";
	} elseif (empty($dateTo)){
		echo "You did not put in an ending date. Both beginning and ending dates are required for date range release even if the range is a single day.<br /><br />";
	} else {
  
		$no_of_records_per_page = 20;
		$offset = ($page-1) * $no_of_records_per_page;
		
		if ($RS=="NO"){$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag IS NULL OR flag=3)";}
		elseif ($RS=="YES"){$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag=1 OR flag=2)";}
		else {$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59'";}

		$result = mysqli_query($con,$total_pages_sql);
		$total_rows = mysqli_fetch_array($result)[0];
		$total_pages = ceil($total_rows / $no_of_records_per_page);

		if ($RS=="NO"){$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag IS NULL OR flag=3) ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";}
		elseif ($RS=="YES"){$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag=1 OR flag=2) ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";}
		else {$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";}

		$res_data = mysqli_query($con,$sql);

		if ($total_rows == 1){$singular = '';} else {$singular= 's';}
		if ($total_rows == 0){
			echo "<br /><br />No results for date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\"";
		} else {
			if (empty($RS)){echo "Results for date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";}
			else {echo "Results for date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\" with release status \"".$RS."\": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";}
			echo "<table class='section'>
				<tr>
					<th>Timestamp</th>
					<th>IP Address</th>
					<th>Reason</th>
					<th>Country</th>
					<th>RS</th>
				</tr>";
			while($row = mysqli_fetch_array($res_data)){
				echo "<tr>";
				echo "<td>" . $row['TimeStamp'] . "</td>";
				echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
				echo "<td>" . $row['ban_reason'] . "</td>";
				echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
				if($row['flag'] === NULL || $row['flag'] == 3) echo "<td><a href=\"./release-ip.php?submit=Release&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to release ".$row['ipaddress']."?')\">No</a></td>";
				else echo "<td>YES</td>";
				echo "</tr>";
			}
			echo "</table>";
			if ($total_pages == 1){echo "";}
			else {
				echo "<ul>";
				if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=1\">First </a><li>";}
				if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".($page - 1)."\">Prev </a></li>";}
				if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".($page + 1)."\">Next </a></li>";}
				if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".$total_pages."\">Last</a></li>";}
				echo "</ul>";
			}
		echo "<br />RS = Released Status (removal from firewall). Clicking on \"NO\" will release the IP.<br /><br />";
		}
	mysqli_close($con);
	}
	echo "<br />";
	echo "</div>";
?>

<?php include("foot.php") ?>