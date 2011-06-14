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
 * @package 		test.php
 * @description		Series of Unit Tests for RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
error_reporting(E_ALL | E_STRICT);
$ini = parse_ini_file("test.ini", true);

function printtext( $text ) {
	if ($_SERVER["DOCUMENT_ROOT"]) {
		echo "<BR>".$text;
	}
	else {
		echo "\n".$text;
	}
}



//New test functions, no objects required here
function asrt( $a, $b ) {
	if ($a === $b) {
		global $tests;
		$tests++;
		print( "[".$tests."]" );
	}
	else {
		printtext("FAILED TEST: EXPECTED $b (".gettype($b).") BUT GOT: $a (".gettype($a).") ");
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

//INCLUDE YOUR REDBEAN FILE HERE!
//require("rb.php");
require("RedBean/redbean.inc.php");
$toolbox = RedBean_Setup::kickstartDev(
  "pgsql:host={$ini['pgsql']['host']} dbname={$ini['pgsql']['schema']}",
  $ini['pgsql']['user'],
  $ini['pgsql']['pass']
);

//Observable Mock Object
class ObservableMock extends RedBean_Observable {
	public function test( $eventname, $info ) {
		$this->signal($eventname, $info);
	}
}

class ObserverMock implements RedBean_Observer {
	public $event = false;
	public $info = false;
	public function onEvent($event, $info) {
		$this->event = $event;
		$this->info = $info;
	}
}

try {
	$adapter = $toolbox->getDatabaseAdapter();
	$writer  = $toolbox->getWriter();
	$redbean = $toolbox->getRedBean();

	testpack("UNIT TEST Toolbox");

	asrt(($adapter instanceof RedBean_Adapter_DBAdapter),true);
	asrt(($writer instanceof RedBean_QueryWriter),true);
	asrt(($redbean instanceof RedBean_OODB),true);


	$pdo = $adapter->getDatabase();
	//$pdo->setDebugMode(1);
	
	
	
	
	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}

	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
	$adapter->exec("DROP TABLE IF EXISTS cask_whisky");
	try {
		$adapter->exec("DROP TABLE IF EXISTS cask CASCADE ");
	}catch(Exception $e) {
		die($e->getMessage());
	}
	
	$adapter->exec("DROP TABLE IF EXISTS whisky CASCADE ");
	
$_tables = $writer->getTables();

	

	
	if (in_array("page_user",$_tables)) $pdo->Execute("DROP TABLE page_user");
	if (in_array("cask_whisky",$_tables)) $pdo->Execute("DROP TABLE cask_whisky");
	if (in_array("cask",$_tables)) $pdo->Execute("DROP TABLE cask");
	if (in_array("whisky",$_tables)) $pdo->Execute("DROP TABLE whisky");
	if (in_array("page_page",$_tables)) $pdo->Execute("DROP TABLE page_page");
	if (in_array("xx_barrel_grapes",$_tables)) $pdo->Execute("DROP TABLE xx_barrel_grapes CASCADE");
	if (in_array("admin_logentry",$_tables)) $pdo->Execute("DROP TABLE admin_logentry"); 
	if (in_array("testa_testb",$_tables)) $pdo->Execute("DROP TABLE testa_testb");

	if (!in_array("hack",$_tables)) $pdo->Execute("CREATE TABLE hack (id serial, PRIMARY KEY (id) ); ");
	if (in_array("page",$_tables)) $pdo->Execute("DROP TABLE page");
	if (in_array("user",$_tables)) $pdo->Execute("DROP TABLE \"user\"");
	if (in_array("book",$_tables)) $pdo->Execute("DROP TABLE book");
	if (in_array("author",$_tables)) $pdo->Execute("DROP TABLE author");
	if (in_array("one",$_tables)) $pdo->Execute("DROP TABLE one");
	if (in_array("post",$_tables)) $pdo->Execute("DROP TABLE post");
	if (in_array("association",$_tables)) $pdo->Execute("DROP TABLE association");
	if (in_array("logentry",$_tables)) $pdo->Execute("DROP TABLE logentry");
	if (in_array("admin",$_tables)) $pdo->Execute("DROP TABLE admin");
	if (in_array("wine",$_tables)) $pdo->Execute("DROP TABLE wine");
	if (in_array("xx_barrel",$_tables)) $pdo->Execute("DROP TABLE xx_barrel CASCADE");
	if (in_array("xx_grapes",$_tables)) $pdo->Execute("DROP TABLE xx_grapes CASCADE");
	
		
	$page = $redbean->dispense("page");

	testpack("UNIT TEST Database");
	try {
		$adapter->exec("an invalid query");
		fail();
	}catch(RedBean_Exception_SQL $e ) {
		pass();
	}
	asrt( (int) $adapter->getCell("SELECT 123") ,123);


//Section C: Integration Tests / Regression Tests
//	$adapter->getDatabase()->setDebugMode(1);
	testpack("Test RedBean OODB: Insert Record");
	$page->name = "my page";
	$id = (int) $redbean->store($page);
	asrt( (int) $page->id, 1 );
	asrt( (int) $pdo->GetCell("SELECT count(*) FROM page"), 1 );
	asrt( $pdo->GetCell("SELECT \"name\" FROM page LIMIT 1"), "my page" );
	asrt( $id, 1 );
	testpack("Test RedBean OODB: Can we Retrieve a Record? ");
	$page = $redbean->load( "page", 1 );
	asrt($page->name, "my page");
	asrt(( (bool) $page->getMeta("type")),true);
	asrt(isset($page->id),true);
	asrt(($page->getMeta("type")),"page");
	asrt((int)$page->id,$id);



	testpack("Test RedBean OODB: Can we Update a Record? ");
	$page->name = "new name";
	$newid = $redbean->store( $page );
	asrt( $newid, $id );
	$page = $redbean->load( "page", $id );
	asrt( $page->name, "new name" );


	$page->rating = "1";
	$newid = $redbean->store( $page );
	asrt( (int) $newid, (int) $id );
	$page = $redbean->load( "page", $id );
	asrt( $page->name, "new name" );
	asrt( (int) $page->rating, 1 );



	$page->rating = 5;
//$page->__info["unique"] = array("name","rating");
	$newid = $redbean->store( $page );
	asrt( (int) $newid, (int) $id );
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
	asrt( strval( $page->rating ), "2.5" );

	$page->rating = -3.3;
	$newid = $redbean->store( $page );
	asrt( $newid, $id );
	$page = $redbean->load( "page", $id );
	asrt( $page->name, "new name" );
	asrt( strval( $page->rating ), "-3.3" );

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

	$redbean->trash( $page );



	asrt( (int) $pdo->GetCell("SELECT count(*) FROM page"), 0 );

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
	try {
		$a->associate($page2,$page2);
		fail();
	}catch(RedBean_Exception_SQL $e) {
		pass();
	}
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


	testpack("Transactions");
	$adapter->startTransaction();
	pass();
	$adapter->rollback();
	pass();
	$adapter->startTransaction();
	pass();
	$adapter->commit();
	pass();

	testpack("Test Frozen ");
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
	$redbean->freeze( false );


	testpack("Test Developer Interface API");
	$post = $redbean->dispense("post");
	$post->title = "My First Post";
	$post->created = time();
	$id = $redbean->store( $post );
	$post = $redbean->load("post",$id);
	$redbean->trash( $post );
	pass();


	testpack("Test Finding");
	$keys = $adapter->getCol("SELECT id FROM page WHERE \"name\" LIKE '%John%'");
	asrt(count($keys),2);
	$pages = $redbean->batch("page", $keys);
	asrt(count($pages),2);



	testpack("Test RedBean Finder Plugin*");
//$adapter->getDatabase()->setDebugMode(1);
	asrt(count(RedBean_Plugin_Finder::where("page", " name LIKE '%more%' ")),3);
	asrt(count(RedBean_Plugin_Finder::where("page", " name LIKE :str ",array(":str"=>'%more%'))),3);
	asrt(count(RedBean_Plugin_Finder::where("page", " name LIKE :str ",array(":str"=>'%mxore%'))),0);
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
	asrt(count(RedBean_Plugin_Finder::where("wine"," id > 0 ")),1);
	asrt(count(RedBean_Plugin_Finder::where("wine"," @id < 100 ")),1);
	asrt(count(RedBean_Plugin_Finder::where("wine"," @id > 100 ")),0);
	asrt(count(RedBean_Plugin_Finder::where("wine"," @id < 100 OR TRUE ")),1);
	asrt(count(RedBean_Plugin_Finder::where("wine"," @id > 100 OR TRUE ")),1);
	asrt(count(RedBean_Plugin_Finder::where("wine",
			  " TRUE OR @grape = 'merlot' ")),1); //non-existant column
	asrt(count(RedBean_Plugin_Finder::where("wine",
			  " TRUE OR @wine.grape = 'merlot' ")),1); //non-existant column
	asrt(count(RedBean_Plugin_Finder::where("wine",
			  " TRUE OR @cork=1 OR @grape = 'merlot' ")),1); //2 non-existant column
	asrt(count(RedBean_Plugin_Finder::where("wine",
			  " TRUE OR @cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
	asrt(count(RedBean_Plugin_Finder::where("wine",
			  " TRUE OR @bottle.cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
	RedBean_Setup::getToolbox()->getRedBean()->freeze( TRUE );
	asrt(count(RedBean_Plugin_Finder::where("wine"," TRUE OR TRUE ")),1);
	try {
		RedBean_Plugin_Finder::where("wine"," TRUE OR @grape = 'merlot' ");
		fail();
	}
	catch(RedBean_Exception_SQL $e) {
		pass();
	}
	try {
		RedBean_Plugin_Finder::where("wine"," TRUE OR @wine.grape = 'merlot' ");
		fail();
	}
	catch(RedBean_Exception_SQL $e) {
		pass();
	}
	try {
		RedBean_Plugin_Finder::where("wine"," TRUE OR @cork=1 OR @wine.grape = 'merlot'  ");
		fail();
	}
	catch(RedBean_Exception_SQL $e) {
		pass();
	}
	try {
		RedBean_Plugin_Finder::where("wine"," TRUE OR @bottle.cork=1 OR @wine.grape = 'merlot'  ");
		fail();
	}
	catch(RedBean_Exception_SQL $e) {
		pass();
	}
	try {
		RedBean_Plugin_Finder::where("wine"," TRUE OR @a=1",array(),false,true);
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

	testpack("Test Plugins: Trees ");
	$tm = new RedBean_TreeManager($toolbox);
	$subpage1 = $redbean->dispense("page");
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


//Test constraints: cascaded delete
	testpack("Test Cascaded Delete");
//$adapter = $toolbox->getDatabaseAdapter();
//$adapter->getDatabase()->setDebugMode(1);
	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}


//$adapter->exec("DROP TRIGGER IF EXISTS fkb8317025deb6e03fc05abaabc748a503b ");

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
//no difference -- DIFFERENCE now because we have included add constr.
	asrt(count($a->related($cask, "whisky")),0);
//	$adapter->exec("DROP TABLE cask_whisky"); //clean up for real test!

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
	//asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),true);
//no error for duplicate
	//asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),false);


	//asrt(count($a->related($cask, "whisky")),1);

	$redbean->trash($cask);
	asrt(count($a->related($cask, "whisky")),0); //should be gone now!

	asrt(count($a->related($whisky2, "cask")),1);
	$redbean->trash($whisky2);
	asrt(count($a->related($whisky2, "cask")),0); //should be gone now!

	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}

	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
		$adapter->exec("DROP TABLE IF EXISTS cask_cask");
	$adapter->exec("DROP TABLE IF EXISTS cask_whisky");
	try {
		$adapter->exec("DROP TABLE IF EXISTS cask CASCADE ");
	}catch(Exception $e) {
		die($e->getMessage());
	}
	
	$adapter->exec("DROP TABLE IF EXISTS whisky CASCADE ");
	


//add cask 101 and whisky 12
	$cask = $redbean->dispense("cask");
	$cask->number = 201;
	$cask2 = $redbean->dispense("cask");
	$cask2->number = 202;
	$a->associate($cask,$cask2);
//	asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),true);
//	asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false);
//now from cache... no way to check if this works :(
//	asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false);
	asrt(count($a->related($cask, "cask")),1);
	$redbean->trash( $cask2 );
	asrt(count($a->related($cask, "cask")),0);
//now in combination with prefixes

class TestFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xx_$table";}
	public function formatBeanID( $table ) {return "id";}
}
$oldwriter = $writer;
$oldredbean = $redbean;
$writer = new RedBean_QueryWriter_PostgreSQL( $adapter, false );
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
//put things back in order for next tests...
$a = new RedBean_AssociationManager($toolbox);
$writer = $oldwriter;
$redbean=$oldredbean;







testpack("Test Custom ID Field");
class MyWriter extends RedBean_QueryWriter_PostgreSQL {
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
//$pdo->setDebugMode(0);
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


testpack("Test Table Prefixes");
R::setup(
  "pgsql:host={$ini['pgsql']['host']} dbname={$ini['pgsql']['schema']}",
  $ini['pgsql']['user'],
  $ini['pgsql']['pass']
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
$_tables = $writer->getTables();
if (in_array("page_user",$_tables)) $pdo->Execute("DROP TABLE page_user");
if (in_array("page_page",$_tables)) $pdo->Execute("DROP TABLE page_page");
if (in_array("xx_page_user",$_tables)) $pdo->Execute("DROP TABLE xx_page_user");
if (in_array("xx_page_page",$_tables)) $pdo->Execute("DROP TABLE xx_page_page");

if (in_array("page",$_tables)) $pdo->Execute("DROP TABLE page");
if (in_array("user",$_tables)) $pdo->Execute("DROP TABLE \"user\"");
if (in_array("xx_page",$_tables)) $pdo->Execute("DROP TABLE xx_page");
if (in_array("xx_user",$_tables)) $pdo->Execute("DROP TABLE xx_user");
//R::debug(1);
$page = R::dispense("page");
$page->title = "mypage";
$id=R::store($page);
$page = R::dispense("page");
$page->title = "mypage2";
R::store($page);
$beans = R::find("page"," id > 0");
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
asrt((int)R::getCell("select bla from xx_page_page where bla > 0"),2);
$t = R::$writer->getTables();
asrt(in_array("xx_page_page",$t),true);
asrt(in_array("page_page",$t),false);


testpack("Testing: combining table prefix and IDField");
if (in_array("cms_blog",$_tables)) $pdo->Execute("DROP TABLE cms_blog");
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




testpack("New relations");

if (in_array("person_person",$_tables)) $pdo->Execute("DROP TABLE person_person");
if (in_array("person",$_tables)) $pdo->Execute("DROP TABLE person");

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
$tracks = R::related( $album, 'track', ' TRUE ORDER BY "orderNum" ' );
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
$students = R::related($t, 'person', ' "role" = ?  ORDER BY "name" ',array("student"));
$s = array_shift($students);
$s2 = array_shift($students);
asrt($s->name,'a');
asrt($s2->name,'b');
$s= R::relatedOne($t, 'person', ' role = ?  ORDER BY "name" ',array("student"));
asrt($s->name,'a');
//empty classroom
R::clearRelations($t, 'person', $s2);
$students = R::related($t, 'person', ' role = ?  ORDER BY "name" ',array("student"));
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

$pdo->Execute("DROP TABLE person_person");
$pdo->Execute("DROP TABLE person");


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

testpack("param binding pgsql");
$page = R::dispense("page");
$page->name = "abc";
$page->number = 2;
R::store($page);
R::exec("insert into page (name) values(:name) ", array(":name"=>"my name"));
R::exec("insert into page (number) values(:one) ", array(":one"=>1));
R::exec("insert into page (number) values(:one) ", array(":one"=>"1"));
R::exec("insert into page (number) values(:one) ", array(":one"=>"1234"));
R::exec("insert into page (number) values(:one) ", array(":one"=>"-21"));
pass();

//this module tests whether values we store are the same we get returned
//PDO is a bit unpred. with this but using STRINGIFY attr this should work we test this here

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

R::exec(" drop table if exists bandmember_musician CASCADE");
R::exec(" drop table if exists band_bandmember CASCADE");
R::exec(" drop table if exists prefix_bandmember_musician CASCADE");
R::exec(" drop table if exists prefix_band_bandmember CASCADE");

R::exec(" drop table if exists musician CASCADE ");
R::exec(" drop table if exists bandmember CASCADE");
R::exec(" drop table if exists band CASCADE");
R::exec(" drop table if exists prefix_musician CASCADE");
R::exec(" drop table if exists prefix_bandmember CASCADE");
R::exec(" drop table if exists prefix_band CASCADE");



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
$nameOfBandWithID1 = R::getCell("select name from ".$p."bandlist where ".R::$writer->getIDField("band")." = 1 
group by  ".R::$writer->getIDField("band").",\"name\"");
asrt($nameOfBandWithID1,"The Groofy");

//can we generate a report? list all bandleaders
$bandleaders = R::getAll("select  bandleader_of_bandmember,name_of_musician,\"name\" AS bandname
	from ".$p."bandlist where bandleader_of_bandmember =  1 group by id ");
	
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
name_of_musician ,count( distinct ".R::$writer->getIDField("band").") as bands 
from ".$p."bandlist group by ".R::$writer->getIDField("musician")."_of_musician,name_of_musician order by name_of_musician asc 
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





printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");


}catch(Exception $e) {
	echo "\n\n\n".$e->getMessage();
	echo "<pre>".$e->getTraceAsString();
}
