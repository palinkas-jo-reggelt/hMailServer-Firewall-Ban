<?php

/* Database Variables 

	'dbtype' = database server type
	
		For MySQL use dbtype = 'mysql'
		For MSSQL use dbtype = 'mssql'

	'driver' = connection type
	
		For MySQL use driver = 'mysql'
		For MSSQL use driver = 'mssql'
		For ODBC  use driver = 'odbc'
		
		* When opting for ODBC use correct DSN! *
		* Example: "MariaDB ODBC 3.0 Driver".   *
		* Exact spelling is critical!           *
	
*/

$Database = array (
	'dbtype'      => 'mysql',
	'host'        => 'localhost',
	'username'    => 'hmailserver',
	'password'    => 'supersecretpassword',
	'dbname'      => 'hmailserver',
	'driver'      => 'mysql',
	'port'        => '3306',
	'dsn'         => 'MariaDB ODBC 3.0 Driver'
);


/* 	GeoLite2MySQL - GeoIP Database Using MaxMind Data
	https://github.com/palinkas-jo-reggelt/GeoLite2MySQL
	
	Database Variables follow the same rules as above.
	
	If GeoLite2MySQL is in use, set 'use_geoip' to 'true'. 
	
	Using a database vs calling ip-api.com for geoip requests will 
	prevent rate limiting in situations where there are a large number 
	of calls. Eg. manually banning a /24 IP range with 256 individual 
	bans - calling 256 geoip requests - exceeding the 150/minute 
	rate limit at ip-api.com.

*/

$GeoIPDatabase = array (
	'use_geoip'   => 'false',
	'dbtype'      => 'mysql',
	'host'        => 'localhost',
	'username'    => 'geoip',
	'password'    => 'supersecretpassword',
	'dbname'      => 'geoip',
	'driver'      => 'mysql',
	'port'        => '3306',
	'dsn'         => 'MariaDB ODBC 3.0 Driver'
);


/*	PowershellScriptDir - Location of project powershell files.  */

$PowershellScriptDir = "C:\\scripts\\hmailserver\\FWBan\\";

?>