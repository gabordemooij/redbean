<?php
/**
 * RedUNIT_Base_Writecace
 *  
 * @file 			RedUNIT/Base/Writecache.php
 * @description		Tests the Query Writer cache implemented in the
 * 					abstract RedBean_QueryWriter_AQueryWriter class.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Writecache extends RedUNIT_Base {
	
	/**
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('mysql','pgsql','sqlite','CUBRID');
	}
		
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run(){
		
		testpack('Testing WriteCache Query Writer Cache');
		R::nuke();
		$logger = RedBean_Plugin_QueryLogger::getInstanceAndAttach(R::$adapter);
		$book = R::dispense('book')->setAttr('title','ABC');
		$book->ownPage[] = R::dispense('page');
		$id = R::store($book);
		
		//test load cache -- without
		$logger->clear();
		$book = R::load('book',$id);
		$book = R::load('book',$id);
		asrt(count($logger->grep('SELECT')),2);
		R::$writer->setUseCache(true); //with cache
		$logger->clear();
		$book = R::load('book',$id);
		$book = R::load('book',$id);
		asrt(count($logger->grep('SELECT')),1);
		R::$writer->setUseCache(false);
		
		//test find cache
		$logger->clear();
		$book = R::find('book');
		$book = R::find('book');
		asrt(count($logger->grep('SELECT')),2);
		R::$writer->setUseCache(true); //with cache
		$logger->clear();
		$book = R::find('book');
		$book = R::find('book');
		asrt(count($logger->grep('SELECT')),1);
		R::$writer->setUseCache(false);
		
		//test combinations
		$logger->clear();
		$book = R::findOne('book',' id = ? ', array($id));
		$book->ownPage;
		R::batch('book',array($id));
		$book = R::findOne('book',' id = ? ', array($id));
		$book->ownPage;
		R::batch('book',array($id));
		asrt(count($logger->grep('SELECT')),6);
		R::$writer->setUseCache(true); //with cache
		$logger->clear();
		$book = R::findOne('book',' id = ? ', array($id));
		$book->ownPage;
		R::batch('book',array($id));
		$book = R::findOne('book',' id = ? ', array($id));
		$book->ownPage;
		R::batch('book',array($id));
		asrt(count($logger->grep('SELECT')),3);
		R::$writer->setUseCache(false);
		
		//test auto flush
		$logger->clear();
		$book = R::findOne('book');
		$book->name = 'X';
		R::store($book);
		$book = R::findOne('book');
		asrt(count($logger->grep('SELECT *')),2);
		R::$writer->setUseCache(true); //with cache
		$logger->clear();
		$book = R::findOne('book');
		$book->name = 'Y';
		R::store($book); //will flush
		$book = R::findOne('book');
		asrt(count($logger->grep('SELECT *')),2); // now the same, auto flushed
		R::$writer->setUseCache(false);
		
		//test whether delete flushes as well (because uses selectRecord - might be a gotcha!)
		R::store(R::dispense('garbage')); 
		$garbage = R::findOne('garbage');
		$logger->clear();
		$book = R::findOne('book');
		R::trash($garbage);
		$book = R::findOne('book');
		asrt(count($logger->grep('SELECT *')),2);
		
		R::store(R::dispense('garbage')); 
		$garbage = R::findOne('garbage');
		R::$writer->setUseCache(true); //with cache
		$logger->clear();
		$book = R::findOne('book');
		R::trash($garbage);
		$book = R::findOne('book');
		asrt(count($logger->grep('SELECT *')),2); // now the same, auto flushed
		R::$writer->setUseCache(false);
		
		R::store(R::dispense('garbage')); 
		$garbage = R::findOne('garbage');
		R::$writer->setUseCache(true); //with cache
		$logger->clear();
		$book = R::findOne('book');
		R::$writer->selectRecord('garbage',array('id'=>array($garbage->id)),null,true);
		$book = R::findOne('book');
		asrt(count($logger->grep('SELECT *')),2); // now the same, auto flushed
		R::$writer->setUseCache(false);
		
	}
}