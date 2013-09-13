<?php
/**
 * RedUNIT_Mysql_Foreignkeys
 *
 * @file    RedUNIT/Mysql/Foreignkeys.php
 * @desc    Tests creation of foreign keys.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Mysql_Foreignkeys extends RedUNIT_Mysql
{
	/**
	 * Basic FK tests.
	 * 
	 * @return void
	 */
	public function testFKS()
	{
		$book  = R::dispense( 'book' );
		$page  = R::dispense( 'page' );
		$cover = R::dispense( 'cover' );

		list( $g1, $g2 ) = R::dispense( 'genre', 2 );

		$g1->name = '1';
		$g2->name = '2';

		$book->ownPage = array( $page );

		$book->cover = $cover;

		$book->sharedGenre = array( $g1, $g2 );

		R::store( $book );

		$fkbook  = R::getAll( 'describe book' );
		$fkgenre = R::getAll( 'describe book_genre' );
		$fkpage  = R::getAll( 'describe cover' );

		$j = json_encode( R::getAll( 'SELECT
		ke.referenced_table_name parent,
		ke.table_name child,
		ke.constraint_name
		FROM
		information_schema.KEY_COLUMN_USAGE ke
		WHERE
		ke.referenced_table_name IS NOT NULL
		AND ke.CONSTRAINT_SCHEMA="oodb"
		ORDER BY
		constraint_name;' ) );

		$json = '[
			{
				"parent": "genre",
				"child": "book_genre",
				"constraint_name": "book_genre_ibfk_1"
			},
			{
				"parent": "book",
				"child": "book_genre",
				"constraint_name": "book_genre_ibfk_2"
			},
			{
				"parent": "cover",
				"child": "book",
				"constraint_name": "cons_fk_book_cover_id_id"
			},
			{
				"parent": "book",
				"child": "page",
				"constraint_name": "cons_fk_page_book_id_id"
			}
		]';

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

	/**
	 * Test widen for constraint.
	 * 
	 * @return void
	 */
	public function testWideningColumnForConstraint()
	{
		testpack( 'widening column for constraint' );

		$bean1 = R::dispense( 'project' );
		$bean2 = R::dispense( 'invoice' );

		R::setStrictTyping( FALSE );

		$bean3 = R::dispense( 'invoice_project' );

		R::setStrictTyping( TRUE );

		$bean3->project_id = 1;
		$bean3->invoice_id = 2;

		R::store( $bean3 );

		$cols = R::getColumns( 'invoice_project' );

		asrt( $cols['project_id'], "tinyint(1) unsigned" );
		asrt( $cols['invoice_id'], "tinyint(3) unsigned" );

		R::$writer->addConstraint( $bean1, $bean2 );

		$cols = R::getColumns( 'invoice_project' );

		asrt( $cols['project_id'], "int(11) unsigned" );
		asrt( $cols['invoice_id'], "int(11) unsigned" );
	}
}
