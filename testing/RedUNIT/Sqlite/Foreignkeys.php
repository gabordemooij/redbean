<?php
/**
 * RedUNIT_Sqlite_Foreignkeys
 * 
 * @file 			RedUNIT/Sqlite/Foreignkeys.php
 * @description		Tests the creation of foreign keys.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Sqlite_Foreignkeys extends RedUNIT_Sqlite {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		$book = R::dispense('book');
		$page = R::dispense('page');
		$cover = R::dispense('cover');
		list($g1,$g2) = R::dispense('genre',2);
		$g1->name = '1';
		$g2->name = '2';
		$book->ownPage = array($page);
		$book->cover = $cover;
		$book->sharedGenre = array($g1,$g2);
		R::store($book);
		
		$fkbook = R::getAll('pragma foreign_key_list(book)');
		$fkgenre = R::getAll('pragma foreign_key_list(book_genre)');
		$fkpage = R::getAll('pragma foreign_key_list(page)');
	
		
		
		asrt($fkpage[0]['from'],'book_id');
		asrt($fkpage[0]['to'],'id');
		asrt($fkpage[0]['table'],'book');
		
		asrt(count($fkgenre),2);
		
		if ($fkgenre[0]['from']=='book') {
			asrt($fkgenre[0]['to'],'id');
			asrt($fkgenre[0]['table'],'book');
		}
		if ($fkgenre[0]['from']=='genre') {
			asrt($fkgenre[0]['to'],'id');
			asrt($fkgenre[0]['table'],'genre');
		}
		
		asrt($fkbook[0]['from'],'cover_id');
		asrt($fkbook[0]['to'],'id');
		asrt($fkbook[0]['table'],'cover');
		
	}
	
}