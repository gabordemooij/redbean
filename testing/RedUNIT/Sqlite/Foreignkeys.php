<?php

namespace RedUNIT\Sqlite;

use RedUNIT\Sqlite as Sqlite;
use RedBeanPHP\Facade as R;

/**
 * Foreignkeys
 *
 * @file    RedUNIT/Sqlite/Foreignkeys.php
 * @desc    Tests the creation of foreign keys.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Foreignkeys extends Sqlite
{
	/**
	 * addIndex should not trigger exception...
	 *
	 * @return void
	 */
	public function testIndexException()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->title = 'a';
		R::store( $book );
		try {
			R::getWriter()->addIndex( 'book' , '\'', 'title' );
			pass();
		} catch( \Exception $e ) {
			fail();
		}
		R::getWriter()->addIndex( 'book' , '\'', 'title' );
		pass();
	}

	/**
	 * Test foreign keys with SQLite.
	 *
	 * @return void
	 */
	public function testForeignKeysWithSQLite()
	{
		$book  = R::dispense( 'book' );
		$page  = R::dispense( 'page' );
		$cover = R::dispense( 'cover' );

		list( $g1, $g2 ) = R::dispense( 'genre', 2 );

		$g1->name          = '1';
		$g2->name          = '2';

		$book->ownPage     = array( $page );

		$book->cover       = $cover;

		$book->sharedGenre = array( $g1, $g2 );

		R::store( $book );

		$fkbook  = R::getAll( 'pragma foreign_key_list(book)' );
		$fkgenre = R::getAll( 'pragma foreign_key_list(book_genre)' );
		$fkpage  = R::getAll( 'pragma foreign_key_list(page)' );

		asrt( $fkpage[0]['from'], 'book_id' );
		asrt( $fkpage[0]['to'], 'id' );
		asrt( $fkpage[0]['table'], 'book' );

		asrt( count( $fkgenre ), 2 );

		if ( $fkgenre[0]['from'] == 'book' ) {
			asrt( $fkgenre[0]['to'], 'id' );
			asrt( $fkgenre[0]['table'], 'book' );
		}

		if ( $fkgenre[0]['from'] == 'genre' ) {
			asrt( $fkgenre[0]['to'], 'id' );
			asrt( $fkgenre[0]['table'], 'genre' );
		}

		asrt( $fkbook[0]['from'], 'cover_id' );
		asrt( $fkbook[0]['to'], 'id' );
		asrt( $fkbook[0]['table'], 'cover' );
	}

	/**
	 * Constrain test for SQLite Writer.
	 *
	 * @return void
	 */
	public function testConstrain()
	{
		R::nuke();

		$sql = 'CREATE TABLE book ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ';

		R::exec( $sql );

		$sql = 'CREATE TABLE page ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ';

		R::exec( $sql );

		$sql = 'CREATE TABLE book_page (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			book_id INTEGER,
			page_id INTEGER
		) ';

		R::exec( $sql );

		$sql = 'PRAGMA foreign_key_list("book_page")';

		$fkList = R::getAll( $sql );

		asrt( count( $fkList), 0 );

		$writer = R::getWriter();

		$writer->addFK( 'book_page', 'book', 'book_id', 'id', TRUE );
		$writer->addFK( 'book_page', 'page', 'page_id', 'id', TRUE );

		$sql = 'PRAGMA foreign_key_list("book_page")';

		$fkList = R::getAll( $sql );

		asrt( count( $fkList), 2 );

		$writer->addFK( 'book_page', 'book', 'book_id', 'id', TRUE );
		$writer->addFK( 'book_page', 'page', 'page_id', 'id', TRUE );

		$sql = 'PRAGMA foreign_key_list("book_page")';

		$fkList = R::getAll( $sql );

		asrt( count( $fkList), 2 );
	}

	/**
	 * Test adding foreign keys.
	 *
	 * @return void
	 */
	public function testAddingForeignKeys()
	{
		R::nuke();

		$sql = 'CREATE TABLE book ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ';

		R::exec( $sql );

		$sql = 'CREATE TABLE page ( id INTEGER PRIMARY KEY AUTOINCREMENT, book_id INTEGER ) ';

		R::exec( $sql );

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 0 );

		$writer = R::getWriter();

		$writer->addFK('page', 'book', 'book_id', 'id', TRUE);

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 1 );

		$writer->addFK('page', 'book', 'book_id', 'id', TRUE);

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 1 );

		$writer->addFK('page', 'book', 'book_id', 'id', FALSE);

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 1 );

		R::nuke();

		$sql = 'CREATE TABLE book ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ';

		R::exec( $sql );

		$sql = 'CREATE TABLE page ( id INTEGER PRIMARY KEY AUTOINCREMENT, book_id INTEGER ) ';

		R::exec( $sql );

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 0 );

		$writer = R::getWriter();

		$writer->addFK('page', 'book', 'book_id', 'id', FALSE);

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 1 );

		$writer->addFK('page', 'book', 'book_id', 'id', TRUE);

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 1 );

		$writer->addFK('page', 'book', 'book_id', 'id', FALSE);

		asrt( count( R::getAll(' PRAGMA foreign_key_list("page") ') ), 1 );

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
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			album_id INTEGER,
			category TEXT
		) ';

		R::exec( $sql );

		$writer = R::getWriter();

		$indexes = R::getAll('PRAGMA index_list("song") ');

		asrt( count( $indexes ), 0 );

		$writer->addIndex( 'song', 'index1', 'album_id' );

		$indexes = R::getAll('PRAGMA index_list("song") ');

		asrt( count( $indexes ), 1 );

		$writer->addIndex( 'song', 'index1', 'album_id' );

		$indexes = R::getAll('PRAGMA index_list("song") ');

		asrt( count( $indexes ), 1 );

		$writer->addIndex( 'song', 'index2', 'category' );

		$indexes = R::getAll('PRAGMA index_list("song") ');

		asrt( count( $indexes ), 2 );

		try {
			$writer->addIndex( 'song', 'index1', 'nonexistant' );
			pass();
		} catch ( \Exception $ex ) {
			fail();
		}

		$indexes = R::getAll('PRAGMA index_list("song") ');
		asrt( count( $indexes ), 2 );

		try {
			$writer->addIndex( 'nonexistant', 'index1', 'nonexistant' );
			pass();
		} catch ( \Exception $ex ) {
			fail();
		}

		$indexes = R::getAll('PRAGMA index_list("song") ');
		asrt( count( $indexes ), 2 );
	}
}
