<?php include("head.php") ?>
<div class="section">
<?php include("cred.php") ?>
<?php
	$thismonth = date('Y-m-1');
	$lastmonth = date('Y-m-1', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$twomonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-2, date("d"), date("Y")));
	$threemonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	$fourmonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-4, date("d"), date("Y")));

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = 1;
	}
	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
?>