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

# Include required files
Try {
	.("$PSScriptRoot\Config.ps1")
}
Catch {
	Write-Output "Error while loading supporting PowerShell Scripts" | Out-File -Path "$PSScriptRoot\PSError.log"
}

#######################################
#                                     #
#             EMAIL CODE              #
#                                     #
#######################################

Function EmailResults {
	$Subject = "Retroactive PTR Results" 
	$Body = (Get-Content -Path $EmailBody | Out-String )
	$SMTPClient = New-Object Net.Mail.SmtpClient($SMTPServer, $SMTPPort) 
	$SMTPClient.EnableSsl = [System.Convert]::ToBoolean($SSL)
	$SMTPClient.Credentials = New-Object System.Net.NetworkCredential($SMTPAuthUser, $SMTPAuthPass); 
	$SMTPClient.Send($FromAddress, $Recipient, $Subject, $Body)
}

#######################################
#                                     #
#           DATABASE CODE             #
#                                     #
#######################################

Function RunSQLQuery($Query){
    If ($DatabaseType -eq "MYSQL") {
        MySQLQuery($Query)
    } ElseIf ($DatabaseType -eq "MSSQL"){
        MySQLQuery($Query)
    } Else {
        Out-Null
    }
}

Function MySQLQuery($Query) {
	$Today = (Get-Date).ToString("yyyyMMdd")
	$DBErrorLog = "$PSScriptRoot\$Today-DBError.log"
	$ConnectionString = "server=" + $SQLHost + ";port=" + $SQLPort + ";uid=" + $SQLAdminUserName + ";pwd=" + $SQLAdminPassword + ";database=" + $SQLDatabase
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
    $ConnectionString = "Data Source=" + $SQLHost + ";port=" + $SQLPort + ";uid=" + $SQLAdminUserName + ";password=" + $SQLAdminPassword + ";Initial Catalog=" + $SQLDatabase
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

Function DBCastDateTimeFieldAsDate($fieldName){
    $Return = ""
    If ($DatabaseType -eq "MYSQL") {
        $Return = "DATE($fieldName)"
    } ElseIf ($DatabaseType -eq "MSSQL"){
        $Return = "CAST($fieldName AS DATE)"
    }
    return $Return
}

Function DBSubtractIntervalFromDate(){
    param
    (
        $dateString,
        $intervalName, 
        $intervalValue
    )

    $Return = ""
    If ($DatabaseType -eq "MYSQL") {
        $Return = "'$dateString' - interval $intervalValue $intervalName"
    } ElseIf ($DatabaseType -eq "MSSQL"){
        $Return = "DATEADD($intervalName,-$intervalValue, '$dateString')"
    }
    return $Return
}

Function DBSubtractIntervalFromField(){
    param
    (
        $fieldName, 
        $intervalName, 
        $intervalValue
    )

    $Return = ""
    If ($DatabaseType -eq "MYSQL") {
        $Return = "$fieldName - interval $intervalValue $intervalName"
    } ElseIf ($DatabaseType -eq "MSSQL"){
        $Return = "DATEADD($intervalName,-$intervalValue, $fieldName)"
    }
    return $Return
}

Function DBGetCurrentDateTime(){
    $Return = ""
    If ($DatabaseType -eq "MYSQL") {
        $Return = "NOW()"
    } ElseIf ($DatabaseType -eq "MSSQL"){
        $Return = "GETDATE()"
    }
    return $Return
}

Function DBLimitRowsWithOffset(){
    param(
        $offset,
        $numRows
	)

	$QueryLimit = ""

    If ($DatabaseType -eq "MYSQL") {
		$QueryLimit = "LIMIT $offset, $numRows"
    } ElseIf ($DatabaseType -eq "MSSQL"){
		$QueryLimit = "OFFSET $offset ROWS 
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

Function RemRuleIP($IP) {
	$Query = "SELECT rulename FROM hm_fwban WHERE ipaddress = '$IP'"
	RunSQLQuery $Query | ForEach {
		$RuleName = $_.rulename
	}

	If (-not($RuleName)) {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
	Else {
		$RuleList = "$PSScriptRoot\fwrulelist.txt"
		$NewLine = [System.Environment]::NewLine

		Get-NetshFireWallrule ("$RuleName") | ForEach {
			$RemoteIP = $_.RemoteIP
			$ReplaceCIDR = ($RemoteIP).Replace("/32", "")
			$ReplaceNL = ($ReplaceCIDR).Replace(",", $NewLine)
			Write-Output $ReplaceNL 
		} | out-file $RuleList

		Get-Content $RuleList | where { $_ -ne $IP } | Out-File "$RuleList.delIP.txt"
		$NL = [System.Environment]::NewLine
		$Content = [String] $Template = [System.IO.File]::ReadAllText("$RuleList.delIP.txt")
		$Content.Replace($NL, ",") | Out-File "$RuleList.rule.txt"
		(Get-Content -Path "$RuleList.rule.txt") -Replace ',$', '' | Set-Content -Path "$RuleList.rule.txt"

		& netsh advfirewall firewall delete rule name=`"$RuleName`"
		& netsh advfirewall firewall add rule name=`"$RuleName`" description="FWB Rules for $DateIP" dir=in interface=any action=block remoteip=$(Get-Content "$RuleList.rule.txt")
	}
}