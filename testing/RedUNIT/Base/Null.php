<?php
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
class RedUNIT_Base_Null extends RedUNIT_Base
{
	public function unnamed0()
	{
		// NULL can change bean
		$bean      = R::dispense( 'bean' );
		$bean->bla = 'a';

		R::store( $bean );

		$bean = $bean->fresh();

		asrt( $bean->hasChanged( 'bla' ), false );

		$bean->bla = null;

		asrt( $bean->hasChanged( 'bla' ), true );

		// NULL test
		$page = R::dispense( 'page' );
		$book = R::dispense( 'book' );

		$page->title = 'a null page';
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
		$page->book_id = null;

		R::store( $page );

		pass();
	}

	/**
	 * Here we test whether the column type is set correctly.
	 * Normally if you store NULL, the smallest type (bool/set) will
	 * be selected. However in case of a foreign key type INT should
	 * be selected because fks columns require matching types.
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
