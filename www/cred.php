<?php
// Fill in variables
$m_host="localhost";
$m_dbuser="hmailserver";
$m_dbpass="SSnGLBs8XswL2r0h";
$m_db="hmailserver";

	$con=mysqli_connect($m_host,$m_dbuser,$m_dbpass,$m_db);
	if (mysqli_connect_errno()){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
		die();
	}
?>