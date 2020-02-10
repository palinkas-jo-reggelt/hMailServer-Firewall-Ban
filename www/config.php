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
	'password'    => 'SSnGLBs8XswL2r0h',
	'dbname'      => 'hmailserver',
	'driver'      => 'mysql',
	'port'        => '3306',
	'dsn'         => 'MariaDB ODBC 3.0 Driver'
);

?>