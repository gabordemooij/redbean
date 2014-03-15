<?php

//Set the directory path
$dir = 'phar://rb.phar/RedBeanPHP/';

//Load Database drivers
require( $dir . 'Logger.php' );
require( $dir . 'Logger/RDefault.php' );
require( $dir . 'Driver.php' );
require( $dir . 'Driver/RPDO.php' );

//Load Infrastructure
require( $dir . 'OODBBean.php' );
require( $dir . 'Observable.php' );
require( $dir . 'Observer.php' );

//Load Database Adapters
require( $dir . 'Adapter.php' );
require( $dir . 'Adapter/DBAdapter.php' );

//Load SQL drivers
require( $dir . 'QueryWriter.php' );
require( $dir . 'QueryWriter/AQueryWriter.php' );
require( $dir . 'QueryWriter/MySQL.php' );
require( $dir . 'QueryWriter/SQLiteT.php' );
require( $dir . 'QueryWriter/PostgreSQL.php' );

//Load required Exceptions
require( $dir . 'RedException.php' );
require( $dir . 'RedException/SQL.php' );

//Load Core functionality
require( $dir . 'OODB.php' );
require( $dir . 'ToolBox.php' );
require( $dir . 'Finder.php' );

//Load extended functionality
require( $dir . 'AssociationManager.php' );
require( $dir . 'BeanHelper.php' );
require( $dir . 'BeanHelper/SimpleFacadeBeanHelper.php' );

/* Developer Comfort */

require( $dir . 'SimpleModel.php' );
require( $dir . 'SimpleModelHelper.php' );
require( $dir . 'TagManager.php' );
require( $dir . 'LabelMaker.php' );
require( $dir . 'Facade.php' );
require( $dir . 'DuplicationManager.php' );
require( $dir . 'Plugin.php' );
require( $dir . 'Functions.php' );

//Allow users to mount the plugin folder.
if ( defined( 'REDBEANPHP_PLUGINS' ) ) {
    Phar::mount( 'RedBeanPHP/Plugin', REDBEANPHP_PLUGINS );
}

//make some classes available for backward compatibility
class RedBean_SimpleModel extends \RedBeanPHP\SimpleModel {};
class R extends \RedBeanPHP\Facade{};

