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
 * @replica:exclude
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
require($dir."QueryWriter/SQLiteT.php");
require($dir."QueryWriter/PostgreSQL.php");

//Load required Exceptions
require($dir."Exception.php");
require($dir."Exception/SQL.php");
require($dir."Exception/Security.php");

//Load Core functionality
require($dir."OODB.php");
require($dir."ToolBox.php");

//Load extended functionality
require($dir."AssociationManager.php");
require($dir."ExtAssociationManager.php");
require($dir."Setup.php");




require($dir."Plugin/IOptimizer.php");
require($dir."Plugin/Optimizer.php");
require($dir."Plugin/Optimizer/Datetime.php");
require($dir."Plugin/Optimizer/Shrink.php");
require($dir."Plugin/QueryLogger.php");
require($dir."Plugin/BeanExport.php");


require($dir."IBeanHelper.php");
require($dir."BeanHelperFacade.php");

/* Developer Comfort */
require($dir."IBeanFormatter.php");
require($dir."DefaultBeanFormatter.php");

require($dir."IModelFormatter.php");
require($dir."SimpleModel.php");
require($dir."ModelHelper.php");
require($dir."Facade.php");
require($dir."FacadeHelper.php");
require($dir."BeanCan.php");
require($dir."Cooker.php");
require($dir."ViewManager.php");

class R extends RedBean_Facade{
}






