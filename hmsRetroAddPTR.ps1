<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Retroactively insert PTR records into database.

.DESCRIPTION
	Adds column "ptr" to database, then checks PTR for each IP in the database and inserts record.

.FUNCTIONALITY
	1) Fill in user variables
	2) Run script

.NOTES
	Takes a while to run if you have lots of bans in the database. Includes email report.
	
.EXAMPLE

#>

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

Function MySQLQuery($Query) {
	$Today = (Get-Date).ToString("yyyyMMdd")
	$DBErrorLog = "$PSScriptRoot\$Today-RetroAddPTR.log"
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

Function EmailResults {
	$Subject = "Retroactive PTR Results" 
	$Body = $Msg
	$SMTPClient = New-Object Net.Mail.SmtpClient($ini['Email']['SMTPServer'], 587) 
	$SMTPClient.EnableSsl = $($ini['Email']['SSL'])
	$SMTPClient.Credentials = New-Object System.Net.NetworkCredential($ini['Email']['SMTPAuthUser'], $ini['Email']['SMTPAuthPass']); 
	$SMTPClient.Send($ini['Email']['FromAddress'], $ini['Email']['Recipient'], $Subject, $Body)
}

#	Load User Variables
$ini = Parse-IniFile("$PSScriptRoot\Config.INI")

$StartTime = (Get-Date -f G)

# 	Add "ptr" column to hm_fwban
$Query = "ALTER TABLE hm_fwban ADD ptr VARCHAR(192) NULL AFTER helo;"
MySQLQuery($Query)

$Query = "SELECT COUNT(ID) AS countnull FROM hm_fwban WHERE ptr IS NULL"
MySQLQuery($Query) | ForEach {
	$CountBeg = $_.countnull
}

$Query = "SELECT ID, ipaddress FROM hm_fwban WHERE ptr IS NULL"
MySQLQuery($Query) | ForEach {
	$IP = $_.ipaddress
	$ID = $_.ID

	Try {
		$ErrorActionPreference = 'Stop'
		$PTR = [System.Net.Dns]::GetHostEntry($IP).HostName
	}
	Catch {
		$PTR = 'No.PTR.Record'
	}

	$Query = "UPDATE hm_fwban SET ptr = '$PTR' WHERE ID = '$ID'"
	MySQLQuery($Query)
}

$Query = "SELECT COUNT(ID) AS countnull FROM hm_fwban WHERE ptr IS NULL"
MySQLQuery($Query) | ForEach {
	$CountEnd = $_.countnull
}

If (($CountBeg - $CountEnd) -gt 0){
	$CountRes = "$(($CountBeg - $CountEnd).ToString('#,##0')) PTR records failed insert into database. Check error log."
} Else {
	$CountRes = "All $(($CountBeg).ToString('#,##0')) PTR records inserted successfully."
}

$EndTime = (Get-Date -f G)
$OperationTime = New-Timespan $StartTime $EndTime
If (($Duration).Hours -eq 1) {$sh = ""} Else {$sh = "s"}
If (($Duration).Minutes -eq 1) {$sm = ""} Else {$sm = "s"}
If (($Duration).Seconds -eq 1) {$ss = ""} Else {$ss = "s"}

$Msg = ("Retroactive PTR update complete.`n`nResults: $CountRes `n`nUpdate completed in {0:%h} hour$sh {0:%m} minute$sm {0:%s} second$ss" -f $OperationTime)
EmailResults