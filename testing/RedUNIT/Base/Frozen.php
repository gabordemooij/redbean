<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Frozen
 *
 * Frozen mode tests
 * When I split the repositories in frozen and fluid I discovered some missed
 * code-paths in the tests.
 * These tests are here to make sure the following scenarios work properly
 * in frozen mode as well.
 *
 * @file    RedUNIT/Base/Frozen.php
 * @desc    Test some scenarios we haven't covered for frozen mode.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Frozen extends Base
{
	/**
	 * Tests the handling of trashed beans in frozen mode.
	 * Are the lists unset etc?
	 *
	 * @return void
	 */
	public function testTrash()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->xownPageList[] = R::dispense( 'page' );
		$book->sharedTagList[] = R::dispense( 'tag' );
		R::store( $book );
		$book = $book->fresh();
		R::freeze( TRUE );

		$book->xownPageList = array();

		R::store( $book );
		$book = $book->fresh();

		asrt( R::count('page'), 0 );

		$book->xownPageList[] = R::dispense( 'page' );

		R::store( $book );
		$book = $book->fresh();

		asrt( R::count('page'), 1 );

		$book->xownPageList;
		$book->sharedTagList;
		R::trash( $book );

		asrt( R::count('book'), 0 );
		asrt( R::count('page'), 0 );
		asrt( R::count('tag'), 1 );
		asrt( R::count('book_tag'), 0 );

		R::freeze( FALSE );
	}

	/**
	 * Tests whether invalid list checks are
	 * operational in frozen mode.
	 *
	 * @return void
	 */
	public function testInvalidList()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->xownPageList[] = R::dispense( 'page' );
		$book->sharedTagList[] = R::dispense( 'tag' );
		R::store( $book );
		R::freeze( TRUE );

		$book = R::dispense( 'book' );
		$book->xownPageList[] = 'nonsense';
		try {
			R::store( $book );
			fail();
		} catch( \Exception $e ) {
			pass();
		}

		R::freeze( FALSE );
	}

	/**
	 * Tests whether loading non-existant beans
	 * returns the same results in frozen mode.
	 *
	 * @return
	 */
	public function testLoadNonExistant()
	{
		R::nuke();
		R::store( R::dispense( 'bean' ) );
		R::freeze( TRUE );
		$bean = R::load( 'bean', 123 );
		R::freeze( FALSE );
		asrt( ( $bean instanceof OODBBean ), TRUE );
		asrt( $bean->id, 0 );
	}
}
