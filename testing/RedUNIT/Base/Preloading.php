<?php
/**
 * RedUNIT_Base_Preloading 
 * 
 * @file 			RedUNIT/Base/Preloading.php
 * @description		Tests eager loading for parent beans.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Preloading extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		//test without preload
		R::nuke();
		$books = R::dispense('book',3);
		$i=0;
		foreach($books as $book) {
			$i++;
			$book->name = $i;
			$book->ownPage[] = R::dispense('page')->setAttr('name',$i);
			$book->author = R::dispense('author')->setAttr('name',$i);
			$book->coauthor = R::dispense('author')->setArr('name',$i);
		}
		R::storeAll($books);
		$books = R::find('book');
		R::nuke();
		$i=0;
		foreach($books as $book) asrt($book->author->id,0);
		
		//test with preload
		R::nuke();
		$books = R::dispense('book',3);
		$i=0;
		foreach($books as $book) {
			$i++;
			$book->name = $i;
			$book->ownPage[] = R::dispense('page')->setAttr('name',$i);
			$book->author = R::dispense('author')->setAttr('name',$i);
			$book->coauthor = R::dispense('author')->setArr('name',$i);
		}
		R::storeAll($books);
		$books = R::find('book');
		R::preload($books,array('author'));
		R::nuke();
		$i=0;
		foreach($books as $book) asrt($book->author->name,strval(++$i));
		
		
		//test aliased preload
		R::nuke();
		$books = R::dispense('book',3);
		$i=0;
		foreach($books as $book) {
			$i++;
			$book->name = $i;
			$book->ownPage[] = R::dispense('page')->setAttr('name',$i);
			$book->author = R::dispense('author')->setAttr('name',$i);
			$book->coauthor = R::dispense('author')->setAttr('name',$i);
		}
		R::storeAll($books);
		
		$books = R::find('book');
		R::preload($books,array('coauthor'=>'author'));
		R::nuke();
		$i=0;
		foreach($books as $book) asrt($book->fetchAs('author')->coauthor->name,strval(++$i));
		
		//combined and multiple
		R::nuke();
		$books = R::dispense('book',3);
		$i=0;
		foreach($books as $book) {
			$i++;
			$book->name = $i;
			$book->ownPage[] = R::dispense('page')->setAttr('name',$i);
			$book->author = R::dispense('author')->setAttr('name',$i);
			$book->coauthor = R::dispense('author')->setAttr('name',$i);
			$book->collection = R::dispense('collection')->setAttr('name',$i);
		}
		R::storeAll($books);
		
		$books = R::find('book');
		R::preload($books,array('coauthor'=>'author','author','collection'));
		R::nuke();
		$i=0;
		foreach($books as $book) asrt($book->author->name,strval(++$i));
		$i=0;
		foreach($books as $book) asrt($book->fetchAs('author')->coauthor->name,strval(++$i));
		$i=0;
		foreach($books as $book) asrt($book->collection->name,strval(++$i));
		
		//Crud
		$books = R::dispense('book',3);
		$i=0;
		foreach($books as $book) {
			$i++;
			$book->name = $i;
			$book->ownPage[] = R::dispense('page')->setAttr('name',$i);
			$book->author = R::dispense('author')->setAttr('name',$i);
			$book->coauthor = R::dispense('author')->setAttr('name',$i);
			$book->collection = R::dispense('collection')->setAttr('name',$i);
		}
		R::storeAll($books);
		$books = R::find('book');
		R::preload($books,array('coauthor'=>'author','author','collection'));
		$i=0;
		foreach($books as $book) $book->author->name .= 'nth';
		$i=0;
		foreach($books as $book) $book->fetchAs('author')->coauthor->name .= 'nth';
		$i=0;
		foreach($books as $book) $book->collection->name .= 'nth';
		R::storeAll($books);
		$books = R::find('books');
		$i=0;
		foreach($books as $book) asrt($book->author->name,strval(++$i).'nth');
		$i=0;
		foreach($books as $book) asrt($book->fetchAs('author')->coauthor->name,strval(++$i).'nth');
		$i=0;
		foreach($books as $book) asrt($book->collection->name,strval(++$i).'nth');
		
		//test with multiple same parents
		R::nuke();
		$author = R::dispense('author');
		$author->setAttr('name', 'John');
		R::store($author);
		$books = R::dispense('book', 3);
		$books[0]->title = 'First book';
		$books[1]->title = 'Second book';
		$books[2]->title = 'Third book';
		$author->ownBook = $books;
		R::store($author);
		$collection = R::findAll('book');
		R::preload($collection, array('author'));
		R::nuke();
		foreach ($collection as $item) {
			asrt($item->author->name,'John');
		}
		
		//test nested bean preloading
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->ownBook = R::dispense('book',2);
			foreach($author->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				foreach($book->ownPage as $page) $page->ownText[] = R::dispense('text',1);
			}
		}
		R::storeAll($authors);
		$texts = R::find('text');
		R::nuke();
		$text = reset($texts);
		asrt((int)($text->page->id),0);
		
		//now with preloading
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->ownBook = R::dispense('book',2);
			foreach($author->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',2);
			}
		}
		R::storeAll($authors);
		$texts = R::find('text');
		R::preload($texts,array('page','page.book','page.book.author'));
		R::nuke();
		$text = reset($texts);
		asrt(($text->page->id)>0,true);
		asrt(($text->page->book->id)>0,true);
		asrt(($text->page->book->author->id)>0,true);
		
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->alias('coauthor')->ownBook = R::dispense('book',2);
			foreach($author->alias('coauthor')->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',2);
			}
		}
		R::storeAll($authors);
		$texts = R::find('text');
		R::preload($texts,array('page','page.book','page.book.coauthor'=>'author'));
		R::nuke();
		$text = reset($texts);
		asrt(($text->page->id)>0,true);
		asrt(($text->page->book->id)>0,true);
		asrt(($text->page->book->fetchAs('author')->coauthor->id)>0,true);
		
		//now test preloading of own-lists
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->ownBook = R::dispense('book',2);
			foreach($author->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',2);
			}
		}
		R::storeAll($authors);
		$authors = R::find('author');
		R::preload($authors,array('ownBook'=>'book','ownBook.ownPage'=>'page','ownBook.ownPage.ownText'=>'text'));
		R::nuke();
		$author = reset($authors);
		asrt(count($author->ownBook),2);
		$book = reset($author->ownBook);
		asrt(count($book->ownPage),2);
		$page = reset($book->ownPage);
		asrt(count($page->ownText),2);
		
		//test preloading of shared lists
		R::nuke();
		list($a1,$a2,$a3) = R::dispense('army',3);
		list($v1,$v2,$v3) = R::dispense('village',3);
		$v1->name = 'a'; $v2->name='b'; $v3->name = 'c';
		$a1->name = 'one'; $a2->name = 'two'; $a3->name = 'three';
		$a1->sharedVillage = array($v1,$v3);
		$a2->sharedVillage = array($v3,$v1,$v2);
		$a3->sharedVillage = array();
		list($g,$e) = R::dispense('people',2);
		$g->nature = 'good';
		$e->nature = 'evil';
		$v1->sharedPeople = array( $g );
		$v2->sharedPeople = array( $e,$g );
		$v3->sharedPeople = array( $g );
		R::storeAll(array($a1,$a2,$a3));
		$armies = R::find('army');
		R::each($armies,array('sharedVillage'=>'village','sharedVillage.sharedPeople'=>'people'),function($army,$villages,$people){
			if ($army->name == 'one') {
				$names = array();
				foreach($villages as $village) {
					$names[] = $village->name;
				}
				sort($names);
				$names = implode(',',$names);
				asrt($names,'a,c');
			}
			if ($army->name == 'two') {
				$names = array();
				foreach($villages as $village) {
					$names[] = $village->name;
				}
				sort($names);
				$names = implode(',',$names);
				asrt($names,'a,b,c');
			}
			if ($army->name == 'three') {
				asrt(count($villages),0);
			}
		});
		
		R::nuke();
		
		foreach($armies as $army) {
			$villages = $army->sharedVillage;
			$ppl = array();
			foreach($villages as $village) {
				$ppl = array_merge($ppl, $village->sharedPeople);
			}
			if ($army->name == 'one') {
				asrt(count($villages),2);
				asrt(count($ppl),2);
				foreach($ppl as $p) {
					if ($p->nature !== 'good') fail();
				}
				$names = array();
				foreach($villages as $village) {
					$names[] = $village->name;
				}
				sort($names);
				$names = implode(',',$names);
				asrt($names,'a,c');
				
				$natures = array();
				foreach($ppl as $p) {
					$natures[] = $p->nature;
				}
				sort($natures);
				$natures = implode(',',$natures);
				asrt($natures,'good,good');
			}
			
			if ($army->name == 'two') {
				asrt(count($villages),3);
				asrt(count($ppl),4);
				$names = array();
				foreach($villages as $village) {
					$names[] = $village->name;
				}
				sort($names);
				$names = implode(',',$names);
				asrt($names,'a,b,c');
				
				$natures = array();
				foreach($ppl as $p) {
					$natures[] = $p->nature;
				}
				sort($natures);
				$natures = implode(',',$natures);
				asrt($natures,'evil,good,good,good');
			}
			if ($army->name == 'three') {
				asrt(count($villages),0);
				asrt(count($ppl),0);
			}
		}
		
		//now test with empty beans
		$authors = R::dispense('author',2);
		R::storeAll($authors);
		$authors = R::find('author');
		R::preload($authors,array('ownBook'=>'book','ownBook.ownPage'=>'page','ownBook.ownPage.ownText'=>'text'));
		$author = reset($authors);
		asrt(count($author->ownBook),0);
		
		$texts = R::dispense('text',2);
		R::storeAll($texts);
		$texts = R::find('text');
		R::preload($texts,array('page','page.book'));
		$text = reset($texts);
		asrt($text->page,null);
		
		
		
		//test with closure
		R::nuke();
		$books = R::dispense('book',3);
		$i=0;
		foreach($books as $book) {
			$i++;
			$book->name = $i;
			$book->ownPage[] = R::dispense('page')->setAttr('name',$i);
			$book->author = R::dispense('author')->setAttr('name',$i);
			$book->coauthor = R::dispense('author')->setArr('name',$i);
		}
		R::storeAll($books);
		$books = R::find('book');
		$hasNuked = false;
		R::preload($books,'author',function($book,$author){
			global $hasNuked;
			if (!$hasNuked) { R::nuke(); $hasNuked = true; }
			asrt($book->getMeta('type'),'book');
			asrt($author->getMeta('type'),'author');
		});
		
		//test with closure and abbrevations
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->ownBook = R::dispense('book',2);
			foreach($author->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',2);
			}
		}
		R::storeAll($authors);
		$texts = R::find('text');
		$hasNuked = false;
		R::preload($texts,'page,*.book,*.author',function($text,$page,$book,$author)use(&$hasNuked){
			if (!$hasNuked) { R::nuke(); $hasNuked = true; }
			asrt($text->getMeta('type'),'text');
			asrt($page->getMeta('type'),'page');
			asrt($book->getMeta('type'),'book');
			asrt($author->getMeta('type'),'author');
		});
		
		//test with closure and abbrevations and same-level abbr
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->ownBook = R::dispense('book',2);
			foreach($author->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',2);
			}
		}
		foreach($authors as $author) {
			foreach($author->ownBook as $book) {
				$book->shelf = R::dispense('shelf')->setAttr('name','abc');
			}
		}
		R::storeAll($authors);
		$texts = R::find('text');
		$hasNuked = false;
		R::preload($texts,'page,*.book,*.author,&.shelf',function($text,$page,$book,$author,$shelf)use(&$hasNuked){
			if (!$hasNuked) { R::nuke(); $hasNuked = true; }
			asrt($text->getMeta('type'),'text');
			asrt($page->getMeta('type'),'page');
			asrt(($page->id>0),true);
			asrt($book->getMeta('type'),'book');
			asrt(($book->id>0),true);
			asrt($author->getMeta('type'),'author');
			asrt($shelf->getMeta('type'),'shelf');
		});
		
		//test with closure, abbreviation and own-list
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->ownBook = R::dispense('book',2);
			foreach($author->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',2);
			}
		}
		R::storeAll($authors);
		$pages = R::find('page');
		$hasNuked = false;
		R::preload($pages,array('book','*.author','ownText'=>'text'),function($page,$book,$author,$texts)use(&$hasNuked){
			if (!$hasNuked) { R::nuke(); $hasNuked = true; }
			asrt($page->getMeta('type'),'page');
			asrt(($page->id>0),true);
			asrt($book->getMeta('type'),'book');
			asrt(($book->id>0),true);
			asrt($author->getMeta('type'),'author');
			asrt(($author->id>0),true);
			asrt(is_array($texts),true);
			asrt(count($texts),2);
			$first = reset($texts);
			asrt($first->getMeta('type'),'text');
		});
	
		//test variations
		R::nuke();
		$authors = R::dispense('author',2);
		foreach($authors as $author) { 
			$author->ownBook = R::dispense('book',2);
			foreach($author->ownBook as $book) {
				$book->ownPage = R::dispense('page',2);
				$book->cover = R::dispense('cover',1);
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',2);
				foreach($book->ownPage as $page) $page->ownPicture = R::dispense('picture',3);
		
			}
		}
		R::storeAll($authors);
		$texts = R::find('text');
		$hasNuked = false;
		$i = 0;
		R::each($texts,array(
			'page',
			'*.ownPicture'=>'picture',
			'&.book',
			'*.cover',
			'&.author'
			),
			function($t,$p,$x,$b,$c,$a)use(&$hasNuked,&$i){
				if (!$hasNuked) { R::nuke(); $hasNuked = true; }
				$i++;
				asrt(count($x),3);
				asrt(($p->id>0),true);
				asrt(($c->id>0),true);
				asrt(($t->id>0),true);
				asrt(($b->id>0),true);
				asrt(($a->id>0),true);
				asrt($t->getMeta('type'),'text');
				asrt($p->getMeta('type'),'page');
				asrt($c->getMeta('type'),'cover');
				asrt($b->getMeta('type'),'book');
				asrt($a->getMeta('type'),'author');
				$x1 = reset($x);
				asrt($x1->getMeta('type'),'picture');
			}
		);
		asrt($i,16); //follows the first parameter 

		R::nuke();
		R::$writer->setUseCache(true); //extra, test in combination with writer cache
		
		$villages = R::dispense('village',3);
		foreach($villages as $v) $v->ownBuilding = R::dispense('building',3);
		foreach($villages as $v) foreach($v->ownBuilding as $b) $b->ownFurniture = R::dispense('furniture',2);
		$armies = R::dispense('army',3);
		$villages[0]->sharedArmy = array($armies[1],$armies[2]);
		$villages[1]->sharedArmy = array($armies[0],$armies[1]);
		$villages[2]->sharedArmy = array($armies[2]);
		$soldiers = R::dispense('soldier',4);
		$armies[0]->sharedSoldier = array($soldiers[0],$soldiers[1],$soldiers[2]);
		$armies[1]->sharedSoldier = array($soldiers[2],$soldiers[1]);
		$armies[2]->sharedSoldier = array($soldiers[2]);
		$counter = 0; foreach($villages as $v) $v->name = $counter++;
		$counter = 0; foreach($armies as $a) $a->name = $counter++;
		$counter = 0; foreach($soldiers as $s) $s->name = $counter++;
		$buildings = R::dispense('building',4);
		$villages[0]->ownBuilding = array($buildings[0]);
		$villages[1]->ownBuilding = array($buildings[1],$buildings[2]);
		$villages[2]->ownBuilding = array($buildings[3]);
		$counter = 0; foreach($buildings as $b) $b->name = $counter++;
		$books = R::dispense('book',5);
		$counter = 0; foreach($books as $b) $b->name = $counter++;
		$buildings[0]->ownBook = array($books[0],$books[1]);
		$buildings[1]->ownBook = array($books[2]);
		$buildings[2]->ownBook = array($books[3],$books[4]);
		$world = R::dispense('world');
		$world->name = 'w1';
		$villages[1]->world = $world;
		R::storeAll($villages);
		$towns = R::find('village');
		$counter = 0;
		R::each($towns,array('sharedArmy'=>'army','sharedArmy.sharedSoldier'=>'soldier','ownBuilding'=>'building','ownBuilding.ownBook'=>'book','world'),function($t,$a,$s,$b,$x,$w) use(&$counter) {
			if ($counter === 0) {
				asrt($w,null);
				asrt((string)$t->name,'0');
				asrt(count($t->sharedArmy),2);
				$list = array(); foreach($a as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'1,2');
				$list = array(); foreach($s as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'1,2');
				$list = array(); foreach($b as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0');
				$list = array(); foreach($x as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0,1');
				
				$first = reset($a);
				asrt($first->getMeta('type'),'army');
				$first = reset($s);
				asrt($first->getMeta('type'),'soldier');
				$first = reset($b);
				asrt($first->getMeta('type'),'building');
				$first = reset($x);
				asrt($first->getMeta('type'),'book');
				
				
			}
			elseif ($counter === 1) {
				asrt($w->name,'w1');
				asrt((string)$t->name,'1');
				asrt(count($t->sharedArmy),2);
				$list = array(); foreach($a as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0,1');
				$list = array(); foreach($s as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0,1,2');
				$list = array(); foreach($b as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'1,2');
				$list = array(); foreach($x as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'2,3,4');
				
				$first = reset($a);
				asrt($first->getMeta('type'),'army');
				$first = reset($s);
				asrt($first->getMeta('type'),'soldier');
				$first = reset($b);
				asrt($first->getMeta('type'),'building');
				$first = reset($x);
				asrt($first->getMeta('type'),'book');
			}
			elseif ($counter === 2) {
				asrt($w,null);
				asrt((string)$t->name,'2');
				asrt(count($t->sharedArmy),1);
				$list = array(); foreach($a as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'2');
				$list = array(); foreach($s as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'2');
				$list = array(); foreach($b as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'3');
				asrt(count($x),0);
				
				$first = reset($a);
				asrt($first->getMeta('type'),'army');
				$first = reset($s);
				asrt($first->getMeta('type'),'soldier');
				$first = reset($b);
				asrt($first->getMeta('type'),'building');
			}
			$counter++;
	
		});
		
		R::nuke();
		R::$writer->setUseCache(false);
		$books = R::dispense('book',4);
		foreach($books as $b) $b->ownPage = R::dispense('page',2);
		foreach($books as $b) $b->sharedAd = R::dispense('ad',2);
		R::storeAll($books);
		$books = R::find('book');
		R::preload($books,array(
				'ownPage' => array('page',array(' AND id > 0 LIMIT ? ', array(2))),
				'sharedAd' => array('ad',array(' AND id > 0 LIMIT ? ',array(4)))
			)
		);
		asrt(count($books[1]->ownPage),2);
		asrt(count($books[1]->sharedAd),2);
		asrt(count($books[2]->ownPage),0);
		asrt(count($books[2]->sharedAd),2);
		asrt(count($books[3]->ownPage),0);
		asrt(count($books[3]->sharedAd),0);
		
		
		
		R::nuke();
		R::$writer->setUseCache(false);
		
		$villages = R::dispense('village',3);
		foreach($villages as $v) $v->ownBuilding = R::dispense('building',3);
		foreach($villages as $v) foreach($v->ownBuilding as $b) $b->ownFurniture = R::dispense('furniture',2);
		$armies = R::dispense('army',3);
		$villages[0]->sharedArmy = array($armies[1],$armies[2]);
		$villages[1]->sharedArmy = array($armies[0],$armies[1]);
		$villages[2]->sharedArmy = array($armies[2]);
		$soldiers = R::dispense('soldier',4);
		$armies[0]->sharedSoldier = array($soldiers[0],$soldiers[1],$soldiers[2]);
		$armies[1]->sharedSoldier = array($soldiers[2],$soldiers[1]);
		$armies[2]->sharedSoldier = array($soldiers[2]);
		$counter = 0; foreach($villages as $v) $v->name = $counter++;
		$counter = 0; foreach($armies as $a) $a->name = $counter++;
		$counter = 0; foreach($soldiers as $s) $s->name = $counter++;
		$buildings = R::dispense('building',4);
		$villages[0]->ownBuilding = array($buildings[0]);
		$villages[1]->ownBuilding = array($buildings[1],$buildings[2]);
		$villages[2]->ownBuilding = array($buildings[3]);
		$counter = 0; foreach($buildings as $b) $b->name = $counter++;
		$books = R::dispense('book',5);
		$counter = 0; foreach($books as $b) $b->name = $counter++;
		$buildings[0]->ownBook = array($books[0],$books[1]);
		$buildings[1]->ownBook = array($books[2]);
		$buildings[2]->ownBook = array($books[3],$books[4]);
		$world = R::dispense('world');
		$world->name = 'w1';
		$villages[1]->world = $world;
		R::storeAll($villages);
		$towns = R::find('village');
		$counter = 0;
		R::each($towns,array(
			'sharedArmy'=>'army',
			'sharedArmy.sharedSoldier' => array('soldier', array(' ORDER BY name DESC ',array())),
			'ownBuilding'=> array('building', array(' ORDER BY name DESC ',array())),
			'ownBuilding.ownBook'=>'book','world'),function($t,$a,$s,$b,$x,$w) use(&$counter) {
			if ($counter === 0) {
				asrt($w,null);
				asrt((string)$t->name,'0');
				asrt(count($t->sharedArmy),2);
				$list = array(); foreach($a as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'1,2');
				$list = array(); foreach($s as $item) $list[] = $item->name;
				asrt(implode(',',$list),'2,1');
				$list = array(); foreach($b as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0');
				$list = array(); foreach($x as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0,1');
				
				$first = reset($a);
				asrt($first->getMeta('type'),'army');
				$first = reset($s);
				asrt($first->getMeta('type'),'soldier');
				$first = reset($b);
				asrt($first->getMeta('type'),'building');
				$first = reset($x);
				asrt($first->getMeta('type'),'book');
				
				
			}
			elseif ($counter === 1) {
				asrt($w->name,'w1');
				asrt((string)$t->name,'1');
				asrt(count($t->sharedArmy),2);
				$list = array(); foreach($a as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0,1');
				$list = array(); foreach($s as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'0,1,2');
				$list = array(); foreach($b as $item) $list[] = $item->name;
				asrt(implode(',',$list),'2,1');
				$list = array(); foreach($x as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'2,3,4');
				
				$first = reset($a);
				asrt($first->getMeta('type'),'army');
				$first = reset($s);
				asrt($first->getMeta('type'),'soldier');
				$first = reset($b);
				asrt($first->getMeta('type'),'building');
				$first = reset($x);
				asrt($first->getMeta('type'),'book');
			}
			elseif ($counter === 2) {
				asrt($w,null);
				asrt((string)$t->name,'2');
				asrt(count($t->sharedArmy),1);
				$list = array(); foreach($a as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'2');
				$list = array(); foreach($s as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'2');
				$list = array(); foreach($b as $item) $list[] = $item->name;
				sort($list);
				asrt(implode(',',$list),'3');
				asrt(count($x),0);
				
				$first = reset($a);
				asrt($first->getMeta('type'),'army');
				$first = reset($s);
				asrt($first->getMeta('type'),'soldier');
				$first = reset($b);
				asrt($first->getMeta('type'),'building');
			}
			$counter++;
	
		});
	}
}
