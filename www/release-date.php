<?php include("head.php") ?>

<div class="section">

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
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['dateFrom'])) {$dateFrom = $_GET['dateFrom'];} else {$dateFrom = "";}
	if (isset($_GET['dateTo'])) {$dateTo = $_GET['dateTo'];} else {$dateFrom = "";}

	if (empty($dateFrom)){echo "Error: Date range empty. Please see administrator.<br><br>";}
	elseif (empty($dateTo)){echo "Error: Date range empty. Please see administrator.<br><br>";}
	else {
		$sqlcount = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag IS NULL OR flag=3)
		");
		$sqlcount->execute();
		$total_rows = $sqlcount->fetchColumn();
		if ($total_rows > 0) { 
			if($total_rows == 1){$singular="";}else{$singular="s";}
			if($total_rows == 1){$singpos="has";}else{$singpos="have";}
			echo "<br>".number_format($total_rows)." hit".$singular." for date range <a href=\"search-date.php?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=YES\">\"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\"</a> ".$singpos." been released from the firewall.<br>";
			$sql = $pdo->prepare("
				SELECT 
					id 
				FROM hm_fwban 
				WHERE timestamp BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag IS NULL OR flag=3)
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				$sql_update = $pdo->exec("
					UPDATE hm_fwban SET flag=2 WHERE id=".$row['id']
				);
			}
		} else {
			echo "<br>Error: Date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\" contains no entries in database. Please try again.";
		}
	}
	
?>
</div>

<?php include("foot.php") ?>