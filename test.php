<?php

//
//                   ._______ __________  .______________
//_______   ____   __| _/    |   \      \ |   \__    ___/
//\_  __ \_/ __ \ / __ ||    |   /   |   \|   | |    |
// |  | \/\  ___// /_/ ||    |  /    |    \   | |    |
// |__|    \___  >____ ||______/\____|__  /___| |____|
//            \/     \/                \/

// Written by Gabor de Mooij Copyright (c) 2009
/**
 * RedUNIT (Test Suite)
 * @file 		test.php
 * @description		Series of Unit Tests for RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
 
 
error_reporting(E_ALL | E_STRICT);
$ini = parse_ini_file("test.ini", true);

/**
 * A simple print function that works
 * both for CLI and HTML.
 * @param string $text
 */
function printtext( $text ) {
	if ($_SERVER["DOCUMENT_ROOT"]) {
		echo "<BR>".$text;
	}
	else {
		echo "\n".$text;
	}
}

/**
 * Tests whether a === b.
 * @global integer $tests
 * @param mixed $a
 * @param mixed $b
 */
function asrt( $a, $b ) {
	if ($a === $b) {
		global $tests;
		$tests++;
		print( "[".$tests."]" );
	}
	else {
		printtext("FAILED TEST: EXPECTED $b BUT GOT: $a ");
		fail();
	}
}

function pass() {
	global $tests;
	$tests++;
	print( "[".$tests."]" );
}

function fail() {
	printtext("FAILED TEST");
	debug_print_backtrace();
	exit;
}


function testpack($name) {
	printtext("testing: ".$name);
}


//Test the Setup Class
testpack("Test Setup");

//INCLUDE YOUR REDBEAN FILE HERE!
require("rb.php");
//require("RedBean/redbean.inc.php");


if (interface_exists("RedBean_ObjectDatabase")) pass(); else fail();


//Test whether a non mysql DSN throws an exception
try {
	RedBean_Setup::kickstart(
	  "blackhole:host={$ini['mysql']['host']};dbname={$ini['mysql']['schema']}",
	  $ini['mysql']['user'],
	  $ini['mysql']['pass']
  );
	fail();
}catch(RedBean_Exception_NotImplemented $e) {
	pass();
}


// $toolbox = RedBean_Setup::kickstartDev( "mysql:host=localhost;dbname=oodb","root","" );
$toolbox = RedBean_Setup::kickstartDev(
  "mysql:host={$ini['mysql']['host']};dbname={$ini['mysql']['schema']}",
  $ini['mysql']['user'],
  $ini['mysql']['pass']
);

/**
 * Observable Mock
 * This is just for testing
 */
class ObservableMock extends RedBean_Observable {
	public function test( $eventname, $info ) {
		$this->signal($eventname, $info);
	}
}
/**
 * Observer Mock
 * This is just for testing
 */
class ObserverMock implements RedBean_Observer {
	public $event = false;
	public $info = false;
	public function onEvent($event, $info) {
		$this->event = $event;
		$this->info = $info;
	}
}


//Helper functions
function tbl($table) {
	return R::$writer->getFormattedTableName($table);
}

function ID($id) {
	return R::$writer->getIDField($table);
}

$nullWriter = new RedBean_QueryWriter_NullWriter();
$redbean = new RedBean_OODB( $nullWriter );
$linker = new RedBean_LinkManager( $toolbox );


//Section A: Config Testing
testpack("CONFIG TEST");
//Can we access the required exceptions?
asrt(class_exists("RedBean_Exception_FailedAccessBean"),true);
asrt(class_exists("RedBean_Exception_Security"),true);
asrt(class_exists("RedBean_Exception_SQL"),true);

//Section B: UNIT TESTING
testpack("UNIT TEST RedBean CompatManager: ScanDirect");
RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"1"));
pass();
try {
	RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"9999"));
	fail();
}catch(RedBean_Exception_UnsupportedDatabase $e) {
	pass();
}
try {
	RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_FOXPRO=>"1"));
	fail();
}catch(RedBean_Exception_UnsupportedDatabase $e) {
	pass();
}
RedBean_CompatManager::ignore(TRUE);
try {
	RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"9999"));
	pass();
}catch(RedBean_Exception_UnsupportedDatabase $e) {
	fail();
}
try {
	RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_FOXPRO=>"1"));
	pass();
}catch(RedBean_Exception_UnsupportedDatabase $e) {
	fail();
}
RedBean_CompatManager::ignore(FALSE);
try {
	RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"9999"));
	fail();
}catch(RedBean_Exception_UnsupportedDatabase $e) {
	pass();
}
try {
	RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_FOXPRO=>"1"));
	fail();
}catch(RedBean_Exception_UnsupportedDatabase $e) {
	pass();
}


testpack("UNIT TEST RedBean OODB: Dispense");
//Can we dispense a bean?
$page = $redbean->dispense("page");
//Does it have a meta type?
asrt(((bool)$page->getMeta("type")),true);
//Does it have an ID?
asrt(isset($page->id),true);
//Type should be 'page'
asrt(($page->getMeta("type")),"page");
//ID should be 0 because bean does not exist in database yet.
asrt(($page->id),0);
//Try some faulty dispense actions.
try {
	$redbean->dispense("");
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$redbean->dispense(".");
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$redbean->dispense("-");
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}

testpack("TEST ARRAY INTERFACE");
$bean = $redbean->dispense("testbean");
$bean["property"] = 123;
$bean["abc"] = "def";
asrt($bean["property"],123);
asrt($bean["abc"],"def");
asrt($bean->abc,"def");
asrt(isset($bean["abd"]),false);
asrt(isset($bean["abc"]),true);

//Test the Check() function (also indirectly using store())
testpack("UNIT TEST RedBean OODB: Check");
$bean = $redbean->dispense("page");
//Set some illegal values in the bean; this should trugger Security exceptions.
//Arrays are not allowed.
$bean->name = array("1");
try {
	$redbean->store($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$redbean->check($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
//Objects should not be allowed.
$bean->name = new RedBean_OODBBean;
try {
	$redbean->store($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$redbean->check($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
//Property names should be alphanumeric
$prop = ".";
$bean->$prop = 1;
try {
	$redbean->store($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$redbean->check($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
//Really...
$prop = "-";
$bean->$prop = 1;
try {
	$redbean->store($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$redbean->check($bean);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}

testpack("OODBBean Tainted");
$spoon = $redbean->dispense("spoon");
asrt($spoon->getMeta("tainted"),false);
$spoon->dirty = "yes";
asrt($spoon->getMeta("tainted"),true);
$spoon = $redbean->dispense("spoon");
$linker->link($spoon,$redbean->dispense("spoon"));
asrt($spoon->getMeta("tainted"),true);
/*
testpack("UNIT TEST RedBean OODB: Load");
$bean = $redbean->load("typetest",2);
asrt($bean->getMeta("tainted"),false);
$nullWriter->returnSelectRecord = array();
asrt($nullWriter->selectRecordArguments[0],"typetest");
asrt($nullWriter->selectRecordArguments[1],array(2));
asrt($bean->id,0);
$nullWriter->returnSelectRecord = array(array("name"=>"abc","id"=>3));
$bean = $redbean->load("typetest",3);
asrt($nullWriter->selectRecordArguments[0],"typetest");
asrt($nullWriter->selectRecordArguments[1],array(3));
asrt($bean->id,3);
try {
	$bean = $redbean->load("typetest",-2);
	pass();
}catch(RedBean_Exception_Security $e) {
	fail();
}
try {
	$bean = $redbean->load("typetest",0);
	pass();
}catch(RedBean_Exception_Security $e) {
	fail();
}
try {
	$bean = $redbean->load("typetest",2.1);
	pass();
}catch(RedBean_Exception_Security $e) {
	fail();
}
try {
	$bean = $redbean->load(" ",3);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$bean = $redbean->load(".",3);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$bean = $redbean->load("type.test",3);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}

testpack("UNIT TEST RedBean OODB: Batch");
$nullWriter->reset();
$beans = $redbean->batch("typetest",array(2));
$nullWriter->returnSelectRecord = array();
asrt($nullWriter->selectRecordArguments[0],"typetest");
asrt($nullWriter->selectRecordArguments[1],array(2));
asrt(count($beans),0);
$nullWriter->reset();
$nullWriter->returnSelectRecord = array(array("name"=>"abc","id"=>3));
$beans = $redbean->batch("typetest",array(3));
asrt($nullWriter->selectRecordArguments[0],"typetest");
asrt($nullWriter->selectRecordArguments[1],array(3));
asrt(count($beans),1);


testpack("UNIT TEST RedBean OODB: Store");
$nullWriter->reset();
$bean = $redbean->dispense("bean");
$bean->name = "coffee";
asrt($bean->getMeta("tainted"),true);
$nullWriter->returnScanType = 91239;
$nullWriter->returnInsertRecord = 1234;
asrt($redbean->store($bean),1234);
asrt($bean->getMeta("tainted"),false);
asrt($nullWriter->getColumnsArgument,"bean");
asrt($nullWriter->createTableArgument,"bean");
asrt($nullWriter->scanTypeArgument,"coffee");
asrt($nullWriter->codeArgument,NULL);
//print_r($nullWriter);
asrt($nullWriter->addColumnArguments,array("bean","name",91239));
asrt($nullWriter->insertRecordArguments,array("bean",array("name"),array(array("coffee"))));
asrt($nullWriter->addUniqueIndexArguments,array());
asrt($nullWriter->updateRecordArguments,array());
asrt($nullWriter->widenColumnArguments,array());
$nullWriter->reset();
$bean = $redbean->dispense("bean");
$bean->name = "chili";
$bean->id=9876;
$nullWriter->returnCode = 0;
$nullWriter->returnScanType = 777;
$nullWriter->returnTables=array("bean");
$nullWriter->returnGetColumns=array("name"=>13);
asrt($redbean->store($bean),9876);
asrt($nullWriter->getColumnsArgument,"bean");
asrt($nullWriter->createTableArgument,NULL);
asrt($nullWriter->scanTypeArgument,"chili");
asrt($nullWriter->codeArgument,13);
asrt($nullWriter->addColumnArguments,array());
asrt($nullWriter->insertRecordArguments,array());
asrt($nullWriter->addUniqueIndexArguments,array());
asrt($nullWriter->updateRecordArguments,array("bean",array(array("property"=>"name","value"=>"chili")),9876 ));
asrt($nullWriter->widenColumnArguments,array("bean","name", 777));


testpack("UNIT TEST RedBean OODB: Freeze");
$nullWriter->reset();
$redbean->freeze(true);
$bean = $redbean->dispense("bean");
$bean->name = "coffee";
$nullWriter->returnScanType = 91239;
$nullWriter->returnInsertRecord = 1234;
asrt($redbean->store($bean),1234);
asrt($nullWriter->getColumnsArgument,NULL);
asrt($nullWriter->createTableArgument,NULL);
asrt($nullWriter->scanTypeArgument,NULL);
asrt($nullWriter->codeArgument,NULL);
asrt($nullWriter->addColumnArguments,array());
asrt($nullWriter->insertRecordArguments,array("bean",array("name"),array(array("coffee"))));
asrt($nullWriter->addUniqueIndexArguments,array());
asrt($nullWriter->updateRecordArguments,array());
asrt($nullWriter->widenColumnArguments,array());
$redbean->freeze(false);
*/

testpack("UNIT TEST RedBean OODBBean: Meta Information");
$bean = new RedBean_OODBBean;
$bean->setMeta( "this.is.a.custom.metaproperty" , "yes" );
asrt($bean->getMeta("this.is.a.custom.metaproperty"),"yes");
asrt($bean->getMeta("nonexistant"),NULL);
asrt($bean->getMeta("nonexistant","abc"),"abc");
asrt($bean->getMeta("nonexistant.nested"),NULL);
asrt($bean->getMeta("nonexistant,nested","abc"),"abc");
$bean->setMeta("test.two","second");
asrt($bean->getMeta("test.two"),"second");
$bean->setMeta("another.little.property","yes");
asrt($bean->getMeta("another.little.property"),"yes");
asrt($bean->getMeta("test.two"),"second");


testpack("UNIT TEST RedBean OODBBean: copyMeta");
$bean = new RedBean_OODBBean;
$bean->setMeta("meta.meta","123");
$bean2 = new RedBean_OODBBean;
asrt($bean2->getMeta("meta.meta"),NULL);
$bean2->copyMetaFrom($bean);
asrt($bean2->getMeta("meta.meta"),"123");

testpack("UNIT TEST RedBean OODBBean: import");
$bean = new RedBean_OODBBean;
$bean->import(array("a"=>1,"b"=>2));
asrt($bean->a, 1);
asrt($bean->b, 2);
$bean->import(array("a"=>3,"b"=>4),"a,b");
asrt($bean->a, 3);
asrt($bean->b, 4);
$bean->import(array("a"=>5,"b"=>6)," a , b ");
asrt($bean->a, 5);
asrt($bean->b, 6);
$bean->import(array("a"=>1,"b"=>2));

testpack("UNIT TEST RedBean OODBBean: export");
$bean->setMeta("justametaproperty","hellothere");
$arr = $bean->export();
asrt(is_array($arr),true);
asrt(isset($arr["a"]),true);
asrt(isset($arr["b"]),true);
asrt($arr["a"],1);
asrt($arr["b"],2);
asrt(isset($arr["__info"]),false);
$arr = $bean->export( true );
asrt(isset($arr["__info"]),true);
asrt($arr["a"],1);
asrt($arr["b"],2);
$exportBean = $redbean->dispense("abean");
$exportBean->setMeta("metaitem.bla",1);
$exportedBean = $exportBean->export(true);
asrt($exportedBean["__info"]["metaitem.bla"],1);
asrt($exportedBean["__info"]["type"],"abean");

//Test observer
testpack("UNIT TEST Observer Mechanism ");
$observable = new ObservableMock();
$observer = new ObserverMock();
$observable->addEventListener("event1",$observer);
$observable->addEventListener("event3",$observer);
$observable->test("event1", "testsignal1");
asrt($observer->event,"event1");
asrt($observer->info,"testsignal1");
$observable->test("event2", "testsignal2");
asrt($observer->event,"event1");
asrt($observer->info,"testsignal1");
$observable->test("event3", "testsignal3");
asrt($observer->event,"event3");
asrt($observer->info,"testsignal3");

$adapter = $toolbox->getDatabaseAdapter();
$writer  = $toolbox->getWriter();
$redbean = $toolbox->getRedBean();

testpack("UNIT TEST Toolbox");
asrt(($adapter instanceof RedBean_Adapter_DBAdapter),true);
asrt(($writer instanceof RedBean_QueryWriter),true);
asrt(($redbean instanceof RedBean_OODB),true);




$pdo = $adapter->getDatabase();

$pdo->setDebugMode(0);
$pdo->Execute("CREATE TABLE IF NOT EXISTS`hack` (
`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
) ENGINE = MYISAM ;
");
$pdo->Execute("DROP TABLE IF EXISTS page");
$pdo->Execute("DROP TABLE IF EXISTS user");
$pdo->Execute("DROP TABLE IF EXISTS movie");
$pdo->Execute("DROP TABLE IF EXISTS movie_movie");
$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS author");
$pdo->Execute("DROP TABLE IF EXISTS one");
$pdo->Execute("DROP TABLE IF EXISTS special");
$pdo->Execute("DROP TABLE IF EXISTS post");
$pdo->Execute("DROP TABLE IF EXISTS page_user");
$pdo->Execute("DROP TABLE IF EXISTS page_page");
$pdo->Execute("DROP TABLE IF EXISTS testa_testb");
$pdo->Execute("DROP TABLE IF EXISTS association");
$pdo->Execute("DROP TABLE IF EXISTS logentry");
$pdo->Execute("DROP TABLE IF EXISTS admin");
$pdo->Execute("DROP TABLE IF EXISTS admin_logentry");
$pdo->Execute("DROP TABLE IF EXISTS genre");
$pdo->Execute("DROP TABLE IF EXISTS genre_movie");
$pdo->Execute("DROP TABLE IF EXISTS cask_whisky");
$pdo->Execute("DROP TABLE IF EXISTS cask_cask");
$pdo->Execute("DROP TABLE IF EXISTS cask");
$pdo->Execute("DROP TABLE IF EXISTS whisky");
$pdo->Execute("DROP TABLE IF EXISTS __log");
$pdo->Execute("DROP TABLE IF EXISTS dummy");

//Test real events: update,open,delete
testpack("Test Real Events");
$dummyBean = $redbean->dispense("dummy");
$redbean->addEventListener("update",$observer);

$dummyBean->prop = 1;
$id = $redbean->store($dummyBean);
asrt($observer->event,"update");
asrt(($observer->info instanceof RedBean_OODBBean),true);

$redbean->addEventListener("open",$observer);
$redbean->addEventListener("dispense",$observer);
$redbean->load("dummy",99);
asrt($observer->event,"dispense"); //not open, no bean found
asrt(($observer->info instanceof RedBean_OODBBean),true);

$redbean->load("dummy",$id);
asrt($observer->event,"open");
asrt(($observer->info instanceof RedBean_OODBBean),true);

$observer2 = new ObserverMock();
$redbean->addEventListener("after_update",$observer2);
$id = $redbean->store($dummyBean);
asrt($observer2->event,"after_update");
asrt(($observer2->info instanceof RedBean_OODBBean),true);


testpack("UNIT TEST RedBean OODB: setObject");
$wine = $redbean->dispense("wine");
$wine->id = 123;
$cask = $redbean->dispense("cask");

$linker->link( $cask, $wine );
asrt($cask->wine_id,123);
$wine->id = 124;
$linker->link($cask,$wine);

asrt($cask->wine_id,124);
asrt($linker->getKey($cask,"wine"),124);
$wine = $redbean->dispense("wine");
$cask = $redbean->dispense("cask");
$wine->title = "my wine";
$cask->title = "my cask";
$redbean->store( $wine );
$linker->link($cask,$wine);
$redbean->store( $cask );
asrt($linker->getKey($cask,"wine"), $wine->id);
asrt(($wine->id>0),true);
$wine = $linker->getBean($cask,"wine");
asrt(($wine instanceof RedBean_OODBBean), true);
asrt($wine->title,"my wine");
$pdo->Execute("DROP TABLE IF EXISTS cask");
$pdo->Execute("DROP TABLE IF EXISTS wine");
pass();

$page = $redbean->dispense("page");

testpack("UNIT TEST Database");
try {
	$adapter->exec("an invalid query");
	fail();
}catch(RedBean_Exception_SQL $e ) {
	pass();
}
asrt( (int) $adapter->getCell("SELECT 123") ,123);
asrt( (int) $adapter->getCell("SELECT ?",array("987")) ,987);
asrt( (int) $adapter->getCell("SELECT ?+?",array("987","2")) ,989);
asrt( (int) $adapter->getCell("SELECT :numberOne+:numberTwo",array(
		  ":numberOne"=>42,":numberTwo"=>50)) ,92);
$pair = $adapter->getAssoc("SELECT 'thekey','thevalue' ");
asrt(is_array($pair),true);
asrt(count($pair),1);
asrt(isset($pair["thekey"]),true);
asrt($pair["thekey"],"thevalue");


//Section C: Integration Tests / Regression Tests

testpack("Test RedBean OODB: Insert Record");
$page->name = "my page";
$id = (int) $redbean->store($page);
asrt( $page->id, 1 );
asrt( (int) $pdo->GetCell("SELECT count(*) FROM page"), 1 );
asrt( $pdo->GetCell("SELECT `name` FROM page LIMIT 1"), "my page" );
asrt( $id, 1 );
testpack("Test RedBean OODB: Can we Retrieve a Record? ");
$page = $redbean->load( "page", 1 );
asrt($page->name, "my page");
asrt(( (bool) $page->getMeta("type")),true);
asrt(isset($page->id),true);
asrt(($page->getMeta("type")),"page");
asrt((int)$page->id,$id);

//testpack("Test param binding");
//$pages = $adapter->exec("select * from page where 1 LIMIT :n ", array(":n"=>1));
//print_r($pages);
//exit;

testpack("Test RedBean OODB: Can we Update a Record? ");
$page->name = "new name";

//Null should == NULL after saving
$page->rating = null;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( ($page->rating == null), true );
asrt( !$page->rating, true );

$page->rating = false;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( (bool) $page->rating, false );
asrt( ($page->rating==false), true );
asrt( !$page->rating, true );

$page->rating = true;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( (bool) $page->rating, true );
asrt( ($page->rating==true), true);
asrt( ($page->rating==true), true );

$page->rating = "1";
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "1" );

$page->rating = "0";
$newid = $redbean->store( $page );
asrt( $page->rating, "0" );
$page->rating = 0;
$newid = $redbean->store( $page );
asrt( $page->rating, 0 );

$page->rating = "0";
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( !$page->rating, true );
asrt( ($page->rating==0), true );
asrt( ($page->rating==false), true );

$page->rating = 5;
//$page->__info["unique"] = array("name","rating");
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( strval( $page->rating ), "5" );

$page->rating = 300;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( strval( $page->rating ), "300" );

$page->rating = -2;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( strval( $page->rating ), "-2" );

$page->rating = 2.5;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt(  ( $page->rating == 2.5 ), true );

$page->rating = -3.3;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( ( $page->rating == -3.3 ), true );

$page->rating = "good";
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "good" );

$longtext = str_repeat('great! because..',100);
$page->rating = $longtext;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, $longtext );

$numAsString = "0001";
$page->numasstring = $numAsString;
$redbean->store($page);
$page = $redbean->load( "page", $id );
asrt($page->numasstring,"1");
$numAsString = "0001";
$page->setMeta("cast.numasstring","string");
$page->numasstring = $numAsString;
$redbean->store($page);
$page = $redbean->load( "page", $id );
asrt($page->numasstring,"0001");


$redbean->trash( $page );

asrt( (int) $pdo->GetCell("SELECT count(*) FROM page"), 0 );

testpack("Test RedBean Issue with converting large doubles");
$largeDouble = 999999888889999922211111; //8.88889999922211e+17;
$page = $redbean->dispense("page");
$page->weight = $largeDouble;
$id = $redbean->store($page);
$cols = $writer->getColumns("page");
asrt($cols["weight"],"double");
$page = $redbean->load("page", $id);
$page->name = "dont change the numbers!";
$redbean->store($page);
$page = $redbean->load("page", $id);
$cols = $writer->getColumns("page");
asrt($cols["weight"],"double");


testpack("Test RedBean OODB: Batch Loader ");
$page = $redbean->dispense("page");
$page->name = "page no. 1";
$page->rating = 1;
$id1 = $redbean->store($page);
$page = $redbean->dispense("page");
$page->name = "page no. 2";
$id2 = $redbean->store($page);
$batch = $redbean->batch( "page", array($id1, $id2) );
asrt(count($batch),2);
asrt($batch[$id1]->getMeta("type"),"page");
asrt($batch[$id2]->getMeta("type"),"page");
asrt((int)$batch[$id1]->id,$id1);
asrt((int)$batch[$id2]->id,$id2);
$book = $redbean->dispense("book");
$book->name="book 1";
$redbean->store($book);
$book = $redbean->dispense("book");
$book->name="book 2";
$redbean->store($book);
$book = $redbean->dispense("book");
$book->name="book 3";
$redbean->store($book);
$books = $redbean->batch("book", $adapter->getCol("SELECT id FROM book"));
asrt(count($books),3);


testpack("Test Custom ID Field");
class MyWriter extends RedBean_QueryWriter_MySQL {
	public function getIDField( $type ) {
		return $type . "_id";
	}
}
$writer2 = new MyWriter($adapter);
$redbean2 = new RedBean_OODB($writer2);
$movie = $redbean2->dispense("movie");
asrt(isset($movie->movie_id),true);
$movie->name="movie 1";
$movieid = $redbean2->store($movie);
asrt(($movieid>0),true);
$columns = array_keys( $writer->getColumns("movie") );
asrt(in_array("movie_id",$columns),true);
asrt(in_array("id",$columns),false);
$movie2 = $redbean2->dispense("movie");
asrt(isset($movie2->movie_id),true);
$movie2->name="movie 2";
$movieid2 = $redbean2->store($movie2);
$movie1 = $redbean2->load("movie",$movieid);
asrt($movie->name,"movie 1");
$movie2 = $redbean2->load("movie",$movieid2);
asrt($movie2->name,"movie 2");
$movies = $redbean2->batch("movie", array($movieid,$movieid2));
asrt(count($movies),2);
asrt($movies[$movieid]->name,"movie 1");
asrt($movies[$movieid2]->name,"movie 2");
$toolbox2 = new RedBean_ToolBox($redbean2, $adapter, $writer2);

$a2 = new RedBean_AssociationManager($toolbox2);
$a2->associate($movie1,$movie2);
$movies = $a2->related($movie1, "movie");
asrt(count($movies),1);
asrt((int) $movies[0],(int) $movieid2);
$movies = $a2->related($movie2, "movie");
asrt(count($movies),1);
asrt((int) $movies[0],(int) $movieid);
$genre = $redbean2->dispense("genre");
$genre->name="western";
$a2->associate($movie,$genre);
$movies = $a2->related($genre, "movie");
asrt(count($movies),1);
asrt((int)$movies[0],(int)$movieid);
$a2->unassociate($movie,$genre);
$movies = $a2->related($genre, "movie");
asrt(count($movies),0);
$a2->clearRelations($movie, "movie");
$movies = $a2->related($movie1, "movie");
asrt(count($movies),0);
$pdo->setDebugMode(0);
$t2 = new RedBean_TreeManager($toolbox2);
$t2->attach($movie1, $movie2);
$movies = $t2->children($movie1);
asrt(count($movies),1);
asrt($movies[$movieid2]->name,"movie 2");
$redbean2->trash($movie1);
asrt((int)$adapter->getCell("SELECT count(*) FROM movie"),1);
$redbean2->trash($movie2);
asrt((int)$adapter->getCell("SELECT count(*) FROM movie"),0);
$columns = array_keys($writer->getColumns("movie_movie"));
asrt(in_array("movie_movie_id",$columns),true);

//test locking
$logger = new RedBean_Plugin_ChangeLogger( $toolbox );
$redbean->addEventListener( "open", $logger );
$redbean->addEventListener( "update", $logger);
$redbean->addEventListener( "delete", $logger);

testpack("Test RedBean Locking: Change Logger method ");
$observers = RedBean_Setup::getAttachedObservers();
$page = $redbean->dispense("page");
$page->name = "a page";
$id = $redbean->store( $page );
$page = $redbean->load("page", $id);
$otherpage = $redbean->load("page", $id);
asrt(((bool)$page->getMeta("opened")),true);
asrt(((bool)$otherpage->getMeta("opened")),true);
try {
	$redbean->store( $page );
	pass();
}catch(Exception $e) {
	fail();
}
try {
	$redbean->store( $otherpage );
	fail();
}catch(Exception $e) {
	pass();
}
asrt(count($logger->testingOnly_getStash()),0); // Stash empty?

testpack("Test Association ");
$rb = $redbean;
$testA = $rb->dispense( 'testA' );
$testB = $rb->dispense( 'testB' );
$a = new RedBean_AssociationManager( $toolbox );
try {
	$a->related( $testA, "testB" );
	pass();
}catch(Exception $e) {
	fail();
}

$user = $redbean->dispense("user");
$user->name = "John";
$redbean->store( $user );
$page = $redbean->dispense("page");
$page->name = "John's page";
$redbean->store($page);
$page2 = $redbean->dispense("page");
$page2->name = "John's second page";
$redbean->store($page2);
$a = new RedBean_AssociationManager( $toolbox );
$a->associate($page, $user);
asrt(count($a->related($user, "page" )),1);
$a->associate($user,$page2);
asrt(count($a->related($user, "page" )),2);
//can we fetch the assoc ids themselves?
$pageKeys = $a->related($user, "page" );
$pages = $redbean->batch("page",$pageKeys);
$links = $redbean->batch("page_user",$a->related($user,"page",true));
//print_r($links);
asrt(count($links),2);
//confirm that the link beans are ok.
$link = array_pop($links);
asrt(isset($link->page_id),true);
asrt(isset($link->user_id),true);
asrt(isset($link->id),true);
$link = array_pop($links);
asrt(isset($link->page_id),true);
asrt(isset($link->user_id),true);
asrt(isset($link->id),true);

$a->unassociate($page, $user);
asrt(count($a->related($user, "page" )),1);
$a->clearRelations($user, "page");
asrt(count($a->related($user, "page" )),0);
$user2 = $redbean->dispense("user");
$user2->name = "Second User";
$a->set1toNAssoc($user2, $page);
$a->set1toNAssoc($user, $page);
asrt(count($a->related($user2, "page" )),0);
asrt(count($a->related($user, "page" )),1);
$a->set1toNAssoc($user, $page2);
asrt(count($a->related($user, "page" )),2);
$pages = ($redbean->batch("page", $a->related($user, "page" )));
asrt(count($pages),2);
$apage = array_shift($pages);
asrt(($apage->name=="John's page" || $apage->name=="John's second page"),true);
$apage = array_shift($pages);
asrt(($apage->name=="John's page" || $apage->name=="John's second page"),true);
//test save on the fly
$page = $redbean->dispense("page");
$page2 = $redbean->dispense("page");
$page->name="idless page 1";
$page2->name="idless page 1";
$a->associate($page, $page2);
asrt(($page->id>0),true);
asrt(($page2->id>0),true);
$idpage = $page->id;
$idpage2 = $page2->id;


testpack("Test Ext. Association ");
$adapter->exec("DROP TABLE IF EXISTS webpage ");
$adapter->exec("DROP TABLE IF EXISTS ad_webpage ");
$adapter->exec("DROP TABLE IF EXISTS ad ");
$webpage = $redbean->dispense("webpage");
$webpage->title = "page with ads";
$ad = $redbean->dispense("ad");
$ad->title = "buy this!";
$top = $redbean->dispense("placement");
$top->position = "top";
$bottom = $redbean->dispense("placement");
$bottom->position = "bottom";
$ea = new RedBean_ExtAssociationManager( $toolbox );
$ea->extAssociate( $ad, $webpage, $top);
$ads = $redbean->batch( "ad", $ea->related( $webpage, "ad") );
$adsPos = $redbean->batch( "ad_webpage", $ea->related( $webpage, "ad", true ) );
asrt(count($ads),1);
asrt(count($adsPos),1);
$theAd = array_pop($ads);
$theAdPos = array_pop($adsPos);
asrt($theAd->title, $ad->title);
asrt($theAdPos->position, $top->position);
$ad2 = $redbean->dispense("ad");
$ad2->title = "buy this too!";
$ea->extAssociate( $ad2, $webpage, $bottom);
$ads = $redbean->batch( "ad", $ea->related( $webpage, "ad", true ) );
asrt(count($ads),2);

testpack("Cross References");
$ids = $a->related($page, "page");
asrt(count($ids),1);
asrt(intval(array_pop($ids)),intval($idpage2));
$ids = $a->related($page2, "page");
asrt(count($ids),1);
asrt(intval(array_pop($ids)),intval($idpage));
$page3 = $redbean->dispense("page");
$page3->name="third";
$page4 = $redbean->dispense("page");
$page4->name="fourth";
$a->associate($page3,$page2);
$a->associate($page2,$page4);
$a->unassociate($page,$page2);
asrt(count($a->related($page, "page")),0);
$ids = $a->related($page2, "page");
asrt(count($ids),2);
asrt(in_array($page3->id,$ids),true);
asrt(in_array($page4->id,$ids),true);
asrt(in_array($page->id,$ids),false);
asrt(count($a->related($page3, "page")),1);
asrt(count($a->related($page4, "page")),1);
$a->clearRelations($page2, "page");
asrt(count($a->related($page2, "page")),0);
asrt(count($a->related($page3, "page")),0);
asrt(count($a->related($page4, "page")),0);
try {
	$a->associate($page2,$page2);
	pass();
}catch(RedBean_Exception_SQL $e) {
	fail();
}
//try{ $a->associate($page2,$page2); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
$pageOne = $redbean->dispense("page");
$pageOne->name = "one";
$pageMore = $redbean->dispense("page");
$pageMore->name = "more";
$pageEvenMore = $redbean->dispense("page");
$pageEvenMore->name = "evenmore";
$pageOther = $redbean->dispense("page");
$pageOther->name = "othermore";
$a->set1toNAssoc($pageOther, $pageMore);
$a->set1toNAssoc($pageOne, $pageMore);
$a->set1toNAssoc($pageOne, $pageEvenMore);
asrt(count($a->related($pageOne, "page")),2);
asrt(count($a->related($pageMore, "page")),1);
asrt(count($a->related($pageEvenMore, "page")),1);
asrt(count($a->related($pageOther, "page")),0);

testpack("Test Locking with Assoc");
$page = $redbean->dispense("page");
$user = $redbean->dispense("page");
$id = $redbean->store($page);
$pageII = $redbean->load("page", $id);
$redbean->store($page);
try {
	$redbean->store($pageII);
	fail();
}catch(RedBean_Exception_FailedAccessBean $e) {
	pass();
}
try {
	$a->associate($pageII,$user);
	fail();
}catch(RedBean_Exception_FailedAccessBean $e) {
	pass();
}
try {
	$a->unassociate($pageII,$user);
	fail();
}catch(RedBean_Exception_FailedAccessBean $e) {
	pass();
}
try {
	$a->clearRelations($pageII, "user");
	fail();
}catch(RedBean_Exception_FailedAccessBean $e) {
	pass();
}
try {
	$redbean->store($page);
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}
try {
	$a->associate($page,$user);
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}
try {
	$a->unassociate($page,$user);
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}
try {
	$a->clearRelations($page, "user");
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}
$pageII = $redbean->load("page",$pageII->id); //reload will help
try {
	$redbean->store($pageII);
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}
try {
	$a->associate($pageII,$user);
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}
try {
	$a->unassociate($pageII,$user);
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}
try {
	$a->clearRelations($pageII, "user");
	pass();
}catch(RedBean_Exception_FailedAccessBean $e) {
	fail();
}

//Test whether we can pre-open, or prelock multiple beans at once and
//if the logger fires less queries
testpack("Test Preloader");
class QueryCounter implements RedBean_Observer {
	public $counter = 0;
	public function onEvent($event, $info) {
		$this->counter++;
	}
}
$querycounter = new QueryCounter;
$observers = RedBean_Setup::getAttachedObservers();
asrt(($logger instanceof RedBean_Observer),true);
$pagea = $redbean->dispense("page");
$pageb = $redbean->dispense("page");
$pagec = $redbean->dispense("page");
$paged = $redbean->dispense("page");
$redbean->store($pagea);
$redbean->store($pageb);
$redbean->store($pagec);
$redbean->store($paged);
$a->associate($pagea, $pageb);
$a->associate($pagea, $pagec);
$a->associate($pagea, $paged);
$ids = $a->related($pagea,"page");
$adapter->exec("TRUNCATE __log");
$adapter->addEventListener("sql_exec", $querycounter);
asrt($querycounter->counter,0); //confirm counter works
asrt(intval($adapter->getCell("SELECT count(*) FROM __log")),0);
asrt($querycounter->counter,1); //confirm counter works
$querycounter->counter=0;
$logger->preLoad("page",$ids);
asrt($querycounter->counter,2); //confirm counter works
asrt(count($ids),3);
asrt(count($logger->testingOnly_getStash()),3); //stash filled with ids
asrt(intval($adapter->getCell("SELECT count(*) FROM __log")),4);
$querycounter->counter=0;
$pages = $redbean->batch("page",$ids);
asrt($querycounter->counter,1);
$querycounter->counter=0;
$pages = $redbean->batch("page",$ids);
asrt($querycounter->counter,4); //compare with normal batch without preloading
//did we save queries (3 is normal, 1 is with preloading)
asrt(intval($adapter->getCell("SELECT count(*) FROM __log")),7);
asrt(count($logger->testingOnly_getStash()),0); //should be used up

testpack("Test RedBean Finder Plugin*");
asrt(count(RedBean_Plugin_Finder::where("page", " name LIKE '%more%' ")),3);
asrt(count(RedBean_Plugin_Finder::where("page", " name LIKE :str ",array(":str"=>'%more%'))),3);
asrt(count(RedBean_Plugin_Finder::where("page", " name LIKE :str ",array(":str"=>'%mxore%'))),0);

testpack("Test OODB Finder");
asrt(count($redbean->find("page",array("id"=>array(2,3)))),2);


$bean = $redbean->dispense("wine");
$bean->name = "bla";
$redbean->store($bean);
$redbean->store($bean);
$redbean->store($bean);
$redbean->store($bean);
$redbean->store($bean);
$redbean->store($bean);
$redbean->store($bean);
$redbean->store($bean);
$redbean->store($bean);
RedBean_Plugin_Finder::where("wine", "id=5"); //  Finder:where call RedBean_OODB::convertToBeans
$bean2 = $redbean->load("anotherbean", 5);
asrt($bean2->id,0);


testpack("Test Gold SQL");
asrt(count(RedBean_Plugin_Finder::where("wine"," 1 OR 1 ")),1);
asrt(count(RedBean_Plugin_Finder::where("wine"," @id < 100 ")),1);
asrt(count(RedBean_Plugin_Finder::where("wine"," @id > 100 ")),0);
asrt(count(RedBean_Plugin_Finder::where("wine"," @id < 100 OR 1 ")),1);
asrt(count(RedBean_Plugin_Finder::where("wine"," @id > 100 OR 1 ")),1);
asrt(count(RedBean_Plugin_Finder::where("wine",
		  " 1 OR @grape = 'merlot' ")),1); //non-existant column
asrt(count(RedBean_Plugin_Finder::where("wine",
		  " 1 OR @wine.grape = 'merlot' ")),1); //non-existant column
asrt(count(RedBean_Plugin_Finder::where("wine",
		  " 1 OR @cork=1 OR @grape = 'merlot' ")),1); //2 non-existant column
asrt(count(RedBean_Plugin_Finder::where("wine",
		  " 1 OR @cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
asrt(count(RedBean_Plugin_Finder::where("wine",
		  " 1 OR @bottle.cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
RedBean_Setup::getToolbox()->getRedBean()->freeze( TRUE );
asrt(count(RedBean_Plugin_Finder::where("wine"," 1 OR 1 ")),1);
try {
	RedBean_Plugin_Finder::where("wine"," 1 OR @grape = 'merlot' ");
	fail();
}
catch(RedBean_Exception_SQL $e) {
	pass();
}
try {
	RedBean_Plugin_Finder::where("wine"," 1 OR @wine.grape = 'merlot' ");
	fail();
}
catch(RedBean_Exception_SQL $e) {
	pass();
}
try {
	RedBean_Plugin_Finder::where("wine"," 1 OR @cork=1 OR @wine.grape = 'merlot'  ");
	fail();
}
catch(RedBean_Exception_SQL $e) {
	pass();
}
try {
	RedBean_Plugin_Finder::where("wine"," 1 OR @bottle.cork=1 OR @wine.grape = 'merlot'  ");
	fail();
}
catch(RedBean_Exception_SQL $e) {
	pass();
}
try {
	RedBean_Plugin_Finder::where("wine"," 1 OR @a=1",array(),false,true);
	pass();
}
catch(RedBean_Exception_SQL $e) {
	fail();
}
RedBean_Setup::getToolbox()->getRedBean()->freeze( FALSE );
asrt(RedBean_Plugin_Finder::parseGoldSQL(" @name ","wine",RedBean_Setup::getToolbox())," name ");
asrt(RedBean_Plugin_Finder::parseGoldSQL(" @name @id ","wine",RedBean_Setup::getToolbox())," name id ");
asrt(RedBean_Plugin_Finder::parseGoldSQL(" @name @id @wine.id ","wine",RedBean_Setup::getToolbox())," name id wine.id ");
asrt(RedBean_Plugin_Finder::parseGoldSQL(" @name @id @wine.id @bla ","wine",RedBean_Setup::getToolbox())," name id wine.id NULL ");
asrt(RedBean_Plugin_Finder::parseGoldSQL(" @name @id @wine.id @bla @xxx ","wine",RedBean_Setup::getToolbox())," name id wine.id NULL NULL ");
asrt(RedBean_Plugin_Finder::parseGoldSQL(" @bla @xxx ","wine",RedBean_Setup::getToolbox())," NULL NULL ");


testpack("Test RedBean Cache plugin");
$adapter->exec("drop table movie");
$querycounter->counter = 0;
$redbean3 = new RedBean_Plugin_Cache( $redbean, $toolbox );
$movie = $redbean3->dispense("movie");
$movie->name = "cached movie 1";
//$pdo->setDebugMode(1);
$movieid = $redbean3->store($movie);
asrt(($querycounter->counter>0),true);
$querycounter->counter=0;
$movie = $redbean3->load("movie",$movieid);
asrt($movie->name,"cached movie 1");
asrt(($querycounter->counter>0),false);
$movie->name = "cached movie 2";
$movieid = $redbean3->store($movie);
asrt(($querycounter->counter>0),true);
$querycounter->counter=0;
$movie = $redbean3->load("movie",$movieid);
asrt($movie->name,"cached movie 2");
asrt(($querycounter->counter>0),false);
$movie2 = $redbean3->dispense("movie");
$movie2->name="another movie";
$movie2id = $redbean3->store($movie2);
$querycounter->counter=0;
$movies = $redbean3->batch("movie",array($movie2id, $movieid));
asrt(count($movies),2);
asrt(($querycounter->counter>0),false);
$redbean3->trash($movie2);
asrt(($querycounter->counter>0),true);
$querycounter->counter=0;
$pages = $redbean3->batch("page",$adapter->getCol("SELECT id FROM page"));
asrt(($querycounter->counter>0),true);
$apage = array_pop($pages);
$querycounter->counter=0;
$samepage = $redbean3->load("page",$apage->id);
asrt(($querycounter->counter>0),false);
asrt($samepage->name,$apage->name);
asrt($samepage->id,$apage->id);
$movie = $redbean3->dispense("movie");
$movie->rating = 5;
$movie->name="scary movie";
$redbean3->store($movie);
asrt($redbean3->test_getColCount(),0);
$movie->rating = 4;
$movie->name="scary movie 2";
$redbean3->store($movie);
asrt($redbean3->test_getColCount(),2);
$movie->rating = 3;
$redbean3->store($movie);
asrt($redbean3->test_getColCount(),1);
$movie->name="scary movie 3";
$redbean3->store($movie);
asrt($redbean3->test_getColCount(),1);
$redbean3->store($movie);
asrt($redbean3->test_getColCount(),0);
$movie = $redbean3->dispense("movie");
$movie->name = "Back to the Future";
$id=$redbean3->store($movie);
$movie=$redbean3->load("movie", $id);
asrt($movie->name=="Back to the Future", true);
$movie->language="EN";
$redbean3->store($movie);
$movie = $redbean3->load("movie", $id);
//did you store the new prop?
asrt($movie->language,"EN");
//really ? -- to database, not only in cache..
$movie = $redbean->load("movie", $id);
//did you store the new prop?
asrt($movie->language,"EN");

//wipe must clear cache
$movie = $redbean3->load("movie", $id);
asrt((int)$movie->id, (int)$id);
$redbean3->wipe("movie");
$movie = $redbean3->load("movie", $id);
asrt((int)$movie->id, 0);

testpack("Transactions");
$adapter->startTransaction();
pass();
$adapter->rollback();
pass();
$adapter->startTransaction();
pass();
$adapter->commit();
pass();




testpack("Test Developer Interface API");
$post = $redbean->dispense("post");
$post->title = "My First Post";
$post->created = time();
$id = $redbean->store( $post );
$post = $redbean->load("post",$id);
$redbean->trash( $post );
pass();


testpack("Test Frozen");




$redbean->freeze( true );
$page = $redbean->dispense("page");
$page->sections = 10;
$page->name = "half a page";
try {
	$id = $redbean->store($page);
	fail();
}catch(RedBean_Exception_SQL $e) {
	pass();
}
$post = $redbean->dispense("post");
$post->title = "existing table";
try {
	$id = $redbean->store($post);
	pass();
}catch(RedBean_Exception_SQL $e) {
	fail();
}
asrt(in_array("name",array_keys($writer->getColumns("page"))),true);
asrt(in_array("sections",array_keys($writer->getColumns("page"))),false);
$newtype = $redbean->dispense("newtype");
$newtype->property=1;
try {
	$id = $redbean->store($newtype);
	fail();
}catch(RedBean_Exception_SQL $e) {
	pass();
}
$logger = RedBean_Plugin_QueryLogger::getInstanceAndAttach( $adapter );
//now log and make sure no 'describe SQL' happens
$page = $redbean->dispense("page");
$page->name = "just another page that has been frozen...";
$id = $redbean->store($page);
$page = $redbean->load("page", $id);
$page->name = "just a frozen page...";
$redbean->store($page);
$page2 = $redbean->dispense("page");
$page2->name = "an associated frozen page";
$a->associate($page, $page2);
$a->related($page, "page");
$a->unassociate($page, $page2);
$a->clearRelations($page,"page");
$items = RedBean_Plugin_Finder::where("page", "1");
$redbean->trash($page);
$redbean->freeze( false );
asrt(count($logger->grep("select"))>0,true);
asrt(count($logger->grep("describe"))<1,true);



testpack("Test Finding");
$keys = $adapter->getCol("SELECT id FROM page WHERE `name` LIKE '%John%'");
asrt(count($keys),2);
$pages = $redbean->batch("page", $keys);
asrt(count($pages),2);


testpack("Test (UN)Common Scenarios");
$page = $redbean->dispense("page");
$page->name = "test page";
$id = $redbean->store($page);
$user = $redbean->dispense("user");
$a->unassociate($user,$page);
pass(); //no error
$a->unassociate($page,$user);
pass(); //no error
$a->clearRelations($page, "user");
pass(); //no error
$a->clearRelations($user, "page");
pass(); //no error
$a->associate($user,$page);
pass();
asrt(count($a->related( $user, "page")),1);
asrt(count($a->related( $page, "user")),1);
$a->clearRelations($user, "page");
pass(); //no error
asrt(count($a->related( $user, "page")),0);
asrt(count($a->related( $page, "user")),0);
$page = $redbean->load("page",$id);
pass();
asrt($page->name,"test page");

testpack("Test: Trees ");
$tm = new RedBean_TreeManager($toolbox);
$subpage1 = $redbean->dispense("page");
$notapage = $redbean->dispense("notapage");
try {
	$tm->attach($notapage,$page);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
try {
	$tm->attach($page,$notapage);
	fail();
}catch(RedBean_Exception_Security $e) {
	pass();
}
$subpage2 = $redbean->dispense("page");
$subpage3 = $redbean->dispense("page");
$tm->attach( $page, $subpage1 );
asrt(count($tm->children($page)),1);
$tm->attach( $page, $subpage2 );
asrt(count($tm->children($page)),2);
$tm->attach( $subpage2, $subpage3 );
asrt(count($tm->children($page)),2);
asrt(count($tm->children($subpage2)),1);
asrt(intval($subpage1->parent_id),intval($id));

testpack("Test Integration Pre-existing Schema");
$adapter->exec("ALTER TABLE `page` CHANGE `name` `name` VARCHAR( 254 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ");
$page = $redbean->dispense("page");
$page->name = "Just Another Page In a Table";
$cols = $writer->getColumns("page");
asrt($cols["name"],"varchar(254)");
//$pdo->setDebugMode(1);
$redbean->store( $page );
pass(); //no crash?
$cols = $writer->getColumns("page");
asrt($cols["name"],"varchar(254)"); //must still be same


testpack("Test Plugins: Optimizer");
$one = $redbean->dispense("one");
$one->col = str_repeat('a long text',100);
$redbean->store($one);
$optimizer = new RedBean_Plugin_Optimizer( $toolbox );

//order is important!
$optimizer->addOptimizer(new RedBean_Plugin_Optimizer_DateTime($toolbox));
$optimizer->addOptimizer(new RedBean_Plugin_Optimizer_Shrink($toolbox));

$redbean->addEventListener("update", $optimizer);
$writer  = $toolbox->getWriter();
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = 1;
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$redbean->store($one);
//$cols = $writer->getColumns("one");
//asrt($cols["col"],"set('1')");

$one->col = str_repeat('a long text',100);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = 12;
$redbean->store($one);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"tinyint(3) unsigned");

$one->col = str_repeat('a long text',100);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = 9000;
$redbean->store($one);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"int(11) unsigned");

$one->col = str_repeat('a long text',100);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = 1.23;
$redbean->store($one);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"double");
$one->col = str_repeat('a long text',100);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = "short text";
$redbean->store($one);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"varchar(255)");

testpack("Test Plugins: MySQL Spec. Column");
$special = $redbean->dispense("special");
$v = "2009-01-01 10:00:00";
$special->datetime = $v;
$redbean->store($special);
$redbean->store($special);
//$optimizer->MySQLSpecificColumns("special", "datetime", "varchar", $v);
$cols = $writer->getColumns("special");
asrt($cols["datetime"],"datetime");
//$adapter->getDatabase()->setDebugMode(1);
for($i=0; $i<100; $i++){
$special2 = $redbean->dispense("special");
$special2->test = md5(rand());
//$redbean->store($special2);
$redbean->store($special);
$cols = $writer->getColumns("special");
if($cols["datetime"]!=="datetime") fail();
}
pass();
$special->datetime = "convertmeback";
$redbean->store($special);
$redbean->store($special);
$cols = $writer->getColumns("special");
asrt(($cols["datetime"]!="datetime"),true);
$special2 = $redbean->dispense("special");
$special2->datetime = "1990-10-10 12:00:00";
$redbean->store($special2);
$redbean->store($special2);
$cols = $writer->getColumns("special");
asrt(($cols["datetime"]!="datetime"),true);
$special->datetime = "1990-10-10 12:00:00";
$redbean->store($special);
$redbean->store($special);
$cols = $writer->getColumns("special");
asrt(($cols["datetime"]!="datetime"),false);


testpack("Test RedBean Extended Journaling with manual Opened modification");
$page = $redbean->dispense("page");
$id = $redbean->store($page);
$page = $redbean->load("page",$id);
$page->name = "antique one";
$redbean->store($page);
$newpage = $redbean->dispense("page");
$newpage->id  = $id;
$newpage->name = "new one";
try {
	$redbean->store($newpage);
	fail();
}catch(Exception $e) {
	pass();
}
$newpage = $redbean->dispense("page");
$newpage->id  = $id;
$newpage->name = "new one";
$newpage->setMeta("opened",$page->getMeta("opened"));
try {
	$redbean->store($newpage);
	pass();
}catch(Exception $e) {
	fail();
}



testpack("Test Logger issue");
//issue#Michiel
$rb=$redbean;
$pdo = $adapter->getDatabase();
//$pdo->setDebugMode(1);
$l = $rb->dispense("logentry");
$rb->store($l);
$l = $rb->dispense("admin");
$rb->store($l);
$l = $rb->dispense("logentry");
$rb->store($l);
$l = $rb->dispense("admin");
$rb->store($l);
$admin = $rb->load('admin' , 1);
$a = new RedBean_AssociationManager($toolbox);
$log = $rb->load('logentry' , 1);
$a->associate($log, $admin); //throws exception
$log2 = $rb->load('logentry' , 2);
$a->associate($log2, $admin);
pass();//no exception? still alive? proficiat.. pass!



testpack("Test Query Writer MySQL");
$adapter->exec("DROP TABLE IF EXISTS testtable");
asrt(in_array("testtable",$adapter->getCol("show tables")),false);
$writer->createTable("testtable");
asrt(in_array("testtable",$adapter->getCol("show tables")),true);
asrt(count(array_diff($writer->getTables(),$adapter->getCol("show tables"))),0);
asrt(count(array_keys($writer->getColumns("testtable"))),1);
asrt(in_array("id",array_keys($writer->getColumns("testtable"))),true);
asrt(in_array("c1",array_keys($writer->getColumns("testtable"))),false);
$writer->addColumn("testtable", "c1", 1);
asrt(count(array_keys($writer->getColumns("testtable"))),2);
asrt(in_array("c1",array_keys($writer->getColumns("testtable"))),true);
foreach($writer->sqltype_typeno as $key=>$type) {
	asrt($writer->code($key),$type);
}
asrt($writer->code("unknown"),99);
asrt($writer->scanType(false),0);
asrt($writer->scanType(NULL),0);
asrt($writer->scanType(2),1);
asrt($writer->scanType(255),1);
asrt($writer->scanType(256),2);
asrt($writer->scanType(-1),3);
asrt($writer->scanType(1.5),3);
asrt($writer->scanType(INF),4);
asrt($writer->scanType("abc"),4);
asrt($writer->scanType(str_repeat("lorem ipsum",100)),5);
$writer->widenColumn("testtable", "c1", 2);
$cols=$writer->getColumns("testtable");
asrt($writer->code($cols["c1"]),2);
$writer->widenColumn("testtable", "c1", 3);
$cols=$writer->getColumns("testtable");
asrt($writer->code($cols["c1"]),3);
$writer->widenColumn("testtable", "c1", 4);
$cols=$writer->getColumns("testtable");
asrt($writer->code($cols["c1"]),4);
$writer->widenColumn("testtable", "c1", 5);
$cols=$writer->getColumns("testtable");
asrt($writer->code($cols["c1"]),5);
//$id = $writer->insertRecord("testtable", array("c1"), array(array("lorem ipsum")));
$id = $writer->updateRecord("testtable", array(array("property"=>"c1","value"=>"lorem ipsum")));
$row = $writer->selectRecord("testtable", array("id"=>array($id)));
asrt($row[0]["c1"],"lorem ipsum");
$writer->updateRecord("testtable", array(array("property"=>"c1","value"=>"ipsum lorem")), $id);
$row = $writer->selectRecord("testtable", array("id"=>array($id)));
asrt($row[0]["c1"],"ipsum lorem");
$writer->selectRecord("testtable", array("id"=>array($id)),null,true);
$row = $writer->selectRecord("testtable", array("id"=>array($id)));
asrt(empty($row),true);
//$pdo->setDebugMode(1);
$writer->addColumn("testtable", "c2", 2);
try {
	$writer->addUniqueIndex("testtable", array("c1","c2"));
	fail(); //should fail, no content length blob
}catch(RedBean_Exception_SQL $e) {
	pass();
}
$writer->addColumn("testtable", "c3", 2);
try {
	$writer->addUniqueIndex("testtable", array("c2","c3"));
	pass(); //should fail, no content length blob
}catch(RedBean_Exception_SQL $e) {
	fail();
}

$a = $adapter->get("show index from testtable");

asrt(count($a),3);
asrt($a[1]["Key_name"],"UQ_64b283449b9c396053fe1724b4c685a80fd1a54d");
asrt($a[2]["Key_name"],"UQ_64b283449b9c396053fe1724b4c685a80fd1a54d");



//Test constraints: cascaded delete
testpack("Test Cascaded Delete");

$n1 = $redbean->dispense("nonexistant1");
$n2 = $redbean->dispense("nonexistant2");
RedBean_Plugin_Constraint::addConstraint($n1, $n2);

//add cask 101 and whisky 12
$cask = $redbean->dispense("cask");
$whisky = $redbean->dispense("whisky");
$cask->number = 100;
$whisky->age = 10;
$a = new RedBean_AssociationManager( $toolbox );
$a->associate( $cask, $whisky );
//first test baseline behaviour, dead record should remain
asrt(count($a->related($cask, "whisky")),1);
$redbean->trash($cask);
//no difference
asrt(count($a->related($cask, "whisky")),1);
$adapter->exec("TRUNCATE cask_whisky"); //clean up for real test!

//add cask 101 and whisky 12
$cask = $redbean->dispense("cask");
$whisky = $redbean->dispense("whisky");
$cask->number = 101;
$whisky->age = 12;
$a = new RedBean_AssociationManager( $toolbox );
$a->associate( $cask, $whisky );

//add cask 102 and whisky 13
$cask2 = $redbean->dispense("cask");
$whisky2 = $redbean->dispense("whisky");
$cask2->number = 102;
$whisky2->age = 13;
$a = new RedBean_AssociationManager( $toolbox );
$a->associate( $cask2, $whisky2 );

//add constraint
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),true,true);
//no error for duplicate
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),false,true);


asrt(count($a->related($cask, "whisky")),1);
$redbean->trash($cask);
asrt(count($a->related($cask, "whisky")),0); //should be gone now!

asrt(count($a->related($whisky2, "cask")),1);
$redbean->trash($whisky2);
asrt(count($a->related($whisky2, "cask")),0); //should be gone now!


$pdo->Execute("DROP TABLE IF EXISTS cask_whisky");
$pdo->Execute("DROP TABLE IF EXISTS cask");
$pdo->Execute("DROP TABLE IF EXISTS whisky");

//add cask 101 and whisky 12
$cask = $redbean->dispense("cask");
$cask->number = 201;
$cask2 = $redbean->dispense("cask");
$cask2->number = 202;
$a->associate($cask,$cask2);
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),true,true);
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false,true);
//now from cache... no way to check if this works :(
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false,false);
asrt(count($a->related($cask, "cask")),1);
$redbean->trash( $cask2 );
asrt(count($a->related($cask, "cask")),0);


//now in combination with prefixes
$pdo->Execute("DROP TABLE IF EXISTS xx_barrel_grapes");
$pdo->Execute("DROP TABLE IF EXISTS xx_grapes");
$pdo->Execute("DROP TABLE IF EXISTS xx_barrel");
class TestFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xx_$table";}
	public function formatBeanID( $table ) {return "id";}
}
$oldwriter = $writer;
$oldredbean = $redbean;
$writer = new RedBean_QueryWriter_MySQL( $adapter, false );
$writer->setBeanFormatter( new TestFormatter );
$redbean = new RedBean_OODB( $writer );
$t2 = new RedBean_ToolBox($redbean,$adapter,$writer);
$a = new RedBean_AssociationManager($t2);
$redbean = new RedBean_OODB( $writer );
RedBean_Plugin_Constraint::setToolBox($t2);
$b = $redbean->dispense("barrel");
$g = $redbean->dispense("grapes");
$g->type = "merlot";
$b->texture = "wood";
$a->associate($g, $b);
asrt(RedBean_Plugin_Constraint::addConstraint($b, $g),true);
asrt(RedBean_Plugin_Constraint::addConstraint($b, $g),false);
asrt($redbean->count("barrel_grapes"),1);
$redbean->trash($g);
asrt($redbean->count("barrel_grapes"),0);
//$adapter->getDatabase()->setDebugMode(1);
//prefixes and logger
$p=$redbean->dispense("page");
$p->name="abc";
$id=$redbean->store($p);
$p=$redbean->load("page",$id);
$p->name="def";
$id=$redbean->store($p);

$p2=$redbean->dispense("page");
$p2->name="abc2";
$a->associate($p2,$p);

$redbean->trash($p);
$redbean->trash($p2);

//put things back in order for next tests...
$a = new RedBean_AssociationManager($toolbox);
$writer = $oldwriter;
$redbean=$oldredbean;


//Zero issue (false should be stored as 0 not as '')
testpack("Zero issue");
$pdo->Execute("DROP TABLE IF EXISTS `zero`");
$bean = $redbean->dispense("zero");
$bean->zero = false;
$bean->title = "bla";
$redbean->store($bean);
asrt( count(RedBean_Plugin_Finder::where("zero"," zero = 0 ")), 1 );

//Section D Security Tests
testpack("Test RedBean Security - bean interface ");
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean = $redbean->load("page","13; drop table hack");
asrt(in_array("hack",$adapter->getCol("show tables")),true);
try {
	$bean = $redbean->load("page where 1; drop table hack",1);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean = $redbean->dispense("page");
$evil = "; drop table hack";
$bean->id = $evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
unset($bean->id);
$bean->name = "\"".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->name = "'".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->$evil = 1;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
unset($bean->$evil);
$bean->id = 1;
$bean->name = "\"".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->name = "'".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->$evil = 1;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
try {
	$redbean->trash($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
try {
	RedBean_Plugin_Finder::where("::");
}catch(Exception $e) {
	pass();
}



$adapter->exec("drop table if exists sometable");
testpack("Test RedBean Security - query writer");
try {
	$writer->createTable("sometable` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , PRIMARY KEY ( `id` ) ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ; drop table hack; --");
}catch(Exception $e) {

}
asrt(in_array("hack",$adapter->getCol("show tables")),true);

//print_r( $adapter->get("select id from page where id = 1; drop table hack") );
//asrt(in_array("hack",$adapter->getCol("show tables")),true);
//$bean = $redbean->load("page","13);show tables; ");
//exit;


testpack("Test ANSI92 issue in clearrelations");
$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS author");
$pdo->Execute("DROP TABLE IF EXISTS book_author");
$pdo->Execute("DROP TABLE IF EXISTS author_book");
$redbean = $toolbox->getRedBean();
$a = new RedBean_AssociationManager( $toolbox );
$book = $redbean->dispense("book");
$author1 = $redbean->dispense("author");
$author2 = $redbean->dispense("author");
$book->title = "My First Post";
$author1->name="Derek";
$author2->name="Whoever";
$a->set1toNAssoc($book,$author1);
$a->set1toNAssoc($book, $author2);
pass();
$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS author");
$pdo->Execute("DROP TABLE IF EXISTS book_author");
$pdo->Execute("DROP TABLE IF EXISTS author_book");
$redbean = $toolbox->getRedBean();
$a = new RedBean_AssociationManager( $toolbox );
$book = $redbean->dispense("book");
$author1 = $redbean->dispense("author");
$author2 = $redbean->dispense("author");
$book->title = "My First Post";
$author1->name="Derek";
$author2->name="Whoever";
$a->associate($book,$author1);
$a->associate($book, $author2);
pass();

testpack("Test Association Issue Group keyword (Issues 9 and 10)");
$pdo->Execute("DROP TABLE IF EXISTS `group`");
$pdo->Execute("DROP TABLE IF EXISTS `book_group`");
$group = $redbean->dispense("group");
$group->name ="mygroup";
$redbean->store( $group );
try {
	$a->associate($group,$book);
	pass();
}catch(RedBean_Exception_SQL $e) {
	fail();
}
//test issue SQL error 23000
try {
	$a->associate($group,$book);
	pass();
}catch(RedBean_Exception_SQL $e) {
	fail();
}
asrt((int)$adapter->getCell("select count(*) from book_group"),1); //just 1 rec!

$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS author");
$pdo->Execute("DROP TABLE IF EXISTS book_author");
$pdo->Execute("DROP TABLE IF EXISTS author_book");
$redbean = $toolbox->getRedBean();
$a = new RedBean_AssociationManager( $toolbox );
$book = $redbean->dispense("book");
$author1 = $redbean->dispense("author");
$author2 = $redbean->dispense("author");
$book->title = "My First Post";
$author1->name="Derek";
$author2->name="Whoever";
$a->unassociate($book,$author1);
$a->unassociate($book, $author2);
pass();
$redbean->trash($redbean->dispense("bla"));
pass();
$bean = $redbean->dispense("bla");
$bean->name = 1;
$bean->id = 2;
$redbean->trash($bean);
pass();

//Can we save and load? -- with empty properties?
$book = $redbean->dispense("book");
$id = $redbean->store($book);
$book = $redbean->load("book", $id);
$id = $redbean->store($book);
pass();


testpack("Test Domain Object");

class Book extends RedBean_DomainObject {

	public function getTitle() {
		return $this->bean->title;
	}

	public function setTitle( $title ) {
		$this->bean->title = $title;
	}

	public function addAuthor( Author $author ) {
		$this->associate($author);
	}

	public function getAuthors() {
		return $this->related( new Author );
	}
}

class Author extends RedBean_DomainObject {
	public function setName( $name ) {
		$this->bean->name = $name;
	}
	public function getName() {
		return $this->bean->name;
	}
}

$book = new Book;
$author = new Author;
$book->setTitle("A can of beans");
$author->setName("Mr. Bean");
$book->addAuthor($author);
$id = $book->getID();

$book2 = new Book;
$book2->find( $id );

asrt($book2->getTitle(),"A can of beans");
$authors = $book2->getAuthors();
asrt((count($authors)),1);
$he = array_pop($authors);
asrt($he->getName(),"Mr. Bean");

testpack("Unit Of Work");

$uow = new RedBean_UnitOfWork();
$count=array();
$uow->addWork("a", function() {
	global $count;
	$count[]="a";
})
;
$uow->addWork("b", function() {
	global $count;
	$count[]="b";
})
;
$uow->doWork("a");
$uow->doWork("a");
$uow->doWork("b");
$cnt = array_count_values($count);
asrt($cnt["a"],2);
asrt($cnt["b"],1);
$book = $redbean->dispense("book");
$book->title = "unit of work book";
$uow->addWork("save", function() use($redbean, $book) {
	$redbean->store($book);
})
;
$uow->addWork("all_save",function() use($uow) {
	$uow->doWork("save");
})
;
$uow->doWork("all_save");
asrt(count( RedBean_Plugin_Finder::where("book","title LIKE '%unit%'") ),1);

testpack("Facade Multiple DB");

$databases = array(
   "database1" => array("dsn"=>"sqlite:/tmp/testsqlite1","username"=>"","password"=>"","frozen"=>false),
   "database2" => array("dsn"=>"sqlite:/tmp/testsqlite2","username"=>"","password"=>"","frozen"=>false),
);

$dbs = R::setupMultiple($databases);

$r1 = $dbs["database1"];
$r2 = $dbs["database2"];

$r1->exec("drop table if exists book");
$r1->exec("drop table if exists book_page");
$r1->exec("drop table if exists page_page");
$r1->exec("drop table if exists page");
$r1->exec("drop table if exists shelf");
$r2->exec("drop table if exists book");
$r2->exec("drop table if exists book_page");
$r2->exec("drop table if exists page_page");
$r2->exec("drop table if exists page");
$r2->exec("drop table if exists shelf");


$book = $r1->dispense("book");
$book->title = "ONE";
$id1 = $r1->store($book);


$book2 = $r2->dispense("book");
$book2->title = "TWO";
$id2 = $r2->store($book2);

asrt($r1->load("book",$id1)->title,"ONE");
asrt($r2->load("book",$id1)->title,"TWO");
asrt($r1->findOne("book")->title,"ONE");
asrt($r2->findOne("book")->title,"TWO");

$page1 = $r1->dispense("page");
$page1->title = "FIRST";

$page2 = $r2->dispense("page");
$page2->title = "SECOND";

$r1->associate($book,$page1);
$r2->associate($book2,$page2);
asrt($r1->relatedOne($book,"page")->title,"FIRST");
asrt($r2->relatedOne($book,"page")->title,"SECOND");
$a=$r1->related($book,"page");
$p = array_pop($a);
asrt($p->title,"FIRST");
$a=$r2->related($book2,"page");
$p = array_pop($a);
asrt($p->title,"SECOND");
$a =$r1->related($book,"page");
//$r1->debug(1);
$c = count($a);
$r1->unassociate($book,$page1);
$c1 = count($r1->related($book,"page"));
asrt($c,$c1+1);
$r2->clearRelations($page2,"book");
$c2 = count($r2->related($book2,"page"));
asrt($c2,0);

asrt(0,count($r2->getAll("select * from book where title='ONE'")));
asrt(0,count($r1->getAll("select * from book where title='TWO'")));
asrt(2,count($r1->getRow("select * from book where title='ONE'")));
asrt(2,count($r2->getRow("select * from book where title='TWO'")));
asrt("ONE",($r1->getCell("select title from book where title='ONE'")));
asrt("TWO",($r2->getCell("select title from book where title='TWO'")));

$r1->tag($book,"nice");
asrt(count($r1->tagged("book","nice"))>0,true);
$r2->tag($book,"better");
asrt(count($r2->tagged("book","better"))>0,true);

$book->cover = "red";
$book2->cover = "green";
$r1->store($book);
$r2->store($book2);
$form1 = array("book"=>array("title"=>"THREE IN ONE","type"=>"book","id"=>$id1));
$form2 = array("book"=>array("title"=>"FOUR IN TWO","type"=>"book","id"=>$id2));
$books = $r1->cooker($form1);
$abook = array_pop($books["can"]);
asrt("red",$abook->cover);
$books = $r2->cooker($form2);
$abook = array_pop($books["can"]);
asrt("green",$abook->cover);

$r1->associate($book,$page1);
$r2->associate($book2,$page2);
$r1->view("shelf","book,page");
$a = $r1->getAll("select * from shelf where id = $id1 ");
$shelf = array_pop($a);
asrt($shelf["title_of_page"],"FIRST");
$r2->view("shelf","book,page");
$shelf = $r2->getCell("select title_of_page from shelf where id = $id2 ");
asrt($shelf,"SECOND");

testpack("Facade Basics");
R::setup("sqlite:/tmp/teststore.txt"); //should work as well
pass();
R::exec("select 123");
pass();
unlink("/tmp/teststore.txt");
asrt(file_exists("/tmp/teststore.txt"),FALSE);
R::setup("sqlite:/tmp/teststore.txt");
asrt(R::$redbean instanceof RedBean_OODB,TRUE);
asrt(R::$toolbox instanceof RedBean_Toolbox,TRUE);
asrt(R::$adapter instanceof RedBean_Adapter,TRUE);
asrt(R::$writer instanceof RedBean_QueryWriter,TRUE);
$book = R::dispense("book");
asrt($book instanceof RedBean_OODBBean,TRUE);
$book->title = "a nice book";
$id = R::store($book);
asrt(($id>0),TRUE);
$book = R::load("book", (int)$id);
asrt($book->title,"a nice book");
$author = R::dispense("author");
$author->name = "me";
R::store($author);
R::link($book,$author);
asrt($book->author_id, $author->id);
asrt(R::getKey($book,"author"), $author->id);
asrt(($book->author_id>0), TRUE);
asrt(R::getBean($book,"author")->name,"me");
R::breakLink($book,"author");
asrt(($book->author_id>0), FALSE);
$book9 = R::dispense("book");
$author9 = R::dispense("author");
$author9->name="mr Nine";
R::link($book9,$author9);//wo save
$bk9 = R::store($book9);
$book9 = R::load("book",$bk9);
asrt(R::getBean($book9, "author")->name,"mr Nine");
R::trash(R::getBean($book9, "author"));
R::trash($book9);
pass();
$book2 = R::dispense("book");
$book2->title="second";
R::store($book2);
R::associate($book,$book2);

asrt(count(R::related($book,"book")),1);
$book3 = R::dispense("book");
$book3->title="third";
R::store($book3);
R::associate($book,$book3);
asrt(count(R::related($book,"book")),2);
R::attach($book,$book2);
R::attach($book,$book3);
asrt( R::getParent($book3)->id, $book->id );
asrt(count(R::children($book)),2);
asrt(count(R::find("book")),3);
asrt(count(R::find("book","1")),3);
asrt(count(R::find("book"," title LIKE ?", array("third"))),1);
asrt(count(R::find("book"," title LIKE ?", array("%d%"))),2);
R::unassociate($book, $book2);
asrt(count(R::related($book,"book")),1);
R::trash($book3);
R::trash($book2);
asrt(count(R::related($book,"book")),0);
asrt(count(R::children($book)),0);
asrt(count(R::getAll("SELECT * FROM book ")),1);
asrt(count(R::getCol("SELECT title FROM book ")),1);
asrt((int)R::getCell("SELECT 123 "),123);
$titles = R::lst("book","title");
asrt(count($titles),1);
asrt(($titles[0]),"a nice book");

testpack("FUSE");


testpack("test FUSE for association removal");

$marker = false;
class Model_Book_Page extends RedBean_SimpleModel {

public function update() {}

public function delete() { global $marker; $marker=true; }
}

$book = R::dispense("book");
$page = R::dispense("page");
$book->name = "a";
$page->name="b";
asrt($marker,false);
R::associate($book,$page);
R::unassociate($book,$page);
asrt($marker,true);
$marker = false;
R::associate($book,$page);
R::unassociate($book,$page);
asrt($marker,true);
$marker = false;
R::associate($book,$page);
R::unassociate($book,$page,true);
asrt($marker,false);

class Model_Cigar extends RedBean_SimpleModel {
	
	public static $reachedDeleted = false;
	public static $reachedDispense = false;
	public static $reachedAfterUpdate = false;
	public static $reachedAfterDeleted = false;
	
	public function after_update() {
		self::$reachedAfterUpdate = true;
	}

	public function update() {
		$this->rating++;
	}
	public function delete() {
		self::$reachedDeleted =true;
	}
	public function after_delete() {
		self::$reachedAfterDeleted =true;
	}
	public function open() {
		$this->rating++;
	}

	public function dispense() {
		self::$reachedDispense = true;
	}
	
	public function getTaste( $what ) {
		return "smokey like $what";
	}
	

}
$cgr = R::dispense("cigar");
$cgr->brand = "Pigge";
$cgr->rating = 3;
$id = R::store( $cgr );
$cgr = R::load( "cigar", $id );
asrt($cgr->rating,5);
R::trash($cgr);
asrt(Model_Cigar::$reachedDeleted,TRUE);
asrt(Model_Cigar::$reachedAfterDeleted,TRUE);
asrt(Model_Cigar::$reachedDispense,TRUE);
asrt(Model_Cigar::$reachedAfterUpdate,TRUE);
asrt($cgr->getTaste("tabacco"),"smokey like tabacco");
//test __isset
asrt(empty($cgr->taste),true);
asrt(empty($cgr->brand),false);
testpack("copy()");
R::setup(
  "mysql:host={$ini['mysql']['host']};dbname={$ini['mysql']['schema']}",
  $ini['mysql']['user'],
  $ini['mysql']['pass']
);






testpack("N:X relationships");

$pdo->Execute("DROP TABLE IF EXISTS address");

$person = R::dispense('person');
$person->name = 'bill';
R::store($person);

$homeAddress = R::dispense('address');
$homeAddress->street = '123 Street St';
$homeAddress->city = 'Cityville';
$homeAddress->postcode = '12345';

$postalAddress = R::dispense('address');
$postalAddress->street = '456 Road Rd';
$postalAddress->city = 'Cityville';
$postalAddress->postcode = '12345';

R::store($homeAddress);
R::store($postalAddress);

R::link($person, $homeAddress, 'home');
R::link($person, $postalAddress, 'postal');


unset($homeAddress);
unset($postalAddress);

$homeAddress = R::getBean($person, 'address', 'home');
$postalAddress = R::getBean($person, 'address', 'postal');

asrt($homeAddress->street,'123 Street St');
asrt($postalAddress->street,'456 Road Rd');






$book = R::dispense("book");
$book->title = "not so original title";
$author = R::dispense("author");
$author->name="Bobby";
R::store($book);
$aid = R::store($author);
R::associate($book,$author);
$author = R::findOne("author"," name = ? ",array("Bobby"));
$books = R::related($author,"book");
$book = reset($books);
$book2 = R::copy($book,"author");
$authors2 = R::related($book2,"author");
$author2 = reset($authors2);
asrt($author2->name,$author->name);
asrt($author2->id,$author->id);
asrt(($book->id!==$book2->id),true);
asrt($book->title,$book2->title);

testpack("Test Swap function in R-facade");
$book = R::dispense("book");
$book->title = "firstbook";
$book->rating = 2;
$id1 = R::store($book);
$book = R::dispense("book");
$book->title = "secondbook";
$book->rating = 3;
$id2 = R::store($book);
$book1 = R::load("book",$id1);
$book2 = R::load("book",$id2);
asrt($book1->rating,'2');
asrt($book2->rating,'3');
$books = R::batch("book",array($id1,$id2));
R::swap($books,"rating");
$book1 = R::load("book",$id1);
$book2 = R::load("book",$id2);
asrt($book1->rating,'3');
asrt($book2->rating,'2');

testpack("Test Serializing Beans");
$bean = R::dispense("book");
asrt(($bean->getMeta("sys.oodb") instanceof RedBean_OODB), false);
$str = serialize($bean);
asrt((strlen($str)>0),true);
$bean = unserialize($str);
$id = R::store($bean);
asrt(($id>0),true);


testpack("Test R::convertToBeans");
$SQL = "SELECT '1' as id, a.name AS name, b.title AS title, '123' as rating FROM author AS a LEFT JOIN book as b ON b.id = ?  WHERE a.id = ? ";
$rows = R::$adapter->get($SQL,array($id2,$aid));
$beans = R::convertToBeans("something",$rows);
$bean = reset($beans);
asrt($bean->getMeta("type"),"something");
asrt($bean->name,"Bobby");
asrt($bean->title,"secondbook");
asrt($bean->rating,"123");


testpack("Ext Assoc with facade and findRelated");
//R::setup("sqlite:/Users/prive/blaataap.db");
R::exec("DROP TABLE IF EXISTS track");
R::exec("DROP TABLE IF EXISTS cd");
R::exec("DROP TABLE IF EXISTS performer");
R::exec("DROP TABLE IF EXISTS cd_track");
$cd = R::dispense("cd");
$cd->title = "Midnight Jazzfest";
R::store($cd);
$track = R::dispense("track");
$track->title="Night in Tunesia";
$track2 = R::dispense("track");
$track2->title="Stompin at one o clock";
$track3 = R::dispense("track");
$track3->title="Nightlife";
R::store($track);
R::store($track2);
R::store($track3);
//assoc ext with json
R::associate($track,$cd,'{"order":1}');
pass();
//width array
R::associate($track2,$cd,array("order"=>2));
pass();
R::associate($track3,$cd,'{"order":3}');
pass();
$tracks = R::findRelated($cd,"track"," title LIKE ? AND id IN (:keys) ",array("Night%"));
asrt(count($tracks),2);
$track = array_pop($tracks);
asrt((strpos($track->title,"Night")===0),true);
$track = array_pop($tracks);
asrt((strpos($track->title,"Night")===0),true);
$track = R::dispense("track");
$track->title = "test";
R::associate($track,$cd,"this column should be named extra");
asrt( R::getCell("SELECT count(*) FROM cd_track WHERE extra = 'this column should be named extra' "),"1");
$composer = R::dispense("performer");
$composer->name = "Miles Davis";
R::store($composer);
R::link($track, $composer);
R::store($track);
R::link($track2, $composer);
R::store($track2);
$beans = R::findLinks($composer,"track");
asrt(count($beans),2);
$bean = reset($beans);
asrt($bean->getMeta("type"),"track");


testpack("Test Table Prefixes");
R::setup(
  "mysql:host={$ini['mysql']['host']};dbname={$ini['mysql']['schema']}",
  $ini['mysql']['user'],
  $ini['mysql']['pass']
);

class MyTableFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {
		return "xx_$table";
	}
	public function formatBeanID( $table ) {
		return "id";
	}
}

R::$writer->tableFormatter = new MyTableFormatter;
$pdo->Execute("DROP TABLE IF EXISTS page");
$pdo->Execute("DROP TABLE IF EXISTS user");
$pdo->Execute("DROP TABLE IF EXISTS page_user");
$pdo->Execute("DROP TABLE IF EXISTS page_page");
$pdo->Execute("DROP TABLE IF EXISTS xx_page");
$pdo->Execute("DROP TABLE IF EXISTS xx_user");
$pdo->Execute("DROP TABLE IF EXISTS xx_page_user");
$pdo->Execute("DROP TABLE IF EXISTS xx_page_page");
$page = R::dispense("page");
$page->title = "mypage";
$id=R::store($page);
$page = R::dispense("page");
$page->title = "mypage2";
R::store($page);
$beans = R::find("page");
asrt(count($beans),2);
$user = R::dispense("user");
$user->name="me";
R::store($user);
R::associate($user,$page);
asrt(count(R::related($user,"page")),1);
$page = R::load("page",$id);
asrt($page->title,"mypage");
R::associate($user,$page);
asrt(count(R::related($user,"page")),2);
asrt(count(R::related($page,"user")),1);
$user2 = R::dispense("user");
$user2->name="Bob";
R::store($user2);
$user3 = R::dispense("user");
$user3->name="Kim";
R::store($user3);
R::attach($user,$user3);
asrt(count(R::children($user)),1);
R::attach($user,$user2);
asrt(count(R::children($user)),2);
$usrs=R::children($user);
$user = reset($usrs);
asrt(($user->name=="Bob" || $user->name=="Kim"),true);
R::link($user2,$page);
$p = R::getBean($user2,"page");
asrt($p->title,"mypage");
$t = R::$writer->getTables();
asrt(in_array("xx_page",$t),true);
asrt(in_array("xx_page_user",$t),true);
asrt(in_array("xx_user",$t),true);
asrt(in_array("page",$t),false);
asrt(in_array("page_user",$t),false);
asrt(in_array("user",$t),false);
$page2 = R::dispense("page");
$page2->title = "mypagex";
R::store($page2);
R::associate($page,$page2,'{"bla":2}');
$pgs = R::related($page,"page");
$p = reset($pgs);
asrt($p->title,"mypagex");
asrt(R::getCell("select bla from xx_page_page where bla > 0"),"2");
$t = R::$writer->getTables();
asrt(in_array("xx_page_page",$t),true);
asrt(in_array("page_page",$t),false);


testpack("Testing: combining table prefix and IDField");
$pdo->Execute("DROP TABLE IF EXISTS cms_blog");
$pdo->Execute("DROP TABLE IF EXISTS cms_blog_post");
$pdo->Execute("DROP TABLE IF EXISTS cms_post");
class MyBeanFormatter implements RedBean_IBeanFormatter{
    public function formatBeanTable($table) {
        return "cms_$table";
    }
    public function formatBeanID( $table ) {
        return "{$table}_id"; // append table name to id. The table should not inclide the prefix.
    }
}


R::$writer->setBeanFormatter(new MyBeanFormatter());
$blog = R::dispense('blog');
$blog->title = 'testing';
$blog->blog = 'tesing';
R::store($blog);
$blogpost = (R::load("blog",1));
asrt((isset($blogpost->cms_blog_id)),false);
asrt((isset($blogpost->blog_id)),true);
asrt(in_array("blog_id",array_keys(R::$writer->getColumns("blog"))),true);
asrt(in_array("cms_blog_id",array_keys(R::$writer->getColumns("blog"))),false); 

$post = R::dispense("post");
$post->message = "hello";
R::associate($blog,$post);
asrt(count(R::related($blog,"post")),1);

asrt(count(R::find("blog"," title LIKE '%est%' ")),1);
$a = R::getAll("select * from ".tbl("blog")." ");
asrt(count($a),1);


testpack("test model formatting");
class mymodelformatter implements RedBean_IModelFormatter{
	public function formatModel($model){
		return "my_weird_".$model."_model";
	}
}
class my_weird_weirdo_model extends RedBean_SimpleModel {
	public function blah(){ return "yes!"; }
}
RedBean_ModelHelper::setModelFormatter(new mymodelformatter);
$w = R::dispense("weirdo");
asrt($w->blah(),"yes!");
//R::debug(1);
testpack("Test Tagging");
R::tag($post,"lousy,smart");
asrt(R::tag($post),"lousy,smart");
R::tag($post,"clever,smart");
asrt(R::tag($post),"smart,clever");
R::tag($blog,array("smart","interesting"));
asrt(R::tag($blog),"smart,interesting");
try{
R::tag($blog,array(";","interesting"));
fail();
}catch(RedBean_Exception $e){ pass(); }
asrt(R::tag($blog),"smart,interesting");
R::tag($blog, false);
asrt(R::tag($blog),"");



testpack("New relations");
$pdo->Execute("DROP TABLE IF EXISTS person");
$pdo->Execute("DROP TABLE IF EXISTS person_person");
R::$writer->tableFormatter = null;
$track = R::dispense('track');
$album = R::dispense('cd');
$track->name = 'a';
$track->orderNum = 1;
$track2 = R::dispense('track');
$track2->orderNum = 2;
$track2->name = 'b';
R::associate( $album, $track );
R::associate( $album, $track2 );
$tracks = R::related( $album, 'track', ' 1 ORDER BY orderNum ' );
$track = array_shift($tracks);
$track2 = array_shift($tracks);
asrt($track->name,'a');
asrt($track2->name,'b');

$t = R::dispense('person');
$s = R::dispense('person');
$s2 = R::dispense('person');
$t->name = 'a';
$t->role = 'teacher';
$s->role = 'student';
$s2->role = 'student';
$s->name = 'a';
$s2->name = 'b';
R::associate($t, $s);
R::associate($t, $s2);
$students = R::related($t, 'person', ' role = "student"  ORDER BY `name` ');
$s = array_shift($students);
$s2 = array_shift($students);
asrt($s->name,'a');
asrt($s2->name,'b');
$s= R::relatedOne($t, 'person', ' role = "student"  ORDER BY `name` ');
asrt($s->name,'a');
//empty classroom
R::clearRelations($t, 'person', $s2);
$students = R::related($t, 'person', ' role = "student"  ORDER BY `name` ');
asrt(count($students),1);
$s = reset($students);
asrt($s->name, 'b');

function getList($beans,$property) {
	$items = array();
	foreach($beans as $bean) {
		$items[] = $bean->$property;
	}
	sort($items);
	return implode(",",$items);
}

testpack("unrelated");
$pdo->Execute("DROP TABLE IF EXISTS person");
$pdo->Execute("DROP TABLE IF EXISTS person_person");
$painter = R::dispense('person');
$painter->job = 'painter';
$accountant = R::dispense('person');
$accountant->job = 'accountant';
$developer = R::dispense('person');
$developer->job = 'developer';
$salesman = R::dispense('person');
$salesman->job = 'salesman';
R::associate($painter, $accountant);
R::associate($salesman, $accountant);
R::associate($developer, $accountant);
R::associate($salesman, $developer);
asrt( getList( R::unrelated($salesman,"person"),"job" ), "painter,salesman" ) ;
asrt( getList( R::unrelated($accountant,"person"),"job" ), "accountant" ) ;
asrt( getList( R::unrelated($painter,"person"),"job" ), "developer,painter,salesman" ) ;
R::associate($accountant, $accountant);
R::associate($salesman, $salesman);
R::associate($developer, $developer);
R::associate($painter, $painter);
asrt( getList( R::unrelated($accountant,"person"),"job" ), "" ) ;
asrt( getList( R::unrelated($painter,"person"),"job" ), "developer,salesman" ) ;
asrt( getList( R::unrelated($salesman,"person"),"job" ), "painter" ) ;
asrt( getList( R::unrelated($developer,"person"),"job" ), "painter" ) ;

testpack("Test parameter binding");
R::$adapter->getDatabase()->flagUseStringOnlyBinding = TRUE;
try{R::getAll("select * from job limit ? ", array(1)); fail(); }catch(Exception $e){ pass(); }
try{R::getAll("select * from job limit :l ", array(":l"=>1)); fail(); }catch(Exception $e){ pass(); }
try{R::exec("select * from job limit ? ", array(1)); fail(); }catch(Exception $e){ pass(); }
try{R::exec("select * from job limit :l ", array(":l"=>1)); fail(); }catch(Exception $e){ pass(); }
R::$adapter->getDatabase()->flagUseStringOnlyBinding = FALSE;
try{R::getAll("select * from job limit ? ", array(1)); pass(); }catch(Exception $e){ print_r($e); fail(); }
try{R::getAll("select * from job limit :l ", array(":l"=>1)); pass(); }catch(Exception $e){ fail(); }
try{R::exec("select * from job limit ? ", array(1)); pass(); }catch(Exception $e){ fail(); }
try{R::exec("select * from job limit :l ", array(":l"=>1)); pass(); }catch(Exception $e){ fail(); }

testpack("Test findOrDispense");
$person = R::findOrDispense("person", " job = ? ", array("developer"));
asrt((count($person)>0), true);
$person = R::findOrDispense("person", " job = ? ", array("musician"));
asrt((count($person)>0), true);
$musician = array_pop($person);
asrt(intval($musician->id),0);

testpack("Test count and wipe");
$page = R::dispense("page");
$page->name = "ABC";
R::store($page);
$n1 = R::count("page");
$page = R::dispense("page");
$page->name = "DEF";
R::store($page);
$n2 = R::count("page");
asrt($n1+1, $n2);
R::wipe("page");
asrt(R::count("page"),0);
asrt(R::$redbean->count("page"),0);


function setget($val) {
global $pdo;
$bean = R::dispense("page");
$_tables = R::$writer->getTables();
if (in_array("page",$_tables)) $pdo->Execute("DROP TABLE page");
$bean->prop = $val;
$id = R::store($bean);
$bean = R::load("page",$id);
return $bean->prop;
}




//this module tests whether values we store are the same we get returned
testpack("setting and getting values, pdo/types");
asrt(setget("-1"),"-1");
asrt(setget(-1),"-1");
asrt(setget("-0.25"),"-0.25");
asrt(setget(-0.25),"-0.25");
asrt(setget("0.12345678"),"0.12345678");
asrt(setget(0.12345678),"0.12345678");
asrt(setget("-0.12345678"),"-0.12345678");
asrt(setget(-0.12345678),"-0.12345678");
asrt(setget("2147483647"),"2147483647");
asrt(setget(2147483647),"2147483647");
asrt(setget(-2147483647),"-2147483647");
asrt(setget("-2147483647"),"-2147483647");
asrt(setget("2147483648"),"2147483648");
asrt(setget("-2147483648"),"-2147483648");
asrt(setget("199936710040730"),"199936710040730");
asrt(setget("-199936710040730"),"-199936710040730");
//Architecture dependent... only test this if you are sure what arch 
//asrt(setget("2147483647123456"),"2.14748364712346e+15");
//asrt(setget(2147483647123456),"2.14748364712e+15");
asrt(setget("2010-10-11"),"2010-10-11");
asrt(setget("2010-10-11 12:10"),"2010-10-11 12:10");
asrt(setget("2010-10-11 12:10:11"),"2010-10-11 12:10:11");
asrt(setget("x2010-10-11 12:10:11"),"x2010-10-11 12:10:11");
asrt(setget("a"),"a");
asrt(setget("."),".");
asrt(setget("\""),"\"");
asrt(setget("just some text"),"just some text");
asrt(setget(true),"1");
asrt(setget(false),"0");
asrt(setget("true"),"true");
asrt(setget("false"),"false");
asrt(setget("null"),"null");
asrt(setget("NULL"),"NULL");
asrt(setget(null),null);
asrt((setget(0)==0),true);
asrt((setget(1)==1),true);
asrt((setget(true)==true),true);
asrt((setget(false)==false),true);  

testpack("fetch tagged items");
R::wipe("book");
R::wipe("tag");
R::wipe("book_tag");
$b = R::dispense("book");
$b->title = 'horror';
R::store($b);
$c = R::dispense("book");
$c->title = 'creepy';
R::store($c);
$d = R::dispense("book");
$d->title = "chicklit";
R::store($d);
R::tag($b, "horror,classic");
R::tag($d, "women,classic");
R::tag($c, "horror");
$x = R::tagged("book","classic");
asrt(count($x),2);
$x = R::tagged("book","classic,horror");

asrt(count($x),3);


testpack("test optimization related() ");
R::$writer->setBeanFormatter( new TestFormatter );
$book = R::dispense("book");
$book->title = "ABC";
$page = R::dispense("page");
$page->content = "lorem ipsum 123 ... ";
R::associate($book,$page);
asrt(count(R::related($book,"page"," content LIKE '%123%' ") ),1);

testpack("test cooker");
$post = array(
	"book"=>array("type"=>"book","title"=>"programming the C64"),
	"book2"=>array("type"=>"book","id"=>1,"title"=>"the art of doing nothing"),
	"book3"=>array("type"=>"book","id"=>1),
	"associations"=>array(
		array("book-book2"),array("page:2-book"),array("0")
	),
	"somethingelse"=>0
);
$beans = R::cooker($post);
asrt(count($beans["can"]),3);
asrt(count($beans["pairs"]),2);
asrt($beans["can"]["book"]->getMeta("tainted"),true);
asrt($beans["can"]["book2"]->getMeta("tainted"),true);
asrt($beans["can"]["book3"]->getMeta("tainted"),false);
asrt($beans["can"]["book3"]->title,"ABC"); 
asrt($beans["pairs"][0][0]->title,"programming the C64");



testpack("test views");

class Fm implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "prefix_$table";}
	public function formatBeanID( $table ) {return $table."__id";}
}


class Fm2 implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "prefix_$table";}
	public function formatBeanID( $table ) {return $table."_id";}
}

function testViews($p) { 

R::exec(" drop table if exists musician ");
R::exec(" drop table if exists bandmember ");
R::exec(" drop table if exists band ");
R::exec(" drop table if exists bandmember_musician ");
R::exec(" drop table if exists band_bandmember ");
R::exec(" drop table if exists prefix_musician ");
R::exec(" drop table if exists prefix_bandmember ");
R::exec(" drop table if exists prefix_band ");
R::exec(" drop table if exists prefix_bandmember_musician ");
R::exec(" drop table if exists prefix_band_bandmember ");



list( $mickey, $donald, $goofy ) = R::dispense("musician",3);
list( $vocals1, $vocals2, $keyboard1, $drums, $vocals3, $keyboard2 ) = R::dispense("bandmember",6);
list( $band1, $band2 ) = R::dispense("band",2);
$band1->name = "The Groofy"; $band2->name="Wickey Mickey";
$mickey->name = "Mickey"; $goofy->name = "Goofy"; $donald->name = "Donald";
$vocals1->instrument = "voice"; $vocals2->instrument="voice";$keyboard1->instrument="keyboard";$drums->instrument="drums";
$vocals3->instrument = "voice"; $keyboard2->instrument="keyboard";
$vocals3->bandleader=true;
$drums->bandleader=true;
$drums->notes = "noisy";
$vocals3->notes = "tenor";

R::associate($mickey,$vocals1);
R::associate($donald,$vocals2);
R::associate($donald,$keyboard1);
R::associate($goofy,$drums);
R::associate($mickey,$vocals3);
R::associate($donald,$keyboard2);

R::associate($band1,$vocals1);
R::associate($band1,$vocals2);
R::associate($band1,$keyboard1);
R::associate($band1,$drums);

R::associate($band2,$vocals3);
R::associate($band2,$keyboard2);

try{
	R::view("bandlist","band");
	fail();
}
catch(Exception $e) {
	pass();
}

try{
	R::view("bandlist","band,bandmember,musician");
	pass();
}
catch(Exception $e) {
	print_r($e);
	fail();
}

//can we do a simple query?
$nameOfBandWithID1 = R::getCell("select `name` from ".$p."bandlist where ".R::$writer->getIDField("band")." = 1 group by  ".R::$writer->getIDField("band"));
asrt($nameOfBandWithID1,"The Groofy");

//can we generate a report? list all bandleaders
$bandleaders = R::getAll("select  `bandleader_of_bandmember`,`name_of_musician`,`name` AS bandname
	from ".$p."bandlist where `bandleader_of_bandmember` =  1 group by id ");
	
foreach($bandleaders as $bl) {
	if ($bl["bandname"]=="Wickey Mickey") {
		asrt($bl["name_of_musician"],"Mickey");
	}
	if ($bl["bandname"]=="The Groofy") {
		asrt($bl["name_of_musician"],"Goofy");
	}
}
//can we draw statistics?
$inHowManyBandsDoYouPlay = R::getAll("select 
`name_of_musician` ,count( distinct `".R::$writer->getIDField("band")."`) as bands 
from ".$p."bandlist group by `".R::$writer->getIDField("musician")."_of_musician`  order by `name_of_musician` asc 
");

asrt($inHowManyBandsDoYouPlay[0]["name_of_musician"],"Donald");
asrt($inHowManyBandsDoYouPlay[0]["bands"],'2');
asrt($inHowManyBandsDoYouPlay[1]["name_of_musician"],"Goofy");
asrt($inHowManyBandsDoYouPlay[1]["bands"],'1');
asrt($inHowManyBandsDoYouPlay[2]["name_of_musician"],"Mickey");
asrt($inHowManyBandsDoYouPlay[2]["bands"],'2');

//who plays in band 2
//can we make a selectbox
$selectbox = R::getAll("
	select m.".R::$writer->getIDField("musician").", m.name, b.".R::$writer->getIDField("band")." as selected from ".$p."musician as m
	left join ".$p."bandlist as b on b.".R::$writer->getIDField("musician")."_of_musician = m.".R::$writer->getIDField("musician")." and 
	b.".R::$writer->getIDField("band")." =2
	order by m.name asc
");

asrt($selectbox[0]["name"],"Donald");
asrt($selectbox[0]["selected"],"2");
asrt($selectbox[1]["name"],"Goofy");
asrt($selectbox[1]["selected"],null);
asrt($selectbox[2]["name"],"Mickey");
asrt($selectbox[2]["selected"],"2");
}

$tf = new Fm();
R::$writer->setBeanFormatter($tf);
testViews("prefix_");
$tf2 = new Fm2();
R::$writer->setBeanFormatter($tf2);
testViews("prefix_");

testpack("export");
list($p1,$p2) = R::dispense("page",2);
$p1->name = '1';
$p2->name = '2';
$arr = ( R::exportAll(array($p1,$p2)) );
asrt(count($arr),2);
asrt($arr[0]["name"],"1");
asrt($arr[1]["name"],"2");

printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");

