<?php

//Set the directory path
define('REDBEANPHP_MAIN_DIR', 'phar://rb.phar/RedBeanPHP/');

//Load Database drivers
require( REDBEANPHP_MAIN_DIR . 'Logger.php' );
require( REDBEANPHP_MAIN_DIR . 'Logger/RDefault.php' );
require( REDBEANPHP_MAIN_DIR . 'Logger/RDefault/Debug.php' );
require( REDBEANPHP_MAIN_DIR . 'Driver.php' );
require( REDBEANPHP_MAIN_DIR . 'Driver/RPDO.php' );

//Load Infrastructure
require( REDBEANPHP_MAIN_DIR . 'OODBBean.php' );
require( REDBEANPHP_MAIN_DIR . 'Observable.php' );
require( REDBEANPHP_MAIN_DIR . 'Observer.php' );

//Load Database Adapters
require( REDBEANPHP_MAIN_DIR . 'Adapter.php' );
require( REDBEANPHP_MAIN_DIR . 'Adapter/DBAdapter.php' );
require( REDBEANPHP_MAIN_DIR . 'Cursor.php');
require( REDBEANPHP_MAIN_DIR . 'Cursor/PDOCursor.php');
require( REDBEANPHP_MAIN_DIR . 'Cursor/NullCursor.php');
require( REDBEANPHP_MAIN_DIR . 'BeanCollection.php' );

//Load SQL drivers
require( REDBEANPHP_MAIN_DIR . 'QueryWriter.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/AQueryWriter.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/MySQL.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/SQLiteT.php' );
require( REDBEANPHP_MAIN_DIR . 'QueryWriter/PostgreSQL.php' );

//Load required Exceptions
require( REDBEANPHP_MAIN_DIR . 'RedException.php' );
require( REDBEANPHP_MAIN_DIR . 'RedException/SQL.php' );

//Load Repository Classes
require( REDBEANPHP_MAIN_DIR . 'Repository.php' );
require( REDBEANPHP_MAIN_DIR . 'Repository/Fluid.php' );
require( REDBEANPHP_MAIN_DIR . 'Repository/Frozen.php' );

//Load Core functionality
require( REDBEANPHP_MAIN_DIR . 'OODB.php' );
require( REDBEANPHP_MAIN_DIR . 'ToolBox.php' );
require( REDBEANPHP_MAIN_DIR . 'Finder.php' );

//Load extended functionality
require( REDBEANPHP_MAIN_DIR . 'AssociationManager.php' );
require( REDBEANPHP_MAIN_DIR . 'BeanHelper.php' );
require( REDBEANPHP_MAIN_DIR . 'BeanHelper/SimpleFacadeBeanHelper.php' );

/* Developer Comfort */
require( REDBEANPHP_MAIN_DIR . 'SimpleModel.php' );
require( REDBEANPHP_MAIN_DIR . 'SimpleModelHelper.php' );
require( REDBEANPHP_MAIN_DIR . 'TagManager.php' );
require( REDBEANPHP_MAIN_DIR . 'LabelMaker.php' );
require( REDBEANPHP_MAIN_DIR . 'Facade.php' );
require( REDBEANPHP_MAIN_DIR . 'DuplicationManager.php' );
require( REDBEANPHP_MAIN_DIR . 'Plugin.php' );
require( REDBEANPHP_MAIN_DIR . 'Functions.php' );

/* Facade Utilities */
require( REDBEANPHP_MAIN_DIR . 'Util/ArrayTool.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/DispenseHelper.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/Dump.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/MultiLoader.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/Transaction.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/QuickExport.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/MatchUp.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/Look.php' );
require( REDBEANPHP_MAIN_DIR . 'Util/Diff.php' );

//Allow users to mount the plugin folder.
if ( defined( 'REDBEANPHP_PLUGINS' ) ) {
    Phar::mount( 'RedBeanPHP/Plugin', REDBEANPHP_PLUGINS );
}

//make some classes available for backward compatibility
class RedBean_SimpleModel extends \RedBeanPHP\SimpleModel {};

if (!class_exists('R')) {
	class R extends \RedBeanPHP\Facade{};
}
