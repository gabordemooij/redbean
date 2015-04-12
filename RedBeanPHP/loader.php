<?php

//Set the directory path
define('REDBEANPHP_MAIN_DIR', dirname(__FILE__) . '/');

//Load Database drivers
require( REDBEANPHP_MAIN_DIR . 'LoggerInterface.php' );
require( REDBEANPHP_MAIN_DIR . 'Logger/RDefault.php' );
require( REDBEANPHP_MAIN_DIR . 'Logger/RDefault/Debug.php' );
require( REDBEANPHP_MAIN_DIR . 'DriverInterface.php' );
require( REDBEANPHP_MAIN_DIR . 'Driver/RPDO.php' );

//Load Infrastructure
require( REDBEANPHP_MAIN_DIR . 'OODBBean.php' );
require( REDBEANPHP_MAIN_DIR . 'Observable.php' );
require( REDBEANPHP_MAIN_DIR . 'Observer.php' );

//Load Database Adapters
require( REDBEANPHP_MAIN_DIR . 'AdaptorInterface.php' );
require( REDBEANPHP_MAIN_DIR . 'Adapter/DBAdapter.php' );
require( REDBEANPHP_MAIN_DIR . 'CursorInterface.php');
require( REDBEANPHP_MAIN_DIR . 'Cursor/PDOCursor.php');
require( REDBEANPHP_MAIN_DIR . 'Cursor/NullCursor.php');
require( REDBEANPHP_MAIN_DIR . 'BeanCollection.php' );

//Load SQL drivers
require( REDBEANPHP_MAIN_DIR . 'QueryWriterInterface.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/Base.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/MySQL.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/SQLiteT.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/PostgreSQL.php' );

//Load required Exceptions
require( REDBEANPHP_MAIN_DIR . 'RedException/Base.php' );
require( REDBEANPHP_MAIN_DIR . 'RedException/SQL.php' );

//Load Repository Classes
require( REDBEANPHP_MAIN_DIR . 'Repository/Base.php' );
require( REDBEANPHP_MAIN_DIR . 'Repository/Fluid.php' );
require( REDBEANPHP_MAIN_DIR . 'Repository/Frozen.php' );

//Load Core functionality
require( REDBEANPHP_MAIN_DIR . 'OODB.php' );
require( REDBEANPHP_MAIN_DIR . 'ToolBox.php' );
require( REDBEANPHP_MAIN_DIR . 'Finder.php' );

//Load extended functionality
require( REDBEANPHP_MAIN_DIR . 'AssociationManager.php' );
require( REDBEANPHP_MAIN_DIR . 'BeanHelperInterface.php' );
require( REDBEANPHP_MAIN_DIR . 'BeanHelper/SimpleFacadeBeanHelper.php' );

/* Developer Comfort */
require( REDBEANPHP_MAIN_DIR . 'SimpleModel.php' );
require( REDBEANPHP_MAIN_DIR . 'SimpleModelHelper.php' );
require( REDBEANPHP_MAIN_DIR . 'TagManager.php' );
require( REDBEANPHP_MAIN_DIR . 'LabelMaker.php' );
require( REDBEANPHP_MAIN_DIR . 'Facade.php' );
require( REDBEANPHP_MAIN_DIR . 'DuplicationManager.php' );
require( REDBEANPHP_MAIN_DIR . 'PluginInterface.php' );
require( REDBEANPHP_MAIN_DIR . 'Functions.php' );

//Allow users to mount the plugin folder.
if ( defined( 'REDBEANPHP_PLUGINS' ) ) {
    Phar::mount( 'RedBeanPHP/Plugin', REDBEANPHP_PLUGINS );
}

//make some classes available for backward compatibility
class RedBean_SimpleModel extends \RedBeanPHP\SimpleModel {};

if (!class_exists('R')) {
	class R extends \RedBeanPHP\Facade{};
}
