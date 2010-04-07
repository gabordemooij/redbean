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

//require("RedBean/redbean.inc.php");
require("rb.pack.php");
$toolbox = RedBean_Setup::kickstartDev( "pgsql:host=localhost dbname=oodb","postgres","maxpass" );

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


try{

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
$page = $redbean->dispense("page");
asrt(((bool)$page->getMeta("type")),true);
asrt(isset($page->id),true);
asrt(($page->getMeta("type")),"page");
asrt(($page->id),0);
try{ $redbean->dispense(""); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->dispense("."); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->dispense("-"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }


testpack("UNIT TEST RedBean OODB: Check");
$bean = $redbean->dispense("page");
$bean->name = array("1");
try{ $redbean->store($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->check($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
$bean->name = new RedBean_OODBBean;
try{ $redbean->store($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->check($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
$prop = ".";
$bean->$prop = 1;
try{ $redbean->store($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->check($bean); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
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
$_tables = $writer->getTables();
if (!in_array("hack",$_tables)) $pdo->Execute("CREATE TABLE hack (id serial, PRIMARY KEY (id) ); ");
if (in_array("page",$_tables)) $pdo->Execute("DROP TABLE page");
if (in_array("user",$_tables)) $pdo->Execute("DROP TABLE \"user\"");
if (in_array("book",$_tables)) $pdo->Execute("DROP TABLE book");
if (in_array("author",$_tables)) $pdo->Execute("DROP TABLE author");
if (in_array("one",$_tables)) $pdo->Execute("DROP TABLE one");
if (in_array("post",$_tables)) $pdo->Execute("DROP TABLE post");
if (in_array("page_user",$_tables)) $pdo->Execute("DROP TABLE page_user");
if (in_array("page_page",$_tables)) $pdo->Execute("DROP TABLE page_page");
if (in_array("association",$_tables)) $pdo->Execute("DROP TABLE association");
if (in_array("logentry",$_tables)) $pdo->Execute("DROP TABLE logentry");
if (in_array("admin",$_tables)) $pdo->Execute("DROP TABLE admin");
if (in_array("wine",$_tables)) $pdo->Execute("DROP TABLE wine");
if (in_array("admin_logentry",$_tables)) $pdo->Execute("DROP TABLE admin_logentry");
$page = $redbean->dispense("page");

testpack("UNIT TEST Database");
try{ $adapter->exec("an invalid query"); fail(); }catch(RedBean_Exception_SQL $e ){ pass(); }
asrt( (int) $adapter->getCell("SELECT 123") ,123);


//Section C: Integration Tests / Regression Tests

testpack("Test RedBean OODB: Insert Record");
$page->name = "my page";
$id = (int) $redbean->store($page);
asrt( $page->id, 1 );
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
try{ $a->associate($page2,$page2); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
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
$adapter->startTransaction(); pass();
$adapter->rollback(); pass();
$adapter->startTransaction(); pass();
$adapter->commit(); pass();

testpack("Test Frozen ");
$redbean->freeze( true );
$page = $redbean->dispense("page");
$page->sections = 10;
$page->name = "half a page";
try{$id = $redbean->store($page); fail();}catch(RedBean_Exception_SQL $e){ pass(); }
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
asrt(count(Finder::where("page", " name LIKE '%more%' ")),3);
asrt(count(Finder::where("page", " name LIKE :str ",array(":str"=>'%more%'))),3);
asrt(count(Finder::where("page", " name LIKE :str ",array(":str"=>'%mxore%'))),0);
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
asrt(count(Finder::where("wine"," id > 0 ")),1);
asrt(count(Finder::where("wine"," @id < 100 ")),1);
asrt(count(Finder::where("wine"," @id > 100 ")),0);
asrt(count(Finder::where("wine"," @id < 100 OR TRUE ")),1);
asrt(count(Finder::where("wine"," @id > 100 OR TRUE ")),1);
asrt(count(Finder::where("wine",
		" TRUE OR @grape = 'merlot' ")),1); //non-existant column
asrt(count(Finder::where("wine",
		" TRUE OR @wine.grape = 'merlot' ")),1); //non-existant column
asrt(count(Finder::where("wine",
		" TRUE OR @cork=1 OR @grape = 'merlot' ")),1); //2 non-existant column
asrt(count(Finder::where("wine",
		" TRUE OR @cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
asrt(count(Finder::where("wine",
		" TRUE OR @bottle.cork=1 OR @wine.grape = 'merlot' ")),1); //2 non-existant column
RedBean_Setup::getToolbox()->getRedBean()->freeze( TRUE );
asrt(count(Finder::where("wine"," TRUE OR TRUE ")),1);
try{Finder::where("wine"," TRUE OR @grape = 'merlot' "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," TRUE OR @wine.grape = 'merlot' "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," TRUE OR @cork=1 OR @wine.grape = 'merlot'  "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," TRUE OR @bottle.cork=1 OR @wine.grape = 'merlot'  "); fail(); }
catch(RedBean_Exception_SQL $e){ pass(); }
try{Finder::where("wine"," TRUE OR @a=1",array(),true); pass(); }
catch(RedBean_Exception_SQL $e){ fail(); }
RedBean_Setup::getToolbox()->getRedBean()->freeze( FALSE );
asrt(Finder::parseGoldSQL(" @name ","wine",RedBean_Setup::getToolbox())," name ");
asrt(Finder::parseGoldSQL(" @name @id ","wine",RedBean_Setup::getToolbox())," name id ");
asrt(Finder::parseGoldSQL(" @name @id @wine.id ","wine",RedBean_Setup::getToolbox())," name id wine.id ");
asrt(Finder::parseGoldSQL(" @name @id @wine.id @bla ","wine",RedBean_Setup::getToolbox())," name id wine.id NULL ");
asrt(Finder::parseGoldSQL(" @name @id @wine.id @bla @xxx ","wine",RedBean_Setup::getToolbox())," name id wine.id NULL NULL ");
asrt(Finder::parseGoldSQL(" @bla @xxx ","wine",RedBean_Setup::getToolbox())," NULL NULL ");









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
try { $adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a "); }catch(Exception $e){}
try { $adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b "); }catch(Exception $e){}
try { $adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a "); }catch(Exception $e){}
try { $adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b "); }catch(Exception $e){}


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
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),false);


asrt(count($a->related($cask, "whisky")),1);

$redbean->trash($cask);
asrt(count($a->related($cask, "whisky")),0); //should be gone now!

asrt(count($a->related($whisky2, "cask")),1);
$redbean->trash($whisky2);
asrt(count($a->related($whisky2, "cask")),0); //should be gone now!

try { $adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a "); }catch(Exception $e){}
try { $adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b "); }catch(Exception $e){}

try { $adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b "); }catch(Exception $e){}
try { $adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a "); }catch(Exception $e){}
try { $adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a "); }catch(Exception $e){}
try { $adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b "); }catch(Exception $e){}
$adapter->exec("DROP TABLE IF EXISTS cask_whisky");
try{ $adapter->exec("DROP TABLE IF EXISTS cask CASCADE "); }catch(Exception $e){ die($e->getMessage()); }
$adapter->exec("DROP TABLE IF EXISTS whisky CASCADE ");

//add cask 101 and whisky 12
$cask = $redbean->dispense("cask");
$cask->number = 201;
$cask2 = $redbean->dispense("cask");
$cask2->number = 202;
$a->associate($cask,$cask2);
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),true);
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false);
//now from cache... no way to check if this works :(
asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false);
asrt(count($a->related($cask, "cask")),1);
$redbean->trash( $cask2 );
asrt(count($a->related($cask, "cask")),0);






printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");


}catch(Exception $e) {
  echo "<pre>".$e->getTraceAsString();
}