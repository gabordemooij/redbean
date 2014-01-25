<?php

chdir( '..' );
xdebug_start_code_coverage( XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE );
require 'testcontainer/rb.phar';

//load core classes
require 'RedUNIT.php';

error_reporting( E_ALL );

//Load configuration file
if ( file_exists( 'config/test.ini' ) ) {
	$ini = parse_ini_file( "config/test.ini", TRUE );
} elseif ( file_exists( 'config/test-travis.ini' ) ) {
	$ini = parse_ini_file( "config/test-travis.ini", TRUE );
} else {
	die( 'Cant find configuration file.' );
}

/**
 * Define some globals.
 */
global $pdo;
global $currentDriver;

//Load basic functions and classes
require_once( 'helpers/functions.php' );
require_once( 'helpers/classes.php' );

//Load main classes
require_once( 'RedUNIT/Base.php' );
require_once( 'RedUNIT/Blackhole.php' );
require_once( 'RedUNIT/Mysql.php' );
require_once( 'RedUNIT/Postgres.php' );
require_once( 'RedUNIT/Sqlite.php' );
require_once( 'RedUNIT/CUBRID.php' );


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

if ( isset( $ini['CUBRID'] ) ) {
	$dsn = "cubrid:host={$ini['CUBRID']['host']};port=33000;dbname={$ini['CUBRID']['schema']}";

	R::addDatabase( 'CUBRID', $dsn, $ini['CUBRID']['user'], $ini['CUBRID']['pass'], FALSE );

	R::selectDatabase( 'CUBRID' );

	R::exec( 'AUTOCOMMIT IS ON' );
}

if ( isset( $ini['oracle'] ) ) {
	R::addDatabase( 'oracle', $ini['oracle']['dsn'], $ini['oracle']['user'], $ini['oracle']['pass'], FALSE );
}

R::selectDatabase( 'sqlite' );

// Function to activate a driver
function activate_driver( $d )
{
	R::selectDatabase( $d );
}


$arguments = $_SERVER['argc'];

$mode = 'all';
if ( $arguments > 1 ) {
	$mode = $_SERVER['argv'][1];
}

$path = 'RedUNIT/';

// Possible Selections
$packList = array();

$allPacks = array(
	'Blackhole/Version',
	'Blackhole/Fusebox',
	'Blackhole/Labels',
	'Blackhole/Tainted',
	'Blackhole/Meta',
	'Blackhole/Import',
	'Blackhole/Export',
	'Blackhole/Glue',
	'Blackhole/Plugins',
	'Base/Dispense',
	'Base/Boxing',
	'Base/Typechecking',
	'Base/Observers',
	'Base/Database',
	'Base/Foreignkeys',
	'Base/Namedparams',
	'Base/Copy',
	'Base/Dup',
	'Base/Update',
	'Base/Utf8',
	'Base/Batch',
	'Base/Writecache',
	'Base/Relations',
	'Base/Association',
	'Base/Aliasing',
	'Base/Cross',
	'Base/Finding',
	'Base/Facade',
	'Base/Fuse',
	'Base/Tags',
	'Base/Null',
	'Base/Issue90',
	'Base/Issue259',
	'Base/Issue303',
	'Base/Nuke',
	'Base/Keywords',
	'Base/Count',
	'Base/Chill',
	'Base/With',
	'Base/Misc',
	'CUBRID/Setget',
	'CUBRID/Writer',
	'Mysql/Preexist',
	'Mysql/Double',
	'Mysql/Writer',
	'Mysql/Freeze',
	'Mysql/Setget',
	'Mysql/Foreignkeys',
	'Mysql/Parambind',
	'Mysql/Uuid',
	'Mysql/Bigint',
	'Postgres/Setget',
	'Postgres/Foreignkeys',
	'Postgres/Parambind',
	'Postgres/Writer',
	'Postgres/Uuid',
	'Postgres/Bigint',
	'Sqlite/Setget',
	'Sqlite/Foreignkeys',
	'Sqlite/Parambind',
	'Sqlite/Writer',
	'Sqlite/Rebuild',
);

$suffix = array(
	'Blackhole/Misc',
	'Base/Close'
);



// Default (mode == all)
if ( $mode == 'all' ) {
	$packList = $allPacks;
} else {
	foreach ( $allPacks as $pack ) {
		if ( strpos( $pack, $mode ) === 0 ) $packList[] = $pack;
	}
}

// Always include the last ones.
$packList = array_merge( $packList, $suffix );
foreach ( $packList as $testPack ) {
	require_once( $path . $testPack . '.php' );

	$testClassName = str_replace( ' ', '\\', ( str_replace( '/', ' ', $testPack ) ) );

	$testClass     = '\\RedUNIT\\' . ucfirst( $testClassName );

	$test          = new $testClass();

	$drivers       = $test->getTargetDrivers();

	$colorMap      = array( 
		 'mysql'  => '0;31',
		 'pgsql'  => '0;32',
		 'sqlite' => '0;34',
		 'oracle' => '0;33',
		 'CUBRID' => '0;35' 
	);

	maintestpack( str_replace( '_', ' ', get_class( $test ) ) );

	if ( $drivers && is_array( $drivers ) ) {
		foreach ( $drivers as $driver ) {
			if ( !isset( $ini[$driver] ) ) continue;

			echo PHP_EOL;
			
			echo '===== DRIVER : (' . $driver . ') =====';

			echo PHP_EOL;
			echo PHP_EOL;

			if ( isset( $colorMap[$driver] ) ) {
				echo "\033[{$colorMap[$driver]}m";
			}

			activate_driver( $driver );

			$currentDriver = $driver;

			$test->setCurrentDriver( $driver );

			$test->prepare();
			$test->run();
			$test->cleanUp();
		
			if ( isset ( $colorMap[$driver] ) ) {
				echo "\033[0m";
			}

			echo PHP_EOL;

		}
	} else {
		$test->prepare();
		$test->run();
		$test->cleanUp();
	}
}

$report = xdebug_get_code_coverage();
$misses = 0;
$hits = 0;

$covLines = array();
foreach( $report as $file => $lines ) {
	$pi = pathinfo( $file );
	
	if ( strpos( $file, 'phar/' ) === false ) continue;
	if ( strpos( $file, '.php' ) === false ) continue;
	if ( strpos( $file, 'RedBeanPHP' ) === false ) continue;

	$covLines[] = '***** File:'.$file.' ******';
	
	$fileData = file_get_contents( $file );
	$fileLines = explode( "\n", $fileData );
	$i = 1;
	foreach( $fileLines as $covLine ) {
		if ( isset( $lines [$i] ) ) {
			if ( $lines[$i] === 1 ) {
				$covLines[] = '[ OK      ] '.$covLine;
				$hits ++;
			} else if ( $lines[$i] === -1 ){
				$covLines[] = '[ MISSED! ] '.$covLine;
				$misses ++;
			} else {
				$covLines[] = '[ -       ] '.$covLine;
			}
		} else {
			$covLines[] = '[ -       ] '.$covLine;
		}
		$i ++;
	}
}
$covFile = implode( "\n", $covLines );
@file_put_contents( 'cli/coverage_log.txt', $covFile );
$perc = ( $hits / ( $hits + $misses ) ) * 100;

echo 'Code Coverage: '.PHP_EOL;
echo 'Hits: '.$hits.PHP_EOL;
echo 'Misses: '.$misses.PHP_EOL;
echo 'Percentage: '.$perc.' %'.PHP_EOL;
exit( 0 );