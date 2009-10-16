<?php 

//Some unittests for RedBean
//Written by G.J.G.T. de Mooij

//I used this file to develop and tweak Redbean
//Redbean has been developed TEST DRIVEN (TDD)

//This file is mostly for me to test RedBean so it might be a bit chaotic because
//I often need to adjust and change this file, however I am now trying to tidy up this file so 
//you can use it as well. Also, test descriptions will be added over time.




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

//Use this database for tests
require("RedBean/Driver.php");
require("RedBean/Driver/PDO.php");

require("RedBean/OODBBean.php");
require("RedBean/Observable.php");
require("RedBean/Observer.php");
require("RedBean/DBAdapter.php");

require("RedBean/QueryWriter.php");
require("RedBean/QueryWriter/MySQL.php");
require("RedBean/ChangeLogger.php");
require("RedBean/Exception.php");
require("RedBean/Exception/Security.php");
require("RedBean/Exception/FailedAccessBean.php");
require("RedBean/OODB.php");
require("RedBean/ToolBox.php");
require("RedBean/Association.php");

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


$pdo = new Redbean_Driver_PDO( "mysql:host=localhost;dbname=oodb","root","" );
$pdo->setDebugMode(0);
$pdo->Execute("DROP TABLE IF EXISTS page");
$pdo->Execute("DROP TABLE IF EXISTS user");
$pdo->Execute("DROP TABLE IF EXISTS page_user");
$pdo->Execute("DROP TABLE IF EXISTS association");
$adapter = new RedBean_DBAdapter( $pdo );
$writer = new RedBean_QueryWriter_MySQL( $adapter );
$redbean = new RedBean_OODB( $writer );
//add concurrency shield
$redbean->addEventListener( "open", new RedBean_ChangeLogger( new RedBean_QueryWriter_MySQL( $adapter ) ));
$redbean->addEventListener( "update", new RedBean_ChangeLogger( new RedBean_QueryWriter_MySQL( $adapter ) ));
$page = $redbean->dispense("page");



testpack("Test RedBean OODB: Dispense");
asrt(isset($page->__info),true);
asrt(isset($page->__info["type"]),true);
asrt(isset($page->id),true);
asrt(($page->__info["type"]),"page");
asrt(($page->id),0);
try{ $redbean->dispense("."); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->dispense("-"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }

testpack("Test RedBean OODB: Insert Record");
$page->name = "my page";
$id = (int) $redbean->store($page);
asrt( (int) $pdo->GetCell("SELECT count(*) FROM page"), 1 );
asrt( $pdo->GetCell("SELECT `name` FROM page LIMIT 1"), "my page" );
asrt( $id, 1 );
testpack("Test RedBean OODB: Can we Retrieve a Record? ");
$page = $redbean->load( "page", 1 );
asrt($page->name, "my page");
asrt(isset($page->__info),true);
asrt(isset($page->__info["type"]),true);
asrt(isset($page->id),true);
asrt(($page->__info["type"]),"page");
asrt((int)$page->id,$id);


testpack("Test RedBean OODB: Can we Update a Record? ");
$page->name = "new name";
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
$page->rating = 5;
//$page->__info["unique"] = array("name","rating");
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "5" );

$page->rating = 300;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "300" );

$page->rating = -2;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "-2" );

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
asrt($batch[$id1]->__info["type"],"page");
asrt($batch[$id2]->__info["type"],"page");
asrt((int)$batch[$id1]->id,$id1);
asrt((int)$batch[$id2]->id,$id2);


//test locking
testpack("Test Locking");
$page = $redbean->dispense("page");
$page->name = "a page";
$id = $redbean->store( $page );
$page = $redbean->load("page", $id);
$otherpage = $redbean->load("page", $id);
asrt(isset($page->__info["opened"]),true);
asrt(isset($otherpage->__info["opened"]),true);
try{ $redbean->store( $page ); pass(); }catch(Exception $e){ fail(); }
try{ $redbean->store( $otherpage ); fail(); }catch(Exception $e){ pass(); }

//Test observer
testpack("Test Observers");
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


testpack("Test Association");
$user = $redbean->dispense("user");
$user->name = "John";
$redbean->store( $user );
$page = $redbean->dispense("page");
$page->name = "John's page";
$redbean->store($page);
$page2 = $redbean->dispense("page");
$page2->name = "John's second page";
$redbean->store($page2);
$a = new RedBean_Association( new RedBean_ToolBox( $redbean, $adapter, $writer ) );
$a->associate($page, $user);
asrt(count($a->related($user, "page" )),1);
$a->associate($user,$page2);
asrt(count($a->related($user, "page" )),2);
$a->unassociate($page, $user);
asrt(count($a->related($user, "page" )),1);


testpack("Test Frozen");
$redbean->freeze( true );
$page = $redbean->dispense("page");
$page->sections = 10;
$page->name = "half a page";
$id = $redbean->store($page);
asrt($id,0);
$page = $redbean->load("page", $id);
asrt($page,NULL);

testpack("Test Tree");
$redbean->freeze( false );
$page = $redbean->dispense("page");
$page->name="nested pages";
$page->user = $redbean->dispense("user");
$page->user->name = "Mark";
$page->page = $redbean->dispense("page");
$page->page->name = "another page";
$id = $redbean->store($page);
$page = $redbean->load("page", $id);
asrt( $page->name, "nested pages" );
asrt( $redbean->bean($page,"user")->name, "Mark" );

printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");
