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


$toolbox = RedBean_Setup::kickstartDev( "mysql:host=localhost;dbname=oodb","root","" );

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
testpack("UNIT TEST RedBean CompatManager: ScanDirect");
RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"1"));
pass();
try{RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"9999"));fail();}catch(RedBean_Exception_UnsupportedDatabase $e){pass();}
try{RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_FOXPRO=>"1"));fail();}catch(RedBean_Exception_UnsupportedDatabase $e){pass();}
RedBean_CompatManager::ignore(TRUE);
try{RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"9999"));pass();}catch(RedBean_Exception_UnsupportedDatabase $e){fail();}
try{RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_FOXPRO=>"1"));pass();}catch(RedBean_Exception_UnsupportedDatabase $e){fail();}
RedBean_CompatManager::ignore(FALSE);
try{RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_MYSQL=>"9999"));fail();}catch(RedBean_Exception_UnsupportedDatabase $e){pass();}
try{RedBean_CompatManager::scanDirect($toolbox,array(RedBean_CompatManager::C_SYSTEM_FOXPRO=>"1"));fail();}catch(RedBean_Exception_UnsupportedDatabase $e){pass();}


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

testpack("UNIT TEST RedBean OODB: setObject");
$wine = $redbean->dispense("wine");
$wine->id = 123;
$cask = $redbean->dispense("cask");
$cask->setBean( $wine );
asrt($cask->wine_id,123);
$wine->id = 124;
$cask->setBean( $wine );
asrt($cask->wine_id,124);
asrt($cask->getKey("wine"),124);
$wine = $redbean->dispense("wine");
$cask = $redbean->dispense("cask");
$wine->title = "my wine";
$cask->title = "my cask";
$redbean->store( $wine );
$cask->setBean( $wine );
$redbean->store( $cask );
asrt($cask->getKey("wine"), $wine->id);
asrt(($wine->id>0),true);
$wine = $cask->getBean("wine");
asrt(($wine instanceof RedBean_OODBBean), true);
asrt($wine->title,"my wine");
$pdo->Execute("DROP TABLE IF EXISTS cask");
$pdo->Execute("DROP TABLE IF EXISTS wine");
pass();

$page = $redbean->dispense("page");

testpack("UNIT TEST Database");
try{ $adapter->exec("an invalid query"); fail(); }catch(RedBean_Exception_SQL $e ){ pass(); }
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
try{ $redbean->store( $page ); pass(); }catch(Exception $e){ fail(); }
try{ $redbean->store( $otherpage ); fail(); }catch(Exception $e){ pass(); }
asrt(count($logger->testingOnly_getStash()),0); // Stash empty?

testpack("Test Association ");
$rb = $redbean;
$testA = $rb->dispense( 'testA' ); 
$testB = $rb->dispense( 'testB' ); 
$a = new RedBean_AssociationManager( $toolbox ); 
try{
$a->related( $testA, "testB" );
pass();
}catch(Exception $e){fail();}

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

testpack("Test Locking with Assoc");
$page = $redbean->dispense("page");
$user = $redbean->dispense("page");
$id = $redbean->store($page);
$pageII = $redbean->load("page", $id);
$redbean->store($page);
try{ $redbean->store($pageII); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); }
try{ $a->associate($pageII,$user); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); }
try{ $a->unassociate($pageII,$user); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); }
try{ $a->clearRelations($pageII, "user"); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); }
try{ $redbean->store($page); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
try{ $a->associate($page,$user); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
try{ $a->unassociate($page,$user); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
try{ $a->clearRelations($page, "user"); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
$pageII = $redbean->load("page",$pageII->id); //reload will help
try{ $redbean->store($pageII); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
try{ $a->associate($pageII,$user); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
try{ $a->unassociate($pageII,$user); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
try{ $a->clearRelations($pageII, "user"); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }

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

$stat = new RedBean_SimpleStat($toolbox);
testpack("Test RedBean Finder Plugin*");
asrt(count(Finder::where("page", " name LIKE '%more%' ")),3);
asrt(count(Finder::where("page", " name LIKE :str ",array(":str"=>'%more%'))),3);
asrt(count(Finder::where("page", " name LIKE :str ",array(":str"=>'%mxore%'))),0);
asrt(count(Finder::where("page")),$stat->numberOf($redbean->dispense("page")));



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
Finder::where("wine", "id=5"); //  Finder:where call RedBean_OODB::convertToBeans
$bean2 = $redbean->load("anotherbean", 5);
asrt($bean2->id,0);


testpack("Test Gold SQL");
asrt(count(Finder::where("wine"," 1 OR 1 ")),1);
asrt(count(Finder::where("wine"," @id < 100 ")),1);
asrt(count(Finder::where("wine"," @id > 100 ")),0);
asrt(count(Finder::where("wine"," @id < 100 OR 1 ")),1);
asrt(count(Finder::where("wine"," @id > 100 OR 1 ")),1);
asrt(count(Finder::where("wine",
		" 1 OR @grape = 'merlot' ")),1); //non-existant column
asrt(count(Finder::where("wine",
		" 1 OR @wine.grape = 'merlot' ")),1); //non-existant column
asrt(count(Finder::where("wine",
		" 1 OR @cork=1 OR @grape = 'merlot' ")),1); //2 non-existant column
asrt(count(Finder::where("wine",
		" 1 OR @cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
asrt(count(Finder::where("wine",
		" 1 OR @bottle.cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
RedBean_Setup::getToolbox()->getRedBean()->freeze( TRUE );
asrt(count(Finder::where("wine"," 1 OR 1 ")),1);
try{Finder::where("wine"," 1 OR @grape = 'merlot' "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," 1 OR @wine.grape = 'merlot' "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," 1 OR @cork=1 OR @wine.grape = 'merlot'  "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," 1 OR @bottle.cork=1 OR @wine.grape = 'merlot'  "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," 1 OR @a=1",array(),true); pass(); }
catch(RedBean_Exception_SQL $e){ fail(); }
RedBean_Setup::getToolbox()->getRedBean()->freeze( FALSE );
asrt(Finder::parseGoldSQL(" @name ","wine",RedBean_Setup::getToolbox())," name ");
asrt(Finder::parseGoldSQL(" @name @id ","wine",RedBean_Setup::getToolbox())," name id ");
asrt(Finder::parseGoldSQL(" @name @id @wine.id ","wine",RedBean_Setup::getToolbox())," name id wine.id ");
asrt(Finder::parseGoldSQL(" @name @id @wine.id @bla ","wine",RedBean_Setup::getToolbox())," name id wine.id NULL ");
asrt(Finder::parseGoldSQL(" @name @id @wine.id @bla @xxx ","wine",RedBean_Setup::getToolbox())," name id wine.id NULL NULL ");
asrt(Finder::parseGoldSQL(" @bla @xxx ","wine",RedBean_Setup::getToolbox())," NULL NULL ");


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

testpack("Transactions");
$adapter->startTransaction(); pass();
$adapter->rollback(); pass();
$adapter->startTransaction(); pass();
$adapter->commit(); pass();




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
$redbean->store($one);$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"tinyint(3) unsigned");

$one->col = str_repeat('a long text',100);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = 9000;
$redbean->store($one);$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"int(11) unsigned");

$one->col = str_repeat('a long text',100);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = 1.23;
$redbean->store($one);$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"double");
$one->col = str_repeat('a long text',100);
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = "short text";
$redbean->store($one);$redbean->store($one);
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
try{ $redbean->store($newpage); fail(); }catch(Exception $e){ pass(); }
$newpage = $redbean->dispense("page");
$newpage->id  = $id;
$newpage->name = "new one";
$newpage->setMeta("opened",$page->getMeta("opened"));
try{ $redbean->store($newpage); pass(); }catch(Exception $e){ fail(); }



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
foreach($writer->sqltype_typeno as $key=>$type){asrt($writer->code($key),$type);}
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
$cols=$writer->getColumns("testtable");asrt($writer->code($cols["c1"]),2);
$writer->widenColumn("testtable", "c1", 3);
$cols=$writer->getColumns("testtable");asrt($writer->code($cols["c1"]),3);
$writer->widenColumn("testtable", "c1", 4);
$cols=$writer->getColumns("testtable");asrt($writer->code($cols["c1"]),4);
$writer->widenColumn("testtable", "c1", 5);
$cols=$writer->getColumns("testtable");asrt($writer->code($cols["c1"]),5);
$id = $writer->insertRecord("testtable", array("c1"), array(array("lorem ipsum")));
$row = $writer->selectRecord("testtable", array($id));
asrt($row[0]["c1"],"lorem ipsum");
$writer->updateRecord("testtable", array(array("property"=>"c1","value"=>"ipsum lorem")), $id);
$row = $writer->selectRecord("testtable", array($id));
asrt($row[0]["c1"],"ipsum lorem");
$writer->deleteRecord("testtable", $id);
$row = $writer->selectRecord("testtable", array($id));
asrt($row,NULL);
//$pdo->setDebugMode(1);
$writer->addColumn("testtable", "c2", 2);
try{ $writer->addUniqueIndex("testtable", array("c1","c2")); fail(); //should fail, no content length blob
}catch(RedBean_Exception_SQL $e){ pass(); }
$writer->addColumn("testtable", "c3", 2);
try{ $writer->addUniqueIndex("testtable", array("c2","c3")); pass(); //should fail, no content length blob
}catch(RedBean_Exception_SQL $e){ fail(); }
$a = $adapter->get("show index from testtable");
asrt(count($a),3);
asrt($a[1]["Key_name"],"UQ_64b283449b9c396053fe1724b4c685a80fd1a54d");
asrt($a[2]["Key_name"],"UQ_64b283449b9c396053fe1724b4c685a80fd1a54d");

testpack("TEST SimpleStat ");
$stat = new RedBean_SimpleStat( $toolbox );
asrt( $stat->numberOf($page), 25);


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



//Section D Security Tests
testpack("Test RedBean Security - bean interface ");
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean = $redbean->load("page","13; drop table hack");
asrt(in_array("hack",$adapter->getCol("show tables")),true);
try{ $bean = $redbean->load("page where 1; drop table hack",1); }catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean = $redbean->dispense("page");
$evil = "; drop table hack";
$bean->id = $evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
unset($bean->id);
$bean->name = "\"".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->name = "'".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->$evil = 1;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
unset($bean->$evil);
$bean->id = 1;
$bean->name = "\"".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->name = "'".$evil;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
$bean->$evil = 1;
try{$redbean->store($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
try{$redbean->trash($bean);}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);
try{Finder::where("::");}catch(Exception $e){pass();}



$adapter->exec("drop table if exists sometable");
testpack("Test RedBean Security - query writer");
try{$writer->createTable("sometable` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , PRIMARY KEY ( `id` ) ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ; drop table hack; --");}catch(Exception $e){}
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
$uow->addWork("a", function(){ global $count; $count[]="a"; });
$uow->addWork("b", function(){ global $count; $count[]="b"; });
$uow->doWork("a");
$uow->doWork("a");
$uow->doWork("b");
$cnt = array_count_values($count);
asrt($cnt["a"],2);
asrt($cnt["b"],1);
$book = $redbean->dispense("book");
$book->title = "unit of work book";
$uow->addWork("save", function() use($redbean, $book){ $redbean->store($book); });
$uow->addWork("all_save",function() use($uow){ $uow->doWork("save"); });
$uow->doWork("all_save");
asrt(count( Finder::where("book","title LIKE '%unit%'") ),1);

testpack("Facade");
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
$book->setBean($author);
asrt($book->author_id, $author->id);
asrt(($book->author_id>0), TRUE);
asrt($book->getBean("author")->name,"me");
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
asrt(count(R::children($book)),2);
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
class Model_Cigar extends RedBean_SimpleModel {
    public static $reachedDeleted = true;
    public function update() {
        $this->rating++;
    }
    public function delete() {
        self::$reachedDeleted =true;
    }
    public function open() {
        $this->rating++;
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

printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");
