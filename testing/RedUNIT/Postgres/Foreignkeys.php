<?php

namespace RedUNIT\Postgres;

use RedUNIT\Postgres as Postgres;
use RedBeanPHP\Facade as R;

/**
 * Foreignkeys
 *
 * Tests creation and validity of foreign keys,
 * foreign key constraints and indexes in PostgreSQL.
 * Also tests whether the correct contraint action has been selected.
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
class Foreignkeys extends Postgres
{
	/**
	 * Test foreign keys with postgres.
	 */
	public function testForeignKeysWithPostgres()
	{
		testpack( 'Test Postgres Foreign keys' );
		$a = R::getWriter()->addFK( 'a', 'b', 'c', 'd' ); //must fail
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
				"constraint_name": "book_genre_genre_id_fkey",
				"table_name": "book_genre",
				"column_name": "genre_id",
				"foreign_table_name": "genre",
				"foreign_column_name": "id"
			},
			{
				"constraint_name": "book_genre_book_id_fkey",
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

	/**
	 * Test constraint function directly in Writer.
	 *
	 * @return void
	 */
	public function testConstraint()
	{
		R::nuke();
		$database = R::getCell('SELECT current_database()');
		$sql = 'CREATE TABLE book (id SERIAL PRIMARY KEY)';
		R::exec( $sql );
		$sql = 'CREATE TABLE page (id SERIAL PRIMARY KEY)';
		R::exec( $sql );
		$sql = 'CREATE TABLE book_page (
			id SERIAL PRIMARY KEY,
			book_id INTEGER,
			page_id INTEGER
		)';
		R::exec( $sql );
		$writer = R::getWriter();
		$sql = "
			SELECT
				COUNT(*)
			FROM information_schema.key_column_usage AS k
			LEFT JOIN information_schema.table_constraints AS c ON c.constraint_name = k.constraint_name
			WHERE k.table_catalog = '$database'
				AND k.table_schema = 'public'
				AND k.table_name = 'book_page'
				AND c.constraint_type = 'FOREIGN KEY'";
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 0 );
		$writer->addFK( 'book_page', 'book', 'book_id', 'id', TRUE );
		$writer->addFK( 'book_page', 'page', 'page_id', 'id', TRUE );
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 2 );
		$writer->addFK( 'book_page', 'book', 'book_id', 'id', TRUE );
		$writer->addFK( 'book_page', 'page', 'page_id', 'id', TRUE );
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 2 );
	}

	/**
	 * Test adding foreign keys.
	 *
	 * @return void
	 */
	public function testAddingForeignKey()
	{
		R::nuke();
		$database = R::getCell('SELECT current_database()');
		$sql = 'CREATE TABLE book (
			id SERIAL PRIMARY KEY
		)';
		R::exec( $sql );
		$sql = 'CREATE TABLE page (
			id SERIAL PRIMARY KEY,
			book_id INTEGER
		)';
		R::exec( $sql );
		$writer = R::getWriter();
		$sql = "
			SELECT
				COUNT(*)
			FROM information_schema.key_column_usage AS k
			LEFT JOIN information_schema.table_constraints AS c ON c.constraint_name = k.constraint_name
			WHERE k.table_catalog = '$database'
				AND k.table_schema = 'public'
				AND k.table_name = 'page'
				AND c.constraint_type = 'FOREIGN KEY'";
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 0 );
		$writer->addFK('page', 'page', 'book_id', 'id', TRUE);
		$sql = "
			SELECT
				COUNT(*)
			FROM information_schema.key_column_usage AS k
			LEFT JOIN information_schema.table_constraints AS c ON c.constraint_name = k.constraint_name
			WHERE k.table_catalog = '$database'
				AND k.table_schema = 'public'
				AND k.table_name = 'page'
				AND c.constraint_type = 'FOREIGN KEY'";
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 1 );
		//dont add twice
		$writer->addFK('page', 'page', 'book_id', 'id', TRUE);
		$sql = "
			SELECT
				COUNT(*)
			FROM information_schema.key_column_usage AS k
			LEFT JOIN information_schema.table_constraints AS c ON c.constraint_name = k.constraint_name
			WHERE k.table_catalog = '$database'
				AND k.table_schema = 'public'
				AND k.table_name = 'page'
				AND c.constraint_type = 'FOREIGN KEY'";
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 1 );
		//even if it is different
		$writer->addFK('page', 'page', 'book_id', 'id', FALSE);
		$sql = "
			SELECT
				COUNT(*)
			FROM information_schema.key_column_usage AS k
			LEFT JOIN information_schema.table_constraints AS c ON c.constraint_name = k.constraint_name
			WHERE k.table_catalog = '$database'
				AND k.table_schema = 'public'
				AND k.table_name = 'page'
				AND c.constraint_type = 'FOREIGN KEY'";
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 1 );
		R::nuke();
		$sql = 'CREATE TABLE book (
			id SERIAL PRIMARY KEY
		)';
		R::exec( $sql );
		$sql = 'CREATE TABLE page (
			id SERIAL PRIMARY KEY,
			book_id INTEGER
		)';
		R::exec( $sql );
		$writer = R::getWriter();
		$sql = "
			SELECT
				COUNT(*)
			FROM information_schema.key_column_usage AS k
			LEFT JOIN information_schema.table_constraints AS c ON c.constraint_name = k.constraint_name
			WHERE k.table_catalog = '$database'
				AND k.table_schema = 'public'
				AND k.table_name = 'page'
				AND c.constraint_type = 'FOREIGN KEY'";
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 0 );
		$writer->addFK('page', 'page', 'book_id', 'id', FALSE);
		$sql = "
			SELECT
				COUNT(*)
			FROM information_schema.key_column_usage AS k
			LEFT JOIN information_schema.table_constraints AS c ON c.constraint_name = k.constraint_name
			WHERE k.table_catalog = '$database'
				AND k.table_schema = 'public'
				AND k.table_name = 'page'
				AND c.constraint_type = 'FOREIGN KEY'";
		$numFKS = R::getCell( $sql );
		asrt( (int) $numFKS, 1 );
	}

	/**
	 * Test whether we can manually create indexes.
	 *
	 * @return void
	 */
	public function testAddingIndex()
	{
		R::nuke();
		$sql = 'CREATE TABLE song (
			id SERIAL PRIMARY KEY,
			album_id INTEGER,
			category VARCHAR(255)
		)';
		R::exec( $sql );
		$indexes = R::getAll( " SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'song' ");
		asrt( count( $indexes ), 1 );
		$writer = R::getWriter();
		$writer->addIndex( 'song', 'index1', 'album_id' );
		$indexes = R::getAll( " SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'song' ");
		asrt( count( $indexes ), 2 );
		//Cant add the same index twice
		$writer->addIndex( 'song', 'index1', 'album_id' );
		$indexes = R::getAll( " SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'song' ");
		asrt( count( $indexes ), 2 );
		$writer->addIndex( 'song', 'index2', 'category' );
		$indexes = R::getAll( " SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'song' ");
		asrt( count( $indexes ), 3 );
		//Dont fail, just dont
		try {
			$writer->addIndex( 'song', 'index3', 'nonexistant' );
			pass();
		} catch( \Exception $e ) {
			fail();
		}
		$indexes = R::getAll( " SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'song' ");
		asrt( count( $indexes ), 3 );
		try {
			$writer->addIndex( 'nonexistant', 'index4', 'nonexistant' );
			pass();
		} catch( \Exception $e ) {
			fail();
		}
		$indexes = R::getAll( " SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'song' ");
		asrt( count( $indexes ), 3 );
	}
}
