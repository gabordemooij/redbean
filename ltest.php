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

//Can we load all modules properly?
//INCLUDE YOUR REDBEAN FILE HERE!
require("rb.php");
//require("RedBean/redbean.inc.php");


//Test whether we can setup a connection
$toolbox = RedBean_Setup::kickstart( "sqlite:{$ini['sqlite']['file']}" );
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

$adapter = $toolbox->getDatabaseAdapter();
$writer  = $toolbox->getWriter();
$redbean = $toolbox->getRedBean();

$adapter->exec(' PRAGMA foreign_keys = ON ');

testpack("UNIT TEST Toolbox");
asrt(($adapter instanceof RedBean_Adapter_DBAdapter),true);
asrt(($writer instanceof RedBean_QueryWriter),true);
asrt(($redbean instanceof RedBean_OODB),true);


testpack("Test RedBean Finder Plugin*");
$page = $redbean->dispense("page");
$page->name = "more pages about less";
$redbean->store($page);
$page = $redbean->dispense("page");
$page->name = "more is worse";
$redbean->store($page);
$page = $redbean->dispense("page");
$page->name = "more is better";
$redbean->store($page);


testpack("Test OODB Finder");
asrt(count($redbean->find("page",array(), array(" name LIKE '%more%' ",array()))),3);
asrt(count($redbean->find("page",array(), array(" name LIKE :str ",array(":str"=>'%more%')))),3);
asrt(count($redbean->find("page",array(),array(" name LIKE :str ",array(":str"=>'%mxore%')))),0);


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
$bean2 = $redbean->load("anotherbean", 5);
asrt((int)$bean2->id,0);
$pdo = $adapter->getDatabase();

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
$pdo->Execute("DROP TABLE IF EXISTS testa_testb");
$pdo->Execute("DROP TABLE IF EXISTS logentry");
$pdo->Execute("DROP TABLE IF EXISTS admin");
$pdo->Execute("DROP TABLE IF EXISTS admin_logentry");
$pdo->Execute("DROP TABLE IF EXISTS genre");
$pdo->Execute("DROP TABLE IF EXISTS genre_movie");

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


//Null should == NULL after saving
$page->rating = null;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( ($page->rating === null), true );
asrt( !$page->rating, true );


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

testpack('Leading zeros');
$numAsString = "0001";
/*$page->numasstring = $numAsString;
$redbean->store($page);
$page = $redbean->load( "page", $id );
asrt($page->numasstring,"1");
$numAsString = "0001";*/
//$page->setMeta("cast.numasstring","string");
$page->numasstring = $numAsString;
$redbean->store($page);
$page = $redbean->load( "page", $id );
asrt($page->numasstring,"0001");
$page->numnotstring = "0.123";
$redbean->store($page);
$page = $redbean->load( "page", $id );
asrt($page->numnotstring,"0.123");
$page->numasstring2 = "00.123";
$redbean->store($page);
$page = $redbean->load( "page", $id );
asrt($page->numasstring2,"00.123");


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
class MyWriter extends RedBean_QueryWriter_SQLiteT {
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

//$adapter->getDatabase()->setDebugMode(1);
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
//print_r($movies);
asrt(count($movies),1);
asrt((int)$movies[0],(int)$movieid);
$a2->unassociate($movie,$genre);
$movies = $a2->related($genre, "movie");
asrt(count($movies),0);
$a2->clearRelations($movie, "movie");
$movies = $a2->related($movie1, "movie");
asrt(count($movies),0);



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


function set1toNAssoc(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		global $a;
        $type = $bean1->getMeta("type");
        $a->clearRelations($bean2, $type);
        $a->associate($bean1, $bean2);
        if (count( $a->related($bean2, $type) )===1) {
               // return $this;
        }
        else {
                throw new RedBean_Exception_SQL("Failed to enforce 1toN Relation for $type ");
        }
}



set1toNAssoc($user2, $page);
set1toNAssoc($user, $page);
asrt(count($a->related($user2, "page" )),0);
asrt(count($a->related($user, "page" )),1);
set1toNAssoc($user, $page2);
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
//try{ $a->associate($page2,$page2); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
$pageOne = $redbean->dispense("page");
$pageOne->name = "one";
$pageMore = $redbean->dispense("page");
$pageMore->name = "more";
$pageEvenMore = $redbean->dispense("page");
$pageEvenMore->name = "evenmore";
$pageOther = $redbean->dispense("page");
$pageOther->name = "othermore";
set1toNAssoc($pageOther, $pageMore);
set1toNAssoc($pageOne, $pageMore);
set1toNAssoc($pageOne, $pageEvenMore);
asrt(count($a->related($pageOne, "page")),2);
asrt(count($a->related($pageMore, "page")),1);
asrt(count($a->related($pageEvenMore, "page")),1);
asrt(count($a->related($pageOther, "page")),0);

testpack("Test OODB Finder");
asrt(count($redbean->find("page",array(), array(" name LIKE '%more%' ",array()))),3);
asrt(count($redbean->find("page",array(), array(" name LIKE :str ",array(":str"=>'%more%')))),3);
asrt(count($redbean->find("page",array(),array(" name LIKE :str ",array(":str"=>'%mxore%')))),0);


asrt(count($redbean->find("page",array("id"=>array(2,3)))),2);
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


//Section D Security Tests
$hack = $redbean->dispense("hack");
$redbean->store($hack);
testpack("Test RedBean Security - bean interface ");
asrt(in_array("hack",$writer->getTables()),true);
$bean = $redbean->load("page","13; drop table hack");
asrt(in_array("hack",$writer->getTables()),true);
try {
	$bean = $redbean->load("page where 1; drop table hack",1);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
$bean = $redbean->dispense("page");
$evil = "; drop table hack";
$bean->id = $evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
unset($bean->id);
$bean->name = "\"".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
$bean->name = "'".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
$bean->$evil = 1;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
unset($bean->$evil);
$bean->id = 1;
$bean->name = "\"".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
$bean->name = "'".$evil;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
$bean->$evil = 1;
try {
	$redbean->store($bean);
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);
try {
	$redbean->trash($bean);
}catch(Exception $e) {

}


$adapter->exec("drop table if exists sometable");
testpack("Test RedBean Security - query writer");
try {
	$writer->createTable("sometable` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , PRIMARY KEY ( `id` ) ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ; drop table hack; --");
}catch(Exception $e) {

}
asrt(in_array("hack",$writer->getTables()),true);

//print_r( $adapter->get("select id from page where id = 1; drop table hack") );
//asrt(in_array("hack",$writer->getTables()),true);
//$bean = $redbean->load("page","13);show tables; ");
//exit;

testpack("Test ANSI92 issue in clearrelations");

//$adapter->getDatabase()->setDebugMode(1);

$pdo->Execute("DROP TABLE IF EXISTS author_book");
$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS author");
$redbean = $toolbox->getRedBean();
$a = new RedBean_AssociationManager( $toolbox );
$book = $redbean->dispense("book");
$author1 = $redbean->dispense("author");
$author2 = $redbean->dispense("author");
$book->title = "My First Post";
$author1->name="Derek";
$author2->name="Whoever";
set1toNAssoc($book,$author1);
set1toNAssoc($book, $author2);
pass();
$pdo->Execute("DROP TABLE IF EXISTS author_book");
$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS author");
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

testpack("Zero issue");
$pdo->Execute("DROP TABLE IF EXISTS `zero`");
$bean = $redbean->dispense("zero");
$bean->zero = false;
$bean->title = "bla";
$redbean->store($bean);
asrt( count($redbean->find("zero",array()," zero = 0 ")), 1 );

testpack("Support for Affinity for sorting order");


$pdo->Execute("DROP TABLE IF EXISTS `project`");
$project = $redbean->dispense("project");
$project->name = "first project";
$project->sequence = "2";
$project2 = $redbean->dispense("project");
$project2->name = "second project";
$project2->sequence = "12";
$redbean->store($project);
//print_r( $adapter->get("PRAGMA table_info('project')") ); exit;
$redbean->store($project2);

$projects = $redbean->find("project",array()," 1 ORDER BY sequence ");
$firstProject = array_shift($projects);
$secondProject = array_shift($projects);
asrt((int)$firstProject->sequence,2);
asrt((int)$secondProject->sequence,12);

testpack("Type Affinity");
$pdo->Execute("DROP TABLE IF EXISTS `typo`");
$bean = $redbean->dispense("typo");
$bean->col1 = false;
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((bool)$bean->col1,false);
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"INTEGER");
$bean->col1 = true;
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((bool)$bean->col1,true);
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"INTEGER");
$bean->col1 = 12;
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((integer)$bean->col1,12);
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"INTEGER");
$bean->col1 = 20173;
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((integer)$bean->col1,20173);
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"INTEGER");

$bean->col1 = 13.23;
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((double)$bean->col1,13.23);
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"NUMERIC");

$bean->col1 = "2008-01-23";
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((string)$bean->col1,"2008-01-23");
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"NUMERIC");
$bean->col1 = "2008-01-23 10:00:12";
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((string)$bean->col1,"2008-01-23 10:00:12");
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"NUMERIC");
$bean->col1 = "aaaa";
$bean = $redbean->load("typo", $redbean->store($bean));
asrt((string)$bean->col1,"aaaa");
$columns = $writer->getColumns("typo");
asrt($columns["col1"],"TEXT");


testpack("Test Association Issue Group keyword (Issues 9 and 10)");
$pdo->Execute("DROP TABLE IF EXISTS `book_group`");
$pdo->Execute("DROP TABLE IF EXISTS `group`");
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
	echo $e->getSQLState();
	fail();
}
asrt((int)$adapter->getCell("select count(*) from book_group"),1); //just 1 rec!

$pdo->Execute("DROP TABLE IF EXISTS author_book");
$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS author");
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


testpack("Test Table Prefixes");
R::setup("sqlite:{$ini['sqlite']['file']}");

class MyTableFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {
		return "xx_$table";
	}
	public function formatBeanID( $table ) {
		return "id";
	}
	public function getAlias($a){ return '__';}
}
//R::debug(1);
R::$writer->setBeanFormatter(  new MyTableFormatter );
$pdo = R::$adapter->getDatabase();
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
class TestFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xx_$table";}
	public function formatBeanID( $table ) {return "id";}
	public function getAlias($a){ return $a; }
}

$pdo->Execute("DROP TABLE IF EXISTS cms_blog");
class MyBeanFormatter implements RedBean_IBeanFormatter{
    public function formatBeanTable($table) {
        return "cms_$table";
    }
    public function formatBeanID( $table ) {
        return "{$table}_id"; // append table name to id. The table should not inclide the prefix.
    }
    public function getAlias($a){ return '__';}
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
$pdo->Execute("DROP TABLE IF EXISTS person");
$pdo->Execute("DROP TABLE IF EXISTS person_person");
R::$writer->tableFormatter = new RedBean_DefaultBeanFormatter();
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
$students = R::related($t, 'person', ' role = ?  ORDER BY `name` ',array("student"));
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
asrt((is_string($bean->prop) || is_null($bean->prop)),true);
return $bean->prop;
}

//this module tests whether values we store are the same we get returned
//PDO is a bit unpred. with this but using STRINGIFY attr this should work we test this here
testpack("pdo and types");

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
	)
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
	public function getAlias($a){ return '__';}
}


class Fm2 implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "prefix_$table";}
	public function formatBeanID( $table ) {return $table."_id";}
	public function getAlias($a){ return '__';}
}

function testViews($p) {

R::exec(" drop table if exists prefix_bandmember_musician ");
R::exec(" drop table if exists prefix_band_bandmember ");
R::exec(" drop table if exists bandmember_musician ");
R::exec(" drop table if exists band_bandmember ");
R::exec(" drop table if exists musician ");
R::exec(" drop table if exists bandmember ");
R::exec(" drop table if exists band ");
R::exec(" drop table if exists prefix_musician ");
R::exec(" drop table if exists prefix_bandmember ");
R::exec(" drop table if exists prefix_band ");




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
$nameOfBandWithID1 = R::getCell("select name from ".$p."bandlist where ".R::$writer->getIDField("band")." = 1 group by  ".R::$writer->getIDField("band"));
asrt($nameOfBandWithID1,"The Groofy");

//can we generate a report? list all bandleaders
$bandleaders = R::getAll("select bandleader_of_bandmember,name_of_musician,name AS bandname
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
from ".$p."bandlist group by ".R::$writer->getIDField("musician")."_of_musician  order by name_of_musician asc
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



testpack("wipe and constraints");

R::exec(" drop table if exists page ");
R::exec(" drop table if exists book ");
R::exec(" drop table if exists book_page ");
R::exec(" drop table if exists prefix_page ");
R::exec(" drop table if exists prefix_book ");
R::exec(" drop table if exists prefix_book_page ");



R::exec("DROP TRIGGER IF EXISTS fkc2d4c7ea9e656a361bc08c9d072914cca ");
R::exec("DROP TRIGGER IF EXISTS fkc2d4c7ea9e656a361bc08c9d072914ccb ");

$page1 = R::dispense("page");
$book1 = R::dispense("book");
$page2 = R::dispense("page");
$book2 = R::dispense("book");
$page1->name = "page1";
$page2->name = "page2";
$book1->name = "book1";
$book2->name = "book2";

R::associate($book1,$page1);
R::associate($book2,$page2);
//exit;
asrt(count( R::getAll("select * from prefix_book_page")),2);
R::trash($book1);
asrt(count( R::getAll("select * from prefix_book_page")),1);
R::wipe("book");
asrt(count(R::getAll("select * from prefix_book_page")),0);




printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");
