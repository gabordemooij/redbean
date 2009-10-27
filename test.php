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


require("RedBean/redbean.inc.php");
$toolbox = RedBean_Setup::kickstartDev( "mysql:host=localhost;dbname=oodb","root","" );

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



$adapter = $toolbox->getDatabaseAdapter();
$writer  = $toolbox->getWriter();
$redbean = $toolbox->getRedBean();
testpack("Test Kickstart and Toolbox: Init");
asrt(($adapter instanceof RedBean_DBAdapter),true);
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
$pdo->Execute("DROP TABLE IF EXISTS book");
$pdo->Execute("DROP TABLE IF EXISTS one");
$pdo->Execute("DROP TABLE IF EXISTS post");
$pdo->Execute("DROP TABLE IF EXISTS page_user");
$pdo->Execute("DROP TABLE IF EXISTS page_page");
$pdo->Execute("DROP TABLE IF EXISTS association");
$pdo->Execute("DROP TABLE IF EXISTS logentry");
$pdo->Execute("DROP TABLE IF EXISTS admin");
$pdo->Execute("DROP TABLE IF EXISTS admin_logentry");
$page = $redbean->dispense("page");

testpack("Test Database");
try{ $adapter->exec("an invalid query"); fail(); }catch(RedBean_Exception_SQL $e ){ pass(); }
asrt( (int) $adapter->getCell("SELECT 123") ,123);
asrt( (int) $adapter->getCell("SELECT ?",array("987")) ,987);
asrt( (int) $adapter->getCell("SELECT ?+?",array("987","2")) ,989);
asrt( (int) $adapter->getCell("SELECT :numberOne+:numberTwo",array(
			":numberOne"=>42,":numberTwo"=>50)) ,92);

testpack("Test RedBean OODBBean: Meta Information");
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


testpack("Test RedBean OODBBean: import");
$bean = new RedBean_OODBBean;
$bean->import(array("a"=>1,"b"=>2));
asrt($bean->a, 1);
asrt($bean->b, 2);

testpack("Test RedBean OODBBean: export");
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




testpack("Test RedBean OODB: Dispense");
asrt(((bool)$page->getMeta("type")),true);
asrt(isset($page->id),true);
asrt(($page->getMeta("type")),"page");
asrt(($page->id),0);
try{ $redbean->dispense(""); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->dispense("."); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $redbean->dispense("-"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }


testpack("Test RedBean OODB: Check");
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

$page->rating = 2.5;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "2.5" );

$page->rating = -3.3;
$newid = $redbean->store( $page );
asrt( $newid, $id );
$page = $redbean->load( "page", $id );
asrt( $page->name, "new name" );
asrt( $page->rating, "-3.3" );

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


//test locking
testpack("Test RedBean Locking: Change Logger method ");
$page = $redbean->dispense("page");
$page->name = "a page";
$id = $redbean->store( $page );
$page = $redbean->load("page", $id);
$otherpage = $redbean->load("page", $id);
asrt(((bool)$page->getMeta("opened")),true);
asrt(((bool)$otherpage->getMeta("opened")),true);
try{ $redbean->store( $page ); pass(); }catch(Exception $e){ fail(); }
try{ $redbean->store( $otherpage ); fail(); }catch(Exception $e){ pass(); }

//Test observer
testpack("Test Observer Mechanism ");
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


testpack("Test Preloader");
$observers = RedBean_Setup::getAttachedObservers();
$logger = array_pop($observers);
asrt(($logger instanceof RedBean_Observer),true);
$pagea = $redbean->dispense("page");
$pageb = $redbean->dispense("page");
$pagec = $redbean->dispense("page");
$redbean->store($pagea);
$redbean->store($pageb);
$redbean->store($pagec);
$a->associate($pagea, $pageb);
$a->associate($pagea, $pagec);
$ids = $a->related($pagea,"page");
$adapter->exec("TRUNCATE __log");
asrt(intval($adapter->getCell("SELECT count(*) FROM __log")),0);
$logger->preLoad("page",$ids);
asrt(count($ids),2);
asrt(intval($adapter->getCell("SELECT count(*) FROM __log")),3);
$pages = $redbean->batch("page",$ids);
asrt(intval($adapter->getCell("SELECT count(*) FROM __log")),3);




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

testpack("Test Plugins: Optimizer");

$one = $redbean->dispense("one");
$one->col = str_repeat('a long text',100);
$redbean->store($one);
require("RedBean/Optimizer.php");
$optimizer = new Optimizer( $toolbox );
$redbean->addEventListener("update", $optimizer);
$writer  = $toolbox->getWriter();
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$one->col = NULL;
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"text");
$redbean->store($one);
$cols = $writer->getColumns("one");
asrt($cols["col"],"set('1')");

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
$writer->deleteRecord("testtable", "id", $id);
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



$adapter->exec("drop table if exists sometable");
testpack("Test RedBean Security - query writer");
try{$writer->createTable("sometable` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , PRIMARY KEY ( `id` ) ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ; drop table hack; --");}catch(Exception $e){}
asrt(in_array("hack",$adapter->getCol("show tables")),true);

//print_r( $adapter->get("select id from page where id = 1; drop table hack") );
//asrt(in_array("hack",$adapter->getCol("show tables")),true);
//$bean = $redbean->load("page","13);show tables; ");
//exit;



printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");
