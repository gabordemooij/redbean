<?php 

namespace RedUNIT\Sqlite;

use RedUNIT\Sqlite as Sqlite;
use RedBeanPHP\Facade as R;

/**
 * RedUNIT_Sqlite_Rebuild
 *
 * @file    RedUNIT/Sqlite/Rebuild.php
 * @desc    Test rebuilding of tables for SQLite
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Rebuild extends Sqlite
{
	/**
	 * Test SQLite table rebuilding.
	 * 
	 * @return void
	 */
	public function testRebuilder()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );

		$book->xownPage[] = $page;

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->xownPage ), 1 );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 1 );

		R::trash( $book );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 0 );

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );

		$book->xownPage[] = $page;

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->xownPage ), 1 );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 1 );

		$book->added = 2;

		R::store( $book );

		$book->added = 'added';

		R::store( $book );

		R::trash( $book );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 0 );
	}
}
