<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\OODBBean;

/**
 * Count
 *
 * Tests whether we can count beans with or without
 * additional conditions and whether we can count associated
 * beans (relationCount).
 *
 * @file    RedUNIT/Base/Count.php
 * @desc    Tests for simple bean counting.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Count extends Base
{
	/**
	 * Tests type check and conversion in
	 * OODB for count().
	 *
	 * @return void
	 */
	public function testCountType()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->sharedPage = R::dispense( 'page', 10 );
		R::store( $book );
		asrt( R::count('bookPage'), 10 );

		try {
			R::count( 'WrongTypeName' );
			fail();
		} catch ( RedException $ex ) {
			pass();
		}

		try {
			R::count( 'wrong_type_name' );
			fail();
		} catch ( RedException $ex ) {
			pass();
		}
	}

	/**
	 * Test count and wipe.
	 *
	 * @return void
	 */
	public function testCountAndWipe()
	{
		testpack( "Test count and wipe" );

		$page = R::dispense( "page" );

		$page->name = "ABC";

		R::store( $page );

		$n1 = R::count( "page" );

		$page = R::dispense( "page" );

		$page->name = "DEF";

		R::store( $page );

		$n2 = R::count( "page" );

		asrt( $n1 + 1, $n2 );

		R::wipe( "page" );

		asrt( R::count( "page" ), 0 );
		asrt( R::getRedBean()->count( "page" ), 0 );
		asrt( R::getRedBean()->count( "kazoo" ), 0 ); // non existing table

		R::freeze( TRUE );

		try {
			asrt( R::getRedBean()->count( "kazoo" ), 0 ); // non existing table
			fail();
		} catch( \Exception $e ) {
			pass();
		}

		R::freeze( FALSE );

		$page = R::dispense( 'page' );

		$page->name = 'foo';

		R::store( $page );

		$page = R::dispense( 'page' );

		$page->name = 'bar';

		R::store( $page );

		asrt( R::count( 'page', ' name = ? ', array( 'foo' ) ), 1 );

		// Now count something that does not exist, this should return 0. (just be polite)
		asrt( R::count( 'teapot', ' name = ? ', array( 'flying' ) ), 0 );
		asrt( R::count( 'teapot' ), 0 );

		$currentDriver = $this->currentlyActiveDriverID;

		// Some drivers don't support that many error codes.
		if ( $currentDriver === 'mysql' || $currentDriver === 'postgres' ) {
			try {
				R::count( 'teaport', ' for tea ' );
				fail();
			} catch ( SQL $e ) {
				pass();
			}
		}
	}

	public function testCountShared() {

		R::nuke();
		$book = R::dispense( 'book' );
		$book->sharedPageList = R::dispense( 'page', 5 );
		R::store( $book );
		asrt( $book->countShared('page'), 5 );
		asrt( $book->countShared('leaflet'), 0 );
		asrt( R::dispense( 'book' )->countShared('page'), 0 );
		$am = R::getRedBean()->getAssociationManager();
		asrt( $am->relatedCount( R::dispense( 'book' ), 'page' ), 0);
		try {
			$am->relatedCount( 'not a bean', 'type' );
			fail();
		} catch( RedException $e ) {
			pass();
		}
		R::getWriter()->setUseCache(TRUE);
		asrt( $book->countShared('page'), 5 );
		R::exec('DELETE FROM book_page WHERE book_id > 0 -- keep-cache');
		asrt( $book->countShared('page'), 5 );
		R::getWriter()->setUseCache(FALSE);
		asrt( $book->countShared('page'), 0 );
	}

	/**
	 * Test $bean->countOwn($type);
	 */
	public function testCountOwn()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$empty = R::dispense( 'book' );
		$nothing = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->ownPageList[] = $page;
		R::store( $book );
		R::store( $empty );
		OODBBean::useFluidCount( FALSE );
		asrt( $book->countOwn('page'), 1 );
		asrt( $empty->countOwn('page'), 0 );
		asrt( $nothing->countOwn('page'), 0 );
		$old = OODBBean::useFluidCount( TRUE );
		asrt( $old, FALSE );
		asrt( $book->countOwn('page'), 1 );
		asrt( $empty->countOwn('page'), 0 );
		asrt( $nothing->countOwn('page'), 0 );
		R::freeze( TRUE );
		asrt( $book->countOwn('page'), 1 );
		asrt( $empty->countOwn('page'), 0 );
		asrt( $nothing->countOwn('page'), 0 );
		R::freeze( FALSE );
		R::nuke();
		asrt( $empty->countOwn('page'), 0 );
		asrt( $nothing->countOwn('page'), 0 );
		R::freeze( TRUE );
		asrt( $nothing->countOwn('page'), 0 );
		try { asrt( $empty->countOwn('page'), 0 ); fail(); } catch(\Exception $e) { pass(); }
		try { asrt( $book->countOwn('page'), 0 ); fail(); } catch(\Exception $e) { pass(); }
		R::freeze( FALSE );
		OODBBean::useFluidCount( FALSE );
		try { asrt( $empty->countOwn('page'), 0 ); fail(); } catch(\Exception $e) { pass(); }
		try { asrt( $book->countOwn('page'), 0 ); fail(); } catch(\Exception $e) { pass(); }
		OODBBean::useFluidCount( TRUE );
	}

	/**
	 * Test $bean->withCondition( ... )->countOwn( $type );
	 */
	public function testCountWithCondition()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->ownPageList[] = R::dispense( 'page' );
		R::store( $book );
		OODBBean::useFluidCount( FALSE );
		$count = $book
			->withCondition(' id > :id ', array( ':id' => 0 ) )
			->countOwn('page');
		asrt( $count, 1 );
		$count = $book
			->withCondition(' id > ? ', array( 0 ) )
			->countOwn('page');
		asrt( $count, 1 );
		$count = $book
			->withCondition(' id < :id ', array( ':id' => 0 ) )
			->countOwn('page');
		asrt( $count, 0 );
		$count = $book
			->withCondition(' id < ? ', array( 0 ) )
			->countOwn('page');
		asrt( $count, 0 );
		OODBBean::useFluidCount( TRUE );
		$count = $book
			->withCondition(' id > :id ', array( ':id' => 0 ) )
			->countOwn('page');
		asrt( $count, 1 );
		$count = $book
			->withCondition(' id > ? ', array( 0 ) )
			->countOwn('page');
		asrt( $count, 1 );
		$count = $book
			->withCondition(' id < :id ', array( ':id' => 0 ) )
			->countOwn('page');
		asrt( $count, 0 );
		$count = $book
			->withCondition(' id < ? ', array( 0 ) )
			->countOwn('page');
		asrt( $count, 0 );
	}
}
