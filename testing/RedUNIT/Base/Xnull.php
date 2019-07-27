<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Null
 *
 * Tests NULL handling.
 *
 * @file    RedUNIT/Base/Xnull.php
 * @desc    Tests handling of NULL values.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Xnull extends Base
{
	/**
	 * Tests whether we can create queries containing IS-NULL with
	 * the IS-NULL-Condition flag.
	 */
	public function testISNULLConditions()
	{
		R::nuke();
		R::useISNULLConditions( FALSE );
		$book = R::dispense('book');
		$book->title = 'Much ado about Null';
		R::store( $book );
		$book = R::dispense('book');
		$book->title = NULL;
		R::store( $book );
		$books = R::findLike('book', array( 'title' => NULL ) );
		asrt(count($books), 2);
		$wasFalse = R::useISNULLConditions( TRUE );
		asrt( $wasFalse, FALSE );
		$books = R::findLike('book', array( 'title' => NULL ) );
		asrt(count($books), 1);
		$books = R::find('book', ' title = :title ',  array( 'title' => NULL ) );
		asrt(count($books), 0);
	}

	/**
	 * Test Null bindings.
	 */
	public function testBindings()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->content = NULL;
		//can we store a NULL?
		asrt( is_null( $book->content ), TRUE );
		R::store( $book );
		//did we really store the NULL value ?
		$book = R::findOne( 'book', ' content IS NULL ' );
		asrt( ( $book instanceof OODBBean ), TRUE );
		//still NULL, not empty STRING ?
		asrt( is_null( $book->content ), TRUE );
		$book->pages = 100;
		R::store( $book );
		//did we save it once again as NULL?
		$book = R::findOne( 'book', ' content IS NULL ' );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( is_null( $book->content ), TRUE );
		asrt( gettype( $book->pages ), 'string' );
		$otherBook = R::dispense( 'book' );
		$otherBook->pages = 99;
		//also if the column is VARCHAR-like?
		$otherBook->content = 'blah blah';
		R::store( $otherBook );
		$book = R::findOne( 'book', ' content IS NULL ' );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( is_null( $book->content ), TRUE );
		asrt( intval( $book->pages ), 100 );
		//can we query not NULL as well?
		$book = R::findOne( 'book', ' content IS NOT NULL ' );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( is_null( $book->content ), FALSE );
		asrt( intval( $book->pages ), 99 );
		asrt( $book->content, 'blah blah' );
		//Can we bind NULL directly?
		$book->isGood = FALSE;
		//Is NULL the default? And... no confusion with boolean FALSE?
		R::store( $book );
		$book = R::findOne( 'book', ' is_good IS NULL' );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( is_null( $book->content ), TRUE );
		asrt( intval( $book->pages ), 100 );
		$book = R::findOne( 'book', ' is_good = ?', array( 0 ) );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( is_null( $book->content ), FALSE );
		asrt( intval( $book->pages ), 99 );
	}

	/**
	 * Tests whether we can NULLify a parent bean
	 * page->book if the parent (book) is already
	 * NULL. (isset vs array_key_exists bug).
	 *
	 * @return void
	 */
	public function testUnsetParent()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->title = 'My Book';
		$page = R::dispense( 'page' );
		$page->text = 'Lorem Ipsum';
		$book->ownPage[] = $page;
		R::store( $book );
		$page = $page->fresh();
		R::freeze( TRUE );
		asrt( (int) $page->book->id, (int) $book->id );
		unset( $page->book );
		R::store( $page );
		$page = $page->fresh();
		asrt( (int) $page->book->id, (int) $book->id );
		$page->book = NULL;
		R::store( $page );
		$page = $page->fresh();
		asrt( $page->book, NULL );
		asrt( $page->book_id, NULL );
		asrt( $page->bookID, NULL );
		asrt( $page->bookId, NULL );
		$page = R::dispense( 'page' );
		$page->text = 'Another Page';
		$page->book = NULL;
		try {
			R::store( $page );
			fail();
		} catch( \Exception $exception ) {
			pass();
		}
		unset($page->book);
		R::store($page);
		$page = $page->fresh();
		$page->book = NULL; //this must set field id to NULL not ADD column!
		try {
			R::store($page);
			pass();
		} catch( \Exception $exception ) {
			fail();
		}
		$page = $page->fresh();
		$page->book = NULL;
		R::store( $page );
		$page = $page->fresh();
		asrt( is_null( $page->book_id ), TRUE );
		$page->book = $book;
		R::store( $page );
		$page = $page->fresh();
		asrt( (int) $page->book->id, (int) $book->id );
		$page->book = NULL;
		R::store( $page );
		asrt( is_null( $page->book_id ), TRUE );
		asrt( is_null( $page->book ), TRUE );
		R::freeze( FALSE );
	}

	/**
	 * Test nullifying aliased parent.
	 *
	 * @return void
	 */
	public function testUnsetAliasedParent()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$author = R::dispense( 'author' );
		$book->coauthor = $author;
		R::store( $book );
		$book = $book->fresh();
		asrt( is_null( $book->fetchAs('author')->coauthor ), FALSE );
		unset( $book->coauthor );
		R::store( $book );
		$book = $book->fresh();
		asrt( is_null( $book->fetchAs('author')->coauthor ), FALSE );
		$book->coauthor = NULL;
		R::store( $book );
		$book = $book->fresh();
		asrt( is_null( $book->fetchAs('author')->coauthor ), TRUE );
		R::trash( $book );
		R::trash( $author );
		R::freeze( TRUE );
		$book = R::dispense( 'book' );
		$author = R::dispense( 'author' );
		$book->coauthor = $author;
		R::store( $book );
		$book = $book->fresh();
		asrt( is_null( $book->fetchAs('author')->coauthor ), FALSE );
		unset( $book->coauthor );
		R::store( $book );
		$book = $book->fresh();
		asrt( is_null( $book->fetchAs('author')->coauthor ), FALSE );
		$book->coauthor = NULL;
		R::store( $book );
		$book = $book->fresh();
		asrt( is_null( $book->fetchAs('author')->coauthor ), TRUE );
		R::trash( $book );
		R::trash( $author );
		R::freeze( FALSE );
	}

	/**
	 * Test NULL handling, setting a property to NULL must
	 * cause a change.
	 *
	 * @return void
	 */
	public function testBasicNullHandling()
	{
		// NULL can change bean
		$bean      = R::dispense( 'bean' );
		$bean->bla = 'a';
		R::store( $bean );
		$bean = $bean->fresh();
		asrt( $bean->hasChanged( 'bla' ), FALSE );
		$bean->bla = NULL;
		asrt( $bean->hasChanged( 'bla' ), TRUE );
		// NULL test
		$page = R::dispense( 'page' );
		$book = R::dispense( 'book' );
		$page->title = 'a NULL page';
		$page->book  = $book;
		$book->title = 'Why NUll is painful..';
		R::store( $page );
		$bookid = $page->book->id;
		unset( $page->book );
		$id = R::store( $page );
		$page = R::load( 'page', $id );
		$page->title = 'another title';
		R::store( $page );
		pass();
		$page = R::load( 'page', $id );
		$page->title   = 'another title';
		$page->book_id = NULL;
		R::store( $page );
		pass();
	}

	/**
	 * Here we test whether the column type is set correctly.
	 * Normally if you store NULL, the smallest type (bool/set) will
	 * be selected. However in case of a foreign key type INT should
	 * be selected because fks columns require matching types.
	 *
	 * @return void
	 */
	public function ColumnType()
	{

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->ownPage[] = $page;
		R::store( $book );
		pass();
		asrt( $page->getMeta( 'cast.book_id' ), 'id' );
	}

	/**
	 * Test meta column type.
	 *
	 * @return void
	 */
	public function TypeColumn()
	{
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$page->book = $book;
		R::store( $page );
		pass();
		asrt( $page->getMeta( 'cast.book_id' ), 'id' );
	}
}
