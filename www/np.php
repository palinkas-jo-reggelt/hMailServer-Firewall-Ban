<?php include("head.php") ?>

<div class="section">

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
	if (isset($_GET['flag'])) {
	$flag = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['flag'])));
	} else {
		$flag = "";
	}
  
	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE flag='{$flag}'";
	$result = mysqli_query($con,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban WHERE flag='{$flag}' ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";
	$res_data = mysqli_query($con,$sql);

	if ($total_rows == 1){
		$singular = '';
	} else {
		$singular= 's';
	}
	if ($flag !== "4" && $flag !== "2" && $flag !== "3"){
		echo "<br /><br />Error: flag error. See administrator.<br /><br />";
	} else {
		if($flag == 4){$np_reason="Newly Added";}
		elseif($flag == 2){$np_reason="Newly Released";}
		elseif($flag == 3){$np_reason="Newly Banned";}
		else{echo "<br /><br />Error: flag error. See administrator.<br /><br />";}
		
		if ($total_rows == 0){
			echo "<br /><br />There are no ".$np_reason." IPs to report.";
		} else {
			echo number_format($total_rows)." ".$np_reason." IP".$singular." to report. (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
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
				echo "<td>".$row['TimeStamp']."</td>";
				echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
				echo "<td>".$row['ban_reason']."</td>";
				echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
				echo "<td>NP</td>";
				echo "</tr>";
			}
			echo "</table>";

			if ($total_pages < 2){
				echo "";
			} else {
				echo "<ul>";
				if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&flag=".$flag."&page=1\">First </a><li>";}
				if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&flag=".$flag."&page=".($page - 1)."\">Prev </a></li>";}
				if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&flag=".$flag."&page=".($page + 1)."\">Next </a></li>";}
				if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&flag=".$flag."&page=".$total_pages."\">Last</a></li>";}
				echo "</ul>";
			}
			if ($total_pages > 0){
				echo "<br />RS = Released Status (removal from firewall). \"NP\" = Not Processed yet.<br /><br />";
			}
		}
	}
	mysqli_close($con);

	echo "<br />";
?>
</div>

<?php include("foot.php") ?>