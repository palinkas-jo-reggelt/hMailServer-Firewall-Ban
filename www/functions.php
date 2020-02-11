<?php

	If ($Database['driver'] == 'mysql') {
		$pdo = new PDO("mysql:host=".$Database['host'].";port=".$Database['port'].";dbname=".$Database['dbname'], $Database['username'], $Database['password']);
	} ElseIf ($Database['driver'] == 'mssql') {
		$pdo = new PDO("sqlsrv:Server=".$Database['host'].",".$Database['port'].";Database=".$Database['dbname'], $Database['username'], $Database['password']);
	} ElseIf ($Database['driver'] == 'odbc') {
		$pdo = new PDO("odbc:Driver={".$Database['dsn']."};Server=".$Database['host'].";Port=".$Database['port'].";Database=".$Database['dbname'].";User=".$Database['username'].";Password=".$Database['password'].";");
	} Else {
		echo "Configuration Error - No database driver specified";
	}

	function ipRangeFinder($cidr) {
	   $range = array();
	   $cidr = explode('/', $cidr);
	   $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
	   $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
	   return $range;
	}

	// $orderBy2 is sub-order to $orderBy1
	Function DBLimitRowsWithOffset($orderBy1, $orderByDir1, $orderBy2, $orderByDir2, $offset, $numRows){
		global $Database;
		$QueryLimit = "";
		
		if (($orderBy1 === 0) && ($orderByDir1 === 0) && ($orderBy2 === 0) && ($orderByDir2 === 0)){
			$orderStmt = " ORDER BY 1";
		} elseif (($orderBy2 === 0) && ($orderByDir2 === 0)){
			$orderStmt = " ORDER BY ".$orderBy1." ".$orderByDir1;
		} else {
			$orderStmt = " ORDER BY ".$orderBy1." ".$orderByDir1.", ".$orderBy2." ".$orderByDir2;
		}
		
		if ($Database['dbtype'] == 'mysql') {
			$QueryLimit = " ".$orderStmt." LIMIT ".$offset.", ".$numRows;
		} elseif ($Database['dbtype'] == 'mssql') {
			$QueryLimit = " ".$orderStmt." OFFSET ".$offset." ROWS FETCH NEXT ".$numRows." ROWS ONLY";
		} else {
			$QueryLimit = "";
		}
		return $QueryLimit;
	}

	Function DBGetCurrentDateTime(){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "NOW()";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "GETDATE()";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsDate($fieldName){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "DATE(".$fieldName.")";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "CAST(".$fieldName." AS DATE)";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsHour($fieldName){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "HOUR(".$fieldName.")";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = DBFormatDate($fieldName, '%H');
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsMonth($fieldName){
		global $Database;
		$Return = "";
		if ($Database['dbtype'] == 'mysql') {
			$Return = "MONTH(".$fieldName.")";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = DBFormatDate($fieldName, '%c');
		}
		return $Return;
	}

	Function DBFormatDate($fieldName, $formatSpecifier){
		global $Database;
		$Return = "";

		$dateFormatSpecifiers = array (
			'%Y'                => 'yyyy',
			'%c'                => 'MM',
			'%e'                => 'dd',
			'Y-m-d'             => 'yyyy-MM-dd',
			'%y/%m/%d'          => 'yyyy/MM/dd',
			'Y-m'               => 'yyyy-MM',
			'%Y-%m'             => 'yyyy-MM',
			'%y/%m/%d %T'		=> 'yyyy-MM-dd HH:mm:ss',
			'%Y/%m/%d %T'       => 'yyyy-MM-dd HH:mm:ss',
			'%Y/%m/01'          => 'yyyy-MM-01',
			'%y/%c/%e'          => 'yyyy/MM/dd',
			'%H'				=> 'HH',
		);

		if ($Database['dbtype'] == 'mysql') {
			$Return = "DATE_FORMAT(".$fieldName.", '".$formatSpecifier."')";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "FORMAT(".$fieldName.", '".$dateFormatSpecifiers[$formatSpecifier]."', 'en-US')";
		}
		return $Return;
	}

	Function DBIpStringToIntField($fieldName){
		global $Database;
		$Return = "";

		if ($Database['dbtype'] == 'mysql') {
			$Return = "INET_ATON(".$fieldName.")";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "dbo.ipStringToInt(".$fieldName.")";
		}
		return $Return;
	}

	Function DBIpStringToIntValue($ipString){
		global $Database;
		$Return = "";

		if ($Database['dbtype'] == 'mysql') {
			$Return = "INET_ATON('".$ipString."')";
		} elseif ($Database['dbtype'] == 'mssql') {
			$Return = "dbo.ipStringToInt('".$ipString."')";
		}
		return $Return;
	}

?>
