#!/usr/bin/env php
<?php

echo "Welcome to Replica 2 Build Script for RedBeanPHP\n";
echo "Now building your beans!\n";
echo "-------------------------------------------\n";


$code = '';
$codeMySQL = '';
$codePostgres = '';
$codeSQLite = '';

function addFile($file, $only = null) {
	global $code;
	global $codeMySQL;
	global $codePostgres;
	global $codeSQLite;
	echo 'Added ', $file , ' to package... ',PHP_EOL;
	$raw = file_get_contents($file);
	$raw = preg_replace('/namespace\s+([a-zA-Z0-9\\\;]+);/m', 'namespace $1 {', $raw);
	$raw .= '}';
	$code .= $raw;
	if ( $only == null || $only == 'mysql' ) $codeMySQL .= $raw;
	if ( $only == null || $only == 'postgres' ) $codePostgres .= $raw;
	if ( $only == null || $only == 'sqlite' ) $codeSQLite .= $raw;
}

define('DIR', 'RedBeanPHP/');

addFile( DIR . 'Logger.php' );
addFile( DIR . 'Logger/RDefault.php' );
addFile( DIR . 'Logger/RDefault/Debug.php' );
addFile( DIR . 'Driver.php' );
addFile( DIR . 'Driver/RPDO.php' );
addFile( DIR . 'OODBBean.php' );
addFile( DIR . 'Observable.php' );
addFile( DIR . 'Observer.php' );
addFile( DIR . 'Adapter.php' );
addFile( DIR . 'Adapter/DBAdapter.php' );
addFile( DIR . 'Cursor.php');
addFile( DIR . 'Cursor/PDOCursor.php');
addFile( DIR . 'Cursor/NullCursor.php');
addFile( DIR . 'BeanCollection.php' );
addFile( DIR . 'QueryWriter.php' );
addFile( DIR . 'QueryWriter/AQueryWriter.php' );
addFile( DIR . 'QueryWriter/MySQL.php', 'mysql' );
addFile( DIR . 'QueryWriter/SQLiteT.php', 'sqlite' );
addFile( DIR . 'QueryWriter/PostgreSQL.php', 'postgres' );
addFile( DIR . 'QueryWriter/CUBRID.php' );
addFile( DIR . 'RedException.php' );
addFile( DIR . 'RedException/SQL.php' );
addFile( DIR . 'Repository.php' );
addFile( DIR . 'Repository/Fluid.php' );
addFile( DIR . 'Repository/Frozen.php' );
addFile( DIR . 'OODB.php' );
addFile( DIR . 'ToolBox.php' );
addFile( DIR . 'Finder.php' );
addFile( DIR . 'AssociationManager.php' );
addFile( DIR . 'BeanHelper.php' );
addFile( DIR . 'BeanHelper/SimpleFacadeBeanHelper.php' );
addFile( DIR . 'BeanHelper/DynamicBeanHelper.php' );
addFile( DIR . 'SimpleModel.php' );
addFile( DIR . 'SimpleModelHelper.php' );
addFile( DIR . 'TagManager.php' );
addFile( DIR . 'LabelMaker.php' );
addFile( DIR . 'Facade.php' );
addFile( DIR . 'DuplicationManager.php' );
addFile( DIR . 'Util/ArrayTool.php' );
addFile( DIR . 'Util/DispenseHelper.php' );
addFile( DIR . 'Util/Dump.php' );
addFile( DIR . 'Util/MultiLoader.php' );
addFile( DIR . 'Util/Transaction.php' );
addFile( DIR . 'Util/QuickExport.php' );
addFile( DIR . 'Util/MatchUp.php' );
addFile( DIR . 'Util/Look.php' );
addFile( DIR . 'Util/Diff.php' );
addFile( DIR . 'Util/Tree.php' );
addFile( DIR . 'Util/Feature.php' );
addFile( DIR . 'Util/Either.php' );
addFile( DIR . 'Plugin.php' );

$func = file_get_contents(DIR . 'Functions.php');

$extra = "
namespace {

//make some classes available for backward compatibility
class RedBean_SimpleModel extends \RedBeanPHP\SimpleModel {};

if (!class_exists('R')) {
	class R extends \RedBeanPHP\Facade{};
}

$func

}
";

$code .= $extra;
$codeMySQL .= $extra;
$codePostgres .= $extra;
$codeSQLite .= $extra;

$code = '<?php'.str_replace('<?php', '', $code);
$codeMySQL = '<?php'.str_replace('<?php', '', $codeMySQL);
$codePostgres = '<?php'.str_replace('<?php', '', $codePostgres);
$codeSQLite = '<?php'.str_replace('<?php', '', $codeSQLite);

$files = array( 'rb.php' => $code, 'rb-mysql.php' => $codeMySQL, 'rb-postgres.php' => $codePostgres, 'rb-sqlite.php' => $codeSQLite );
foreach( $files as $file => $content ) {
	echo 'Okay, seems we have all the code.. now writing file: ', $file ,PHP_EOL;
	$b = file_put_contents($file, $content);
	echo 'Written: ',$b,' bytes.',PHP_EOL;
	if ($b > 0) {
		echo 'Done!' ,PHP_EOL;
	} else {
		echo 'Hm, something seems to have gone wrong... ',PHP_EOL;
	}
}



