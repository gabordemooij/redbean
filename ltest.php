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

//Can we load all modules properly?
//require("RedBean/redbean.inc.php");
require("rb.pack.php");
if (interface_exists("RedBean_ObjectDatabase")) pass(); else fail();

//Test whether a non mysql DSN throws an exception
try{RedBean_Setup::kickstart("blackhole:host=localhost;dbname=oodb","root",""); fail();}catch(RedBean_Exception_NotImplemented $e){ pass(); }

//Test whether we can setup a connection
$toolbox = RedBean_Setup::kickstartDevL( "sqlite:/Applications/XAMPP/xamppfiles/temp/base.txt" );
//prepare... empty the database
foreach( $toolbox->getWriter()->getTables() as $table ) {
	$sql = "DROP TABLE `".$table."`";
	$toolbox->getDatabaseAdapter()->exec($sql);
}
//check whether we emptied the database correctly...
asrt(count($toolbox->getWriter()->getTables()),0);

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


$nullWriter = new RedBean_QueryWriter_NullWriter();
$redbean = new RedBean_OODB( $nullWriter );

//Section A: Config Testing
testpack("CONFIG TEST");
//Can we access the required exceptions?
asrt(class_exists("RedBean_Exception_FailedAccessBean"),true);
asrt(class_exists("RedBean_Exception_Security"),true);
asrt(class_exists("RedBean_Exception_SQL"),true);

//Section B: UNIT TESTING
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
try{ $redbean->dispense(""); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->dispense("."); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->dispense("-"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }

//Test the Check() function (also indirectly using store())
testpack("UNIT TEST RedBean OODB: Check");
$bean = $redbean->dispense("page");
//Set some illegal values in the bean; this should trugger Security exceptions.
//Arrays are not allowed.
$bean->name = array("1");
try{ $redbean->store($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->check($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
//Objects should not be allowed.
$bean->name = new RedBean_OODBBean;
try{ $redbean->store($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->check($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
//Property names should be alphanumeric
$prop = ".";
$bean->$prop = 1;
try{ $redbean->store($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->check($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
//Really...
$prop = "-";
$bean->$prop = 1;
try{ $redbean->store($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->check($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }


testpack("UNIT TEST RedBean OODB: Load");
$bean = $redbean->load("typetest",2);
$nullWriter->returnSelectRecord = array();
asrt($nullWriter->selectRecordArguments[0],"typetest");
asrt($nullWriter->selectRecordArguments[1],array(2));
asrt($bean->id,0);
$nullWriter->returnSelectRecord = array(array("name"=>"abc","id"=>3));
$bean = $redbean->load("typetest",3);
asrt($nullWriter->selectRecordArguments[0],"typetest");
asrt($nullWriter->selectRecordArguments[1],array(3));
asrt($bean->id,3);
try { $bean = $redbean->load("typetest",-2); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try { $bean = $redbean->load("typetest",0); pass(); }catch(RedBean_Exception_Security $e){ fail(); }
try { $bean = $redbean->load("typetest",2.1); pass(); }catch(RedBean_Exception_Security $e){ fail(); }
try { $bean = $redbean->load(" ",3); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try { $bean = $redbean->load(".",3); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try { $bean = $redbean->load("type.test",3); fail(); }catch(RedBean_Exception_Security $e){ pass(); }


testpack("Finder");
Finder::where("whatever");


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
$nullWriter->returnScanType = 91239;
$nullWriter->returnInsertRecord = 1234;
asrt($redbean->store($bean),1234);
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
asrt($nullWriter->getColumnsArgument,"bean");
asrt($nullWriter->createTableArgument,NULL);
asrt($nullWriter->scanTypeArgument,NULL);
asrt($nullWriter->codeArgument,NULL);
asrt($nullWriter->addColumnArguments,array());
asrt($nullWriter->insertRecordArguments,array("bean",array("name"),array(array("coffee"))));
asrt($nullWriter->addUniqueIndexArguments,array());
asrt($nullWriter->updateRecordArguments,array());
asrt($nullWriter->widenColumnArguments,array());
$redbean->freeze(false);


testpack("UNIT TEST RedBean OODBBean: Meta Information");
$bean = new RedBean_OODBBean;
$bean->setMeta( "this.is.a.custom.metaproperty" , "yes" );
asrt($bean->getMeta("this.is.a.custom.metaproperty"),"yes");
$bean->setMeta( "test", array( "one" => 123 ));
asrt($bean->getMeta("test.one"),123);
$bean->setMeta( "arr", array(1,2) );
asrt(is_array($bean->getMeta("arr")),true);
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



//Test constraints: cascaded delete
testpack("Test Cascaded Delete");
$adapter = $toolbox->getDatabaseAdapter();
//$adapter->getDatabase()->setDebugMode(1);
$adapter->exec("DROP TRIGGER IF EXISTS fkb8317025deb6e03fc05abaabc748a503a ");
$adapter->exec("DROP TRIGGER IF EXISTS fkb8317025deb6e03fc05abaabc748a503b ");

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
$adapter->exec("DROP TABLE cask_whisky"); //clean up for real test!

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
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),true);
//no error for duplicate
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),true);


asrt(count($a->related($cask, "whisky")),1);
 
$redbean->trash($cask); 
asrt(count($a->related($cask, "whisky")),0); //should be gone now!

asrt(count($a->related($whisky2, "cask")),1);
$redbean->trash($whisky2);
asrt(count($a->related($whisky2, "cask")),0); //should be gone now!

$adapter->exec("DROP TABLE IF EXISTS cask_whisky");
$adapter->exec("DROP TABLE IF EXISTS cask");
$adapter->exec("DROP TABLE IF EXISTS whisky");

//add cask 101 and whisky 12
$cask = $redbean->dispense("cask");
$cask->number = 201;
$cask2 = $redbean->dispense("cask");
$cask2->number = 202;
$a->associate($cask,$cask2);
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),true);
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),true);
//now from cache... no way to check if this works :(
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),true);
asrt(count($a->related($cask, "cask")),1);
$redbean->trash( $cask2 );
asrt(count($a->related($cask, "cask")),0);





$pdo = $adapter->getDatabase();
//$pdo->setDebugMode(1);
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
$pdo->Execute("DROP TABLE IF EXISTS association");
$pdo->Execute("DROP TABLE IF EXISTS logentry");
$pdo->Execute("DROP TABLE IF EXISTS admin");
$pdo->Execute("DROP TABLE IF EXISTS admin_logentry");
$pdo->Execute("DROP TABLE IF EXISTS genre");
$pdo->Execute("DROP TABLE IF EXISTS genre_movie");

$page = $redbean->dispense("page");

testpack("UNIT TEST Database");
try{ $adapter->exec("an invalid query"); fail(); }catch(RedBean_Exception_SQL $e ){ pass(); }
asrt( (int) $adapter->getCell("SELECT 123") ,123);
asrt( (int) $adapter->getCell("SELECT ?",array("987")) ,987);
asrt( (int) $adapter->getCell("SELECT ?+?",array("987","2")) ,989);
asrt( (int) $adapter->getCell("SELECT :numberOne+:numberTwo",array(
			":numberOne"=>42,":numberTwo"=>50)) ,92);


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



testpack("Test RedBean OODB: Can we Update a Record? ");
$page->name = "new name";
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );


$page->rating = "1";
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "1" );



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


testpack("Test Custom ID Field");
class MyWriter extends RedBean_QueryWriter_SQLite {
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


testpack("Test Association ");
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
try{ $a->associate($page2,$page2); pass(); }catch(RedBean_Exception_SQL $e){ fail(); }
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


$stat = new RedBean_SimpleStat($toolbox);
testpack("Test RedBean Finder Plugin*");
asrt(count(Finder::where("page", " name LIKE '%more%' ")),3);
asrt(count(Finder::where("page", " name LIKE :str ",array(":str"=>'%more%'))),3);
asrt(count(Finder::where("page", " name LIKE :str ",array(":str"=>'%mxore%'))),0);
asrt(count(Finder::where("page")),$stat->numberOf($redbean->dispense("page")));

testpack("Test Developer Interface API");
$post = $redbean->dispense("post");
$post->title = "My First Post";
$post->created = time();
$id = $redbean->store( $post );
$post = $redbean->load("post",$id);
$redbean->trash( $post );
pass();



testpack("Test Frozen ");
$redbean->freeze( true );
$page = $redbean->dispense("page");
$page->sections = 10;
$page->name = "half a page";
try{$id = $redbean->store($page); fail();}catch(RedBean_Exception_SQL $e){ pass(); }
$post = $redbean->dispense("post");
$post->title = "existing table";
try{$id = $redbean->store($post); pass();}catch(RedBean_Exception_SQL $e){ fail(); }
asrt(in_array("name",array_keys($writer->getColumns("page"))),true);
asrt(in_array("sections",array_keys($writer->getColumns("page"))),false);
$newtype = $redbean->dispense("newtype");
$newtype->property=1;
try{$id = $redbean->store($newtype); fail();}catch(RedBean_Exception_SQL $e){ pass(); }
$redbean->freeze( false );



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
$a->unassociate($user,$page); pass(); //no error
$a->unassociate($page,$user); pass(); //no error
$a->clearRelations($page, "user"); pass(); //no error
$a->clearRelations($user, "page"); pass(); //no error
$a->associate($user,$page); pass();
asrt(count($a->related( $user, "page")),1);
asrt(count($a->related( $page, "user")),1);
$a->clearRelations($user, "page"); pass(); //no error
asrt(count($a->related( $user, "page")),0);
asrt(count($a->related( $page, "user")),0);
$page = $redbean->load("page",$id); pass();
asrt($page->name,"test page");

testpack("Test: Trees ");
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


testpack("TEST SimpleStat ");
$stat = new RedBean_SimpleStat( $toolbox );
asrt( $stat->numberOf($page), 16);

//Section D Security Tests
$hack = $redbean->dispense("hack");
$redbean->store($hack);
testpack("Test RedBean Security - bean interface ");
asrt(in_array("hack",$writer->getTables()),true);
$bean = $redbean->load("page","13; drop table hack");
asrt(in_array("hack",$writer->getTables()),true);
try{ $bean = $redbean->load("page where 1; drop table hack",1); }catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
$bean = $redbean->dispense("page");
$evil = "; drop table hack";
$bean->id = $evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
unset($bean->id);
$bean->name = "\"".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
$bean->name = "'".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
$bean->$evil = 1;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
unset($bean->$evil);
$bean->id = 1;
$bean->name = "\"".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
$bean->name = "'".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
$bean->$evil = 1;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
try{$redbean->trash($bean);}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);
try{Finder::where("::");}catch(Exception $e){pass();}



$adapter->exec("drop table if exists sometable");
testpack("Test RedBean Security - query writer");
try{$writer->createTable("sometable` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , PRIMARY KEY ( `id` ) ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ; drop table hack; --");}catch(Exception $e){}
asrt(in_array("hack",$writer->getTables()),true);

//print_r( $adapter->get("select id from page where id = 1; drop table hack") );
//asrt(in_array("hack",$writer->getTables()),true);
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
try{ $a->associate($group,$book); pass(); }catch(RedBean_Exception_SQL $e){ fail(); }
//test issue SQL error 23000
try { $a->associate($group,$book); pass(); }catch(RedBean_Exception_SQL $e){ fail(); }
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
printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");
