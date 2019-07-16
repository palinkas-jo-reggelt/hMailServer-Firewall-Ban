<?php include("cred.php") ?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="/resources/demos/style.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript">
$(function () {
    $("#dateFrom").datepicker({
        dateFormat: "yy-mm-dd",
		minDate: <?php
	$query = "SELECT DATE(timestamp) Date FROM hm_fwban ORDER BY DATE(timestamp) ASC LIMIT 1";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "'".$row['Date']."',";
	}
?>
		maxDate: new Date,
        onSelect: function (selected) {
            $("#dateTo").datepicker("option", "minDate", selected);
        }
    });
    $("#dateTo").datepicker({
        dateFormat: "yy-mm-dd",
		minDate: <?php
	$query = "SELECT DATE(timestamp) Date FROM hm_fwban ORDER BY DATE(timestamp) ASC LIMIT 1";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "'".$row['Date']."',";
	}
?>
		maxDate: new Date,
        onSelect: function (selected) {
            $("#dateFrom").datepicker("option", "maxDate", selected);
        }
    });
});
</script>
