<?php

use \R as R;
require 'RedUNIT/CUBRID.php';
//Load the CUBRID query writer
require('../RedBeanPHP/QueryWriter/CUBRID.php');

//Define extra test packages from the hook
$extraTestsFromHook = array(
	'CUBRID/Setget',
	'CUBRID/Writer'
);

//Create a connection for this database
$ini['CUBRID'] = array(
	'host'	=> 'localhost',
	'schema' => 'cubase',
	'user'	=> 'dba',
	'pass'	=> ''
);

$colorMap[ 'CUBRID' ] = '0;35';

$dsn = "cubrid:host={$ini['CUBRID']['host']};port=33000;dbname={$ini['CUBRID']['schema']}";
R::addDatabase( 'CUBRID', $dsn, $ini['CUBRID']['user'], $ini['CUBRID']['pass'], FALSE );
R::selectDatabase( 'CUBRID' );
R::exec( 'AUTOCOMMIT IS ON' );
