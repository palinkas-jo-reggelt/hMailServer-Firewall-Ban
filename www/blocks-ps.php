<?php include("head.php") ?>

<div class="wrapper">
<div class="section">
	<h2>Block List Analyzer</h2>
	Clicking will run BlockCount.ps1 powershell script. The script will send an email when completed.<br><br>
	
<?php
	include("config.php");
	include("functions.php");

	if(!isset($_POST["submit"])){
		echo "<form name='testForm' id='testForm' action='blocks-ps.php' method='post' />";
		echo "	<input type='submit' name='submit' id='submit' value='Run Script' />";
		echo "</form>";
	} elseif(isset($_POST["submit"])) {
		$script_command = "Powershell.exe -ExecutionPolicy Bypass -File ".$PowershellScriptDir."BlockCount.ps1";
		pclose(popen("start /B ". $script_command, "r")); 

		echo "Script is running. You will receive an email with the results shortly.<br><br>";
		echo "Script Location: ".$PowershellScriptDir."BlockCount.ps1";
	} else {
		echo "Script running. Please check your email in a few minutes.";
	}






?>