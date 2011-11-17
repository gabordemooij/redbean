<?php

class RedUNIT_Mysql_Foreignkeys extends RedUNIT_Mysql {

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
		
		/*if ($db=='sqlite') {
			$fkbook = R::getAll('pragma foreign_key_list(book)');
			$fkgenre = R::getAll('pragma foreign_key_list(book_genre)');
			$fkpage = R::getAll('pragma foreign_key_list(page)');
			$j = json_encode(array($fkbook,$fkgenre,$fkpage));
			$json = '[[{"id":"0","seq":"0","table":"cover","from":"cover_id","to":"id","on_update":"SET NULL","on_delete":"SET NULL","match":"NONE"}],[{"id":"0","seq":"0","table":"book","from":"book_id","to":"id","on_update":"NO ACTION","on_delete":"CASCADE","match":"NONE"},{"id":"1","seq":"0","table":"genre","from":"genre_id","to":"id","on_update":"NO ACTION","on_delete":"CASCADE","match":"NONE"}],[{"id":"0","seq":"0","table":"book","from":"book_id","to":"id","on_update":"SET NULL","on_delete":"SET NULL","match":"NONE"}]]';
		}*/
		
		//if ($db=='mysql') {
			$fkbook = R::getAll('describe book');
			$fkgenre = R::getAll('describe book_genre');
			$fkpage = R::getAll('describe cover');
			$j = json_encode(R::getAll('SELECT
			ke.referenced_table_name parent,
			ke.table_name child,
			ke.constraint_name
			FROM
			information_schema.KEY_COLUMN_USAGE ke
			WHERE
			ke.referenced_table_name IS NOT NULL
			ORDER BY
			constraint_name;'));
			$json = '[{"parent":"genre","child":"book_genre","constraint_name":"book_genre_ibfk_1"},{"parent":"book","child":"book_genre","constraint_name":"book_genre_ibfk_2"},{"parent":"cover","child":"book","constraint_name":"book_ibfk_1"},{"parent":"book","child":"page","constraint_name":"page_ibfk_1"}]';
		//}
		/*
		if ($db=='pgsql') {
			$sql="SELECT
			    tc.constraint_name, tc.table_name, kcu.column_name,
			    ccu.table_name AS foreign_table_name,
			    ccu.column_name AS foreign_column_name
			FROM
			    information_schema.table_constraints AS tc
			    JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
			    JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
			WHERE constraint_type = 'FOREIGN KEY' AND (tc.table_name='book' OR tc.table_name='book_genre' OR tc.table_name='page');";
			$fks=R::getAll($sql);
			$json='[{"constraint_name":"book_cover_id_fkey","table_name":"book","column_name":"cover_id","foreign_table_name":"cover","foreign_column_name":"id"},{"constraint_name":"page_book_id_fkey","table_name":"page","column_name":"book_id","foreign_table_name":"book","foreign_column_name":"id"},{"constraint_name":"fk65c02fc3a418eb08d0c7b3e8440204f3a","table_name":"book_genre","column_name":"genre_id","foreign_table_name":"genre","foreign_column_name":"id"},{"constraint_name":"fk65c02fc3a418eb08d0c7b3e8440204f3b","table_name":"book_genre","column_name":"book_id","foreign_table_name":"book","foreign_column_name":"id"}]';
			$j = json_encode($fks);
		}
		*/
		
		
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