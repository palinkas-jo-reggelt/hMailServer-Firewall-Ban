
# hMailServer Firewall Ban

Ban hMailServer rejects to Windows Defender Firewall.

## Changelog

- 0.35 changed Hits Per Day chart from polynomial regression to plain after crazy out of whack result line appeared (google says to beware of skewing); moved all of chart javascript into chart php files for housekeeping purposes
- 0.34 improved reban IP elements
- 0.33 minor formatting changes; added stats.php which is the same as index.php except with a) no links and b) no includes except cred.php and is meant as a public information page
- 0.33 added "stats.php": an information only (no links) page for public view; rename to index and drop anywhere with cred.php for public viewing of stats
- 0.32 added NEW FLAG = "4"; EventHandlers.vbs must be changed to reflect this change as all new hits are recorded into the database with flag=4; the reason for this is to distinguish and count IPs that are new/newly released/rebanned/manually banned; if you upgrade, you must change function fwban in EventHandlers.vbs to insert flag value = 4; added np.php (not yet processed) to view new/newly released/rebanned/manually banned entries; added count new/newly released/rebanned/manually banned entries on index.php; changes to hmsFirewallBan.ps1 to process these other changes
- 0.31 added delete duplicate entries control to powershell script; tweaked index.php for duplicate entries; added LockFile function to EventHandlers.vbs
- 0.30 added duplicate entries table
- 0.29 changed "Top 5 spammer IPs" to "Last 5 duplicate IPs" which is far more informative, I think. Ultimately, you don't want duplicates at all, unless its a return after expiring a firewall rule. Its a good way to see if your hMailServer filters/triggers are working like a finely tuned machine.
- 0.28 added plural function for "hit"/"hits"; rounded avg hits per hour data to 1 decimal; changed db queries in hmsFirewallBan.ps1 from "WHERE something =" to "WHERE something LIKE"
- 0.27 removed "months" pages as redundant; search handles month views now
- 0.26 removed current day from "hits per day" chart so as not to skew the trendline with low number of hits early in the day; removed "history" pages because they're useless after accumulating thousands of hits (there's no point to it); in place of "history", search page defaults to ALL records
- 0.25 changed "Hits per hour" chart from total hits per hour to average hits per hour
- 0.24 converted chart data from strings to 'number', 'date' & 'timeofday' for better formatting; added regression to hits per day chart
- 0.23 proper credits for hmailserver functions and logic update to reduce redundant IPs
- 0.22 minor tweaks; added minDate to jquery datepicker to match oldest record in database
- 0.21 added chart to stats page
- 0.20 minor tweaks
- 0.19 bug fixes; fixed up search pages
- 0.18 bug fixes; added dropdown select for release status on search page
- 0.17 bug fixes; reban single IP rebans ALL matching IPs, not just an individual record; release single IP releases all matching IPs, not just a single record
- 0.16 bug fixes
- 0.15 Added review before release/reban
- 0.14 & before:  too many to list/forgot

## Prerequisites

1) Working hMailServer
2) Working MySQL with hmailserver database
3) Working Apache

## Optional Components

1) RvdH's DNS resolver if you want to lookup & reject based on zen.spamhaus.org. https://d-fault.nl/files/ - DNSResolverComponent_1.3.exe.zip - unzip and run installer.
2) RvdH's Disconnector to immediately disconnect spammer connections. https://d-fault.nl/files/ - Disconnect.zip - unzip into hms/Events folder.
3) VbsJson.vbs in order to lookup GeoIP listings. https://github.com/eklam/VbsJson/blob/master/VbsJson.vbs

## MySQL Create Table

```
CREATE TABLE hm_fwban (
	id INT NOT NULL AUTO_INCREMENT UNIQUE,
	ipaddress VARCHAR (192) NOT NULL,
	timestamp TIMESTAMP,
	ban_reason VARCHAR (192),
	countrycode VARCHAR (4),
	country VARCHAR (192),
	flag INT (1) NULL DEFAULT,
	PRIMARY KEY (id)
); 
```
   
## Instructions

1) Copy everything from EventHandlers.vbs into your EventHandlers.vbs (C:\Program Files (x86)\hMailServer\Events\EventHandlers.vbs)
2) Copy vbsjson.vbs to hMailServer Events folder (C:\Program Files (x86)\hMailServer\Events is default location)
3) Install RvdH's DNS resolver (https://d-fault.nl/files/)
4) Copy RvdH's Disconnect.exe to hMailServer Events folder.
5) Edit db variables in hmsFirewallBan.ps1
6) Using phpMyAdmin or whatever you want, add table "hm_fwban" to hmailserver database.
7) Create scheduled task to run every 5 minutes with action: 
```powershell -executionpolicy bypass -File C:\scripts\checkstate\hmsFirewallBan.ps1```
!!! TASK MUST BE RUN WITH HIGHEST PRIVILEGES !!! Or powershell will fail to create/delete firewall rules on grounds of permissions. 
8) Copy the files in /www/ to your webserver and edit the db info in cred.php and edit .htaccess to allow your subnet.
9) Sit back and watch your firewall rule count grow.


## Flag Logic

```
Flag	Meaning
====	=======
NULL	Has been added as a firewall rule
1   	Has been released from firewall (firewall rule deleted)
2   	Marked for release by manual release (release.php) but not firewall rule not yet deleted - after firewall rule deleted, reset flag to 1
3   	Marked for reban by manual reban (reban.php) but firewall rule not yet added - after firewall rule added, reset flag to NULL
4   	NEW default entry - signifies IP has been added to database but firewall rule has not yet been added - after firewall rule added, reset flag to NULL
```

## Security Notes

Security is provided by Apache. You will not want the web admin to be publicly available. The .htaccess restricts access to localhost and your LAN subnet only. If you want to allow access to the WAN, I strongly suggest you password protect the directory or something else that will keep outsiders out as they will have the ability to control your firewall.


## Other Notes

I ran across an issue where a single IP hammered my server enough times to cause ip-api.com to rate limit me (150/minute). Besides that, since firewall rules get added on an interval (via scheduled task / powershell), many connections between the interval can add redundant IPs to the rule list. To get around both of these issues I setup RvdH's disconnect and SorenR's autoban. On each trigger now, three functions are called:

1) Disconnect
2) Firewall Ban
3) Autoban

This way, autoban will prevent the same IP from getting to any of my filters and thereby prevent calling firewall ban multiple times for the same IP. This will drastically reduce the number of redundant IP firewall rules and redundant IP entries in the database. If you setup your EventHandlers.vbs this way from the very beginning, you will only possibly have duplicate IPs in the database after an IP has been released. 