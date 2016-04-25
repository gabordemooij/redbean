<?php

chdir( '..' );
$xdebugSupported = (function_exists('xdebug_start_code_coverage'));

if ($xdebugSupported) xdebug_start_code_coverage( XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE );
require 'testcontainer/rb.php';

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

echo '*** RedUNIT ***'.PHP_EOL;
echo 'Welcome to RedUNIT Unit testing framework for RedBeanPHP.'.PHP_EOL;
echo PHP_EOL;

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

require_once( 'RedUNIT/Pretest.php' );

$extraTestsFromHook = array();
$hookPath = '';

$colorMap = array(
		 'mysql'  => '0;31',
		 'pgsql'  => '0;32',
		 'sqlite' => '0;34',
);

@include 'cli/test_hook.php';

//Configure the databases
if ( isset( $ini['mysql'] ) ) {
	$dsn = "mysql:host={$ini['mysql']['host']};dbname={$ini['mysql']['schema']}";

	R::addDatabase( 'mysql', $dsn, $ini['mysql']['user'], $ini['mysql']['pass'], FALSE );

	R::selectDatabase( 'mysql' );

	R::exec( ' SET GLOBAL sql_mode="" ' );
}

//HHVM on Travis does not yet support PostgreSQL.
if ( defined( 'HHVM_VERSION' ) ) {
	unset( $ini['pgsql'] );
} else {
	if ( isset( $ini['pgsql'] ) ) {
		$dsn = "pgsql:host={$ini['pgsql']['host']};dbname={$ini['pgsql']['schema']}";
		R::addDatabase( 'pgsql', $dsn, $ini['pgsql']['user'], $ini['pgsql']['pass'], FALSE );
	}
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

$arguments = $_SERVER['argc'];

$mode = 'all';
if ( $arguments > 1 ) {
	$mode = $_SERVER['argv'][1];
}

$classSpec = null;
if ( $arguments > 2 ) {
	$classSpec = $_SERVER['argv'][2];
}

$covFilter = null;
if ( $arguments > 3 ) {
	$covFilter = $_SERVER['argv'][3];
}

$path = 'RedUNIT/';

// Possible Selections
$packList = array();

$allPacks = array(
	'Blackhole/Version',
	'Blackhole/Toolbox',
	'Blackhole/Fusebox',
	'Blackhole/Labels',
	'Blackhole/Tainted',
	'Blackhole/Meta',
	'Blackhole/Import',
	'Blackhole/Export',
	'Blackhole/Glue',
	'Blackhole/Plugins',
	'Blackhole/Debug',
	'Base/Dispense',
	'Base/Logging',
	'Base/Cursors',
	'Base/Indexes',
	'Base/Issue408',
	'Base/Threeway',
	'Base/Chill',
	'Base/Arrays',
	'Base/Boxing',
	'Base/Typechecking',
	'Base/Observers',
	'Base/Database',
	'Base/Foreignkeys',
	'Base/Namedparams',
	'Base/Prefixes',
	'Base/Copy',
	'Base/Dup',
	'Base/Update',
	'Base/Utf8',
	'Base/Batch',
	'Base/Bean',
	'Base/Writecache',
	'Base/Relations',
	'Base/Association',
	'Base/Aliasing',
	'Base/Cross',
	'Base/Finding',
	'Base/Facade',
	'Base/Frozen',
	'Base/Fuse',
	'Base/Tags',
	'Base/Xnull',
	'Base/Largenum',
	'Base/Issue90',
	'Base/Issue259',
	'Base/Issue303',
	'Base/Nuke',
	'Base/Keywords',
	'Base/Count',
	'Base/With',
	'Base/Joins',
	'Base/Traverse',
	'Base/Misc',
	'Base/Via',
	'Mysql/Preexist',
	'Mysql/Double',
	'Mysql/Writer',
	'Mysql/Freeze',
	'Mysql/Setget',
	'Mysql/Foreignkeys',
	'Mysql/Parambind',
	'Mysql/Uuid',
	'Mysql/Bigint',
	'Mysql/Issue411',
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
	//Add plugin pack to list.
	if ( count($packList) === 0 && count($extraTestsFromHook) === 0 ) {
		$packList[] = $mode;
	}
}

// Always include the last ones.
$packList = array_unique(array_merge( $packList, $suffix, $extraTestsFromHook ));
$j = 0;
foreach ( $packList as $testPack ) {
	$j++;
	if ( file_exists( $path . $testPack . '.php' ) ) require( $path . $testPack . '.php' );
	elseif ( file_exists( $hookPath . $testPack . '.php') ) require( $hookPath . $testPack . '.php' );
	$testPack = str_replace( '../', '', $testPack );
	if ($j === 1 && $classSpec) {
		$testClass = $classSpec;
	} else {
		$testClassName = str_replace( ' ', '\\', ( str_replace( '/', ' ', $testPack ) ) );
		$testClass     = '\\RedUNIT\\' . ucfirst( $testClassName );
	}
	$test          = new $testClass();
	$drivers       = $test->getTargetDrivers();
	maintestpack( str_replace( '_', ' ', get_class( $test ) ) );
	$round = 0;
	$test->setRound( $round );
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
			$test->setRound( ++$round );
		}
	} else {
		$test->prepare();
		$test->run();
		$test->cleanUp();
	}
}

if (!$xdebugSupported) {
	echo 'Done. No report - XDEBUG not installed.';
	exit(0);
}

$report = xdebug_get_code_coverage();
$misses = 0;
$hits = 0;

$covLines = array();
foreach( $report as $file => $lines ) {

	$pi = pathinfo( $file );

	if ( $covFilter !== null ) {
		if ( strpos( $file, $covFilter ) === false ) continue;
	} else {
		if ( strpos( $file, '/rb.php' ) === false) {
			if ( strpos( $file, 'phar/' ) === false ) continue;
			if ( strpos( $file, '.php' ) === false ) continue;
			if ( strpos( $file, 'RedBeanPHP' ) === false ) continue;
		}
	}
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
if ( $hits > 0 ) {
	$perc = ( $hits / ( $hits + $misses ) ) * 100;
} else {
	$perc = 0;
}
echo 'Code Coverage: '.PHP_EOL;
echo 'Hits: '.$hits.PHP_EOL;
echo 'Misses: '.$misses.PHP_EOL;
echo 'Percentage: '.$perc.' %'.PHP_EOL;
exit( 0 );
