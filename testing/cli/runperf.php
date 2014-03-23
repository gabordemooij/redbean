<?php

chdir( '..' );

require 'testcontainer/rb.phar';

//load core classes
require 'RedUNIT.php';
require 'RedUNIT/Base.php';
require 'RedUNIT/Base/Performance.php';


error_reporting( E_ALL );

//Load configuration file
if ( file_exists( 'config/test.ini' ) ) {
	$ini = parse_ini_file( "config/test.ini", TRUE );
} else {
	die( 'Cant find configuration file.' );
}


//Configure the databases
if ( isset( $ini['mysql'] ) ) {
	$dsn = "mysql:host={$ini['mysql']['host']};dbname={$ini['mysql']['schema']}";

	R::addDatabase( 'mysql', $dsn, $ini['mysql']['user'], $ini['mysql']['pass'], FALSE );

	R::selectDatabase( 'mysql' );

	R::exec( ' SET GLOBAL sql_mode="" ' );
}

if ( isset( $ini['pgsql'] ) ) {
	$dsn = "pgsql:host={$ini['pgsql']['host']};dbname={$ini['pgsql']['schema']}";

	R::addDatabase( 'pgsql', $dsn, $ini['pgsql']['user'], $ini['pgsql']['pass'], FALSE );
}

if ( isset( $ini['sqlite'] ) ) {
	R::addDatabase( 'sqlite', 'sqlite:' . $ini['sqlite']['file'], NULL, NULL, FALSE );
}

R::selectDatabase( 'sqlite' );

// Function to activate a driver
function activate_driver( $d )
{
	R::selectDatabase( $d );
}
$test = new \RedUNIT\Base\Performance();

$drivers       = $test->getTargetDrivers();



foreach ( $drivers as $driver ) {
			
			if ( !isset( $ini[$driver] ) ) continue;
			if ( !isset( $_SERVER['argv'][1])) die('Missing parameter.');
			
			$method = $_SERVER['argv'][1];
			
			echo "Performing test: $method with driver $driver ".PHP_EOL;
			
			$t1 = microtime( TRUE );
			
			$test->$method();
			
			$t2 = microtime( TRUE );
			
			$d = $t2 - $t1;
			
			echo "Time elapsed: $d ".PHP_EOL;
			
}