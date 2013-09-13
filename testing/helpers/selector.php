<?php
/**
 * Selector
 *
 * The role of the selector is to load (i.e. select) the tests that should be
 * run and to instrument the tests using phpcoverage tools.
 *
 */
error_reporting( E_ALL );

//Load configuration file
if ( file_exists( '../config/test.ini' ) ) {
	$ini = parse_ini_file( "../config/test.ini", TRUE );
} elseif ( file_exists( '../config/test-travis.ini' ) ) {
	$ini = parse_ini_file( "../config/test-travis.ini", TRUE );
} else {
	die( 'Cant find configuration file.' );
}

/**
 * Define some globals.
 */
global $pdo;
global $currentDriver;

//Load basic functions and classes
require_once( '../helpers/functions.php' );
require_once( '../helpers/classes.php' );

//Load main classes
require_once( '../RedUNIT/Base.php' );
require_once( '../RedUNIT/Blackhole.php' );
require_once( '../RedUNIT/Mysql.php' );
require_once( '../RedUNIT/Postgres.php' );
require_once( '../RedUNIT/Sqlite.php' );
require_once( '../RedUNIT/CUBRID.php' );
require_once( '../RedUNIT/Oracle.php' );
require_once( '../RedUNIT/Plugin.php' );

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
if ( $arguments > 2 ) {
	$mode = $_SERVER['argv'][2];
}

$path = '../RedUNIT/';

// Possible Selections
$packList = array();

$allPacks = array(
	'Blackhole/Version',
	'Blackhole/DIContainer',
	'Blackhole/Fusebox',
	'Blackhole/Labels',
	'Blackhole/Tainted',
	'Blackhole/Meta',
	'Blackhole/Import',
	'Blackhole/Export',
	'Blackhole/Glue',
	'Base/Dispense',
	'Base/Preloading',
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
	'Base/Extassoc',
	'Base/Cross',
	'Base/Finding',
	'Base/Facade',
	'Base/Fuse',
	'Base/Tags',
	'Base/Null',
	'Base/Issue90',
	'Base/Issue259',
	'Base/Nuke',
	'Base/Keywords',
	'Base/Count',
	'Base/Chill',
	'Base/With',
	'Base/Misc',
	'CUBRID/Setget',
	'CUBRID/Writer',
	'Oracle/Base',
	'Oracle/Database',
	'Oracle/Facade',
	'Oracle/Writer',
	'Mysql/Preexist',
	'Mysql/Double',
	'Mysql/Writer',
	'Mysql/Freeze',
	'Mysql/Setget',
	'Mysql/Mix',
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

$pluginPacks = array(
	'Plugin/Export',
	'Plugin/Graph',
	'Plugin/Beancan',
	'Plugin/Cache',
	'Plugin/Timeline'
);

// Default (mode == all)
if ( $mode == 'all' ) {
	$packList = $allPacks;
} elseif ( $mode == 'plugins' ) {
	$packList = $pluginPacks;
} elseif ( $mode == 'all+plugins' ) {
	$packList = array_merge( $allPacks, $pluginPacks );
} else {
	foreach ( array_merge( $allPacks, $pluginPacks ) as $pack ) {
		if ( strpos( $pack, $mode ) === 0 ) $packList[] = $pack;
	}
}

// Always include the last ones.
$packList = array_merge( $packList, $suffix );
foreach ( $packList as $testPack ) {
	require_once( $path . $testPack . '.php' );

	$testClassName = str_replace( ' ', '_', ( str_replace( '/', ' ', $testPack ) ) );

	$testClass     = 'RedUNIT_' . ucfirst( $testClassName );

	$test          = new $testClass();

	$drivers       = $test->getTargetDrivers();

	maintestpack( str_replace( '_', ' ', get_class( $test ) ) );

	if ( $drivers && is_array( $drivers ) ) {
		foreach ( $drivers as $driver ) {
			if ( !isset( $ini[$driver] ) ) continue;

			echo '(' . $driver . '):';

			activate_driver( $driver );

			$currentDriver = $driver;

			$test->setCurrentDriver( $driver );

			$test->prepare();
			$test->run();
			$test->cleanUp();
		}
	} else {
		$test->prepare();
		$test->run();
		$test->cleanUp();
	}
}
