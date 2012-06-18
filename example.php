<?php

//Example Script, saves Hello World to the database.

//First, we need to include redbean
//require_once('rb.php');
require_once('RedBean/redbean.inc.php');
//Second, we need to setup the database

//If you work on Mac OSX or Linux (or another UNIX like system)
//You can simply use this line:

R::setup("oracle:(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = stef-PC)(PORT = 1521)))(CONNECT_DATA =(SID = ORCL)(SERVER = DEDICATED)))",'hr','hr'); 
//R::setup();
//R::setup('database.txt'); -- for other systems

//Ready. Now insert a bean!
$bean = R::dispense('leaflet1');
$bean->title = 'Hello World!!!!!!!!!!!';

//Store the bean
$id = R::store($bean);

//Reload the bean
$leaflet = R::load('leaflet1',$id);

//Display the title
echo $leaflet->title;
echo R::$adapter->getDatabase()->getDatabaseVersion();
$oci = R::$adapter->getDatabase();
try {
	$oci->GetCell("SELECT aname FROM page WHERE ROWNUM<=1");
}catch (Exception $e){};
$e = oci_execute($s);
echo R::$adapter->getDatabase()->Errormsg();
echo R::$adapter->getDatabase()->ErrorNo();
echo PHP_EOL;



