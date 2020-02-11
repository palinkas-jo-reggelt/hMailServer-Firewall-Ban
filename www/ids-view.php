<?php include("head.php") ?>

<div class="section">
<h2>Search IDS Hits</h2>


<?php

	include_once("config.php");
	include_once("functions.php");

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
	if (isset($_GET['search'])) {$search = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['search'])));} else {$search = "";}
	if ($search=="") {$search_sql="";} else {$search_sql=" WHERE ipaddress LIKE '{$search}%' OR timestamp LIKE '{$search}%' OR helo LIKE '%{$search}%' OR country LIKE '%{$search}%'";}
	if ($search=="") {$search_page="";} else {$search_page="&search=".$search."";}
	if ($search=="") {$search_list="";} else {$search_list=" matching <b>\"".$search."\"</b>";}
	if ($search=="") {$search_all="All ";} else {$search_all="";}
  
	echo "<div class='section'>";
	echo "<form action='ids-view.php' method='GET'> ";
	echo	"<input type='text' size='20' name='search' placeholder='Search...' value='".$search."'>";
	echo	" ";
	echo	"<input type='submit' name='submit' value='Search' > <a href=\"./ids-view.php\">List All</a>";
	echo "</form>";
	echo "</div>";
	echo "<div class='section'>";

	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	$total_pages_sql = "SELECT COUNT(ipaddress) FROM hm_ids".$search_sql."";

	$sql = $pdo->prepare($total_pages_sql);
	$sql->execute();
	$total_rows = $sql->fetchColumn();

	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$query = "
		SELECT 
			ipaddress, 
			".DBFormatDate('timestamp', '%y/%m/%d %T')." as TimeStamp, 
			country, 
			helo, 
			hits 
		FROM 
			hm_ids ".$search_sql." 
		GROUP BY 
			ipaddress, ".DBFormatDate('timestamp', '%y/%m/%d %T').", country, helo, hits
		".DBLimitRowsWithOffset('timestamp','DESC',0,0,$offset,$no_of_records_per_page)
	;

	$sql = $pdo->prepare($query);
	$sql->execute();


	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
	if ($total_rows == 0){
		echo "<br />There are no IDS entries to report for search term <b>\"".$search."\"</b>. Please enter only IP address or date.";
	} else {
		echo $search_all."".number_format($total_rows)." IP".$singular." hit by IDS".$search_list.". (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
		echo "<table class='section'>
			<tr>
				<th>Timestamp</th>
				<th>IP Address</th>
				<th>Country</th>
				<th>HELO</th>
				<th>Hits</th>
			</tr>";

		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr>";
			echo "<td>".$row['TimeStamp']."</td>";
			echo "<td>".$row['ipaddress']."</td>";
			echo "<td>".$row['country']."</td>";
			echo "<td>".$row['helo']."</td>";
			echo "<td style=\"text-align:center;\">".$row['hits']."</td>";
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "<br /><br />";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
	}

	echo "<br />";
?>
</div>

<?php include("foot.php") ?>