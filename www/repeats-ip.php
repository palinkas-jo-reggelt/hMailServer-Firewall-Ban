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
	if (isset($_GET['repeatIP'])) {
	$repeatIP = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['repeatIP'])));
	} else {
		$repeatIP = "";
	}
  
	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	$total_pages_sql = "SELECT COUNT(ipaddress) FROM hm_fwban_rh WHERE ipaddress='{$repeatIP}'";
	$result = mysqli_query($con,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = "SELECT ipaddress, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp FROM hm_fwban_rh WHERE ipaddress = '{$repeatIP}' ORDER BY timestamp DESC LIMIT $offset, $no_of_records_per_page";
	$res_data = mysqli_query($con,$sql);

	if ($total_rows == 1){
		$singular = '';
	} else {
		$singular= 's';
	}
		
	if ($total_rows == 0){
		echo "<br /><br />There are no repeat dropped IPs to report.";
	} else {
		echo "IP <b>".$repeatIP."</b> with ".number_format($total_rows)." repeated drop".$singular." at firewall. (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
		echo "<table class='section'>
			<tr>
				<th>Timestamp</th>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>RS</th>
			</tr>";

		while($row = mysqli_fetch_array($res_data)){
			$res_country = mysqli_query($con,"SELECT country FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'");
			$country = mysqli_fetch_array($res_country)[0];
			$res_ban_reason = mysqli_query($con,"SELECT ban_reason FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'");
			$ban_reason = mysqli_fetch_array($res_ban_reason)[0];
			$res_flag = mysqli_query($con,"SELECT flag FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'");
			$flag = mysqli_fetch_array($res_flag)[0];
			echo "<tr>";
			echo "<td>".$row['TimeStamp']."</td>";
			echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$ban_reason."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$country."</a></td>";
			if($flag === NULL || $flag == 3) echo "<td style=\"text-align:center;\"><a href=\"./release-ip.php?submit=Release&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to release ".$row['ipaddress']."?')\">No</a></td>";
			elseif($flag == 4) echo "<td style=\"text-align:center;\">NP</td>";
			else echo "<td style=\"text-align:center;\">YES</td>";
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&repeatIP=".$repeatIP."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
		if ($total_pages > 0){
			echo "<br />RS = Released Status (removal from firewall). <br />RC = \"Repeat Customer\" (IPs with connections dropped at firewall)<br /><br />";
		}
	}

	mysqli_close($con);

	echo "<br />";
?>
</div>

<?php include("foot.php") ?>