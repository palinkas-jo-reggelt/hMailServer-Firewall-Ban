# hMailServer Firewall Ban

## Changelog

0.15 Added review before release/reban

0.14 & before:  too many to list/forgot

## Prerequisites

1) Working hMailServer
2) Working MySQL with hmailserver database


## Optional Components

1) RvdH's DNS resolver if you want to lookup & reject based on zen.spamhaus.org. https://d-fault.nl/files/
2) VbsJson.vbs in order to lookup GeoIP listings. https://github.com/eklam/VbsJson/blob/master/VbsJson.vbs

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
8) Copy the files in /www/ to your webserver and edit the db info in cred.php.
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