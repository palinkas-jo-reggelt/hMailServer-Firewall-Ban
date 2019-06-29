# hMailServer Firewall Ban

Ban hMailServer rejects to Windows Defender Firewall.

## Changelog

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
	id int NOT NULL AUTO_INCREMENT UNIQUE,
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
2) Copy vbsjson.vbs to hMailServer Events folder (C:\Program Files (x86)\hMailServer\Events)
3) Install RvdH's DNS resolver (https://d-fault.nl/files/)
4) Copy VbsJson to hMailServer Events folder.
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
NULL	Default - has been added to firewall rule
1   	Marked as released by auto expire after removing firewall rule
2   	Marked for release by manual release (release.php), after firewall rule deleted, reset flag to 1
3   	Marked for reban by manual reban (reban.php), after firewall rule added, reset flag to NULL
```

## Security Notes

Security is provided by Apache. You will not want the web admin to be publicly available. The .htaccess restricts access to localhost and your LAN subnet only. If you want to allow access to the WAN, I strongly suggest you password protect the directory or something else that will keep outsiders out as they will have the ability to control your firewall.


## Other Notes

I ran across an issue where a single IP hammered my server enough times to cause ip-api.com to rate limit me (150/minute). Besides that, since firewall rules get added on an interval (via scheduled task / powershell), many connections between the interval can add redundant IPs to the rule list. To get around both of these issues I setup RvdH's disconnect and SorenR's autoban. On each trigger now, three functions are called:

1) Disconnect
2) Firewall Ban
3) Autoban

This way, autoban will prevent the same IP from getting to any of my filters and thereby prevent calling firewall ban multiple times for the same IP. This will drastically reduce the number of redundant IP firewall rules and redundant IP entries in the database. If you setup your EventHandlers.vbs this way from the very beginning, you will only possibly have duplicate IPs in the database after an IP has been released. 