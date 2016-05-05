<?php

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;
use RedBeanPHP\Facade as R;

/**
 * Issue 411
 *
 * InnoDB has a maximum index length of 767 bytes, so with utf8mb4
 * you can only store 191 characters. This means that when you
 * subsequently add an index to the column you get a
 * (not-so-obvious) MySQL-error.  That's why we limit the varchar to
 * 191 chars and then switch to TEXT type.
 *
 * @file    RedUNIT/Mysql/Issue411.php
 * @desc    Tests intermediate varchar 191 type for MySQL utf8mb4.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Issue411 extends Mysql
{

	/**
	 * Test varchar 191 condition.
	 *
	 * @return void
	 */
	public function testInnoDBIndexLimit()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->text = 'abcd';
		R::store( $book );
		$columns = R::inspect( 'book' );
		asrt( isset( $columns['text'] ), TRUE );
		asrt( $columns['text'], 'varchar(191)' );
		$book = $book->fresh();
		$book->text = str_repeat( 'x', 190 );
		R::store( $book );
		$columns = R::inspect( 'book' );
		asrt( isset( $columns['text'] ), TRUE );
		asrt( $columns['text'], 'varchar(191)' );
		$book = $book->fresh();
		$book->text = str_repeat( 'x', 191 );
		R::store( $book );
		$columns = R::inspect( 'book' );
		asrt( isset( $columns['text'] ), TRUE );
		asrt( $columns['text'], 'varchar(191)' );
		$book = $book->fresh();
		$book->text = str_repeat( 'x', 192 );
		R::store( $book );
		$columns = R::inspect( 'book' );
		asrt( isset( $columns['text'] ), TRUE );
		asrt( $columns['text'], 'varchar(255)' );
	}
}

