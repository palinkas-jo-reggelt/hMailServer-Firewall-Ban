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
		
		if (IsMySQL()) {
			$QueryLimit = " ".$orderStmt." LIMIT ".$offset.", ".$numRows;
		} elseif (IsMSSQL()) {
			$QueryLimit = " ".$orderStmt." OFFSET ".$offset." ROWS FETCH NEXT ".$numRows." ROWS ONLY";
		} else {
			$QueryLimit = "";
		}
		return $QueryLimit;
	}

	Function DBGetCurrentDateTime(){
		global $Database;
		$Return = "";
		if (IsMySQL()) {
			$Return = "NOW()";
		} elseif (IsMSSQL()) {
			$Return = "GETDATE()";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsDate($fieldName){
		global $Database;
		$Return = "";
		if (IsMySQL()) {
			$Return = "DATE(".$fieldName.")";
		} elseif (IsMSSQL()) {
			$Return = "CAST(".$fieldName." AS DATE)";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsHour($fieldName){
		global $Database;
		$Return = "";
		if (IsMySQL()) {
			$Return = "HOUR(".$fieldName.")";
		} elseif (IsMSSQL()) {
			$Return = "DBFormatDate($fieldName, '%H')";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsMonth($fieldName){
		global $Database;
		$Return = "";
		if (IsMySQL()) {
			$Return = "MONTH(".$fieldName.")";
		} elseif (IsMSSQL()) {
			$Return = "DATEPART(month,".$fieldName.")";
		}
		return $Return;
	}

	Function DBCastDateTimeFieldAsYear($fieldName){ 
		global $Database; 
		$Return = ""; 
		if (IsMySQL()) { 
			$Return = "YEAR(".$fieldName.")"; 
		} elseif (IsMSSQL()) { 
			$Return = DBFormatDate($fieldName, '%Y');
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
			'%y/%m/%d'          => 'yy/MM/dd',
			'Y-m'               => 'yyyy-MM',
			'%Y-%m'             => 'yyyy-MM',
			'%y/%m/%d %T'       => 'yy-MM-dd HH:mm:ss',
			'%Y/%m/%d %H:%m:%s' => 'yyyy-MM-dd HH:mm:ss',
			'%Y/%m/01'          => 'yyyy-MM-01',
			'%y/%c/%e'          => 'yy/MM/dd',
			'%H'				=> 'HH',
			'%M %D, %Y'         => 'MMMM d, yyyy',
		);

		if (IsMySQL()) {
			$Return = "DATE_FORMAT(".$fieldName.", '".$formatSpecifier."')";
		} elseif (IsMSSQL()) {
			switch ($formatSpecifier) 
			{ 
				case '%Y': 
					$Return = DBCastDateTimeFieldAsYear($fieldName); 
					break; 
				case '%c': 
					$Return = DBCastDateTimeFieldAsMonth($fieldName); 
					break; 
				case '%e': 
					$Return = DBCastDateTimeFieldAsDay($fieldName); 
					break; 
				case '%H': 
					$Return = DBCastDateTimeFieldAsHour($fieldName); 
					break; 
				default: 
					$Return = "FORMAT(".$fieldName.", '".$dateFormatSpecifiers[$formatSpecifier]."', 'en-US')"; 
					break; 
			}		}
		return $Return;
	}

	Function DBIpStringToIntField($fieldName){
		global $Database;
		$Return = "";

		if (IsMySQL()) {
			$Return = "INET_ATON(".$fieldName.")";
		} elseif (IsMSSQL()) {
			$Return = "dbo.ipStringToInt(".$fieldName.")";
		}
		return $Return;
	}

	Function DBIpStringToIntValue($ipString){
		global $Database;
		$Return = "";

		if (IsMySQL()) {
			$Return = "INET_ATON('".$ipString."')";
		} elseif (IsMSSQL()) {
			$Return = "dbo.ipStringToInt('".$ipString."')";
		}
		return $Return;
	}

	Function IsMySQL(){
		global $Database;
		return ($Database['dbtype'] == 'mysql');
	}

	Function IsMSSQL(){
		global $Database;
		return ($Database['dbtype'] == 'mssql');
	}


?>
