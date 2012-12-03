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
				foreach($book->ownPage as $page) $page->ownText = R::dispense('text',1);
			}
		}
		R::storeAll($authors);
		$texts = R::find('text');
		R::nuke();
		$text = reset($texts);
		asrt(($text->page),null);
		
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
	}	
	
}