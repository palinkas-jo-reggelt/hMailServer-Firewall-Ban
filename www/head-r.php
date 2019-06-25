<!DOCTYPE html> 
<html>
<head>
<title>hMailServer Firewall Ban</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" media="all" href="stylesheet.css">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet"> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="/resources/demos/style.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript">
$(function () {
    $("#dateFrom").datepicker({
        dateFormat: "yy-mm-dd",
		maxDate: new Date,
        onSelect: function (selected) {
            $("#dateTo").datepicker("option", "minDate", selected);
        }
    });
    $("#dateTo").datepicker({
        dateFormat: "yy-mm-dd",
		maxDate: new Date,
        onSelect: function (selected) {
            $("#dateFrom").datepicker("option", "maxDate", selected);
        }
    });
});
</script>


</head>
<body>

<div class="header">
	<div class="banner"><h1>hMailServer Firewall Ban</h1></div>
	<div class="headlinks">
		<div class="headlinkswidth">
			<a href="./">stats</a> | <a href="search.php">search</a> | <a href="history.php">history</a> | <a href="release.php">release</a> | <a href="reban.php">reban</a>
		</div>
	</div>
</div>

<div class="wrapper">