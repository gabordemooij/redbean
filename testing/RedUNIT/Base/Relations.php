<?php
/**
 * RedUNIT_Base_Relations
 * 
 * @file 			RedUNIT/Base/Relations.php
 * @description		Tests N:1 relations, nested beans.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Relations extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
	
		
		testpack('Test new shared relations with link conditions');
		R::nuke();
		$w = R::$writer;
		list($b1, $b2) = R::dispense('book', 2);
		$b1->name = 'book1';
		$b2->name = 'book2';
		list($p1, $p2, $p3) = R::dispense('page', 3);
		$p1->text = 'page1';
		$p1->number = 3;
		$p2->text = 'page2';
		$p3->text = 'page3';
		$b1->link('book_page', array('order'=>1))->page = $p1;
		$b1->link('book_page', array('order'=>2))->page = $p2;
		$b2->link('book_page', array('order'=>1))->page = $p3;
		$b2->link('book_page', array('order'=>2))->page = $p2;
		$b2->link('book_page', array('order'=>3))->page = $p1;
		R::storeAll(array($b1, $b2));
		$b1 = R::load('book', $b1->id);
		$b2 = R::load('book', $b2->id);
		$pages = $b1->withCondition(' book_page.'.$w->esc('order').' = 2 ')->sharedPage;
		$page = reset($pages);
		asrt($page->text, 'page2');
		$pages = $b2->withCondition(' '.$w->esc('order').' = 3 ')->sharedPage;
		$page = reset($pages);
		asrt($page->text, 'page1');
		$b1 = R::load('book', $b1->id);
		$b2 = R::load('book', $b2->id);
		$pages = $b1->withCondition(' book_page.'.$w->esc('order').' < 3  AND page.number = 3')->sharedPage;
		$page = reset($pages);
		asrt($page->text, 'page1');
		$pages = $b2->withCondition(' '.$w->esc('order').' > 1  ORDER BY book_page.'.$w->esc('order').' ASC ')->sharedPage;
		$page = array_shift($pages);
		asrt($page->text, 'page2');
		$page = array_shift($pages);
		asrt($page->text, 'page1');
		
		testpack('Test new shared relations and cache');
		R::exec('UPDATE page SET '.$w->esc('number').' = 1 '); //why does this not destroy cache in psql? ah: An error occurred: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "page" of relation "page" does not exist
		R::$writer->setUseCache(true);
		$p1 = R::load('page', (int)$p1->id);
		R::exec(' UPDATE page SET '.$w->esc('number').' = 9 -- keep-cache'); //someone else changes the records. Cache remains.
		$b1 = R::load('book', $b1->id);
		$p1 = R::load('page', (int)$p1->id);
		asrt((int)$p1->number, 1);//yupz a stale cache, phantom read!
		$pages = $b1->withCondition(' book_page.'.$w->esc('order').' = 1 ')->sharedPage;
		$page = reset($pages);
		asrt((int)$page->number, 9);//inconsistent, sad but true, different query -> cache key is different
		//however, cache must have been invalidated by this query
		$p1 = R::load('page', (int)$p1->id);
		asrt((int)$page->number, 9); //yes! we're consistent again! -- as if the change just happened later!
		$b1->fresh()->withCondition(' book_page.'.$w->esc('order').' = 1 ')->sharedPage; //by doing this we keep getting 9 instead of 8
		R::exec(' UPDATE page SET '.$w->esc('number').' = 8 -- keep-cache'); //someone else is busy again...
		$b1 = R::load('book', $b1->id);
		$pages = $b1->withCondition(' book_page.'.$w->esc('order').' = 1 ')->sharedPage;
		$page = reset($pages);
		asrt((int)$page->number, 9); //yes! we get 9 instead of 8, why because the 
		//cache key has not changed, our last query was PAGE-BOOK-RELATION and now we ask for PAGE-BOOK-RELATION again.
		//if we would have used just a load page query we would have gotten the new value (8).... let's test that!
		R::exec(' UPDATE page SET '.$w->esc('number').' = 9');
		$p1 = R::load('page', (int)$p1->id);
		asrt((int)$page->number, 9);
		R::exec(' UPDATE page SET '.$w->esc('number').' = 8 -- keep-cache'); //someone else is busy again...
		$b1 = R::load('book', $b1->id);
		$pages = $b1->withCondition(' book_page.'.$w->esc('order').' = 1 ')->sharedPage;
		$page = reset($pages);
		asrt((int)$page->number, 8); //yes, keep-cache wont help, cache key changed!
		R::$writer->setUseCache(false);
		
		testpack('Test relatedCount()');
		R::nuke();
		list($d, $d2) = R::dispense('document', 2);
		list($p, $p2, $p3) = R::dispense('page', 3);
		$d->sharedPage = array($p, $p3);
		$d2->sharedPage = array($p, $p2, $p3);
		R::storeAll(array($d, $d2));
		
		asrt(R::relatedCount($d, 'page', ' WHERE page.id = ? ', array($p->id)), 1);
		asrt(R::relatedCount($d, 'page'), 2);
		asrt(R::relatedCount($d2, 'page'), 3);
		//now using bean methods..
		asrt($d2->countShared('page'), 3);
		asrt($d->countShared('page'), 2);
		//n-1 counts
		$author = R::dispense('author');
		$author->ownDocument = array($d, $d2);
		R::store($author);
		
		R::nuke();
		$book = R::dispense('book');
		$book->ownPage = R::dispense('page',10);
		$book2 = R::dispense('book');
		$book2->ownPage = R::dispense('page', 4);
		list($Bill, $James, $Andy) = R::dispense('person', 3);
		$book->author = $Bill;
		$book->coAuthor = $James;
		$book2->author = $Bill;
		$book2->coAuthor = $Andy;
		$book->price = 25;
		$book2->price = 50;
		$notes = R::dispense('note',10);
		$book->sharedNote = array($notes[0], $notes[1], $notes[2]);
		$book2->sharedNote = array($notes[3], $notes[4], $notes[1], $notes[0]);
		$books = R::dispense('book', 5);
		$books[2]->title = 'boe';
		$book->sharedBook = array($books[0], $books[1]);
		$book2->sharedBook = array($books[0], $books[2], $books[4]);
		R::storeAll(array($book, $book2));
		asrt($book->countOwn('page'),10);
		asrt($book->withCondition(' id < 5 ')->countOwn('page'), 4);
		asrt($Bill->alias('author')->countOwn('book'), 2);
		asrt($Andy->alias('coAuthor')->countOwn('book'), 1);
		asrt($James->alias('coAuthor')->countOwn('book'), 1);
		asrt($Bill->alias('author')->countOwn('book'), 2);
		asrt($book->countShared('note'), 3);
		asrt($book2->countShared('note'), 4);
		asrt($book2->countShared('book'), 3);
		asrt($book2->withCondition(' title = ? ',array('boe'))->countShared('book'), 1);
		testpack('Test via()');
		R::nuke();
		$d = R::dispense('doctor')->setAttr('name', 'd1');
		$p = R::dispense('patient')->setAttr('name', 'p1');
		$d->via('consult')->sharedPatient[] = $p;
		R::store($d);
		$d = R::load('doctor', $d->id);
		asrt(count($d->sharedPatient), 1);
		asrt(in_array('consult', R::$writer->getTables()), true);
		R::nuke();
		asrt(in_array('consult', R::$writer->getTables()), false);
		$d = R::dispense('doctor')->setAttr('name', 'd1');
		$p = R::dispense('patient')->setAttr('name', 'p1');
		R::associate($d,$p);
		asrt(in_array('consult', R::$writer->getTables()), true);
		
		testpack('Test fast-track linkBlock exceptions');
		//Fast track link block code should not affect self-referential N-M relations.
		R::nuke();
		list($donald, $mickey, $goofy, $pluto) = R::dispense('friend', 4);
		$donald->name = 'D';
		$mickey->name = 'M';
		$goofy->name = 'G';
		$pluto->name = 'P';
		$donald->sharedFriend = array($mickey, $goofy);
		$mickey->sharedFriend = array($pluto, $goofy);
		R::storeAll(array($mickey, $donald, $goofy, $pluto));
		$donald = R::load('friend', $donald->id);
		$mickey = R::load('friend', $mickey->id);
		$goofy = R::load('friend', $goofy->id);
		$pluto = R::load('friend', $pluto->id);
		$names = implode(',', R::gatherLabels($donald->sharedFriend));
		asrt($names, 'G,M');
		$names = implode(',', R::gatherLabels($goofy->sharedFriend));
		asrt($names, 'D,M');
		$names = implode(',', R::gatherLabels($mickey->sharedFriend));
		asrt($names, 'D,G,P');
		$names = implode(',', R::gatherLabels($pluto->sharedFriend));
		asrt($names, 'M');
		
		//now in combination with with() conditions...
		$donald = R::load('friend', $donald->id);
		$names = implode(',', R::gatherLabels($donald->withCondition(' name = ? ', array('M'))->sharedFriend));
		asrt($names, 'M');
		
		//now in combination with with() conditions...
		$donald = R::load('friend', $donald->id);
		$names = implode(',', R::gatherLabels($donald->with(' ORDER BY name ')->sharedFriend));
		asrt($names, 'G,M');
		
		//now counting
		$goofy = R::load('friend', $goofy->id);
		asrt((int)$goofy->countShared('friend'), 2);
		asrt((int)$donald->countShared('friend'), 2);
		asrt((int)$mickey->countShared('friend'), 3);
		asrt((int)$pluto->countShared('friend'), 1);
		
		testpack('Test list beautifications');
		R::nuke();
		$book = R::dispense('book');
		$page = R::dispense('page')->setAttr('name','a');
		$book->sharedPage[] = $page;
		$id = R::store($book);
		$book = R::load('book',$id);
		$p = reset( $book->ownBookPage );
		asrt($p->page->name,'a');
		$bean = R::dispense('bean');
		$bean->sharedAclRole[] = R::dispense('role')->setAttr('name','x');
		R::store($bean);
		asrt(R::count('role'),1);

		$aclrole = R::$redbean->dispense('acl_role');
		$aclrole->name = 'role';
		$bean = R::dispense('bean');
		$bean->sharedAclRole[] = $aclrole;
		R::store($bean);
		asrt(count($bean->sharedAclRole),1);
		
		testpack('Test list add/delete scenarios.');
		R::nuke();
		R::dependencies(array('page'=>array('book','paper')));

		$b = R::dispense('book');
		$p = R::dispense('page');
		$b->title = 'a';
		$p->name = 'b';
		$b->ownPage[] = $p;
		R::store($b);
		$b->ownPage = array();
		R::store($b);
		asrt(R::count('page'),0);
		$p = R::dispense('page');
		$z = R::dispense('paper');
		$z->ownPage[] = $p;
		R::store($z);
		asrt(R::count('page'),1);
		$z->ownPage = array();
		R::store($z);
		asrt(R::count('page'),0);
		$i=R::dispense('magazine');
		$i->ownPage[] = R::dispense('page');
		R::store($i);
		asrt(R::count('page'),1);
		$i->ownPage=array();
		R::store($i);
		asrt(R::count('page'),1);	
		R::dependencies(array());
		R::nuke();
		
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
		//another less elegant way to remove
		$page1->book = null;
		R::store($page1);
		$cols = R::getColumns('page');
		asrt(isset($cols['book']),false);
		$b2 = R::load('book',$book2->id);
		asrt(count($b2->ownPage),0);
		//re-add the page
		$b2->ownPage[] = $page1;
		R::store($b2);
		$b2 = R::load('book',$book2->id);
		asrt(count($b2->ownPage),1);
		//another less elegant... just plain ugly... way to remove
		$page1->book = false;
		R::store($page1);
		$cols = R::getColumns('page');
		asrt(isset($cols['book']),false);
		$b2 = R::load('book',$book2->id);
		asrt(count($b2->ownPage),0);
		//re-add the page
		$b2->ownPage[] = $page1;
		R::store($b2);
		$b2 = R::load('book',$book2->id);
		asrt(count($b2->ownPage),1);
	
		//not allowed to re-use the field for something else
		try { $page1->book = 1; fail(); } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = -2.1; fail(); } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = array(); fail(); } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = true; fail(); } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = 'null'; fail(); } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = new stdClass; } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = 'just a string'; } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = array('a'=>1); fail(); } catch(RedBean_Exception_Security $e) { pass(); }
		try { $page1->book = 0; fail(); } catch(RedBean_Exception_Security $e) { pass(); }
		
		
		//test fk, not allowed to set to 0
		$page1 = reset($b2->ownPage);
		$page1->book_id = 0;
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
		asrt($book->sharedTopic[1]->name,'tropics');
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
		asrt((R::relatedOne($topic3,'book') instanceof RedBean_OODBBean),true);
		$items = R::related($topic3,'book');
		$a = reset($items);
		asrt(R::relatedOne($topic3,'book')->id,$a->id);
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
		//test with alias format
		$book3->cover = $page6;
		$idb3=R::store($book3);
		$book3=R::load('book',$idb3);
		$justACover = $book3->fetchAs('page')->cover;
		asrt(($book3->cover instanceof RedBean_OODBBean),true);
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
		asrt(count($logger->grep("DELETE FROM")),0); //no deletes
		$book->sharedTopic['a'] = $topic3;
		unset($book->sharedTopic['a']);
		R::store($book);
		$book=R::load('book',1);
		asrt(count($book->sharedTopic),3);
		asrt(count($logger->grep("DELETE FROM")),0); //no deletes
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
		//if ($db=="mysql") asrt(count($logger->grep("SELECT")),0);
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
			for($x=0;$x<rand(1,20); $x++) modgr($book3,$quotes,$pictures,$topics); //do several mutations
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
		
		R::nuke();
		$village = R::dispense('village');
		$village->name = 'village';
		$home = R::dispense('building');
		$home->village = $village;
		$id = R::store($home);
		$home = R::load('building',$id);
		asrt($home->village->name,'village');
		asrt(R::count('village'),1);
		asrt(R::count('building'),1);
		R::trash($home);
		pass();
		asrt(R::count('village'),1);
		asrt(R::count('building'),0);
		
		//test N-M relations through intermediate beans
		R::nuke();
		list($mrA,$mrB,$mrC) = R::dispense('person',3);
		list($projA,$projB,$projC) = R::dispense('project',3);
		$projA->title = 'A';
		$projB->title = 'B';
		$projC->title = 'C';
		$participant = R::dispense('participant');
		$projA->link('participant',array('role'=>'manager'))->person = $mrA;
		$projA->link($participant->setAttr('role','developer'))->person = $mrB;
		$projB->link(R::dispense('participant')->setAttr('role','developer'))->person = $mrB;
		$projB->link('participant','{"role":"helpdesk"}')->person = $mrC;
		$projC->link('participant','{"role":"sales"}')->person = $mrC;
		R::storeAll(array($projA,$projB,$projC));
		$a = R::findOne('project',' title = ? ',array('A'));
		$b = R::findOne('project',' title = ? ',array('B'));
		$c = R::findOne('project',' title = ? ',array('C'));
		asrt(count($a->ownParticipant),2);
		asrt(count($b->ownParticipant),2);
		asrt(count($c->ownParticipant),1);
		$managers = $developers = 0;
		foreach($a->ownParticipant as $p) {
			if ($p->role === 'manager') {
				$managers ++;
			}
			if ($p->role === 'developer') {
				$developers ++;
			}
		}
		$p = reset($a->ownParticipant);
		asrt($p->person->getMeta('type'),'person');
		asrt(($p->person->id >0),true);
		asrt($managers,1);
		asrt($developers,1);
		asrt((int)R::count('participant'),5);
		asrt((int)R::count('person'),3);
		
		//test emulation of sharedList through intermediate beans
		R::nuke();
		list($v1,$v2,$v3) = R::dispense('village',3);
		list($a1,$a2,$a3) = R::dispense('army',3);
		$a1->name = 'one';
		$a2->name = 'two';
		$a3->name = 'three';
		$v1->name = 'Ville 1';
		$v2->name = 'Ville 2';
		$v3->name = 'Ville 3';
		$v1->link('army_village')->army = $a3;
		$v2->link('army_village')->army = $a2;
		$v3->link('army_village')->army = $a1;
		$a2->link('army_village')->village = $v1;
		$id1 = R::store($v1);
		$id2 = R::store($v2);
		$id3 = R::store($v3);
		$village1 = R::load('village',$id1);
		$village2 = R::load('village',$id2);
		$village3 = R::load('village',$id3);
		asrt(count($village1->sharedArmy),2);
		asrt(count($village2->sharedArmy),1);
		asrt(count($village3->sharedArmy),1);
		
		//test emulation via association renaming
		R::nuke();
		list($p1,$p2,$p3) = R::dispense('painting',3);
		list($m1,$m2,$m3) = R::dispense('museum',3);
		$p1->name = 'painting1';
		$p2->name = 'painting2';
		$p3->name = 'painting3';
		$m1->thename = 'a';
		$m2->thename = 'b';
		$m3->thename = 'c';
		R::renameAssociation('museum_painting','exhibited');
		R::renameAssociation(array('museum_museum'=>'center')); //also test array syntax
		$m1->link('center',array('name'=>'History Center'))->museum2 = $m2;
		$m1->link('exhibited','{"from":"2014-02-01","til":"2014-07-02"}')->painting = $p3;
		$m2->link('exhibited','{"from":"2014-07-03","til":"2014-10-02"}')->painting = $p3;
		$m3->link('exhibited','{"from":"2014-02-01","til":"2014-07-02"}')->painting = $p1;
		$m2->link('exhibited','{"from":"2014-02-01","til":"2014-07-02"}')->painting = $p2;
		R::storeAll(array($m1,$m2,$m3));
		list($m1,$m2,$m3) = array_values(R::findAll('museum',' ORDER BY thename ASC'));
		asrt(count($m1->sharedMuseum),1);
		asrt(count($m1->sharedPainting),1);
		asrt(count($m2->sharedPainting),2);
		asrt(count($m3->sharedPainting),1);
		$p3 = reset($m1->sharedPainting);
		asrt(count($p3->ownExhibited),2);
		asrt(count($m2->ownExhibited),2);
		R::storeAll(array($m1,$m2,$m3));
		list($m1,$m2,$m3) = array_values(R::findAll('museum',' ORDER BY thename ASC'));
		asrt(count($m1->sharedPainting),1);
		asrt(count($m2->sharedPainting),2);
		asrt(count($m3->sharedPainting),1);
		$p3 = reset($m1->sharedPainting);
		asrt(count($p3->ownExhibited),2);
		$paintings = $m2->sharedPainting;
		foreach($paintings as $painting) {
			if ($painting->name === 'painting2') {
				pass();	
				$paintingX = $painting;
			}
		}
		unset($m2->sharedPainting[$paintingX->id]);
		R::store($m2);
		$m2 = R::load('museum',$m2->id);
		asrt(count($m2->sharedPainting),1);
		$left = reset($m2->sharedPainting);
		asrt($left->name,'painting3');
		asrt(count($m2->ownExhibited),1);
		$exhibition = reset($m2->ownExhibited);
		asrt($exhibition->from,'2014-07-03');
		asrt($exhibition->til,'2014-10-02');
	}
}
