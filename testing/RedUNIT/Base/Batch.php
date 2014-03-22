<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException\SQL as SQL;

/**
 * RedUNIT_Base_Batch
 *
 * @file    RedUNIT/Base/Batch.php
 * @desc    Tests batch loading of beans, i.e. loading large collections of beans in optimized way.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Batch extends Base
{

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 *
	 * @return void
	 */
	public function testBatch()
	{
		R::freeze( FALSE );
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$page = $redbean->dispense( "page" );

		$page->name   = "page no. 1";
		$page->rating = 1;

		$id1 = $redbean->store( $page );

		$page = $redbean->dispense( "page" );

		$page->name = "page no. 2";

		$id2 = $redbean->store( $page );

		$batch = $redbean->batch( "page", array( $id1, $id2 ) );

		asrt( count( $batch ), 2 );
		asrt( $batch[$id1]->getMeta( "type" ), "page" );
		asrt( $batch[$id2]->getMeta( "type" ), "page" );
		asrt( (int) $batch[$id1]->id, $id1 );
		asrt( (int) $batch[$id2]->id, $id2 );

		$book = $redbean->dispense( "book" );

		$book->name = "book 1";

		$redbean->store( $book );

		$book = $redbean->dispense( "book" );

		$book->name = "book 2";

		$redbean->store( $book );

		$book = $redbean->dispense( "book" );

		$book->name = "book 3";

		$redbean->store( $book );

		$books = $redbean->batch( "book", $adapter->getCol( "SELECT id FROM book" ) );

		asrt( count( $books ), 3 );

		$a = $redbean->batch( 'book', 9919 );

		asrt( is_array( $a ), TRUE );
		asrt( count( $a ), 0 );
		$a = $redbean->batch( 'triangle', 1 );

		asrt( is_array( $a ), TRUE );
		asrt( count( $a ), 0 );

		R::freeze( TRUE );

		$a = $redbean->batch( 'book', 9919 );

		asrt( is_array( $a ), TRUE );
		asrt( count( $a ), 0 );
		try {
			$a = $redbean->batch( 'triangle', 1 );
			fail();
		} catch(SQL $e) {
			pass();
		}
		R::freeze( FALSE );
		asrt( R::wipe( 'spaghettimonster' ), FALSE );
	}

	/**
	 * Test missing bean scenarios.
	 * 
	 * @return void
	 */
	public function testMissingBeans()
	{
		testpack( 'deal with missing beans' );

		$id      = R::store( R::dispense( 'beer' ) );
		$bottles = R::batch( 'beer', array( $id, $id + 1, $id + 2 ) );

		asrt( count( $bottles ), 3 );
		asrt( (int) $bottles[$id]->id, (int) $id );
		asrt( (int) $bottles[$id + 1]->id, 0 );
		asrt( (int) $bottles[$id + 2]->id, 0 );
	}
	
	/**
	 * Test batch alias loadAll.
	 * 
	 * @return void
	 */
	public function testBatchAliasLoadAll() 
	{	
		$ids = R::storeAll( R::dispense( 'page', 2 ) );
		$pages = R::loadAll( 'page', $ids );
		asrt( is_array( $pages ), true );
		asrt( count( $pages ), 2 );
		asrt( ( $pages[$ids[0]] instanceof OODBBean ), true );
	}
}
