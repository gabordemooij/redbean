<?php

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;
use RedBeanPHP\Facade as R;

/**
 * Foreignkeys
 *
 * Tests creation and validity of foreign keys,
 * foreign key constraints and indexes in Mysql/MariaDB.
 * Also tests whether the correct contraint action has been selected.
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
class Foreignkeys extends Mysql
{
	/**
	 * Test whether we can use foreign keys with keywords.
	 *
	 * @return void
	 */
	public function testKWConflicts()
	{
		R::nuke();
		$metrics = R::dispense( 'metrics' );
		$constraint = R::dispense( 'constraint' );
		$constraint->xownMetrics[] = $metrics;
		R::store( $constraint );
		asrt( 1, R::count( 'metrics' ) );
		R::trash($constraint);
		asrt( 0, R::count( 'metrics') );
	}

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
		$j = strtolower(json_encode( R::getAll( 'SELECT
		ke.referenced_table_name parent,
		ke.table_name child,
		ke.constraint_name
		FROM
		information_schema.KEY_COLUMN_USAGE ke
		WHERE
		ke.referenced_table_name IS NOT NULL
		AND ke.CONSTRAINT_SCHEMA="oodb"
		ORDER BY
		constraint_name;' ) ));
		$json = '[
			{
				"parent": "genre",
				"child": "book_genre",
				"constraint_name": "c_fk_book_genre_genre_id"
			},
			{
				"parent": "book",
				"child": "book_genre",
				"constraint_name": "c_fk_book_genre_book_id"
			},
			{
				"parent": "cover",
				"child": "book",
				"constraint_name": "c_fk_book_cover_id"
			},
			{
				"parent": "book",
				"child": "page",
				"constraint_name": "c_fk_page_book_id"
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
					break;
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
		$bean3 = R::getRedBean()->dispense( 'invoice_project' );
		$bean3->project_id = FALSE;
		$bean3->invoice_id = TRUE;
		R::store( $bean3 );
		$cols = R::getColumns( 'invoice_project' );
		asrt( ( $cols['project_id'] == "int(11) unsigned" || $cols['project_id'] == "int unsigned" ), TRUE );
		asrt( ( $cols['invoice_id'] == "int(11) unsigned" || $cols['invoice_id'] == "int unsigned" ), TRUE );
	}

	/**
	 * Test adding of constraints directly by invoking
	 * the writer method.
	 *
	 * @return void
	 */
	public function testContrain()
	{
		R::nuke();
		$sql   = '
			CREATE TABLE book (
				id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$sql   = '
			CREATE TABLE page (
				id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$sql   = '
			CREATE TABLE book_page (
				id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				book_id INT( 11 ) UNSIGNED NOT NULL,
				page_id INT( 11 ) UNSIGNED NOT NULL,
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "book_page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 0 );
		$writer = R::getWriter();
		$writer->addFK( 'book_page', 'book', 'book_id', 'id', TRUE );
		$writer->addFK( 'book_page', 'page', 'page_id', 'id', TRUE );
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "book_page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 2 );
		$writer->addFK( 'book_page', 'book', 'book_id', 'id', TRUE );
		$writer->addFK( 'book_page', 'page', 'page_id', 'id', TRUE );
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "book_page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 2 );
	}

	/**
	 * Test adding foreign keys.
	 *
	 * @return void
	 */
	public function testAddingForeignKey()
	{
		R::nuke();
		$sql   = '
			CREATE TABLE book (
				id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$sql   = '
			CREATE TABLE page (
				id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				book_id INT( 11 ) UNSIGNED NOT NULL,
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 0 );
		$writer = R::getWriter();
		//Can we add a foreign key with cascade?
		$writer->addFK('page', 'book', 'book_id', 'id', TRUE);
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 1 );
		//dont add it twice
		$writer->addFK('page', 'book', 'book_id', 'id', TRUE);
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 1 );
		//even if different
		$writer->addFK('page', 'book', 'book_id', 'id', FALSE);
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" ');
		asrt( (int) $numOfFKS, 1 );
		//Now add non-dep key
		R::nuke();
		$sql   = '
			CREATE TABLE book (
				id INT( 11 ) UNSIGNED AUTO_INCREMENT,
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$sql   = '
			CREATE TABLE page (
				id INT( 11 ) UNSIGNED AUTO_INCREMENT,
				book_id INT( 11 ) UNSIGNED NULL,
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 0 );
		//even if different
		$writer->addFK('page', 'book', 'book_id', 'id', FALSE);
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" AND DELETE_RULE = "CASCADE"');
		asrt( (int) $numOfFKS, 0 );
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" AND DELETE_RULE = "SET NULL"');
		asrt( (int) $numOfFKS, 1 );
		$writer->addFK('page', 'book', 'book_id', 'id', TRUE);
		$numOfFKS = R::getCell('
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
			WHERE TABLE_NAME = "page" ');
	}

	/**
	 * Test whether we can manually create indexes.
	 *
	 * @return void
	 */
	public function testAddingIndex()
	{
		R::nuke();
		$sql   = '
			CREATE TABLE song (
				id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				album_id INT( 11 ) UNSIGNED NOT NULL,
				category VARCHAR( 255 ),
				PRIMARY KEY ( id )
			)
			ENGINE = InnoDB
		';
		R::exec( $sql );
		$sql = 'SHOW INDEX FROM song';
		$indexes = R::getAll( $sql );
		asrt( count( $indexes ), 1 );
		asrt( $indexes[0]['Table'], 'song' );
		asrt( $indexes[0]['Key_name'], 'PRIMARY' );
		$writer = R::getWriter();
		$writer->addIndex('song', 'index1', 'album_id');
		$indexes = R::getAll( 'SHOW INDEX FROM song' );
		asrt( count( $indexes ), 2 );
		asrt( $indexes[0]['Table'], 'song' );
		asrt( $indexes[0]['Key_name'], 'PRIMARY' );
		asrt( $indexes[1]['Table'], 'song' );
		asrt( $indexes[1]['Key_name'], 'index1' );
		//Cant add the same index twice
		$writer->addIndex('song', 'index2', 'category');
		$indexes = R::getAll( 'SHOW INDEX FROM song' );
		asrt( count( $indexes ), 3 );
		//Dont fail, just dont
		try {
			$writer->addIndex('song', 'index3', 'nonexistant');
			pass();
		} catch( \Exception $e ) {
			fail();
		}
		asrt( count( $indexes ), 3 );
		try {
			$writer->addIndex('nonexistant', 'index4', 'nonexistant');
			pass();
		} catch( \Exception $e ) {
			fail();
		}
		asrt( count( $indexes ), 3 );
		try {
			$writer->addIndex('nonexistant', '', 'nonexistant');
			pass();
		} catch( \Exception $e ) {
			fail();
		}
		asrt( count( $indexes ), 3 );
	}
}
