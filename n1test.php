<?php

error_reporting(E_ALL | E_STRICT);
require("RedBean/redbean.inc.php");
R::setup("mysql:host=localhost;dbname=oodb","root");
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



R::exec('drop table if exists author_page');
R::exec('drop table if exists book_tag');
R::exec('drop table if exists book_genre');
R::exec('drop table if exists page_tag');
R::exec('drop table if exists book_page');
R::exec('drop table if exists book_tag');
R::exec('drop table if exists page_topic');
R::exec('drop table if exists book_topic');
R::exec('drop table if exists book_cover');
R::exec('drop table if exists page');
R::exec('drop table if exists picture');
R::exec('drop table if exists quote');
R::exec('drop table if exists book');
R::exec('drop table if exists topic');
R::exec('drop table if exists cover');
list($q1,$q2) = R::dispense('quote',2);
list($pic1,$pic2) = R::dispense('picture',2);
list($book,$book2,$book3) = R::dispense('book',3);
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
$page1->book_id = 0;
R::store($page1);
$b2 = R::load('book',$book2->id);
asrt(count($b2->ownPage),0);
//re-add the page
$b2->ownPage[] = $page1;
R::store($b2);
$b2 = R::load('book',$book2->id);
asrt(count($b2->ownPage),1);
//even uglier way, but still needs to work
$page1 = reset($b2->ownPage);
$page1->book_id = 0;
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

//test aliasing
//test with alias format
class Aliaser implements RedBean_IBeanFormatter {
	public function formatBeanID($t){ return 'id'; }
	public function formatBeanTable($t){ return $t; }
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
//R::debug(1);
R::store($book);
//print_r($logger->getLogs());
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
//$book=R::load('book',1);
$book->sharedTopic[] = $topic3; 
//now do NOT clear all and then add one, just add the one
//R::debug(1);
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
//R::debug(1);
$book->ownPage = array();
R::store($book);
asrt(count($book->ownPage),0);
$book->ownPage[] = $page1;
$book->ownPage['a'] = $page2;
asrt(count($book->ownPage),2);
R::store($book);
//($book->ownPage);
//keys have been renum, so this has no effect:
unset($book->ownPage['a']);
asrt(count($book->ownPage),2);
unset($book->ownPage[11]);
R::store($book);
$book=R::load('book',1);
asrt(count($book->ownPage),1);
//$pageInBook = reset($book->ownPage);
//$pageInBook->title .= ' changed ';
$aPage = $book->ownPage[10];
unset($book->ownPage[10]);
$aPage->title .= ' changed ';
$book->ownPage['anotherPage'] = $aPage;
$logger->clear();
R::store($book);
asrt(count($logger->grep("SELECT")),0);
$book=R::load('book',1);
asrt(count($book->ownPage),1);
$ap = reset($book->ownPage);
asrt($ap->title,"pagina1 changed ");



//echo '------------------------------------------------------------------';
//R::debug(1);
//$page1 = R::load('page',$page1->id);
//$page3 = R::load('page',$page3->id);
//$page3->setMeta('tainted',true);
//$page1->setMeta('tainted',true);
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
//print_r($book3->ownPicture);
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
	//echo "\n bean before: ".json_encode(array($book3->ownQuote,$book3->ownPicture,$book3->sharedTopic));
	//echo "\n bean before: ".print_r($book3,1);
	$book3=R::load('book',R::store($book3));
	//echo "\n $qbefore == ".count($book3->ownQuote);
	//echo "\n $pbefore == ".count($book3->ownPicture);
	//echo "\n $tbefore == ".count($book3->sharedTopic);
	//echo "\n bean after: ".print_r($book3,1);
	//echo "\n bean after: ".json_encode(array($book3->ownQuote,$book3->ownPicture,$book3->sharedTopic));
	asrt(count($book3->ownQuote),$qbefore);
	asrt(count($book3->ownPicture),$pbefore);
	asrt(count($book3->sharedTopic),$tbefore);
	asrt(json_encode($book->ownQuote),$qjson);
	asrt(json_encode($book->ownPicture),$pjson);
	asrt(json_encode($book->sharedTopic),$tjson);
}


 
//graph
R::exec('drop table if exists army_village');
R::exec('drop table if exists cd_track');
R::exec('drop table if exists song_track');
R::exec('drop table if exists village');
R::exec('drop table if exists building');
R::exec('drop table if exists farmer');
R::exec('drop table if exists furniture');
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
//R::debug(1);
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
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v1"');
asrt($noOfBuildings,2);
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v2"');
asrt($noOfBuildings,1);
$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v3"');
asrt($noOfBuildings,1);
//what villages does not have furniture
$emptyHouses = R::getAll('select name,count(id_of_furniture) from people group by id having count(id_of_furniture) = 0');
asrt(count($emptyHouses),2);
foreach($emptyHouses as $empty){
	if ($empty['name']!=='v3') pass(); else fail();
}

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
//print_r($playList);
$id = R::store(reset($playList));
$play = R::load("playlist", $id);
//print_r($play);
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
//print_r($play);
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
//print_r($play);
asrt(count($play->ownTrack),2);
foreach($play->ownTrack as $track) {
	asrt(count($track->sharedSong),1);
	asrt(($track->cover instanceof RedBean_OODBBean),true);
}
$track = reset($play->ownTrack);
$song = reset($track->sharedSong);
asrt(intval($song->id),1);
asrt(($song->url),"changedurl");

