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
	 * Github Issue #754.
	 * If I set up the default values to some specific columns,
	 * these columns can not act the same expectation in partial bean mode. #754.
	 * "When I upgrade my code to Redbean 5.4 and turn on the partial bean mode,
	 * I found this issue I mentioned.
	 * As Redbean recommends, I set up the default values
	 * to some specific columns before I use them.
	 * And then I use the partial bean mode to store
	 * the columns updated rather than the entire bean.
	 * But I found if I set up the default values,
	 * it will change the changelist in the bean which is the
	 * foundation of the partial bean mode."
	 *
	 * @return void
	 */
	public function testChangeListIssue()
	{
		R::nuke();
		R::usePartialBeans( TRUE );
		\Model_Coffee::$defaults = array(
			'strength'    => 'strong',
			'beans'       => 'Arabica',
			'preparation' => 'Kettle'
		);
		$coffee = R::dispense('coffee');
		$changelist = $coffee->getMeta('changelist');
		asrt( count( $changelist), 3 );
		$coffee->preparation = 'Espresso';
		$changelist = $coffee->getMeta('changelist');
		asrt( count( $changelist), 4 );
		$id = R::store( $coffee );
		$coffee = R::load( 'coffee', $id );
		$changelist = $coffee->getMeta('changelist');
		asrt( count( $changelist), 0 );
	}

	/**
	 * Github Issue #754.
	 * The importRow() function should clear the changeList.
	 *
	 * @return void
	 */
	public function testChangeListImportRow()
	{
		R::usePartialBeans( TRUE );
		$bean = R::dispense( 'bean' );
		asrt( count( $bean->getMeta('changelist') ), 0 );
		$bean->property = 'abc';
		asrt( count( $bean->getMeta('changelist') ), 1 );
		$bean->importRow( array( 'property' => 123 ) );
		asrt( count( $bean->getMeta('changelist') ), 0 );
	}

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

	/**
	 * Tests whether we can pass a list of specific bean types
	 * to apply partial saving to.
	 *
	 * @return void
	 */
	public function testPartialBeansTypeList()
	{
		R::nuke();
		R::usePartialBeans( array( 'notbook' ) );
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
		R::usePartialBeans( array( 'book', 'more' ) );
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

	/**
	 * Tests the basic scenarios for Partial Beans.
	 * Frozen.
	 *
	 * @return void
	 */
	public function testPartialBeansFrozen()
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
		R::freeze( TRUE );
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
		R::freeze( FALSE );
	}

	/**
	 * Tests whether we can pass a list of specific bean types
	 * to apply partial saving to.
	 * Frozen.
	 *
	 * @return void
	 */
	public function testPartialBeansTypeListFrozen()
	{
		R::nuke();
		R::usePartialBeans( array( 'notbook' ) );
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
		R::freeze( TRUE );
		R::usePartialBeans( array( 'book', 'more' ) );
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
		R::freeze( FALSE );
	}
}
