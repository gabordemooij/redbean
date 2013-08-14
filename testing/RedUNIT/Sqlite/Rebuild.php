<?php
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
class RedUNIT_Sqlite_Rebuild extends RedUNIT_Sqlite
{
	/**
	 * Test SQLite table rebuilding.
	 * 
	 * @return void
	 */
	public function testRebuilder()
	{
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		R::dependencies( array( 'page' => array( 'book' ) ) );

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );

		$book->ownPage[] = $page;

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 1 );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 1 );

		R::trash( $book );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 0 );

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );

		$book->ownPage[] = $page;

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 1 );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 1 );

		$book->added = 2;

		R::store( $book );

		$book->added = 'added';

		R::store( $book );

		R::trash( $book );

		asrt( (int) R::getCell( 'SELECT COUNT(*) FROM page' ), 0 );
	}
}
