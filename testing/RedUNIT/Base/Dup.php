<?php
/**
 * RedUNIT_Base_Copy 
 * @file 			RedUNIT/Base/Copy.php
 * @description		Intensive test for dup()
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Dup extends RedUNIT_Base {

	

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		testpack('Dup() and Export() should not taint beans');
		R::nuke();
		
		$p = R::dispense('page');
		$b = R::dispense('book');
		$b->ownPage[] = $p;
		$b->title = 'a';
		$id = R::store($b);
		$b = R::load('book',$id);
		asrt((!$b->getMeta('tainted')),true);
		R::exportAll($b);
		asrt((!$b->getMeta('tainted')),true);
		R::dup($b);
		asrt((!$b->getMeta('tainted')),true);
		
		
		testpack('Test issue with ownItems and stealing Ids.');
		R::nuke();
		$bill = R::dispense('bill');
		$item = R::dispense('item');
		$element = R::dispense('element');
		$bill->ownItem[] = $item;
		$bill->sharedElement[] = $element;
		R::store($bill);
		$bill = R::load('bill',1);
		$bill->ownItem;
		$bill->sharedElement;
		$copy = R::dup($bill);
		R::store($copy);
		
		$rows=(R::getAll('select * from bill_element'));
		asrt(count($rows),2);
		
		$rows=(R::getAll('select * from item'));
		
		foreach($rows as $row) {
			asrt(($row['bill_id']>0),true);
		}
		
		R::nuke();
		$this->runOnce();
		R::freeze(true);
		$this->runOnce(false);
		R::freeze(false);
		
		testpack('Export with filters');
		R::nuke();
		$book = R::dispense('book');
		$pages = R::dispense('page',2);
		$author = R::dispense('author');
		$book->ownPage = $pages;
		$book->author = $author;
		R::store($book);
		R::$duplicationManager->setTables( R::$writer->getTables() );
		$objects = (R::exportAll(array($book),true,array()));
		asrt(isset($objects[0]['ownPage']),true);
		asrt(count($objects[0]['ownPage']),2);
		asrt(isset($objects[0]['author']),true);
		$objects = (R::exportAll(array($book),true,array('page')));
		asrt(isset($objects[0]['ownPage']),false);
		$objects = (R::exportAll(array($book),true,array('author')));
		asrt(isset($objects[0]['author']),false);
		$objects = (R::exportAll(array($book),true,array('author','page')));
		asrt(isset($objects[0]['author']),false);
		asrt(isset($objects[0]['ownPage']),false);
		R::$duplicationManager->setCacheTables(false);
		
	}

	/**
	 * Compares object with export
	 * @param type $object
	 * @param type $array 
	 */
	public function compare($object,$array) {
		foreach($object as $property=>$value) {
			if (is_array($value)) {
					foreach($value as $index=>$nestedObject) {
						if ($nestedObject->id) {
							$foundMatch = false;
							//order might be different
							foreach($array[$property] as $k=>$a) {
								if ($a['id']==$nestedObject->id) {
									$foundMatch = true;
									$index = $k;
								}
							}
							if (!$foundMatch) throw new Exception('failed to find match for object '.$nestedObject->id);
						}
						$this->compare($nestedObject,$array[$property][$index]);
					}
			}
			elseif (!is_object($value)){
				asrt(strval($array[$property]),strval($value));
			}
		}
	}
	
	/**
	 * Run tests
	 */
	public function runOnce($n=true) {
	
		
		$books = R::dispense('book',10);
		$pages = R::dispense('page',10);
		$readers = R::dispense('reader',10);
		$texts = R::dispense('text',10);
		
		$i = 0;
		foreach($books as $book) $book->name = 'book-'.($i++);
		$i = 0;
		foreach($pages as $page) $page->name = 'page-'.($i++);
		$i = 0;
		foreach($readers as $reader) $reader->name = 'reader-'.($i++);
		$i = 0;
		foreach($texts as $text) $text->content = 'lorem ipsum -'.($i++);
		
		
		foreach($texts as $text) {
			$pages[array_rand($pages)]->ownText[] = $text;
		}
		foreach($pages as $page) {
			$books[array_rand($books)]->ownPage[] = $page;
		}
		foreach($readers as $reader) {
			$books[array_rand($books)]->sharedReader[] = $reader;
		}
		$i = $noOfReaders = $noOfPages = $noOfTexts = 0;
		foreach($books as $key=>$book) { 
			$i++;
			$noOfPages += count($book->ownPage);
			$noOfReaders += count($book->sharedReader);
			foreach($book->ownPage as $page) $noOfTexts += count($page->ownText); 
			$arr = R::exportAll($book);
			echo "\nIntermediate info: ".json_encode($arr).": Totals = $i,$noOfPages,$noOfReaders,$noOfTexts ";
			
			$this->compare($book,$arr[0]);
			$copiedBook = R::dup($book);
			$copiedBookArray = R::exportAll($copiedBook);
			$this->compare($book,$copiedBookArray[0]);
			$copiedBookArrayII = $copiedBook->export();
			$this->compare($book,$copiedBookArrayII);
			$copyFromCopy = R::dup($copiedBook);
			$copyFromCopyArray = R::exportAll($copyFromCopy);
			$this->compare($book,$copyFromCopyArray[0]);
			$copyFromCopyArrayII = $copyFromCopy->export();
			$this->compare($book,$copyFromCopyArrayII);
			$id = R::store($book);
			$copiedBook = R::dup($book);
			R::store($book); //should not be damaged
			$copiedBookArray = R::exportAll($copiedBook);
			$originalBookArray = R::exportAll($book);
			$this->compare($copiedBook,$copiedBookArray[0]);
			$this->compare($book,$originalBookArray[0]);
			$book = R::load('book',$id);
			$this->compare($book,$originalBookArray[0]);
			$copiedBook = R::dup($book);
			$this->compare($copiedBook,$copiedBook->export());
			R::store($copiedBook);
			$this->compare($copiedBook,$copiedBook->export());
			$copyFromCopy = R::dup($copiedBook);
			$this->compare($copyFromCopy,$copyFromCopy->export());
			R::store($copyFromCopy);
			$newPage = R::dispense('page');
			$newPage->name = 'new';
			$copyFromCopy->ownPage[] = $newPage;
			$modifiedCopy = R::dup($copyFromCopy);
			$exportMod = R::exportAll($modifiedCopy);
			$this->compare($modifiedCopy,$exportMod[0]);
			asrt(count($modifiedCopy->ownPage),count($copiedBook->ownPage)+1);
			R::store($modifiedCopy);
			
			if ($n) {
			asrt((int)R::getCell('SELECT count(*) FROM book'),$i*4);
			asrt((int)R::getCell('SELECT count(*) FROM page'),($noOfPages*4)+$i);
			asrt((int)R::getCell('SELECT count(*) FROM text'),$noOfTexts*4);
			asrt((int)R::getCell('SELECT count(*) FROM book_reader'),$noOfReaders*4);
			asrt((int)R::getCell('SELECT count(*) FROM reader'),$noOfReaders);
			}
			
		}
		
		if ($n) {
		asrt($noOfTexts,10);
		asrt($noOfReaders,10);
		asrt($noOfPages,10);
		asrt($i,10);
		}
	}
	
	
}