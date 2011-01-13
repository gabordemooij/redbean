<?php

/*

                   .______.
_______   ____   __| _/\_ |__   ____ _____    ____
\_  __ \_/ __ \ / __ |  | __ \_/ __ \\__  \  /    \
 |  | \/\  ___// /_/ |  | \_\ \  ___/ / __ \|   |  \
 |__|    \___  >____ |  |___  /\___  >____  /___|  /
             \/     \/      \/     \/     \/     \/

 Written by Gabor de Mooij Copyright (c) 2009

*/

/**
 * RedBean Loader
 * @file 		RedBean/redbean.inc.php
 * @description		If you do not use a pre-packaged version of RedBean
 *					you can use this RedBean loader to include all RedBean
 *					files for you.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */




//Check the current PHP version
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
	throw new Exception("Sorry, RedBean is not compatible with PHP versions < 5.");
}

//Set the directory path
$dir = dirname(__FILE__) . "/";

//Load Core Intefaces
require($dir."ObjectDatabase.php");
require($dir."Plugin.php");

//Load Database drivers
require($dir."Driver.php");
require($dir."Driver/PDO.php");

//Load Infrastructure
require($dir."OODBBean.php");
require($dir."Observable.php");
require($dir."Observer.php");

//Load Database Adapters
require($dir."Adapter.php");
require($dir."Adapter/DBAdapter.php");

//Load SQL drivers
require($dir."QueryWriter.php");
require($dir."QueryWriter/AQueryWriter.php");
require($dir."QueryWriter/MySQL.php");
require($dir."QueryWriter/SQLite.php");
require($dir."QueryWriter/SQLiteT.php");
require($dir."QueryWriter/PostgreSQL.php");


//Load required Exceptions
require($dir."Exception.php");
require($dir."Exception/SQL.php");
require($dir."Exception/Security.php");
require($dir."Exception/FailedAccessBean.php");
require($dir."Exception/NotImplemented.php");
require($dir."Exception/UnsupportedDatabase.php");

//Load Core functionality
require($dir."OODB.php");
require($dir."ToolBox.php");
require($dir."CompatManager.php");

//Load extended functionality
require($dir."AssociationManager.php");
require($dir."TreeManager.php");
require($dir."LinkManager.php");
require($dir."ExtAssociationManager.php");
require($dir."Setup.php");
require($dir."SimpleStat.php");

//Load the default plugins
require($dir."Plugin/ChangeLogger.php");
require($dir."Plugin/Cache.php");
require($dir."Plugin/Finder.php");
require($dir."Plugin/Constraint.php");

require($dir."DomainObject.php");
require($dir."Plugin/IOptimizer.php");
require($dir."Plugin/Optimizer.php");
require($dir."Plugin/Optimizer/Datetime.php");
require($dir."Plugin/Optimizer/Shrink.php");


require($dir."QueryWriter/NullWriter.php");

/* Developer Comfort */
require($dir."UnitOfWork.php");
require($dir."IBeanFormatter.php");
require($dir."IModelFormatter.php");
require($dir."SimpleModel.php");
require($dir."ModelHelper.php");
require($dir."Facade.php");
require($dir."BeanCan.php");







