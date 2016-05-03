<?php

namespace RedUNIT\Postgres;

use RedUNIT\Postgres as Postgres;
use RedBeanPHP\Facade as R;

/**
 * Bigint
 *
 * Tests handling of bigint type columns for primary key IDs,
 * can we use bigint primary keys without issues ?
 * These tests should be able to detect incorrect intval
 * casts for instance.
 *
 * @file    RedUNIT/Postgres/Bigint.php
 * @desc    Tests support for BIGINT primary keys.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Bigint extends Postgres
{
	/**
	 * Test BIG INT primary key support.
	 *
	 * @return void
	 */
	public function testBigIntSupport()
	{
		R::nuke();

		$createPageTableSQL = '
			CREATE TABLE
			page
			(
				id BIGSERIAL PRIMARY KEY,
				book_id BIGSERIAL,
				magazine_id BIGSERIAL,
				title VARCHAR(255)
			)';

		$createBookTableSQL = '
			CREATE TABLE
			book
			(
				id BIGSERIAL PRIMARY KEY,
				title VARCHAR(255)
			)';

		$createPagePageTableSQL = '
			CREATE TABLE
			page_page
			(
				id BIGSERIAL PRIMARY KEY,
				page_id BIGSERIAL,
				page2_id BIGSERIAL
			) ';

		R::exec( $createBookTableSQL );
		R::exec( $createPageTableSQL );
		R::exec( $createPagePageTableSQL );

		//insert some records

		$book1ID     = '2223372036854775808';
		$book2ID     = '2223372036854775809';
		$page1ID     = '1223372036854775808';
		$page2ID     = '1223372036854775809';
		$page3ID     = '1223372036854775890';
		$pagePage1ID = '3223372036854775808';

		R::exec("ALTER SEQUENCE book_id_seq RESTART WITH $book1ID");
		R::exec("ALTER SEQUENCE page_id_seq RESTART WITH $page1ID");
		R::exec("ALTER SEQUENCE page_page_id_seq RESTART WITH $pagePage1ID");

		$insertBook1SQL = "
			INSERT INTO book (title) VALUES( 'book 1' );
		";

		$insertBook2SQL = "
			INSERT INTO book (title) VALUES( 'book 2' );
		";

		$insertPage1SQL = "
			INSERT INTO page (id, book_id, title, magazine_id) VALUES( '$page1ID', '$book1ID', 'page 1 of book 1', '$book2ID' );
		";

		$insertPage2SQL = "
			INSERT INTO page (id, book_id, title) VALUES( '$page2ID', '$book1ID', 'page 2 of book 1' );
		";

		$insertPage3SQL = "
			INSERT INTO page (id, book_id, title) VALUES( '$page3ID', '$book2ID', 'page 1 of book 2' );
		";

		$insertPagePage1SQL = "
			INSERT INTO page_page (id, page_id, page2_id) VALUES( '$pagePage1ID', '$page2ID', '$page3ID' );
		";

		R::exec( $insertBook1SQL );
		R::exec( $insertBook2SQL );
		R::exec( $insertPage1SQL );
		R::exec( $insertPage2SQL );
		R::exec( $insertPage3SQL );
		R::exec( $insertPagePage1SQL );

		//basic tour of basic functions....
		$book1 = R::load( 'book', $book1ID );

		asrt( $book1->id, $book1ID );
		asrt( $book1->title, 'book 1' );

		$book2 = R::load( 'book', $book2ID );

		asrt( $book2->id, $book2ID );
		asrt( $book2->title, 'book 2' );

		asrt( count( $book1->ownPage ), 2 );
		asrt( count( $book1->fresh()->with( 'LIMIT 1' )->ownPage ), 1 );
		asrt( count( $book1->fresh()->withCondition( ' title = ? ', array('page 2 of book 1'))->ownPage ), 1 );

		asrt( count($book2->ownPage), 1 );
		asrt( $book2->fresh()->countOwn( 'page' ), 1 );

		$page1 = R::load( 'page', $page1ID );
		asrt( count( $page1->sharedPage ), 0 );
		asrt( $page1->fetchAs( 'book' )->magazine->id, $book2ID );

		$page2 = R::load( 'page', $page2ID );
		asrt( count($page2->sharedPage), 1 );
		asrt( $page2->fresh()->countShared( 'page' ), 1 );

		$page3 = R::findOne( 'page', ' title = ? ', array( 'page 1 of book 2' ) );
		asrt( $page3->id, $page3ID );
		asrt( $page3->book->id, $book2ID );
	}
}
