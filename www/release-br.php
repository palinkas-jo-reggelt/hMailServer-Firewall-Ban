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
	if (isset($_GET['ban_reason'])) {$ban_reason = $_GET['ban_reason'];} else {$ban_reason="";}

	if (empty($ban_reason)){echo "Error: No ban reason selected. Please see administrator.".$ban_reason."<br /><br />";} 
	else {
		$sqlcount = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE ban_reason LIKE '{$ban_reason}' AND (flag IS NULL OR flag=3)
		");
		$sqlcount->execute();
		$total_rows = $sqlcount->fetchColumn();
		if ($total_rows > 0) { 
			if($total_rows == 1){$singular="";}else{$singular="s";}
			if($total_rows == 1){$singpos="has";}else{$singpos="have";}
			echo "<br />".number_format($total_rows)." hit".$singular." for <a href=\"search.php?submit=Search&search=".$ban_reason."&RS=YES\">".$ban_reason."</a> ".$singpos." been released from the firewall.<br />";
			$sql = $pdo->prepare("
				SELECT 
					id 
				FROM hm_fwban 
				WHERE ban_reason LIKE '{$ban_reason}' AND (flag IS NULL OR flag=3)
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				$sql_update = $pdo->exec("
					UPDATE hm_fwban SET flag=2 WHERE id = ".$row['id']
				);
			}
		} else {
			echo "<br />Error: No unreleased records for \"<b>".$ban_reason."</b>\". Please <a href=\"search.php?submit=Search&search=".$ban_reason."\">search release status</a> or check the spelling and try again.";
		}
	}
?>
</div>

<?php include("foot.php") ?>