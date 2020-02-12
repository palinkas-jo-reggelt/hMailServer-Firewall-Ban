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

# Include required files
Try {
	.("$PSScriptRoot\Config.ps1")
	.("$PSScriptRoot\CommonCode.ps1")
}
Catch {
	Write-Output "$((get-date).ToString(`"yy/MM/dd HH:mm:ss.ff`")) : ERROR : Unable to load supporting PowerShell Scripts : $query `n$Error[0]" | out-file "$PSScriptRoot\PSError.log" -append
}

#	Set start time
$StartTime = (Get-Date -f G)

# 	Add "ptr" column to hm_fwban
$Query = "ALTER TABLE hm_fwban ADD ptr VARCHAR(192) NULL;"
RunSQLQuery($Query)

$Query = "SELECT COUNT(ID) AS countnull FROM hm_fwban WHERE ptr IS NULL"
RunSQLQuery($Query) | ForEach {
	$CountBeg = $_.countnull
}

$Query = "SELECT ID, ipaddress FROM hm_fwban WHERE ptr IS NULL"
RunSQLQuery($Query) | ForEach {
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
	RunSQLQuery($Query)
}

$Query = "SELECT COUNT(ID) AS countnull FROM hm_fwban WHERE ptr IS NULL"
RunSQLQuery($Query) | ForEach {
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

$EmailBody = ("Retroactive PTR update complete.`n`nResults: $CountRes `n`nUpdate completed in {0:%h} hour$sh {0:%m} minute$sm {0:%s} second$ss" -f $OperationTime)
EmailResults