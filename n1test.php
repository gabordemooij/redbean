<?php


error_reporting(E_ALL | E_STRICT);
//require("RedBean/redbean.inc.php");
require('rb.php');

//R::setup("pgsql:host=localhost;dbname=oodb","postgres"); $db="pgsql";
R::setup("mysql:host=localhost;dbname=oodb","root"); $db="mysql";
//R::setup(); $db="sqlite"; R::exec(' PRAGMA foreign_keys = ON ');


function printtext( $text ) {
	if ($_SERVER["DOCUMENT_ROOT"]) {
		echo "<BR>".$text;
	}
	else {
		echo "\n".$text;
	}
}

function testpack($name) {
	printtext("testing: ".$name);
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

$nukepass=0;
function droptables() {
global $nukepass;
R::nuke();
if (!count($t=R::$writer->getTables())) $nukepass++; else {
echo "\nFailed to clean up database: ".print_r($t,1);
fail();
}
return;

if ($db=='mysql') R::exec('SET FOREIGN_KEY_CHECKS=0;');
if ($db=='sqlite') R::exec('PRAGMA foreign_keys = 0 ');
R::exec('drop view if exists people');
R::exec('drop view if exists library2');
foreach(R::$writer->getTables() as $t) {

	if ($db=='mysql') R::exec("drop table `$t`");
	if ($db=='pgsql') R::exec("drop table \"$t\" cascade");
	if ($db=='sqlite') R::exec("drop table $t ");
}
if ($db=='mysql') R::exec('SET FOREIGN_KEY_CHECKS=1;');
if ($db=='sqlite') R::exec('PRAGMA foreign_keys = 1 ');

}




function testids($array) {
	foreach($array as $key=>$bean) {
		asrt(intval($key),intval($bean->getID()));
	}
}

droptables();

if ($db=='sqlite') {
testpack('Test widen column in combination with bean formatter. (discovered while testing FKs)');
class BF extends RedBean_DefaultBeanFormatter {
	public function formatBeanTable($type){ return 'prefixed_'.$type; }
	public function formatBeanID($type){ return 'lousy_shitty_id'; }
}

R::$writer->setBeanFormatter(new BF);
$bean = R::dispense('page');
$bean->rating = 1;
R::store($bean);
$cols = R::$writer->getColumns('page');
asrt($cols['rating'],'INTEGER');

$bean->rating = 1.4;
R::store($bean);
$cols = R::$writer->getColumns('page');
asrt($cols['rating'],'NUMERIC');

$bean->rating = '1999-02-02';
R::store($bean);
$cols = R::$writer->getColumns('page');
asrt($cols['rating'],'NUMERIC');

$bean->rating = 'reasonable';
R::store($bean);
$cols = R::$writer->getColumns('page');
asrt($cols['rating'],'TEXT');
R::$writer->setBeanFormatter(new RedBean_DefaultBeanFormatter);

droptables();
}
list($q1,$q2) = R::dispense('quote',2);
list($pic1,$pic2) = R::dispense('picture',2);
list($book,$book2,$book3) = R::dispense('book',4);
list($topic1, $topic2,$topic3,$topic4,$topic5) = R::dispense('topic',5);
list($page1,$page2,$page3,$page4,$page5,$page6,$page7) = R::dispense('page',7);
$q1->text = 'lorem';
$q2->text = 'ipsum';
$book->title = 'abc';
$book2->title = 'def';
$book3->title = 'ghi';
$page1->title = 'pagina1';
$page2->title = 'pagina2';
$page3->title = 'pagina3';
$page4->title = 'pagina4';
$page5->title = 'pagina5';
$page6->title = 'cover1';
$page7->title = 'cover2';
$topic1->name = 'holiday';
$topic2->name = 'cooking';
$topic3->name = 'gardening';
$topic4->name = 'computing';
$topic5->name = 'christmas';
//Add one page to the book
$book->ownPage[] = $page1;
$id = R::store($book);
asrt(count($book->ownPage),1);
asrt(reset($book->ownPage)->getMeta('type'),'page');
$book = R::load('book',$id);
asrt(count($book->ownPage),1);
asrt(reset($book->ownPage)->getMeta('type'),'page');
//performing an own addition
$book->ownPage[] = $page2;
$id = R::store($book);
$book = R::load('book',$id);
asrt(count($book->ownPage),2);
//performing a deletion
$book = R::load('book',$id);
unset($book->ownPage[1]);
$id = R::store($book);
$book = R::load('book',$id);
asrt(count($book->ownPage),1);
asrt(reset($book->ownPage)->getMeta('type'),'page');
asrt(R::count('page'),2);//still exists
asrt(reset($book->ownPage)->id,'2');
//doing a change in one of the owned items
$book->ownPage[2]->title='page II';
$id = R::store($book);
$book = R::load('book',$id);
asrt(reset($book->ownPage)->title,'page II');
//change by reference now... dont copy!
$refToPage2 = $book->ownPage[2];
$refToPage2->title = 'page II b';
$id = R::store($book);
$book = R::load('book',$id);
asrt(reset($book->ownPage)->title,'page II b');
//doing all actions combined
$book->ownPage[] = $page3;
R::store($book);
$book = R::load('book',$id);
unset($book->ownPage[2]);
$book->ownPage['customkey'] = $page4; //and test custom key
$book->ownPage[3]->title = "THIRD";
R::store($book);
$book = R::load('book',$id);
asrt(count($book->ownPage),2);
$p4 = $book->ownPage[4];
$p3 = $book->ownPage[3];
asrt($p4->title,'pagina4');
asrt($p3->title,'THIRD');
//test replacing an element
$book = R::load('book',$id);
$book->ownPage[4] = $page5;
R::store($book);
$book = R::load('book',$id);
asrt(count($book->ownPage),2);
$p5 = $book->ownPage[5];



asrt($p5->title,'pagina5');
//other way around - single bean
asrt($p5->book->title,'abc');
asrt(R::load('page',5)->book->title,'abc');
asrt(R::load('page',3)->book->title,'abc');
//add the other way around - single bean
$page1->id =0;
$page1->book = $book2;
$page1 = R::load('page',R::store($page1));
asrt($page1->book->title,'def');
$b2 = R::load('book',$id);
asrt(count($b2->ownPage),2);

//remove the other way around - single bean
unset($page1->book);
R::store($page1);
$b2 = R::load('book',$book2->id);
asrt(count($b2->ownPage),0);
//re-add the page
$b2->ownPage[] = $page1;
R::store($b2);
$b2 = R::load('book',$book2->id);
asrt(count($b2->ownPage),1);
//different, less elegant way to remove
$page1 = reset($b2->ownPage);
$page1->book_id = null;
R::store($page1);
$b2 = R::load('book',$book2->id);
asrt(count($b2->ownPage),0);
//re-add the page
$b2->ownPage[] = $page1;
R::store($b2);
$b2 = R::load('book',$book2->id);
asrt(count($b2->ownPage),1);
//test fk, not allowed to set to 0
$page1 = reset($b2->ownPage);
$page1->book_id = 0;
if ($db=='pgsql' || $db=='mysql') {
	try{
		R::store($page1);
		fail();
	}
	catch(Exception $e){
		pass();
	}

}
//even uglier way, but still needs to work
$page1 = reset($b2->ownPage);
$page1->book_id = null;
R::store($b2);
$b2 = R::load('book',$book2->id);
asrt(count($b2->ownPage),0);



//test shared items
$book = R::load('book',$id);
$book->sharedTopic[] = $topic1;
$id = R::store($book);

//add an item
asrt(count($book->sharedTopic),1);
asrt(reset($book->sharedTopic)->name,'holiday');
$book = R::load('book',$id);
asrt(count($book->sharedTopic),1);
asrt(reset($book->sharedTopic)->name,'holiday');
//add another item
$book->sharedTopic[] = $topic2;


$id = R::store($book);
$tidx = R::store(R::dispense('topic'));
$book = R::load('book',$id);
asrt(count($book->sharedTopic),2);
$t1 = $book->sharedTopic[1];
$t2 = $book->sharedTopic[2];
asrt($t1->name,'holiday');
asrt($t2->name,'cooking');
//remove an item
unset($book->sharedTopic[2]);
asrt(count($book->sharedTopic),1);
$id = R::store($book);
$book = R::load('book',$id);
asrt(count($book->sharedTopic),1);
asrt(reset($book->sharedTopic)->name,'holiday');
//add and change
$book->sharedTopic[] = $topic3;
$book->sharedTopic[1]->name = 'tropics';
$id = R::store($book);
$book = R::load('book',$id);
asrt(count($book->sharedTopic),2);
asrt(reset($book->sharedTopic)->name,'tropics');
testids($book->sharedTopic);
R::trash(R::load('topic',$tidx));
$id = R::store($book);
$book = R::load('book',$id);
//delete without save
unset($book->sharedTopic[1]);
$book = R::load('book',$id);
asrt(count($book->sharedTopic),2);
$book = R::load('book',$id);
//delete without init
asrt((R::count('topic')),3);
unset($book->sharedTopic[1]);
$id = R::store($book);

asrt((R::count('topic')),3);
asrt(count($book->sharedTopic),1);
asrt(count($book2->sharedTopic),0);
//add same topic to other book
$book2->sharedTopic[] = $topic3;
asrt(count($book2->sharedTopic),1);
$id2 = R::store($book2);
asrt(count($book2->sharedTopic),1);
$book2 = R::load('book',$id2);
asrt(count($book2->sharedTopic),1);
//get books for topic
asrt(count(R::related($topic3,'book')),2);
$t3 = R::load('topic',$topic3->id);
asrt(count($t3->sharedBook),2);
//nuke an own-array, replace entire array at once without getting first
$page2->id=0;
$page2->title = 'yet another page 2';
$page4->id=0;
$page4->title = 'yet another page 4';
$book= R::load('book',$id);
$book->ownPage = array($page2,$page4);
R::store($book);
$book= R::load('book',$id);
asrt(count($book->ownPage),2);
asrt(reset($book->ownPage)->title,'yet another page 2');
asrt(end($book->ownPage)->title,'yet another page 4');
testids($book->ownPage);
//test aliasing
//test with alias format
class Aliaser extends RedBean_DefaultBeanFormatter {
	public function getAlias($a){
		if ($a=='cover') return 'page'; else return $a;
	}
}
$formatter = new Aliaser();
R::$writer->setBeanFormatter($formatter);
$book3->cover = $page6;
$idb3=R::store($book3);
$book3=R::load('book',$idb3);
asrt(($book3->cover instanceof RedBean_OODBBean),true);
$justACover = $book3->cover;
asrt($justACover->title,'cover1');
asrt(isset($book3->page),false);//no page property

//test doubling and other side effects ... should not occur..
$book3->sharedTopic = array($topic1, $topic2);
$book3=R::load('book',R::store($book3));
$book3->sharedTopic = array();
$book3=R::load('book',R::store($book3));

asrt(count($book3->sharedTopic),0);
$book3->sharedTopic[] = $topic1;
$book3=R::load('book',R::store($book3));

//added really one, not more?
asrt(count($book3->sharedTopic),1);
asrt(intval(R::getCell("select count(*) from book_topic where book_id = $idb3")),1);
//add the same
$book3->sharedTopic[] = $topic1;
$book3=R::load('book',R::store($book3));

asrt(count($book3->sharedTopic),1);
asrt(intval(R::getCell("select count(*) from book_topic where book_id = $idb3")),1);
$book3->sharedTopic['differentkey'] = $topic1;
$book3=R::load('book',R::store($book3));
asrt(count($book3->sharedTopic),1);
asrt(intval(R::getCell("select count(*) from book_topic where book_id = $idb3")),1);
//ugly assign, auto array generation
$book3->ownPage[] = $page1;
$book3=R::load('book',R::store($book3));

asrt(count($book3->ownPage),1);
asrt(intval(R::getCell("select count(*) from page where book_id = $idb3 ")),1);
$book3=R::load('book',$idb3);
$book3->ownPage = array();
asrt(intval(R::getCell("select count(*) from page where book_id = $idb3 ")),1); //no change until saved
$book3=R::load('book',R::store($book3));
asrt(intval(R::getCell("select count(*) from page where book_id = $idb3 ")),0);
asrt(count($book3->ownPage),0);
$book3=R::load('book',$idb3);
//why do I need to do this ---> why does trash() not set id -> 0, because you unset() so trash is done on orign not bean
$page1->id = 0;
$page2->id = 0;
$page3->id = 0;
$book3->ownPage[] = $page1;
$book3->ownPage[] = $page2;
$book3->ownPage[] = $page3;
//print_r($book3->ownPage);
$book3=R::load('book',R::store($book3));
asrt(intval(R::getCell("select count(*) from page where book_id = $idb3 ")),3);
asrt(count($book3->ownPage),3);


unset($book3->ownPage[$page2->id]);
$book3->ownPage[] = $page3;
$book3->ownPage['try_to_trick_ya'] = $page3;
$book3=R::load('book',R::store($book3));
asrt(intval(R::getCell("select count(*) from page where book_id = $idb3 ")),2);
asrt(count($book3->ownPage),2);
//delete and re-add
$book3=R::load('book',$idb3);
unset($book3->ownPage[10]);
$book3->ownPage[] = $page1;
$book3=R::load('book',R::store($book3));
asrt(count($book3->ownPage),2);//exit;
$book3=R::load('book',$idb3);
//print_r($book3->sharedTopic);
unset($book3->sharedTopic[1]);
$book3->sharedTopic[] = $topic1;
$book3=R::load('book',R::store($book3));
asrt(count($book3->sharedTopic),1);



//test performance
$logger = RedBean_Plugin_QueryLogger::getInstanceAndAttach(R::$adapter);
$book = R::load('book',1);
$book->sharedTopic = array();
R::store($book);
asrt(count($logger->grep('UPDATE')),1);  //no more than 1 update
$book=R::load('book',1);
$logger->clear();
print_r($book->sharedTopic,1);
asrt(count($logger->grep('SELECT')),1);  //no more than 1 select

$logger->clear();
$book->sharedTopic[] = $topic1;
$book->sharedTopic[] = $topic2;
asrt(count($logger->grep('SELECT')),0);
R::store($book);
$book->sharedTopic[] = $topic3;
//now do NOT clear all and then add one, just add the one
$logger->clear();
R::store($book);
$book=R::load('book',1);
asrt(count($book->sharedTopic),3);
asrt(count($logger->grep("DELETE")),0); //no deletes
$book->sharedTopic['a'] = $topic3;
unset($book->sharedTopic['a']);
R::store($book);
$book=R::load('book',1);
asrt(count($book->sharedTopic),3);
asrt(count($logger->grep("DELETE")),0); //no deletes
$book->ownPage = array();
R::store($book);
asrt(count($book->ownPage),0);
$book->ownPage[] = $page1;
$book->ownPage['a'] = $page2;
asrt(count($book->ownPage),2);
R::store($book);
unset($book->ownPage['a']);
asrt(count($book->ownPage),2);
unset($book->ownPage[11]);
R::store($book);
$book=R::load('book',1);
asrt(count($book->ownPage),1);
$aPage = $book->ownPage[10];
unset($book->ownPage[10]);
$aPage->title .= ' changed ';
$book->ownPage['anotherPage'] = $aPage;
$logger->clear();
R::store($book);
if ($db=="mysql") asrt(count($logger->grep("SELECT")),0);
$book=R::load('book',1);
asrt(count($book->ownPage),1);
$ap = reset($book->ownPage);
asrt($ap->title,"pagina1 changed ");



//fix udiff instead of diff
$book3->ownPage = array($page3,$page1);
$i = R::store($book3);
//exit;
$book3=R::load('book',$i);
asrt(intval(R::getCell("select count(*) from page where book_id = $idb3 ")),2);
asrt(count($book3->ownPage),2);
$pic1->name = 'aaa';
$pic2->name = 'bbb';
R::store($pic1);
R::store($q1);

$book3->ownPicture[] = $pic1;
$book3->ownQuote[] = $q1;
$book3=R::load('book',R::store($book3));
//two own-arrays -->forgot array_merge
asrt(count($book3->ownPicture),1);
asrt(count($book3->ownQuote),1);
asrt(count($book3->ownPage),2);
$book3=R::load('book',R::store($book3));
unset($book3->ownPicture[1]);
$book3=R::load('book',R::store($book3));
asrt(count($book3->ownPicture),0);
asrt(count($book3->ownQuote),1);
asrt(count($book3->ownPage),2);
$book3=R::load('book',R::store($book3));

function modgr($book3) {

	global $quotes,$pictures,$topics;

	$key = array_rand($quotes);
	$quote = $quotes[$key];
	$keyPic = array_rand($pictures);
	$picture = $pictures[$keyPic];
	$keyTop = array_rand($topics);
	$topic = $topics[$keyTop];




	if (rand(0,1)) {
		$f=0;
		foreach($book3->ownQuote as $z) {
			if ($z->note == $quote->note) { $f = 1; break; }
		}
		if (!$f) {
		//echo "\n add a quote ";
		$book3->ownQuote[] = $quote;
		}
	}
	if (rand(0,1)){
		$f=0;
		foreach($book3->ownPicture as $z) {
			if ($z->note == $picture->note) { $f = 1; break; }
		}
		if (!$f) {
		//	echo "\n add a picture ";
			$book3->ownPicture[] = $picture;
		}
	}
	if (rand(0,1)) {
		$f=0;
		foreach($book3->sharedTopic as $z) {
			if ($z->note == $topic->note) { $f = 1; break; }
		}
		if (!$f) {
		//	echo "\n add a shared topic ";
			$book3->sharedTopic[] = $topic;
		}
	}
	if (rand(0,1) && count($book3->ownQuote)>0) {
		$key = array_rand($book3->ownQuote);
		unset($book3->ownQuote[ $key ]);
	//	echo "\n delete quote with key $key ";
	}
	if (rand(0,1) && count($book3->ownPicture)>0) {
		$key = array_rand($book3->ownPicture);
		unset($book3->ownPicture[ $key ]);
	//	echo "\n delete picture with key $key ";
	}
	if (rand(0,1) && count($book3->sharedTopic)>0) {
		$key = array_rand($book3->sharedTopic);
		unset($book3->sharedTopic[ $key ]);
	//	echo "\n delete sh topic  with key $key ";
	}

	if (rand(0,1) && count($book3->ownPicture)>0) {
		$key = array_rand($book3->ownPicture);
		$book3->ownPicture[ $key ]->change = rand(0,100);
	//	echo "\n changed picture with key $key ";
	}
	if (rand(0,1) && count($book3->ownQuote)>0) {
		$key = array_rand($book3->ownQuote);
		$book3->ownQuote[ $key ]->change = 'note ch '.rand(0,100);
	//	echo "\n changed quote with key $key ";
	}
	if (rand(0,1) && count($book3->sharedTopic)>0) {
		$key = array_rand($book3->sharedTopic);
		$book3->sharedTopic[ $key ]->change = rand(0,100);
	//	echo "\n changed sharedTopic with key $key ";
	}
}


$NOTE = 0;

$quotes = R::dispense('quote',10);
foreach($quotes as &$justSomeQuote) {
	$justSomeQuote->note = 'note'.(++$NOTE);
}
$pictures = R::dispense('picture',10);
foreach($pictures as &$justSomePic) {
	$justSomePic->note = 'note'.(++$NOTE);
}
$topics = R::dispense('topic',10);
foreach($topics as &$justSomeTopic) {
	$justSomeTopic->note = 'note'.(++$NOTE);
}


for($j=0; $j<10; $j++) {
	//echo "\n bean start: ".print_r($book3,1);
	for($x=0;$x<rand(1,20); $x++) modgr($book3); //do several mutations
	$qbefore = count($book3->ownQuote);
	$pbefore = count($book3->ownPicture);
	$tbefore = count($book3->sharedTopic);
	$qjson = json_encode($book->ownQuote);
	$pjson = json_encode($book->ownPicture);
	$tjson = json_encode($book->sharedTopic);
	$book3=R::load('book',R::store($book3));
	asrt(count($book3->ownQuote),$qbefore);
	asrt(count($book3->ownPicture),$pbefore);
	asrt(count($book3->sharedTopic),$tbefore);
	asrt(json_encode($book->ownQuote),$qjson);
	asrt(json_encode($book->ownPicture),$pjson);
	asrt(json_encode($book->sharedTopic),$tjson);
	testids($book->ownQuote);
	testids($book->ownPicture);
	testids($book->sharedTopic);

}



//graph
R::exec('drop table if exists band_bandmember');
R::exec('drop table if exists band_location');
R::exec('drop table if exists band_genre');
R::exec('drop table if exists army_village');
R::exec('drop table if exists cd_track');
R::exec('drop table if exists song_track');
R::exec('drop table if exists location');
R::exec('drop table if exists bandmember');
R::exec('drop table if exists band');
R::exec('drop table if exists genre');
R::exec('drop table if exists farmer cascade');
R::exec('drop table if exists furniture');
R::exec('drop table if exists building');
R::exec('drop table if exists village');
R::exec('drop table if exists army');
R::exec('drop table if exists people');
R::exec('drop table if exists song');
R::exec('drop table if exists track');
R::exec('drop table if exists cover');
R::exec('drop table if exists playlist');
list($v1,$v2,$v3) = R::dispense('village',3);
list($b1,$b2,$b3,$b4,$b5,$b6) = R::dispense('building',6);
list($f1,$f2,$f3,$f4,$f5,$f6) = R::dispense('farmer',6);
list($u1,$u2,$u3,$u4,$u5,$u6) = R::dispense('furniture',6);
list($a1,$a2) = R::dispense('army',2);

$a1->strength = 100;
$a2->strength = 200;
$v1->name = 'v1';
$v2->name = 'v2';
$v3->name = 'v3';
$v1->ownBuilding = array($b4,$b6);
$v2->ownBuilding = array($b1);
$v3->ownBuilding = array($b5);
$b1->ownFarmer = array($f1,$f2);
$b6->ownFarmer = array($f3);
$b5->ownFarmer = array($f4);
$b5->ownFurniture = array($u6,$u5,$u4);
$v2->sharedArmy[] = $a2;
$v3->sharedArmy = array($a2,$a1);
$i2=R::store($v2);
$i1=R::store($v1);
$i3=R::store($v3);
$v1 = R::load('village',$i1);
$v2 = R::load('village',$i2);
$v3 = R::load('village',$i3);
asrt(count($v3->ownBuilding),1);
asrt(count(reset($v3->ownBuilding)->ownFarmer),1);
asrt(count(reset($v3->ownBuilding)->ownFurniture),3);
asrt(count(($v3->sharedArmy)),2);
asrt(count($v1->sharedArmy),0);
asrt(count($v2->sharedArmy),1);
asrt(count($v2->ownBuilding),1);
asrt(count($v1->ownBuilding),2);
asrt(count(reset($v1->ownBuilding)->ownFarmer),0);
asrt(count(end($v1->ownBuilding)->ownFarmer),1);
asrt(count($v3->ownTapestry),0);

//test views for N-1 - we use the village for this
R::view('people','village,building,farmer,building,furniture');
//count buildings
if ($db=="mysql") {
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v1"');
asrt($noOfBuildings,2);
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v2"');
asrt($noOfBuildings,1);
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v3"');
asrt($noOfBuildings,1);
}

if ($db=="pgsql") {
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name=\'v1\'');
asrt($noOfBuildings,2);
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name=\'v2\'');
asrt($noOfBuildings,1);
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name=\'v3\'');
asrt($noOfBuildings,1);
}

if ($db=="mysql") {
//what villages does not have furniture
$emptyHouses = R::getAll('select name,count(id_of_furniture) from people group by id having count(id_of_furniture) = 0');
asrt(count($emptyHouses),2);
foreach($emptyHouses as $empty){
	if ($empty['name']!=='v3') pass(); else fail();
}
}

//test invalid views - should trigger error
try{ R::view('messy','building,village,farmer'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }

//R::view('impossible','nonexistant,fictional');

//Change the names and add the same building should not change the graph
$v1->name = 'village I';
$v2->name = 'village II';
$v3->name = 'village III';
$v1->ownBuilding[] = $b4;
$i2=R::store($v2);
$i1=R::store($v1);
$i3=R::store($v3);
$v1 = R::load('village',$i1);
$v2 = R::load('village',$i2);
$v3 = R::load('village',$i3);
asrt(count($v3->ownBuilding),1);
asrt(count(reset($v3->ownBuilding)->ownFarmer),1);
asrt(count(reset($v3->ownBuilding)->ownFurniture),3);
asrt(count(($v3->sharedArmy)),2);
asrt(count($v1->sharedArmy),0);
asrt(count($v2->sharedArmy),1);
asrt(count($v2->ownBuilding),1);
asrt(count($v1->ownBuilding),2);
asrt(count(reset($v1->ownBuilding)->ownFarmer),0);
asrt(count(end($v1->ownBuilding)->ownFarmer),1);
asrt(count($v3->ownTapestry),0);


$json = '{"mysongs":{"type":"playlist","name":"JazzList","ownTrack":[{"type":"track","name":"harlem nocturne","order":"1","sharedSong":[{"type":"song","url":"music.com.harlem"}],"cover":{"type":"cover","url":"albumart.com\/duke1"}},{"type":"track","name":"brazil","order":"2","sharedSong":[{"type":"song","url":"music.com\/djan"}],"cover":{"type":"cover","url":"picasa\/django"}}]}}';

$playList = json_decode( $json, true );
$cooker = new RedBean_Cooker;
$cooker->setToolbox(R::$toolbox);

$playList = ($cooker->graph(($playList)));
$id = R::store(reset($playList));
$play = R::load("playlist", $id);
asrt(count($play->ownTrack),2);
foreach($play->ownTrack as $track) {
	asrt(count($track->sharedSong),1);
	asrt(($track->cover instanceof RedBean_OODBBean),true);
}

$json = '{"mysongs":{"type":"playlist","id":"1","ownTrack":[{"type":"track","name":"harlem nocturne","order":"1","sharedSong":[{"type":"song","id":"1"}],"cover":{"type":"cover","id":"2"}},{"type":"track","name":"brazil","order":"2","sharedSong":[{"type":"song","url":"music.com\/djan"}],"cover":{"type":"cover","url":"picasa\/django"}}]}}';

$playList = json_decode( $json, true );
$cooker = new RedBean_Cooker;
$cooker->setToolbox(R::$toolbox);
$playList = ($cooker->graph(($playList)));
$id = R::store(reset($playList));
$play = R::load("playlist", $id);
asrt(count($play->ownTrack),2);
foreach($play->ownTrack as $track) {
	asrt(count($track->sharedSong),1);
	asrt(($track->cover instanceof RedBean_OODBBean),true);
}
$track = reset($play->ownTrack);
$song = reset($track->sharedSong);
asrt(intval($song->id),1);
asrt($song->url,"music.com.harlem");

$json = '{"mysongs":{"type":"playlist","id":"1","ownTrack":[{"type":"track","name":"harlem nocturne","order":"1","sharedSong":[{"type":"song","id":"1","url":"changedurl"}],"cover":{"type":"cover","id":"2"}},{"type":"track","name":"brazil","order":"2","sharedSong":[{"type":"song","url":"music.com\/djan"}],"cover":{"type":"cover","url":"picasa\/django"}}]}}';

$playList = json_decode( $json, true );
$cooker = new RedBean_Cooker;
$cooker->setToolbox(R::$toolbox);
$playList = ($cooker->graph(($playList)));
$id = R::store(reset($playList));
$play = R::load("playlist", $id);
asrt(count($play->ownTrack),2);
foreach($play->ownTrack as $track) {
	asrt(count($track->sharedSong),1);
	asrt(($track->cover instanceof RedBean_OODBBean),true);
}
$track = reset($play->ownTrack);
$song = reset($track->sharedSong);
asrt(intval($song->id),1);
asrt(($song->url),"changedurl");


//Tree
$page = R::dispense('page');
$page->name = 'root of all evil';
list( $subPage, $subSubPage, $subNeighbour, $subOfSubNeighbour, $subSister ) = R::dispense('page',5);
$subPage->name = 'subPage';
$subSubPage->name = 'subSubPage';
$subOfSubNeighbour->name = 'subOfSubNeighbour';
$subNeighbour->name = 'subNeighbour';
$subSister->name = 'subSister';
$page->ownPage = array( $subPage, $subNeighbour, $subSister );
R::store($page);
asrt(count($page->ownPage),3);
foreach($page->ownPage as $p) {
	if ($p->name=='subPage') {
		$p->ownPage[] = $subSubPage;
	}
	if ($p->name=='subNeighbour') {
		$p->ownPage[] = $subOfSubNeighbour;
	}
}
R::store($page);
asrt(count($page->ownPage),3);
list($first, $second) = array_keys($page->ownPage);
foreach($page->ownPage as $p) {
	if ($p->name=='subPage' || $p->name=='subNeighbour') {
		asrt(count($p->ownPage),1);
	}
	else {
		asrt(count($p->ownPage),0);
	}
}
droptables();

function candy_canes()  {
$canes = R::dispense('cane',10);
$i = 0;
foreach($canes as $k=>$cane) {
 $canes[$k]->label = 'Cane No. '.($i++);
}
$canes[0]->cane = $canes[1];
$canes[1]->cane = $canes[4];
$canes[9]->cane = $canes[4];
$canes[6]->cane = $canes[4];
$canes[4]->cane = $canes[7];
$canes[8]->cane = $canes[7];
return $canes;
}


$canes = candy_canes();
$id = R::store($canes[0]);
$cane = R::load('cane',$id);
asrt($cane->label,'Cane No. 0');
asrt($cane->cane->label,'Cane No. 1');
asrt($cane->cane->cane->label,'Cane No. 4');
asrt($cane->cane->cane->cane->label,'Cane No. 7');
asrt($cane->cane->cane->cane->cane,NULL);

//test backward compatibility
asrt($page->owner,null);


//Test fuse
class Model_Band extends RedBean_SimpleModel {

	public function after_update() {
	}

	public function update() {
		if (count($this->ownBandmember)>4) {
			throw new Exception('too many!');
		}
	}
}

$band = R::dispense('band');
$musicians = R::dispense('bandmember',5);
$band->ownBandmember = $musicians;
try{
R::store($band);
fail();
}
catch(Exception $e){
pass();
}
$band = R::dispense('band');
$musicians = R::dispense('bandmember',4);
$band->ownBandmember = $musicians;
try{
$id=R::store($band);
pass();
}
catch(Exception $e){
fail();
}

$band=R::load('band',$id);
$band->ownBandmember[] = R::dispense('bandmember');
try{
R::store($band);
fail();
}
catch(Exception $e){
pass();
}

$lifeCycle = "";
class Model_Bandmember extends RedBean_SimpleModel {

	public function open() {
		global $lifeCycle;
		$lifeCycle .= "\n called open: ".$this->id;
	}


	public function dispense(){
		global $lifeCycle;
		$lifeCycle .= "\n called dispense() ".$this->bean;
	}

	public function update() {
		global $lifeCycle;
		$lifeCycle .= "\n called update() ".$this->bean;
	}

	public function after_update(){
		global $lifeCycle;
		$lifeCycle .= "\n called after_update() ".$this->bean;
	}

	public function delete() {
		global $lifeCycle;
		$lifeCycle .= "\n called delete() ".$this->bean;
	}

	public function after_delete() {
		global $lifeCycle;
		$lifeCycle .= "\n called after_delete() ".$this->bean;
	}



}

$bandmember = R::dispense('bandmember');
$bandmember->name = 'Fatz Waller';
$id = R::store($bandmember);
$bandmember = R::load('bandmember',$id);
R::trash($bandmember);


//echo "\n\n\n".$lifeCycle."\n";

$expected = 'calleddispenseid0calledupdateid0nameFatzWallercalledafter_updateid5nameFatzWallercalleddispenseid0calledopen5calleddeleteid5band_idnullnameFatzWallercalledafter_deleteid0band_idnullnameFatzWaller';

$lifeCycle = preg_replace("/\W/","",$lifeCycle);
//$expected = "\n\n".preg_replace("/\W/","",$expected)."\n\n";


asrt($lifeCycle,$expected);


//NULL test
$page = R::dispense('page');
$book = R::dispense('book');
$page->title = 'a null page';
$page->book = $book;
$book->title = 'Why NUll is painful..';
R::store($page);
$bookid = $page->book->id;
unset($page->book);
$id = R::store($page);
$page = R::load('page',$id);
$page->title = 'another title';
R::store($page);
pass();
$page = R::load('page',$id);
$page->title = 'another title';
$page->book_id = null;
R::store($page);
pass();
droptables();
/*
Here we test whether the column type is set correctly. Normally if you store NULL, the smallest
type (bool/set) will be selected. However in case of a foreign key type INT should be selected because
fks columns require matching types.
*/
$book=R::dispense('book');
$page=R::dispense('page');
$book->ownPage[] = $page;
R::store($book);
pass(); //survive?
asrt($page->getMeta('cast.book_id'),'id'); //check cast
droptables(); //again, but now the other way around
$book=R::dispense('book');
$page=R::dispense('page');
$page->book = $book;
R::store($page);
pass();
asrt($page->getMeta('cast.book_id'),'id');


//Test combination of bean formatter and N1
class N1AndFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xy_$table";}
	public function formatBeanID( $table ) {return "theid";}
	public function getAlias($a){ return $a; }
}
R::$writer->setBeanFormatter(new N1AndFormatter);
droptables();
$book=R::dispense('book');
$page=R::dispense('page');
$book->ownPage[] = $page;
$bookid = R::store($book);
pass(); //survive?
asrt($page->getMeta('cast.book_id'),'id');
$book = R::load('book',$bookid);
asrt(count($book->ownPage),1);
$book->ownPage[] = R::dispense('page');
$bookid = R::store($book);
$book = R::load('book',$bookid);
asrt(count($book->ownPage),2);

//Test whether a nested bean will be saved if tainted
droptables();
$page = R::dispense('page');
$page->title = 'a blank page';
$book = R::dispense('book');
$book->title = 'shiny white pages';
$book->ownPage[] = $page;
$id = R::store($book);
$book = R::load('book', $id);
$page = reset($book->ownPage);
asrt($page->title,'a blank page');
$page->title = 'slightly different white';
R::store($book);
$book = R::load('book', $id);
$page = reset($book->ownPage);
asrt($page->title,'slightly different white');
$page = R::dispense('page');
$page->title = 'x';
$book = R::load('book', $id);
$book->title = 'snow white pages';
$page->book = $book;
$pid = R::store($page);
$page = R::load('page', $pid);
asrt($page->book->title,'snow white pages');

//test you cannot unset a relation list
asrt(count($book->ownPage),2);
unset($book->ownPage);
$book=R::load('book',R::store($book));
asrt(count($book->ownPage),2);



//Invalid properties
droptables();
$book = R::dispense('book');
$page = R::dispense('page');
//wrong property name
$book->wrongProperty = array($page);
try{
	$book->wrongProperty[] = $page;
	R::store($book);
	fail();
}
catch(RedBean_Exception_Security $e){
	pass();
}
catch(Exception $e){
	fail();
}

//Test for quick detect change
droptables();
$book = R::dispense('book');
if ($book->prop) { }
//echo $book;
asrt(isset($book->prop),false);//not a very good test
asrt(in_array('prop',array_keys($book->export())),false);//better...


$book = R::dispense('book');
$page = R::dispense('page');
$book->paper = $page;
$id = R::store($book);
$book = R::load('book', $id);
asrt(false,(isset($book->paper)));
asrt(false,(isset($book->page)));

//Try to add invalid things in arrays; should not be possible...
try{
	$book->ownPage[] = new stdClass(); R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->sharedPage[] = new stdClass(); R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}


try{
	$book->ownPage[] = "a string"; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->sharedPage[] = "a string"; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->ownPage[] = 1928; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->sharedPage[] = 1928; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->ownPage[] = true; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->sharedPage[] = false; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->ownPage[] = null; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->sharedPage[] = null; R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}


try{
	$book->ownPage[] = array(); R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}

try{
	$book->sharedPage[] = array(); R::store($book); fail();
}
catch(RedBean_Exception_Security $e){ pass();}
catch(Exception $e){fail();}


//test views icw aliases and n1
droptables();
$book = R::dispense('book');
$page = R::dispense('page');
$book->title = 'my book';
$page->title = 'my page';
$book->ownPage[] = $page;
R::store($book);
R::view('library2','book,page');
$l2 = R::getRow('select * from xy_library2 limit 1');
asrt($l2['title'],'my book');
asrt($l2['title_of_page'],'my page');


class Aliaser2 implements RedBean_IBeanFormatter {
        public function formatBeanID($t){ return 'id'; }
        public function formatBeanTable($t){ return $t; }
        public function getAlias($a){
                if ($a=='creator' || $a=='recipient') return 'user';
                return $a;
        }
}

$formatter = new Aliaser2();
R::$writer->setBeanFormatter($formatter);
$message = R::dispense('message');
list($creator,$recipient) = R::dispense('user',2);
$recipient->name = 'r';
$creator->name = 'c';
$message->recipient = $recipient;
$message->creator = $creator;
$id = R::store($message);
$message = R::load('message', $id);
$recipient = $message->recipient;


droptables();
class Alias3 extends RedBean_DefaultBeanFormatter {
	public function getAlias($type) {
		if ($type=='familyman' || $type=='buddy') return 'person';
		return $type;
	}
}

R::$writer->setBeanFormatter(new Alias3);

list($p1,$p2,$p3)  = R::dispense('person',3);
$p1->name = 'Joe';
$p2->name = 'Jack';
$p3->name = 'James';
$fm = R::dispense('familymember');
$fr = R::dispense('friend');
$fr->buddy = $p1;
$fm->familyman = $p2;
$p3->ownFamilymember[] = $fm;
$p3->ownFriend[] = $fr;
$id = R::store($p3);


$friend = R::load('person', $id);
asrt(reset($friend->ownFamilymember)->familyman->name,'Jack');
asrt(reset($friend->ownFriend)->buddy->name,'Joe');

$Jill = R::dispense('person');
$Jill->name = 'Jill';
$familyJill = R::dispense('familymember');
$friend->ownFamilymember[] = $familyJill;
R::store($friend);
$friend = R::load('person', $id);
asrt(count($friend->ownFamilymember),2);
array_pop($friend->ownFamilymember);
R::store($friend);
$friend = R::load('person', $id);
asrt(count($friend->ownFamilymember),1);

droptables();
R::$writer->setBeanFormatter(new RedBean_DefaultBeanFormatter);
$message = R::dispense('message');
$message->subject = 'Roommate agreement';
list($sender,$recipient) = R::dispense('person',2);
$sender->name = 'Sheldon';
$recipient->name = 'Leonard';
$message->sender = $sender;
$message->recipient = $recipient;
$id = R::store($message);
$message = R::load('message', $id);
asrt($message->fetchAs('person')->sender->name,'Sheldon');
asrt($message->fetchAs('person')->recipient->name,'Leonard');
$otherRecipient = R::dispense('person');
$otherRecipient->name = 'Penny';
$message->recipient = $otherRecipient;
R::store($message);
$message = R::load('message', $id);
asrt($message->fetchAs('person')->sender->name,'Sheldon');
asrt($message->fetchAs('person')->recipient->name,'Penny');


droptables();
$project = R::dispense('project');
$project->name = 'Mutant Project';
list($teacher,$student) = R::dispense('person',2);
$teacher->name = 'Charles Xavier';
$project->student = $student;
$project->student->name = 'Wolverine';
$project->teacher = $teacher;
$id = R::store($project);
$project = R::load('project',$id);
asrt($project->fetchAs('person')->teacher->name,'Charles Xavier');
asrt($project->fetchAs('person')->student->name,'Wolverine');

droptables();
$farm = R::dispense('building');
$village = R::dispense('village');
$farm->name = 'farm';
$village->name = 'Dusty Mountains';
$farm->village = $village;
$id = R::store($farm);
$farm = R::load('building',$id);
asrt($farm->name,'farm');
asrt($farm->village->name,'Dusty Mountains');

$village = R::dispense('village');
list($mill,$tavern) = R::dispense('building',2);
$mill->name = 'Mill';
$tavern->name = 'Tavern';
$village->ownBuilding = array($mill,$tavern);
$id = R::store($village);
$village = R::load('village',$id);
asrt(count($village->ownBuilding),2);


$village2 = R::dispense('village');
$army = R::dispense('army');
$village->sharedArmy[] = $army;
$village2->sharedArmy[] = $army;
$id1=R::store($village);
$id2=R::store($village2);
$village1 = R::load('village',$id1);
$village2 = R::load('village',$id2);
asrt(count($village1->sharedArmy),1);
asrt(count($village2->sharedArmy),1);
asrt(count($village1->ownArmy),0);
asrt(count($village2->ownArmy),0);

droptables();

testpack('Recursive Export');

//require('../RedBean/redbean.inc.php');
//R::setup('mysql:host=localhost;dbname=oodb','root','');

//require('../RedBean/Plugin/BeanExport.php');
$e = new RedBean_Plugin_BeanExport( R::$toolbox );

list($v1,$v2,$v3) = R::dispense('village',3);
list($b1,$b2,$b3,$b4,$b5,$b6) = R::dispense('building',6);
$amulets = R::dispense('amulet',4);
list($a1,$a2) = R::dispense('army',2);

$v1->name = 'Ole Town';
$v2->name = 'Sandy winds';
$v3->name = 'Autumn Hill';
$b1->kind = 'pub';
$b2->kind = 'farm';
$b3->kind = 'mill';
$b4->kind = 'tower';
$b5->kind = 'shed';
$b6->kind = 'shop';
$i=0;
foreach($amulets as $k=>$a) $amulets[$k]->name = 'Amulet '+(++$i);
$a1->name = 'Army 1';
$a2->name = 'Army 2';
$world = R::dispense('world');
$world->name = 'Middle Earth';

$v1->ownBuilding = array($b1,$b4);
$v2->ownBuilding = array($b3,$b5,$b6);
$v3->ownBuilding = array($b2);
$b2->ownAmulet = array($amulets[0],$amulets[1]);
$b6->ownAmulet = array($amulets[2]);
$b1->ownAmulet[] = $amulets[3];
$v2->sharedArmy = array($a1,$a2);
$v3->sharedArmy = array($a2);
$v1->world = $world;
$v2->universe = $world;



R::store($v1);
R::store($v2);
R::store($v3);

$v1 = R::load('village',$v1->getID());
$v2 = R::load('village',$v2->getID());
$v3 = R::load('village',$v3->getID());

class ExportBeanFormatter extends RedBean_DefaultBeanFormatter{
   public function getAlias( $type ) {
   	if ($type == 'universe') return 'world'; else return $type;
   }
}

R::$toolbox->getWriter()->setBeanFormatter( new ExportBeanFormatter );
$e->loadSchema();
//print_r($e->export($v2));

$export = $e->export($v2);
$out = json_encode($export);
$expected = '{"2":{"id":"2","name":"Sandy winds","world_id":null,"universe_id":"1","universe":{"1":{"id":"1","name":"Middle Earth","ownVillage":{"1":{"id":"1","name":"Ole Town","world_id":"1","universe_id":null,"world":{"1":null},"ownBuilding":{"1":{"id":"1","kind":"pub","village_id":"1","village":{"1":null},"ownAmulet":{"1":{"id":"1","name":"4","building_id":"1","building":{"1":null}}}},"2":{"id":"2","kind":"tower","village_id":"1","village":{"1":null},"ownAmulet":[]}},"sharedArmy":[]}}}},"ownBuilding":{"3":{"id":"3","kind":"mill","village_id":"2","village":{"2":null},"ownAmulet":[]},"4":{"id":"4","kind":"shed","village_id":"2","village":{"2":null},"ownAmulet":[]},"5":{"id":"5","kind":"shop","village_id":"2","village":{"2":null},"ownAmulet":{"2":{"id":"2","name":"3","building_id":"5","building":{"5":null}}}}},"sharedArmy":{"1":{"id":"1","name":"Army 1","sharedVillage":{"2":null}},"2":{"id":"2","name":"Army 2","sharedVillage":{"2":null,"3":{"id":"3","name":"Autumn Hill","world_id":null,"universe_id":null,"ownBuilding":{"6":{"id":"6","kind":"farm","village_id":"3","village":{"3":null},"ownAmulet":{"3":{"id":"3","name":"1","building_id":"6","building":{"6":null}},"4":{"id":"4","name":"2","building_id":"6","building":{"6":null}}}}},"sharedArmy":{"2":null}}}}}}}';
asrt(preg_replace("/\W/","",trim($out)),preg_replace("/\W/","",trim($expected)));
$export=R::exportAll($v2,true);
$out = json_encode($export);
asrt(preg_replace("/\W/","",trim($out)),preg_replace("/\W/","",trim($expected)));


testpack('Test foreign keys');
droptables();
$book = R::dispense('book');
$page = R::dispense('page');
$cover = R::dispense('cover');
list($g1,$g2) = R::dispense('genre',2);
$g1->name = '1';
$g2->name = '2';
$book->ownPage = array($page);
$book->cover = $cover;
$book->sharedGenre = array($g1,$g2);
R::store($book);

if ($db=='sqlite') {
	$fkbook = R::getAll('pragma foreign_key_list(book)');
	$fkgenre = R::getAll('pragma foreign_key_list(book_genre)');
	$fkpage = R::getAll('pragma foreign_key_list(page)');
	$j = json_encode(array($fkbook,$fkgenre,$fkpage));
	$json = '[[{"id":"0","seq":"0","table":"cover","from":"cover_id","to":"id","on_update":"SET NULL","on_delete":"SET NULL","match":"NONE"}],[{"id":"0","seq":"0","table":"book","from":"book_id","to":"id","on_update":"NO ACTION","on_delete":"CASCADE","match":"NONE"},{"id":"1","seq":"0","table":"genre","from":"genre_id","to":"id","on_update":"NO ACTION","on_delete":"CASCADE","match":"NONE"}],[{"id":"0","seq":"0","table":"book","from":"book_id","to":"id","on_update":"SET NULL","on_delete":"SET NULL","match":"NONE"}]]';
}

if ($db=='mysql') {
	$fkbook = R::getAll('describe book');
	$fkgenre = R::getAll('describe book_genre');
	$fkpage = R::getAll('describe cover');
	$j = json_encode(R::getAll('SELECT
	ke.referenced_table_name parent,
	ke.table_name child,
	ke.constraint_name
	FROM
	information_schema.KEY_COLUMN_USAGE ke
	WHERE
	ke.referenced_table_name IS NOT NULL
	ORDER BY
	constraint_name;'));
	$json = '[{"parent":"genre","child":"book_genre","constraint_name":"book_genre_ibfk_1"},{"parent":"book","child":"book_genre","constraint_name":"book_genre_ibfk_2"},{"parent":"cover","child":"book","constraint_name":"book_ibfk_1"},{"parent":"book","child":"page","constraint_name":"page_ibfk_1"}]';
}

if ($db=='pgsql') {
	$sql="SELECT
	    tc.constraint_name, tc.table_name, kcu.column_name,
	    ccu.table_name AS foreign_table_name,
	    ccu.column_name AS foreign_column_name
	FROM
	    information_schema.table_constraints AS tc
	    JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
	    JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
	WHERE constraint_type = 'FOREIGN KEY' AND (tc.table_name='book' OR tc.table_name='book_genre' OR tc.table_name='page');";
	$fks=R::getAll($sql);
	$json='[{"constraint_name":"book_cover_id_fkey","table_name":"book","column_name":"cover_id","foreign_table_name":"cover","foreign_column_name":"id"},{"constraint_name":"page_book_id_fkey","table_name":"page","column_name":"book_id","foreign_table_name":"book","foreign_column_name":"id"},{"constraint_name":"fk65c02fc3a418eb08d0c7b3e8440204f3a","table_name":"book_genre","column_name":"genre_id","foreign_table_name":"genre","foreign_column_name":"id"},{"constraint_name":"fk65c02fc3a418eb08d0c7b3e8440204f3b","table_name":"book_genre","column_name":"book_id","foreign_table_name":"book","foreign_column_name":"id"}]';
	$j = json_encode($fks);
}

//asrt($j,$json);


$j1 = json_decode($j,true);
$j2 = json_decode($json,true);

//print_r($j1);
//print_r($j2);

foreach($j1 as $jrow) {
	$s = json_encode($jrow);
	$found = 0;
	foreach($j2 as $k=>$j2row) {
		if (json_encode($j2row)===$s) {
			pass();
			unset($j2[$k]);
			$found = 1;
		}
	}
	if (!$found) fail();
}

testpack('Test issue #90 - cannot trash bean with ownproperty if checked in model');
droptables();
class Model_Box extends RedBean_SimpleModel {
        public function delete() {
                $a = $this->bean->ownBottle;
        }
}

$s = R::dispense('box');
$s->name = 'a';
$f = R::dispense('bottle');
$s->ownBottle[] = $f;
R::store($s);
$s2 = R::dispense('box');
$s2->name = 'a';
R::store($s2);
R::trash($s2);
pass();

testpack('Test Nuke()');
for($i=0; $i<$nukepass; $i++) pass();
R::nuke();
$bean = R::dispense('bean');
R::store($bean);
asrt(count(R::$writer->getTables()),1);
R::nuke();
asrt(count(R::$writer->getTables()),0);
$bean = R::dispense('bean');
R::store($bean);
asrt(count(R::$writer->getTables()),1);
R::freeze();
R::nuke();
asrt(count(R::$writer->getTables()),1); //no effect



$keywords = array('anokeyword','znokeyword','group','DROP','inner','JOIN','select',
'table','int','cascade','float','CALL','in','status','order',
'limit','having','else','if','while','distinct','like');

$counter = 0;

foreach($keywords as $k) {
	R::nuke();
	$bean = R::dispense($k);
	$bean->$k = $k;
	$id = R::store($bean);
	$bean = R::load($k,$id);
	$bean2 = R::dispense('other');
	$bean2->name = $k;
	$bean->bean = $bean2;
	$bean->ownBean[] = $bean2;
	$bean->sharedBean[] = $bean2;
	$id = R::store($bean);

	R::view('perspective',$k.',other');
	$e = new RedBean_Plugin_BeanExport(R::$toolbox);
	$e->loadSchema();
	$s=((unserialize($e->getSchema())));
	ksort($s);
	$s = json_encode($s);
	asrt((strlen($s)>20),true);
	
	if (!$counter) $refs1 = $s; 
	if ($counter==1) $refs2 = $s;
	
	if (str_replace('anokeyword',$k,$refs1)===$s || str_replace('znokeyword',$k,$refs2)===$s) pass(); else fail(); 
	$counter++;
	R::trash($bean);
	
}





