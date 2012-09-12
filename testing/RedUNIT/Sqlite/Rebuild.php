<?php
/**
 * RedUNIT_Sqlite_Rebuild
 * 
 * @file			RedUNIT/Sqlite/Rebuild.php
 * @description		Test rebuilding of tables for SQLite
 *					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Sqlite_Rebuild extends RedUNIT_Sqlite {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		R::nuke();
		R::dependencies(array('page'=>array('book')));
		$book = R::dispense('book');
		$page = R::dispense('page');
		$book->ownPage[] = $page;
		$id = R::store($book);
		$book = R::load('book',$id);
		asrt(count($book->ownPage),1);
		asrt((int)R::getCell('SELECT COUNT(*) FROM page'),1);
		R::trash($book);
		asrt((int)R::getCell('SELECT COUNT(*) FROM page'),0);
		
		$book = R::dispense('book');
		$page = R::dispense('page');
		$book->ownPage[] = $page;
		$id = R::store($book);
		$book = R::load('book',$id);
		asrt(count($book->ownPage),1);
		asrt((int)R::getCell('SELECT COUNT(*) FROM page'),1);
		$book->added = 2;
		R::store($book);
		$book->added = 'added';
        R::store($book);
		R::trash($book);
        asrt((int)R::getCell('SELECT COUNT(*) FROM page'),0);

		
	}	
}
