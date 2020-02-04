<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell component to hMailServer Firewall Ban (CommonCode.ps1)

.DESCRIPTION
	Backend firewall rule administration for hMailServer Firewall Ban Project

.FUNCTIONALITY
	* Provide Common Code to run Firewall Ban modules

.NOTES

.EXAMPLE

#>

#######################################
#                                     #
#           INI FILE CODE             #
#                                     #
#######################################

<# https://stackoverflow.com/a/422529 #>
Function Parse-IniFile ($file) {
	$ini = @{}

	$section = "NO_SECTION"
	$ini[$section] = @{}

	switch -regex -file $file {
		"^\[(.+)\]$" {
			$section = $matches[1].Trim()
			$ini[$section] = @{}
		}
		"^\s*([^#].+?)\s*=\s*(.*)" {
			$name,$value = $matches[1..2]
			if (!($name.StartsWith(";"))) {
				$ini[$section][$name] = $value.Trim()
			}
		}
	}
	$ini
}

#######################################
#                                     #
#           DATABASE CODE             #
#                                     #
#######################################

Function RunSQLQuery($Query)
{
    if (IsMSSQL) {
        MSSQLQuery($Query)
    } elseif (IsMySQL){
        MySQLQuery($Query)
    } else {
        Out-Null
    }
}

Function IsMSSQL()
{
    return $ini['Database']['DatabaseType'] -eq "MSSQL"
}

Function IsMySQL()
{
    return $ini['Database']['DatabaseType'] -eq "MySQL"
}

Function MySQLQuery($Query) {
	$Today = (Get-Date).ToString("yyyyMMdd")
	$DBErrorLog = "$PSScriptRoot\$Today-DBError.log"
	$ConnectionString = "server=" + $ini['Database']['Host'] + ";port=3306;uid=" + $ini['Database']['Username'] + ";pwd=" + $ini['Database']['Password'] + ";database=" + $ini['Database']['DBase']
	Try {
		[void][System.Reflection.Assembly]::LoadWithPartialName("MySql.Data")
		$Connection = New-Object MySql.Data.MySqlClient.MySqlConnection
		$Connection.ConnectionString = $ConnectionString
		$Connection.Open()
		$Command = New-Object MySql.Data.MySqlClient.MySqlCommand($Query, $Connection)
		$DataAdapter = New-Object MySql.Data.MySqlClient.MySqlDataAdapter($Command)
		$DataSet = New-Object System.Data.DataSet
		$RecordCount = $dataAdapter.Fill($dataSet, "data")
		$DataSet.Tables[0]
	}
	Catch {
		Write-Output "$((get-date).ToString(`"yy/MM/dd HH:mm:ss.ff`")) : ERROR : Unable to run query : $query `n$Error[0]" | out-file $DBErrorLog -append
	}
	Finally {
		$Connection.Close()
	}
}

Function MSSQLQuery($Query) {
	$Today = (Get-Date).ToString("yyyyMMdd")
	$DBErrorLog = "$PSScriptRoot\$Today-DBError.log"
    $ConnectionString = "Data Source=$($ini['Database']['Host']);uid=$($ini['Database']['Username']);password=$($ini['Database']['Password']);Initial Catalog=$($ini['Database']['DBase'])"
	Try {
		[void][System.Reflection.Assembly]::LoadWithPartialName("MySql.Data")
		$Connection = New-Object System.Data.SqlClient.SQLConnection($connectionString)
		$Connection.Open()
		$Command = New-Object System.Data.SqlClient.SqlCommand($Query, $Connection)
		$DataAdapter = New-Object System.Data.SqlClient.SqlDataAdapter($Command)
		$DataSet = New-Object System.Data.DataSet
		$RecordCount = $dataAdapter.Fill($dataSet, "data")
		$DataSet.Tables[0]
	}
	Catch {
		Write-Output "$((get-date).ToString(`"yy/MM/dd HH:mm:ss.ff`")) : ERROR : Unable to run query : $query `n$Error[0]" | out-file $DBErrorLog -append
	}
	Finally {
		$Connection.Close()
	}
}

Function DBCastDateTimeFieldAsDate($fieldName)
{
    $Return = ""
    if (IsMySQL) {
        $Return = "DATE($fieldName)"
    } elseif (IsMSSQL){
        $Return = "CAST($fieldName AS DATE)"
    }
    return $Return
}

Function DBSubtractIntervalFromDate()
{
    param
    (
        $dateString,
        $intervalName, 
        $intervalValue
    )

    $Return = ""
    if (IsMySQL) {
        $Return = "'$dateString' - interval $intervalValue $intervalName"
    } elseif (IsMSSQL){
        $Return = "DATEADD($intervalName,-$intervalValue, '$dateString')"
    }
    return $Return
}

Function DBSubtractIntervalFromField()
{
    param
    (
        $fieldName, 
        $intervalName, 
        $intervalValue
    )

    $Return = ""
    if (IsMySQL) {
        $Return = "$fieldName - interval $intervalValue $intervalName"
    } elseif (IsMSSQL){
        $Return = "DATEADD($intervalName,-$intervalValue, $fieldName)"
    }
    return $Return
}

Function DBGetCurrentDateTime()
{
    $Return = ""
    if (IsMySQL) {
        $Return = "NOW()"
    } elseif (IsMSSQL){
        $Return = "GETDATE()"
    }
    return $Return
}

Function DBLimitRowsWithOffset()
{
    param(
        $offset,
        $numRows
    )

    $QueryLimit = ""

    IF (IsMySQL)
	{
		$QueryLimit = "LIMIT $offset, $numRows"
	} elseif (IsMSSQL) {
		$QueryLimit = "    OFFSET $offset ROWS 
		   	           FETCH NEXT $numRows ROWS ONLY"
    }
    
    return $QueryLimit
}




#######################################
#                                     #
#           FIREWALL CODE             #
#                                     #
#######################################


<#  https://gist.github.com/Stephanevg/a951872bd13d91c0eefad7ad52994f47  #>
Function Get-NetshFireWallrule {
	Param(
		[String]$RuleName
	)
	$Rules = & netsh advfirewall firewall show rule name="$ruleName"
	$return = @()
		$HAsh = [Ordered]@{}
		foreach ($Rule in $Rules){
			switch -Regex ($Rule){
				'^Rule Name:\s+(?<RuleName>.*$)'{$Hash.RuleName = $MAtches.RuleName}
				'^RemoteIP:\s+(?<RemoteIP>.*$)'{$Hash.RemoteIP = $Matches.RemoteIP;$obj = New-Object psobject -Property $Hash;$return += $obj}
			}
		}
	return $return
}