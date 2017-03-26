<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Partial Beans
 *
 * Test whether we can use 'partial bean mode'.
 * In 'partial bean mode' only changed properties of a bean
 * will get updated in the database. This feature has been designed
 * to deal with 'incompatible table fields'. The specific case that
 * led to this feature is available as test Postgres/Partial and is
 * based on Github issue #547. This test only covers the basic functionality.
 *
 * @file    RedUNIT/Base/Partial.php
 * @desc    Tests Partial Beans Mode
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Partial extends Base {

	/**
	 * Tests the basic scenarios for Partial Beans.
	 *
	 * @return void
	 */
	public function testPartialBeans()
	{
		R::nuke();
		R::usePartialBeans( FALSE );
		$book = R::dispense( 'book' );
		$book->title = 'A book about half beans';
		$book->price = 99;
		$book->pages = 60;
		$id = R::store( $book );
		/* test baseline condition */
		$book = R::load( 'book', $id );
		asrt( $book->title, 'A book about half beans' );
		asrt( (integer) $book->pages, 60 );
		$book->pages++;
		R::exec( 'UPDATE book SET title = ? ', array( 'Another Title' ) );
		$id = R::store( $book );
		$book = R::load( 'book', $id );
		asrt( $book->title, 'A book about half beans' );
		asrt( (integer) $book->pages, 61 );
		/* now test partial beans mode */
		R::usePartialBeans( TRUE );
		$book->pages++;
		R::exec( 'UPDATE book SET title = ? ', array( 'Another Title' ) );
		$id = R::store( $book );
		$book = R::load( 'book', $id );
		asrt( $book->title, 'Another Title' );
		asrt( (integer) $book->pages, 62 );
		/* mask should be cleared... */
		R::exec( 'UPDATE book SET pages = ? ', array( 64 ) );
		$book->price = 92;
		$id = R::store( $book );
		$book = R::load( 'book', $id );
		asrt( (integer) $book->pages, 64 );
		asrt( $book->title, 'Another Title' );
		asrt( (integer) $book->price, 92 );
		R::usePartialBeans( FALSE );
	}
}
