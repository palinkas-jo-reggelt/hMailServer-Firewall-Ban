<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell script to retroactively add firewall rule names

.DESCRIPTION
	Adds column "rulename" to database, queries firewall for rules, extracts IP scope from rules and inserts rule name into database for each IP. 
	
.FUNCTIONALITY
	1) Fill in user variables
	2) Run script

.NOTES
	Includes email notification when complete. Also includes a search for bans with no rules to bring the firewall up to match the database.

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

$ChartHitsPerDayCombinedData = "$wwwFolder\charthitsperdaycombineddata.php"
$ChartTotalBlocksPerDayData = "$wwwFolder\charttotalblocksperdaydata.php"
$ChartHitsPerHourData = "$wwwFolder\charthitsperhourdata.php"
$ChartBlocksPerHourData = "$wwwFolder\chartblocksperhourdata.php"
$ChartBlocksPerDayData = "$wwwFolder\chartblocksperdaydata.php"
$BlocksData = "$wwwFolder\blocksdata.php"

If (Test-Path $ChartHitsPerDayCombinedData) {Remove-Item -Force -Path $ChartHitsPerDayCombinedData}
If (Test-Path $ChartTotalBlocksPerDayData) {Remove-Item -Force -Path $ChartTotalBlocksPerDayData}
If (Test-Path $ChartHitsPerHourData) {Remove-Item -Force -Path $ChartHitsPerHourData}
If (Test-Path $ChartBlocksPerHourData) {Remove-Item -Force -Path $ChartBlocksPerHourData}
If (Test-Path $ChartBlocksPerDayData) {Remove-Item -Force -Path $ChartBlocksPerDayData}
If (Test-Path $BlocksData) {Remove-Item -Force -Path $BlocksData}

New-Item $ChartHitsPerDayCombinedData -ItemType "file"
New-Item $ChartTotalBlocksPerDayData -ItemType "file"
New-Item $ChartHitsPerHourData -ItemType "file"
New-Item $ChartBlocksPerHourData -ItemType "file"
New-Item $ChartBlocksPerDayData -ItemType "file"
New-Item $BlocksData -ItemType "file"

#	Hits Per Day Combined
$Query = "
	SELECT 
		a.daily,
		a.year,
		a.month,
		a.day,
		a.ipperday,
		b.blockperday
	FROM
	(
		SELECT 
			$( DBCastDateTimeFieldAsDate 'timestamp') AS daily,
			$( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%Y') AS year,
			($( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%c') - 1) AS month,
			$( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%e') AS day,
			COUNT(id) AS ipperday 
		FROM hm_fwban 
		WHERE $( DBCastDateTimeFieldAsDate 'timestamp') < $( DBCastDateTimeFieldAsDate (DBGetCurrentDateTime ))
		GROUP BY $( DBCastDateTimeFieldAsDate 'timestamp')
		$(If ($DatabaseType -eq 'MYSQL'){Write "ORDER BY $( DBCastDateTimeFieldAsDate 'timestamp') ASC"})
	) AS a
	LEFT JOIN
	(
		SELECT 
			$( DBCastDateTimeFieldAsDate 'timestamp') AS daily, 
			COUNT(DISTINCT(ipaddress)) AS blockperday  
		FROM hm_fwban_rh 
		WHERE $( DBCastDateTimeFieldAsDate 'timestamp') < $( DBCastDateTimeFieldAsDate (DBGetCurrentDateTime )) 
		GROUP BY $(DBCastDateTimeFieldAsDate 'timestamp')
	) AS b
	ON a.daily = b.daily
	ORDER BY a.daily
"

RunSQLQuery $Query | ForEach {
	$daily = $_.daily
	$year = $_.year
	$month = $_.month
	$day = $_.day
	$ipperday = $_.ipperday
	If ([string]::IsNullOrWhiteSpace($_.blockperday)) {$blockperday = 0} Else {$blockperday = $_.blockperday}
	Write-Output "<?php echo '[new Date($year, $month, $day), $ipperday, $blockperday],'?>" | Out-File $ChartHitsPerDayCombinedData -Encoding ASCII -Append
}

#	Block Frequency
$Query = "
	SELECT 
		$( DBCastDateTimeFieldAsDate 'timestamp') AS daily,
		$( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%Y') AS year,
		($( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%c') - 1) AS month,
		$( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%e') AS day,
		COUNT(ipaddress) AS ipperday 
	FROM hm_fwban_rh 
	WHERE $( DBCastDateTimeFieldAsDate 'timestamp') < $( DBCastDateTimeFieldAsDate (DBGetCurrentDateTime )) 
	GROUP BY $(DBCastDateTimeFieldAsDate 'timestamp')
	ORDER BY daily ASC
"

RunSQLQuery $Query | ForEach {
	$daily = $_.daily
	$year = $_.year
	$month = $_.month
	$day = $_.day
	$ipperday = $_.ipperday
	Write-Output "<?php echo '[new Date($year, $month, $day), $ipperday],'?>" | Out-File $ChartTotalBlocksPerDayData -Encoding ASCII -Append
}

#	Hits Per Hour
$Query = "
	SELECT 
		hour, 
		ROUND($(If ($(IsMySQL)) { 'AVG(numhits)' } elseIf ($(IsMSSQL)) { 'AVG(CAST(numhits as DECIMAL(6,2)) )' }), 1) AS avghits 
	FROM (
		SELECT 
			$( DBCastDateTimeFieldAsDate 'timestamp') AS day, 
			$( DBCastDateTimeFieldAsHour 'timestamp') AS hour, 
			COUNT(*) as numhits 
		FROM hm_fwban 
		GROUP BY $( DBCastDateTimeFieldAsDate 'timestamp'), $( DBCastDateTimeFieldAsHour 'timestamp')
	) d 
	GROUP BY hour 
	ORDER BY hour ASC
"
RunSQLQuery $Query | ForEach {
	$hour = $_.hour
	$avghits = $_.avghits
	Write-Output "<?php echo '[[$hour, 0, 0], $avghits],'?>" | Out-File $ChartHitsPerHourData -Encoding ASCII -Append
}

#	Blocks Per Hour
$Query = "
	SELECT 
		hour, 
		ROUND(AVG(numhits), 1) AS avghits 
	FROM (
		SELECT 
			$( DBCastDateTimeFieldAsDate 'timestamp') AS day, 
			$( DBCastDateTimeFieldAsHour 'timestamp') AS hour, 
			COUNT(*) as numhits 
		FROM hm_fwban_rh 
		GROUP BY $( DBCastDateTimeFieldAsDate 'timestamp'), $( DBCastDateTimeFieldAsHour 'timestamp')
	) d 
	GROUP BY hour 
	ORDER BY hour ASC
"
RunSQLQuery $Query | ForEach {
	$hour = $_.hour
	$avghits = $_.avghits
	Write-Output "<?php echo '[[$hour, 0, 0], $avghits],'?>" | Out-File $ChartBlocksPerHourData -Encoding ASCII -Append
}

#	Blocks Per Day
$Query = "
	SELECT 
		$( DBCastDateTimeFieldAsDate 'timestamp') AS daily,
		$( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%Y') AS year,
		($( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%c') - 1) AS month,
		$( DBFormatDate (DBCastDateTimeFieldAsDate 'timestamp') '%e') AS day,
		COUNT(DISTINCT(ipaddress)) AS ipperday 
	FROM hm_fwban_rh 
	WHERE $( DBCastDateTimeFieldAsDate 'timestamp') < $(DBGetCurrentDateTime) 
	GROUP BY $( DBCastDateTimeFieldAsDate 'timestamp') 
	ORDER BY daily ASC
"

RunSQLQuery $Query | ForEach {
	$daily = $_.daily
	$year = $_.year
	$month = $_.month
	$day = $_.day
	$ipperday = $_.ipperday
	Write-Output "<?php echo '[new Date($year, $month, $day), $ipperday],'?>" | Out-File $ChartBlocksPerDayData -Encoding ASCII -Append
}

#	Top 5 Repeater IPs
Write-Output "<?php `$topfive = '`n" | Out-File $BlocksData -Encoding ASCII -Append
$Query = "
	SELECT
		a.ipaddress,
		a.countip,
		b.country
	FROM
	(
		SELECT 
			COUNT(ipaddress) AS countip, 
			ipaddress
		FROM hm_fwban_rh 
		GROUP BY ipaddress 
		$(If ($DatabaseType -eq 'MYSQL') {'ORDER BY countip DESC '} Else {' '})
	) AS a
	JOIN
	(
		SELECT ipaddress, country 
		FROM hm_fwban
	) AS b
	ON a.ipaddress = b.ipaddress
	ORDER BY countip DESC
	$( DBLimitRowsWithOffset 0 5)
"

RunSQLQuery $Query | ForEach {
	$TopFiveIP = $_.ipaddress
	$TopFiveCount = $_.countip
	$TopFiveCountry = $_.country
	Write-Output "'.number_format($TopFiveCount).' knocks by <a href=`"./repeats-ip.php?submit=Search&repeatIP=$TopFiveIP`">$TopFiveIP</a> from $TopFiveCountry<br>" | Out-File $BlocksData -Encoding ASCII -Append
}
Write-Output "' ?>`n" | Out-File $BlocksData -Encoding ASCII -Append

#	This week's daily blocks
Write-Output "<?php `$dailyblocks = '`n" | Out-File $BlocksData -Encoding ASCII -Append

#	Yesterday
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddDays(-1)).ToString("yyyy-MM-dd")) 00:00:00' AND '$(((Get-Date).AddDays(-1)).ToString("yyyy-MM-dd")) 23:59:59'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddDays(-1)).ToString("yyyy-MM-dd"))`">'.number_format($ipsblocked).' IPs blocked</a> Yesterday attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}

#	2 days ago
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddDays(-2)).ToString("yyyy-MM-dd")) 00:00:00' AND '$(((Get-Date).AddDays(-2)).ToString("yyyy-MM-dd")) 23:59:59'
"

RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddDays(-2)).ToString("yyyy-MM-dd"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddDays(-2)).DayOfWeek) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}

#	3 days ago
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddDays(-3)).ToString("yyyy-MM-dd")) 00:00:00' AND '$(((Get-Date).AddDays(-3)).ToString("yyyy-MM-dd")) 23:59:59'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddDays(-3)).ToString("yyyy-MM-dd"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddDays(-3)).DayOfWeek) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}

#	4 days ago
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddDays(-4)).ToString("yyyy-MM-dd")) 00:00:00' AND '$(((Get-Date).AddDays(-4)).ToString("yyyy-MM-dd")) 23:59:59'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddDays(-4)).ToString("yyyy-MM-dd"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddDays(-4)).DayOfWeek) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}

#	5 days ago
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddDays(-5)).ToString("yyyy-MM-dd")) 00:00:00' AND '$(((Get-Date).AddDays(-5)).ToString("yyyy-MM-dd")) 23:59:59'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddDays(-5)).ToString("yyyy-MM-dd"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddDays(-5)).DayOfWeek) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}
Write-Output "' ?>`n" | Out-File $BlocksData -Encoding ASCII -Append


#	This year's monthly blocks
Write-Output "<?php `$monthlyblocks = '`n" | Out-File $BlocksData -Encoding ASCII -Append

#	Last month
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddMonths(-1)).ToString("yyyy-MM"))-01 00:00:00' AND '$((Get-Date).ToString("yyyy-MM"))-01 00:00:00'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddMonths(-1)).ToString("yyyy-MM"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddMonths(-1)).ToString("MMMM")) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}

#	2 months ago
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddMonths(-2)).ToString("yyyy-MM"))-01 00:00:00' AND '$(((Get-Date).AddMonths(-1)).ToString("yyyy-MM"))-01 00:00:00'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddMonths(-2)).ToString("yyyy-MM"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddMonths(-2)).ToString("MMMM")) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}

#	3 months ago
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddMonths(-3)).ToString("yyyy-MM"))-01 00:00:00' AND '$(((Get-Date).AddMonths(-2)).ToString("yyyy-MM"))-01 00:00:00'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddMonths(-3)).ToString("yyyy-MM"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddMonths(-3)).ToString("MMMM")) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}

#	4 months ago
$Query = "
	SELECT 
		COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
		COUNT(ipaddress) AS totalblocks 
	FROM hm_fwban_rh 
	WHERE timestamp BETWEEN '$(((Get-Date).AddMonths(-4)).ToString("yyyy-MM"))-01 00:00:00' AND '$(((Get-Date).AddMonths(-3)).ToString("yyyy-MM"))-01 00:00:00'
"
RunSQLQuery $Query | ForEach {
	$ipsblocked = $_.ipsblocked
	$totalblocks = $_.totalblocks
	Write-Output "<a href=`"./repeats-view.php?ipdate=Date&submit=Search&search=$(((Get-Date).AddMonths(-4)).ToString("yyyy-MM"))`">'.number_format($ipsblocked).' IPs blocked</a> $(((Get-Date).AddMonths(-4)).ToString("MMMM")) attemtpting access '.number_format($totalblocks).' times<br>" | Out-File $BlocksData -Encoding ASCII -Append
}
Write-Output "' `n?>`n" | Out-File $BlocksData -Encoding ASCII -Append

#	Max blocks for "Today's Total Blocks" dial
Write-Output "<?php `n" | Out-File $BlocksData -Encoding ASCII -Append
$Query = "
	SELECT	
		ROUND(((COUNT(ipaddress)) * 1.2), -3) AS dailymax,
		$( DBCastDateTimeFieldAsDate 'timestamp' ) AS daily
	FROM hm_fwban_rh
	GROUP BY daily
	ORDER BY dailymax DESC
	$( DBLimitRowsWithOffset 0 1 )
"
RunSQLQuery $Query | ForEach {
	$maxblocks = $_.dailymax
	Write-Output "`$redToBlock = '$maxblocks';" | Out-File $BlocksData -Encoding ASCII -Append
}
Write-Output " `n?>`n" | Out-File $BlocksData -Encoding ASCII -Append

#	Max blocked IPs for "Today's IPs Blocked" dial
Write-Output "<?php `n" | Out-File $BlocksData -Encoding ASCII -Append
$Query = "
	SELECT	
		ROUND(((COUNT(DISTINCT(ipaddress))) * 1.2), -1) AS dailymax,
		$( DBCastDateTimeFieldAsDate 'timestamp' ) AS daily
	FROM hm_fwban_rh
	GROUP BY daily
	ORDER BY dailymax DESC
	$( DBLimitRowsWithOffset 0 1 )
"
RunSQLQuery $Query | ForEach {
	$maxblocks = $_.dailymax
	Write-Output "`$redToIP = '$maxblocks';" | Out-File $BlocksData -Encoding ASCII -Append
}
Write-Output " `n?>`n" | Out-File $BlocksData -Encoding ASCII -Append

#	List of banned countries 
Write-Output "<?php `n`$country_list=array(" | Out-File $BlocksData -Encoding ASCII -Append
$Query = "
	SELECT	
		DISTINCT(country) AS country_list
	FROM hm_fwban
"
RunSQLQuery $Query | ForEach {
	$country_list = $_.country_list
	Write-Output "   '$country_list'," | Out-File $BlocksData -Encoding ASCII -Append
}
Write-Output "); `n?>`n" | Out-File $BlocksData -Encoding ASCII -Append

#	List of ban reasons
Write-Output "<?php `n`$ban_reason_list=array(" | Out-File $BlocksData -Encoding ASCII -Append
$Query = "
	SELECT	
		DISTINCT(ban_reason) AS ban_reason_list
	FROM hm_fwban
"
RunSQLQuery $Query | ForEach {
	$ban_reason_list = $_.ban_reason_list
	Write-Output "   '$ban_reason_list'," | Out-File $BlocksData -Encoding ASCII -Append
}
Write-Output "); `n?>`n" | Out-File $BlocksData -Encoding ASCII -Append
