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

Function EmailResults($HTML) {
	$Subject = "hMS Firewall Ban Notification" 
	$Body = (Get-Content -Path $EmailBody | Out-String )
	$Message = New-Object System.Net.Mail.Mailmessage $FromAddress, $Recipient, $Subject, $Body
	$Message.IsBodyHTML = [System.Convert]::ToBoolean($HTML)
	$SMTP = New-Object System.Net.Mail.SMTPClient $SMTPServer,$SMTPPort
	$SMTP.EnableSsl = [System.Convert]::ToBoolean($SSL)
	$SMTP.Credentials = New-Object System.Net.NetworkCredential($SMTPAuthUser, $SMTPAuthPass); 
	$SMTP.Send($Message)
}

#######################################
#                                     #
#           DATABASE CODE             #
#                                     #
#######################################

Function IsMSSQL(){
	return ($DatabaseType -eq "MSSQL")
}

Function IsMySQL(){
	return ($DatabaseType -eq "MYSQL")
}

Function RunSQLQuery($Query){
    If ($(IsMySQL)) {
        MySQLQuery($Query)
    } ElseIf ($(IsMSSQL)){
        MSSQLQuery($Query)
    } Else {
        Out-Null
    }
}

Function MySQLQuery($Query) {
	$Today = (Get-Date).ToString("yyyyMMdd")
	$DBErrorLog = "$PSScriptRoot\$Today-DBError.log"
	$ConnectionString = "server=" + $SQLHost + ";port=" + $SQLPort + ";uid=" + $SQLAdminUserName + ";pwd=" + $SQLAdminPassword + ";database=" + $SQLDatabase + ";SslMode=" + $SQLSSL + ";"
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
    $ConnectionString = "Data Source=" + $SQLHost + "," + $SQLPort + ";uid=" + $SQLAdminUserName + ";password=" + $SQLAdminPassword + ";Initial Catalog=" + $SQLDatabase
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
    If ($(IsMySQL)) {
        $Return = "DATE($fieldName)"
    } ElseIf ($(IsMSSQL)){
        $Return = "CAST($fieldName AS DATE)"
    }
    return $Return
}

Function DBCastDateTimeFieldAsHour($fieldName){
	$Return = ""
    If ($(IsMySQL)) {
		$Return = "HOUR($fieldName)"
    } ElseIf ($(IsMSSQL)){
		$Return = "DATEPART(hour,$fieldName)"
	}
	return $Return;
}

Function DBSubtractIntervalFromDate(){
    param
    (
        $dateString,
        $intervalName, 
        $intervalValue
    )

    $Return = ""
    If ($(IsMySQL)) {
        $Return = "'$dateString' - interval $intervalValue $intervalName"
    } ElseIf ($(IsMSSQL)){
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
    If ($(IsMySQL)) {
        $Return = "$fieldName - interval $intervalValue $intervalName"
    } ElseIf ($(IsMSSQL)){
        $Return = "DATEADD($intervalName,-$intervalValue, $fieldName)"
    }
    return $Return
}

Function DBGetCurrentDateTime(){
    $Return = ""
    If ($(IsMySQL)) {
        $Return = "NOW()"
    } ElseIf ($(IsMSSQL)){
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

    If ($(IsMySQL)) {
		$QueryLimit = "LIMIT $offset, $numRows"
    } ElseIf ($(IsMSSQL)){
		$QueryLimit = "OFFSET $offset ROWS 
		   	           FETCH NEXT $numRows ROWS ONLY"
	}
	return $QueryLimit
}

Function DBFormatDate(){

	param(
		$fieldName, 
		$formatSpecifier
	)

	$Return = ""

	$dateFormatSpecifiers = @{
		'%Y'                   = 'yyyy'
		'%c'                   = 'MM'
		'%e'                   = 'dd'
		'Y-m-d'                = 'yyyy-MM-dd'
		'%y/%m/%d'             = 'yy/MM/dd'
		'Y-m'                  = 'yyyy-MM'
		'%Y-%m'                = 'yyyy-MM'
		'%y/%m/%d %T'          = 'yy-MM-dd HH:mm:ss'
		'%Y/%m/%d %HH:%mm:%ss' = 'yyyy-MM-dd HH:mm:ss'
		'%Y/%m/01'             = 'yyyy-MM-01'
		'%y/%c/%e'             = 'yy/MM/dd'
		'%H'                   = 'HH'
	}
	
    If ($(IsMySQL)) {
		$Return = "DATE_FORMAT($fieldName, '$formatSpecifier')"
    } ElseIf ($(IsMSSQL)){
		$Return = "FORMAT($fieldName, '$($dateFormatSpecifiers[$formatSpecifier])', 'en-US')"
	}
	return $Return
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
	$Rules = & netsh advfirewall firewall show rule name="$RuleName"
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