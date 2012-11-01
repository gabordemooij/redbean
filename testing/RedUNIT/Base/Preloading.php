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
		
	}	
	
}