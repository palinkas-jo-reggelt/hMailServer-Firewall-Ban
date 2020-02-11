<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Database Setup

.DESCRIPTION

.FUNCTIONALITY

.NOTES

.EXAMPLE

#>

# Include required files

Try {
	.("$PSScriptRoot\Config.ps1")
	.("$PSScriptRoot\CommonCode.ps1")
}
Catch {
	Write-Output "Error while loading supporting PowerShell Scripts" | Out-File -Path "$PSScriptRoot\PSError.log"
}

If ($DatabaseType -eq "MSSQL") {

	#	Create hm_fwban table if it doesn't exist
	$Query = "
		IF NOT EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME = 'hm_fwban')
		BEGIN
			CREATE TABLE hm_fwban (
				ID int IDENTITY(1,1) NOT NULL PRIMARY KEY,
				ipaddress varchar NOT NULL,
				timestamp datetime NOT NULL,
				ban_reason varchar(192) DEFAULT NULL,
				country varchar(192) DEFAULT NULL,
				flag int DEFAULT NULL,
				helo varchar(192) DEFAULT NULL,
				ptr varchar(192) DEFAULT NULL,
				rulename varchar(192) DEFAULT NULL
			)
		END;
		"
	RunSQLQuery $Query

	#	Create hm_fwban_rh table if it doesn't exist
	$Query = "
		IF NOT EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME = 'hm_fwban_rh')
		BEGIN
			CREATE TABLE hm_fwban_rh (
				id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
				timestamp datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				ipaddress varchar(15) NOT NULL
			)
		END;
		"
	RunSQLQuery $Query

	#	Create hm_ids table if it doesn't exist
	$Query = "
		IF NOT EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME = 'hm_ids')
		BEGIN
			CREATE TABLE hm_ids (
				timestamp datetime NOT NULL,
				ipaddress varchar(15) NOT NULL PRIMARY KEY,
				hits int NOT NULL,
				country varchar(64) DEFAULT NULL,
				helo varchar(128) DEFAULT NULL
			)
		END;
		"
	RunSQLQuery $Query

	#Create MSSQL Function equivalent to INET_ATON() from MySQL
	#first drop if exists
	$Query = "
	IF EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME = 'ipStringToInt')
		DROP FUNCTION dbo.ipStringToInt 
	"
	RunSQLQuery $Query
	#then create
	$Query = "
		CREATE FUNCTION dbo.ipStringToInt 
		( 
			@ip CHAR(15) 
		) 
		RETURNS INT 
		AS 
		BEGIN 
			DECLARE @rv INT, 
				@o1 INT, 
				@o2 INT, 
				@o3 INT, 
				@o4 INT, 
				@base INT 
		
			SELECT 
				@o1 = CONVERT(INT, PARSENAME(@ip, 4)), 
				@o2 = CONVERT(INT, PARSENAME(@ip, 3)), 
				@o3 = CONVERT(INT, PARSENAME(@ip, 2)), 
				@o4 = CONVERT(INT, PARSENAME(@ip, 1)) 
		
			IF (@o1 BETWEEN 0 AND 255) 
				AND (@o2 BETWEEN 0 AND 255) 
				AND (@o3 BETWEEN 0 AND 255) 
				AND (@o4 BETWEEN 0 AND 255) 
			BEGIN      
				SELECT @base = CASE 
					WHEN @o1 < 128 THEN 
						(@o1 * 16777216) 
					ELSE 
						-(256 - @o1) * 16777216 
					END 
		
				SET @rv = @base +  
					(@o2 * 65536) +  
					(@o3 * 256) + 
					(@o4) 
			END 
			ELSE 
				SET @rv = -1 
			RETURN @rv 
		END
	"
	RunSQLQuery $Query


}

If ($DatabaseType -eq "MYSQL") {

	#	Create hm_fwban table if it doesn't exist
	$Query = "
		CREATE TABLE IF NOT EXISTS hm_fwban (
		ID int(11) NOT NULL AUTO_INCREMENT,
		ipaddress varchar(192) NOT NULL,
		timestamp datetime NOT NULL,
		ban_reason varchar(192) DEFAULT NULL,
		country varchar(192) DEFAULT NULL,
		flag int(1) DEFAULT NULL,
		helo varchar(192) DEFAULT NULL,
		ptr varchar(192) DEFAULT NULL,
		rulename varchar(192) DEFAULT NULL,
		PRIMARY KEY (ID),
		UNIQUE KEY ID (ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		COMMIT;
		"
	RunSQLQuery $Query

	#	Create hm_fwban_rh table if it doesn't exist
	$Query = "
	CREATE TABLE IF NOT EXISTS hm_fwban_rh (
	  id int(12) NOT NULL AUTO_INCREMENT,
	  timestamp datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	  ipaddress varchar(15) NOT NULL,
	  PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	COMMIT;
	"
	RunSQLQuery $Query

	#	Create hm_ids table if it doesn't exist
	$Query = "
	CREATE TABLE IF NOT EXISTS hm_ids (
	  timestamp datetime NOT NULL,
	  ipaddress varchar(15) NOT NULL,
	  hits int(1) NOT NULL,
	  country varchar(64) DEFAULT NULL,
	  helo varchar(128) DEFAULT NULL,
	  PRIMARY KEY (ipaddress),
	  UNIQUE KEY ipaddress (ipaddress)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	COMMIT;
	"
	RunSQLQuery $Query
}
