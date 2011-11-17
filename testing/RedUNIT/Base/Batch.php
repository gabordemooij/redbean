<?php

class RedUNIT_Base_Batch extends RedUNIT_Base {

	public function run() {
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
			
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
	
	}	
}