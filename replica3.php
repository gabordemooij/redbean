<?php

echo 'Welcome to Replica3'.PHP_EOL;
echo 'Building regular rb.php file for you now... (non-phar).'.PHP_EOL;
echo '--------------------------------------------------------'.PHP_EOL;


$code = '';

function addFile($file) {
	global $code;
	echo 'Added '. $file . ' to package... '.PHP_EOL;
	$raw = file_get_contents($file);
	$raw = preg_replace('/namespace\s+([a-zA-Z0-9\\\;]+);/m', 'namespace $1 {', $raw);
	$raw .= '}';
	$code .= $raw;
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
addFile( DIR . 'QueryWriter.php' );
addFile( DIR . 'QueryWriter/AQueryWriter.php' );
addFile( DIR . 'QueryWriter/MySQL.php' );
addFile( DIR . 'QueryWriter/SQLiteT.php' );
addFile( DIR . 'QueryWriter/PostgreSQL.php' );
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
addFile( DIR . 'SimpleModel.php' );
addFile( DIR . 'SimpleModelHelper.php' );
addFile( DIR . 'TagManager.php' );
addFile( DIR . 'LabelMaker.php' );
addFile( DIR . 'Facade.php' );
addFile( DIR . 'DuplicationManager.php' );
addFile( DIR . 'Plugin.php' );

$func = file_get_contents(DIR . 'Functions.php');

$code .= "
namespace {

//make some classes available for backward compatibility
class RedBean_SimpleModel extends \RedBeanPHP\SimpleModel {};
class R extends \RedBeanPHP\Facade{};

$func

}
";

$code = '<?php'.str_replace('<?php', '', $code);

echo 'Okay, seems we have all the code.. now writing file: rb.php' .PHP_EOL;

$b = file_put_contents('rb.php', $code);

echo 'Written: '.$b.' bytes.'.PHP_EOL;

if ($b > 0) {
	echo 'Done!' .PHP_EOL;
} else {
	echo 'Hm, something seems to have gone wrong... '.PHP_EOL;
}


