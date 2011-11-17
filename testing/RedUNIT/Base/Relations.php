<?php


class RedUNIT_Base_Relations extends RedUNIT_Base {

	public function run() {
		
				
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
	/*	if ($db=='pgsql' || $db=='mysql') {
			try{
				R::store($page1);
				fail();
			}
			catch(Exception $e){
				pass();
			}
		
		}*/
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
			//echo "\n bean start: ".print_r($book3,1);
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
				
		
	}

}