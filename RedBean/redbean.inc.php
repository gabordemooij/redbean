<?php
/*

                   .______.
_______   ____   __| _/\_ |__   ____ _____    ____
\_  __ \_/ __ \ / __ |  | __ \_/ __ \\__  \  /    \
 |  | \/\  ___// /_/ |  | \_\ \  ___/ / __ \|   |  \
 |__|    \___  >____ |  |___  /\___  >____  /___|  /
             \/     \/      \/     \/     \/     \/

 Written by Gabor de Mooij Copyright (c) 2012

*/

/**
 * RedBean Loader
 *
 * @file               RedBean/redbean.inc.php
 * @description        If you do not use a pre-packaged version of RedBean
 *                     you can use this RedBean loader to include all RedBean
 *                     files for you.
 * @author             Gabor de Mooij
 * @license            BSD
 * @replica            :exclude
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

//Check the current PHP version
if ( version_compare( PHP_VERSION, '5.2', '<' ) ) {
	throw new Exception( 'Sorry, RedBean is not compatible with PHP versions < 5.' );
}

//Set the directory path
$dir = dirname( __FILE__ ) . '/';

//Load Database drivers
require( $dir . 'Logger.php' );
require( $dir . 'Logger/Default.php' );
require( $dir . 'Driver.php' );
require( $dir . 'Driver/PDO.php' );

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
require( $dir . 'QueryWriter/CUBRID.php' );

//Load required Exceptions
require( $dir . 'Exception.php' );
require( $dir . 'Exception/SQL.php' );
require( $dir . 'Exception/Security.php' );

//Load Core functionality
require( $dir . 'OODB.php' );
require( $dir . 'ToolBox.php' );
require( $dir . 'Finder.php' );

//Load extended functionality
require( $dir . 'AssociationManager.php' );
require( $dir . 'Preloader.php' );
require( $dir . 'AssociationManager/ExtAssociationManager.php' );
require( $dir . 'Setup.php' );

require( $dir . 'BeanHelper.php' );
require( $dir . 'BeanHelper/Facade.php' );

/* Developer Comfort */

require( $dir . 'IModelFormatter.php' );
require( $dir . 'SimpleModel.php' );
require( $dir . 'ModelHelper.php' );
require( $dir . 'TagManager.php' );
require( $dir . 'LabelMaker.php' );
require( $dir . 'Facade.php' );;
require( $dir . 'SQLHelper.php' );

require( $dir . 'DependencyInjector.php' );
require( $dir . 'DuplicationManager.php' );

require( $dir . 'Plugin.php' );
require( $dir . 'Plugin/BeanCan.php' );
require( $dir . 'Plugin/BeanCanResty.php' );
require( $dir . 'Plugin/Cooker.php' );
require( $dir . 'Plugin/Cache.php' );
require( $dir . 'Plugin/QueryLogger.php');
require( $dir . 'Plugin/TimeLine.php' );

class R extends RedBean_Facade
{
}






