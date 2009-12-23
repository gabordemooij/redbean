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
require($dir."QueryWriter/MySQL.php");

//Load required Exceptions
require($dir."Exception.php");
require($dir."Exception/SQL.php");
require($dir."Exception/Security.php");
require($dir."Exception/FailedAccessBean.php");
require($dir."Exception/NotImplemented.php");
require($dir."Exception/UnsupportedDatabase.php");

//Load Core functionality
require("OODB.php");
require("ToolBox.php");
require("CompatManager.php");

//Load extended functionality
require("AssociationManager.php");
require("TreeManager.php");
require("Setup.php");
require("SimpleStat.php");

//Load the default plugins
require("Plugin/ChangeLogger.php");
require("Plugin/Cache.php");
require("Plugin/Finder.php");
require("Plugin/Constraint.php");
