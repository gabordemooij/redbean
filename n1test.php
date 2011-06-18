<?php

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
R::exec('drop table if exists book');
R::exec('drop table if exists topic');
R::exec('drop table if exists cover');
list($book,$book2,$book3) = R::dispense('book',3);
list($topic1, $topic2,$topic3,$topic4,$topic5) = R::dispense('topic',5);
list($page1,$page2,$page3,$page4,$page5,$page6,$page7) = R::dispense('page',7);
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
asrt(reset($book->ownPage)->id,'2');
asrt(R::count('page'),1);
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
//$page1->book_id = 0;
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



//graph
R::exec('drop table if exists army_village');
R::exec('drop table if exists village');
R::exec('drop table if exists building');
R::exec('drop table if exists farmer');
R::exec('drop table if exists furniture');
R::exec('drop table if exists army');
R::exec('drop table if exists people');
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
	if ($empty->name!=='v3') pass(); else fail();
}








