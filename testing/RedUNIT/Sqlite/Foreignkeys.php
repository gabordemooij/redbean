<?php

class RedUNIT_Sqlite_Foreignkeys extends RedUNIT_Sqlite {

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
		$j = json_encode(array($fkbook,$fkgenre,$fkpage));
		$json = '[[{"id":"0","seq":"0","table":"cover","from":"cover_id","to":"id","on_update":"SET NULL","on_delete":"SET NULL","match":"NONE"}],[{"id":"0","seq":"0","table":"book","from":"book_id","to":"id","on_update":"NO ACTION","on_delete":"CASCADE","match":"NONE"},{"id":"1","seq":"0","table":"genre","from":"genre_id","to":"id","on_update":"NO ACTION","on_delete":"CASCADE","match":"NONE"}],[{"id":"0","seq":"0","table":"book","from":"book_id","to":"id","on_update":"SET NULL","on_delete":"SET NULL","match":"NONE"}]]';
		
		$j1 = json_decode($j,true);
		$j2 = json_decode($json,true);
		foreach($j1 as $jrow) {
			$s = json_encode($jrow);
			$found = 0;
			foreach($j2 as $k=>$j2row) {
				if (json_encode($j2row)===$s) {
					pass();
					unset($j2[$k]);
					$found = 1;
				}
			}
			if (!$found) fail();
		}
				
	}	
	
}