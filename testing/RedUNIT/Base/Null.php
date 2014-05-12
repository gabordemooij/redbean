<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * RedUNIT_Base_Null
 *
 * @file    RedUNIT/Base/Null.php
 * @desc    Tests handling of NULL values.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Null extends Base
{

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
