<?php

/**
 * Selector
 * 
 * The role of the selector is to load (i.e. select) the tests that should be
 * runned and to instrument the tests using phpcoverage tools.
 *  
 */
 
error_reporting(E_ALL);
$ini = parse_ini_file("../config/test.ini", true);
global $a;
global $pdo;

//Load basic functions and classes
require_once('../helpers/functions.php');
require_once('../helpers/classes.php');

//Load main classes
require_once('../RedUNIT/Base.php');
require_once('../RedUNIT/Blackhole.php');
require_once('../RedUNIT/Mysql.php');
require_once('../RedUNIT/Postgres.php');
require_once('../RedUNIT/Sqlite.php');

//Configure the databases
$dsn = "mysql:host={$ini['mysql']['host']};dbname={$ini['mysql']['schema']}";
R::addDatabase('mysql',$dsn,$ini['mysql']['user'],$ini['mysql']['pass'],false);
$dsn="pgsql:host={$ini['pgsql']['host']};dbname={$ini['pgsql']['schema']}";
R::addDatabase('pgsql',$dsn,$ini['pgsql']['user'],$ini['pgsql']['pass'],false);
R::addDatabase('sqlite','sqlite:'.$ini['sqlite']['file'],null,null,false);
R::selectDatabase('sqlite');

//Function to activate a driver
function activate_driver($d) {
	R::selectDatabase($d);
}

$arguments = $_SERVER['argc'];

$mode = 'all';
if ($arguments > 2) {
	$mode = $_SERVER['argv'][2];
}

$path = '../RedUNIT/';

//Possible Selections
$packList = array();
//Default (mode == all)
if ($mode == 'all') {
	$packList = array(
		'Blackhole/Version',
		'Blackhole/Tainted',
		'Blackhole/Meta',
		'Blackhole/Import',
		'Blackhole/Export',
		'Blackhole/Beancan',
		'Blackhole/Misc',
		'Base/Dispense',
		'Base/Typechecking',
		'Base/Observers',
		'Base/Database',
		'Base/Update',
		'Base/Batch',
		'Base/Relations',
		'Base/Association',
		'Base/Extassoc',
		'Base/Cross',
		'Base/Finding',
		'Base/Facade',
		'Base/Unrelated',
		'Base/Fuse',
		'Base/Formats',
		'Base/Tags',
		'Base/Graph',
		'Base/Null',
		'Base/Export',
		'Base/Issue90',
		'Base/Nuke',
		'Base/Keywords',
		'Base/Count',
		'Base/Misc',
		'Mysql/Preexist',
		'Mysql/Double',
		'Mysql/Writer',
		'Mysql/Freeze',
		'Mysql/Setget',
		'Mysql/Views',
		'Mysql/Foreignkeys',
		'Mysql/Parambind',
		'Postgres/Setget',
		'Postgres/Views',
		'Postgres/Foreignkeys',
		'Postgres/Parambind',
		'Sqlite/Setget',
		'Sqlite/Views',
		'Sqlite/Foreignkeys',
		//optimizers last
		'Mysql/Optimizer'
	);
}
else {
	$packList = array($mode);
}

global $currentDriver;
foreach($packList as $testPack) {
	require_once($path.$testPack.'.php');
	$testClassName = str_replace(' ','_',(str_replace('/',' ',$testPack)));
	$testClass = 'RedUNIT_'.ucfirst($testClassName);
	$test = new $testClass();
	$drivers = $test->getTargetDrivers();
	testpack(str_replace('_',' ',get_class($test)));
	if ($drivers && is_array($drivers)) {
		foreach($drivers as $driver) {
			echo '('.$driver.'):';
			activate_driver($driver);
			$currentDriver = $driver;
			$test->prepare();
			$test->run();
			$test->cleanUp();
		}
	}
	else {
		$test->prepare();
		$test->run();
		$test->cleanUp();
	}
}

