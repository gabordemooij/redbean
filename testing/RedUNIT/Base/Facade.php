<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Facade
 *
 * @file    RedUNIT/Base/Facade.php
 * @desc    Tests basic functions through facade.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Facade extends Base
{

	/**
	 * What drivers should be loaded for this test pack?
	 * This pack contains some SQL incomp. with OCI
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite', 'CUBRID' );
	}

	/**
	 * Tests quick trash method: R::trash( type, id ).
	 *
	 * @return void
	 */
	public function testQuickTrash()
	{
		R::nuke();
		$bean = R::dispense( 'bean' );
		$id = R::store( $bean );
		asrt( R::count( 'bean' ), 1 );
		R::trash( 'bean', $id );
		asrt( R::count( 'bean' ), 0 );
	}

	/**
	 * Test common Facade usage scenarios.
	 *
	 * @return void
	 */
	public function testCommonUsageFacade()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		$a       = new AssociationManager( $toolbox );

		asrt( R::getRedBean() instanceof OODB, TRUE );
		asrt( R::getToolBox() instanceof ToolBox, TRUE );
		asrt( R::getDatabaseAdapter() instanceof Adapter, TRUE );
		asrt( R::getWriter() instanceof QueryWriter, TRUE );

		$book = R::dispense( "book" );

		asrt( $book instanceof OODBBean, TRUE );

		$book->title = "a nice book";

		$id = R::store( $book );

		asrt( ( $id > 0 ), TRUE );

		$book = R::load( "book", (int) $id );

		asrt( $book->title, "a nice book" );

		asrt( R::load( 'book', 999 )->title, NULL );

		R::freeze( TRUE );

		try {
			R::load( 'bookies', 999 );

			fail();
		} catch (\Exception $e ) {
			pass();
		}

		R::freeze( FALSE );

		$author = R::dispense( "author" );

		$author->name = "me";

		R::store( $author );

		$book9   = R::dispense( "book" );
		$author9 = R::dispense( "author" );

		$author9->name = "mr Nine";

		$a9 = R::store( $author9 );

		$book9->author_id = $a9;

		$bk9 = R::store( $book9 );

		$book9  = R::load( "book", $bk9 );
		$author = R::load( "author", $book9->author_id );

		asrt( $author->name, "mr Nine" );

		R::trash( $author );
		R::trash( $book9 );

		pass();

		$book2 = R::dispense( "book" );

		$book2->title = "second";

		R::store( $book2 );

		$book3 = R::dispense( "book" );

		$book3->title = "third";

		R::store( $book3 );

		asrt( count( R::find( "book" ) ), 3 );
		asrt( count( R::findAll( "book" ) ), 3 );
		asrt( count( R::findAll( "book", " LIMIT 2" ) ), 2 );

		asrt( count( R::find( "book", " id=id " ) ), 3 );
		asrt( count( R::find( "book", " title LIKE ?", array( "third" ) ) ), 1 );
		asrt( count( R::find( "book", " title LIKE ?", array( "%d%" ) ) ), 2 );

		// Find without where clause
		asrt( count( R::findAll( 'book', ' order by id' ) ), 3 );

		R::trash( $book3 );
		R::trash( $book2 );

		asrt( count( R::getAll( "SELECT * FROM book " ) ), 1 );
		asrt( count( R::getCol( "SELECT title FROM book " ) ), 1 );

		asrt( (int) R::getCell( "SELECT 123 " ), 123 );

		$book = R::dispense( "book" );

		$book->title = "not so original title";

		$author = R::dispense( "author" );

		$author->name = "Bobby";

		R::store( $book );

		$aid = R::store( $author );

		$author = R::findOne( "author", " name = ? ", array( "Bobby" ) );

	}
}
