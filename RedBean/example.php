<?php

/*
 * == EXAMPLE ==
 *    -------
 *
 * This is an EXAMPLE file to get you started real quickly.
 * The purpose of this file is to do all the wirings for you,
 * you can therefore use this file as a starting point for your
 * project.
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */

//First we include redbean
require_once( "redbean.inc.php" );

//If you did not specify a database, we do this for you
if (!isset($database)) $database = "oodb";

//If you did not specify a username, we do this for you
if (!isset($username)) $username = "root";

//If you did not specify a database type, we will use mysql
if (!isset($typeofdatabase)) $typeofdatabase="mysql";

//Assemble a database connection string (DSN)
$dsn = "$typeofdatabase:host=localhost;dbname=$database";

//Connect to database and fetch the toolbox; either with or without password
if (!isset($password)) {
	$toolbox = RedBean_Setup::kickstartDev($dsn, $username);
}
else {
	$toolbox = RedBean_Setup::kickstartDev($dsn, $username, $password);
}

//Extracting tools for you
$redbean = $toolbox->getRedBean();
$db = $toolbox->getDatabaseAdapter();
$writer = $toolbox->getWriter();

//Creating an Association Manager for you
$assoc = new RedBean_AssociationManager( $toolbox );

//Creating a Tree Manager for you
$tree = new RedBean_TreeManager( $toolbox );

function assoc( $bean1, $bean2 ) {
	global $assoc;
	$assoc->associate($bean1, $bean2);

	//also put a cascaded delete constraint - why not?
	RedBean_Plugin_Constraint::addConstraint($bean1, $bean2);
}

function related( $bean, $type ) {
	global $assoc;
	return $assoc->related($bean, $type);
}

function clearassoc($bean, $type) {
	global $assoc;
	$assoc->clearRelations($bean, $type);
}

function unassoc($bean1, $bean2) {
	global $assoc;
	$assoc->unassociate($bean1, $bean2);
}

/*

 PUT YOUR CODE HERE...

		OR...

			...INCLUDE THIS FILE AND WORK WITH THE
				        CREATED OBJECTS DIRECTLY.


*/