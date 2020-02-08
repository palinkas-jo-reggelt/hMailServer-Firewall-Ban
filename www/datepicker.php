<?php include_once("config.php") ?>
<?php include_once("functions.php") ?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="/resources/demos/style.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript">
$(function () {
    $("#dateFrom").datepicker({
        dateFormat: "yy-mm-dd",
		minDate: <?php
	$sql = $pdo->prepare("
		SELECT 
			".DBCastDateTimeFieldAsDate('timestamp')." AS date 
		FROM hm_fwban 
		".DBLimitRowsWithOffset((DBCastDateTimeFieldAsDate('timestamp')),'ASC',0,0,0,1)
	);
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "'".$row['date']."',";
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
	$sql = $pdo->prepare("
		SELECT 
			".DBCastDateTimeFieldAsDate('timestamp')." AS date 
		FROM hm_fwban 
		".DBLimitRowsWithOffset((DBCastDateTimeFieldAsDate('timestamp')),'ASC',0,0,0,1)
	);
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "'".$row['date']."',";
	}
?>
		maxDate: new Date,
        onSelect: function (selected) {
            $("#dateFrom").datepicker("option", "maxDate", selected);
        }
    });
});
</script>