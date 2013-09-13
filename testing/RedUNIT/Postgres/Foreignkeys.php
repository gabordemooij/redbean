<?php
/**
 * RedUNIT_Postgres_Foreignkeys
 *
 * @file    RedUNIT/Postgres/Foreignkeys.php
 * @desc    Tests the creation of foreign keys.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Postgres_Foreignkeys extends RedUNIT_Postgres
{
	/**
	 * Test foreign keys with postgres.
	 */
	public function testForeignKeysWithPostgres()
	{
		testpack( 'Test Postgres Foreign keys' );

		$a = R::$writer->addFK( 'a', 'b', 'c', 'd' ); //must fail

		pass(); //survive without exception

		asrt( $a, FALSE ); //must return false

		$book  = R::dispense( 'book' );
		$page  = R::dispense( 'page' );
		$cover = R::dispense( 'cover' );

		list( $g1, $g2 ) = R::dispense( 'genre', 2 );

		$g1->name = '1';
		$g2->name = '2';

		$book->ownPage     = array( $page );
		$book->cover       = $cover;
		$book->sharedGenre = array( $g1, $g2 );

		R::store( $book );

		$sql = "SELECT
		    tc.constraint_name, tc.table_name, kcu.column_name,
		    ccu.table_name AS foreign_table_name,
		    ccu.column_name AS foreign_column_name
		FROM
		    information_schema.table_constraints AS tc
		    JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
		    JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
		WHERE constraint_type = 'FOREIGN KEY' AND (tc.table_name='book' OR tc.table_name='book_genre' OR tc.table_name='page');";

		$fks = R::getAll( $sql );

		$json = '[
			{
				"constraint_name": "book_cover_id_fkey",
				"table_name": "book",
				"column_name": "cover_id",
				"foreign_table_name": "cover",
				"foreign_column_name": "id"
			},
			{
				"constraint_name": "page_book_id_fkey",
				"table_name": "page",
				"column_name": "book_id",
				"foreign_table_name": "book",
				"foreign_column_name": "id"
			},
			{
				"constraint_name": "fk65c02fc3a418eb08d0c7b3e8440204f3a",
				"table_name": "book_genre",
				"column_name": "genre_id",
				"foreign_table_name": "genre",
				"foreign_column_name": "id"
			},
			{
				"constraint_name": "fk65c02fc3a418eb08d0c7b3e8440204f3b",
				"table_name": "book_genre",
				"column_name": "book_id",
				"foreign_table_name": "book",
				"foreign_column_name": "id"
			}
		]';

		$j  = json_encode( $fks );
		$j1 = json_decode( $j, TRUE );
		$j2 = json_decode( $json, TRUE );

		foreach ( $j1 as $jrow ) {
			$s = json_encode( $jrow );

			$found = 0;
			foreach ( $j2 as $k => $j2row ) {
				if ( json_encode( $j2row ) === $s ) {
					pass();

					unset( $j2[$k] );

					$found = 1;
				}
			}

			if ( !$found ) fail();
		}
	}
}
