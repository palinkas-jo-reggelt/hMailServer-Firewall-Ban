<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell script to retroactively create hm_fwban_blocks_ip table and fill it.

.DESCRIPTION
	
.FUNCTIONALITY

.NOTES
	Includes email notification when complete.

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
if ($DatabaseType -eq "MYSQL"){
	$Query = "
		CREATE TABLE IF NOT EXISTS hm_fwban_blocks_ip (
		  id INT(22) NOT NULL AUTO_INCREMENT,
		  ipaddress varchar(15) NOT NULL UNIQUE,
		  hits INT(8),
		  lasttimestamp datetime NOT NULL,
		  PRIMARY KEY (id)
		  UNIQUE KEY ipaddress (ipaddress)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		COMMIT;
	"
} elseif ($DatabaseType -eq "MSSQL") {
	$Query = "
		IF NOT EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME='hm_fwban_blocks_ip')
			CREATE TABLE  hm_fwban_blocks_ip (
			  id INT IDENTITY PRIMARY KEY,
			  ipaddress varchar(15) NOT NULL UNIQUE,
			  hits INT,
			  lasttimestamp DATETIME NOT NULL,
			) 
	"
}
	RunSQLQuery $Query
if ($DatabaseType -eq "MYSQL"){
	$Query = "ALTER TABLE hm_fwban_rh DROP id;"
} elseif ($DatabaseType -eq "MSSQL") {
	$Query = "ALTER TABLE hm_fwban_rh DROP COLUMN id;"
}
RunSQLQuery $Query

if ($DatabaseType -eq "MYSQL"){
	$Query = "ALTER TABLE hm_fwban_rh ADD ipid INT(22) NULL;"
} elseif ($DatabaseType -eq "MSSQL") {
	$Query = "ALTER TABLE hm_fwban_rh ADD ipid INT NULL;"
}	
RunSQLQuery $Query

$Query = "
	SELECT 
		timestamp,
		ipaddress 
	FROM hm_fwban_rh
	WHERE timestamp > '2020-02-14 06:22:00'
"
RunSQLQuery $Query | foreach {
	$timestamp = (Get-Date $_.timestamp).ToString("yyyy-MM-dd HH:mm:ss")
	$ipaddress = $_.ipaddress
	if ($DatabaseType -eq "MYSQL"){
		$Query = "INSERT INTO hm_fwban_blocks_ip (ipaddress, hits, lasttimestamp) VALUES ('$ipaddress',1,'$timestamp') ON DUPLICATE KEY UPDATE hits=(hits+1),lasttimestamp='$timestamp';"
	} elseif ($DatabaseType -eq "MSSQL") {
		$Query = "IF NOT EXISTS (SELECT 1 FROM hm_fwban_blocks_ip WHERE ipaddress='$ipaddress') INSERT INTO hm_fwban_blocks_ip (ipaddress, hits, lasttimestamp) VALUES ('$ipaddress',1,'$timestamp') ELSE UPDATE hm_fwban_blocks_ip SET hits=(hits+1),lasttimestamp='$timestamp'  WHERE ipaddress='$ipaddress';"
	}
	RunSQLQuery $Query
}

$Query = "
	SELECT 
		id,
		ipaddress 
	FROM hm_fwban_blocks_ip
"
RunSQLQuery $Query | foreach {
	$id = $_.id
	$ipaddress = $_.ipaddress
	$Query = "UPDATE hm_fwban_rh SET ipid='$id' WHERE ipaddress='$ipaddress';"
	RunSQLQuery $Query
}

$EndTime = (Get-Date -f G)
$OperationTime = New-Timespan $StartTime $EndTime
If (($OperationTime).Hours -eq 1) {$sh = ""} Else {$sh = "s"}
If (($OperationTime).Minutes -eq 1) {$sm = ""} Else {$sm = "s"}
If (($OperationTime).Seconds -eq 1) {$ss = ""} Else {$ss = "s"}

$EmailBody = ("Retroactive hm_fwban_blocks_ip fill script compete.`n`nUpdate completed in {0:%h} hour$sh {0:%m} minute$sm {0:%s} second$ss" -f $OperationTime)
Write-Host $EmailBody
#EmailResults