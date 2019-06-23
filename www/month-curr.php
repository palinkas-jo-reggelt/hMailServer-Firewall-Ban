<?php include("month-atop.php") ?>
<?php
	$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE `timestamp` BETWEEN '{$thismonth} 00:00:00' AND NOW()";
	$result = mysqli_query($con,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban WHERE `timestamp` BETWEEN '{$thismonth} 00:00:00' AND NOW() ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";

	$res_data = mysqli_query($con,$sql);
	echo number_format($total_rows)." IP Reports in the month of ".date("F", strtotime($thismonth))." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
?>
<?php include("month-abottom.php") ?>