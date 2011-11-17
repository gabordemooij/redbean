<?php

class RedUNIT_Base_Null extends RedUNIT_Base {

	public function run() {
		
		
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
			
				
				
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
	}

}