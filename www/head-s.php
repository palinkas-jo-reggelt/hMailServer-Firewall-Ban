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
<script type="text/javascript" language="JavaScript">
<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
<script src="https://code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
<!--link rel="stylesheet" href="/resources/demos/style.css"-->
<script>
function addRowHandlers() {
    var table = document.getElementById("tableId");
    var rows = table.getElementsByTagName("td");
    for (i = 0; i < rows.length; i++) {
        var currentRow = table.rows[i];
        var createClickHandler = 
            function(row) 
            {
                return function() { 
                                        var cell = row.getElementsByTagName("td")[1];
                                        var id = cell.innerHTML;

                                        //use ajax to post id to ajax.php file
                                        $.post( "ajax.php", { ip:ip }, function( data ) { 
                                              //add result to a div and create a modal/dialog popup similar to alert()
                                              $('<div />').html('data').dialog();
                                        });

                                 };
            };

        currentRow.onclick = createClickHandler(currentRow);
    }
}
window.onload = addRowHandlers();
</script>
</head>
<body>

<div class="header">
	<div class="banner"><h1>hMailServer Firewall Ban</h1></div>
	<div class="headlinks">
		<div class="headlinkswidth">
			<a href="./">stats</a> | <a href="search.php">search</a> | <a href="release.php">release</a> | <a href="reban.php">reban</a> | <a href="ids.php">ids</a> | <a href="repeats.php">blocks</a>
		</div>
	</div>
</div>

<div class="wrapper">