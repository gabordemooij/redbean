<?php 

use \RedBean\Plugin\QueryLogger as QueryLogger; 
/**
 * RedUNIT_Base_Writecache
 *
 * @file    RedUNIT/Base/Writecache.php
 * @desc    Tests the Query Writer cache implemented in the
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Writecache extends RedUNIT_Base
{

	/**
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite', 'CUBRID' );
	}

	/**
	 * Test effects of cache.
	 * 
	 * @return void
	 */
	public function testCachingEffects()
	{
		testpack( 'Testing WriteCache Query Writer Cache' );

		$logger = QueryLogger::getInstanceAndAttach( R::$adapter );

		$book = R::dispense( 'book' )->setAttr( 'title', 'ABC' );

		$book->ownPage[] = R::dispense( 'page' );

		$id = R::store( $book );

		// Test load cache -- without
		$logger->clear();

		$book = R::load( 'book', $id );
		$book = R::load( 'book', $id );

		asrt( count( $logger->grep( 'SELECT' ) ), 2 );

		// With cache
		R::useWriterCache( TRUE );

		$logger->clear();

		$book = R::load( 'book', $id );
		$book = R::load( 'book', $id );

		asrt( count( $logger->grep( 'SELECT' ) ), 1 );

		R::useWriterCache( FALSE );

		// Test find cache
		$logger->clear();

		$book = R::find( 'book' );
		$book = R::find( 'book' );

		asrt( count( $logger->grep( 'SELECT' ) ), 2 );

		// With cache
		R::$writer->setUseCache( TRUE );

		$logger->clear();

		$book = R::find( 'book' );
		$book = R::find( 'book' );

		asrt( count( $logger->grep( 'SELECT' ) ), 1 );

		R::$writer->setUseCache( FALSE );

		// Test combinations
		$logger->clear();

		$book = R::findOne( 'book', ' id = ? ', array( $id ) );

		$book->ownPage;

		R::batch( 'book', array( $id ) );

		$book = R::findOne( 'book', ' id = ? ', array( $id ) );

		$book->ownPage;

		R::batch( 'book', array( $id ) );

		asrt( count( $logger->grep( 'SELECT' ) ), 6 );

		// With cache
		R::$writer->setUseCache( TRUE );

		$logger->clear();

		R::batch( 'book', array( $id ) );

		$book = R::findOne( 'book', ' id = ? ', array( $id ) );

		$book->ownPage;

		$book = R::findOne( 'book', ' id = ? ', array( $id ) );

		$book->ownPage;

		asrt( count( $logger->grep( 'SELECT' ) ), 3 );

		R::$writer->setUseCache( FALSE );

		// Test auto flush
		$logger->clear();

		$book = R::findOne( 'book' );

		$book->name = 'X';

		R::store( $book );

		$book = R::findOne( 'book' );

		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		// With cache
		R::$writer->setUseCache( TRUE );

		$logger->clear();

		$book = R::findOne( 'book' );

		$book->name = 'Y';

		// Will flush
		R::store( $book );

		$book = R::findOne( 'book' );

		// Now the same, auto flushed
		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		R::$writer->setUseCache( FALSE );

		// Test whether delete flushes as well (because uses selectRecord - might be a gotcha!)
		R::store( R::dispense( 'garbage' ) );

		$garbage = R::findOne( 'garbage' );

		$logger->clear();

		$book = R::findOne( 'book' );

		R::trash( $garbage );

		$book = R::findOne( 'book' );

		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		R::store( R::dispense( 'garbage' ) );

		$garbage = R::findOne( 'garbage' );

		// With cache
		R::$writer->setUseCache( TRUE );

		$logger->clear();

		$book = R::findOne( 'book' );

		R::trash( $garbage );

		$book = R::findOne( 'book' );

		// Now the same, auto flushed
		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		R::$writer->setUseCache( FALSE );

		R::store( R::dispense( 'garbage' ) );

		$garbage = R::findOne( 'garbage' );

		// With cache
		R::$writer->setUseCache( TRUE );

		$logger->clear();

		$book = R::findOne( 'book' );

		R::$writer->queryRecord( 'garbage', array( 'id' => array( $garbage->id ) ) );

		$book = R::findOne( 'book' );

		// Now the same, auto flushed
		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );
	}

	/**
	 * Try to fool the cache :)
	 * 
	 * @return void
	 */
	public function testRegressions()
	{
		testpack( 'Testing possible regressions: Try to fool the cache' );

		$str = 'SELECT * FROM ' . R::$writer->esc( 'bean', TRUE ) . ' WHERE ( ' . R::$writer->esc( 'id', TRUE ) . '  IN ( 1)  ) ';

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id = R::store( $bean );

		$bean = R::load( 'bean', $id );

		$bean->title = 'xxx';

		R::store( $bean );

		// Fire exact same query so cache may think no other query has been fired
		R::exec( $str );

		$bean = R::load( 'bean', $id );

		asrt( $bean->title, 'xxx' );
	}

	/**
	 * Test keep-cache comment.
	 * 
	 * @return void
	 */
	public function testKeepCacheCommentInSQL()
	{
		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id = R::store( $bean );

		$bean = R::load( 'bean', $id );

		$bean->title = 'xxx';

		R::store( $bean );

		// Causes flush even though it contains -- keep-cache (not at the end, not intended)
		R::findOne( 'bean', ' title = ? ', array( '-- keep-cache' ) );

		$bean = R::load( 'bean', $id );

		asrt( $bean->title, 'xxx' );
	}

	/**
	 * 
	 * Same as above.. test keep cache.
	 * 
	 * @return void
	 */
	public function testInstructNoDrop()
	{
		$str = 'SELECT * FROM ' . R::$writer->esc( 'bean', TRUE ) . ' -- keep-cache';

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id = R::store( $bean );

		$bean = R::load( 'bean', $id );

		$bean->title = 'xxx';

		R::store( $bean );

		R::exec( $str );

		$bean = R::load( 'bean', $id );

		asrt( $bean->title, 'abc' );

		R::nuke();

		// Now INSTRUCT the cache to not drop the cache CASE 2
		$str = 'SELECT * FROM ' . R::$writer->esc( 'bean', TRUE ) . ' -- keep-cache';

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id = R::store( $bean );

		$bean = R::load( 'bean', $id );

		$bean->title = 'xxx';

		R::store( $bean );

		R::findOne( 'bean', ' title = ? ', array( 'cache' ) );

		$bean = R::load( 'bean', $id );

		asrt( $bean->title, 'xxx' );
	}

	/**
	 * Can we confuse the cache?
	 * 
	 * @return void
	 */
	public function testConfusionRegression()
	{
		testpack( 'Testing possible confusion regression' );

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id1 = R::store( $bean );

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc2';

		$id2 = R::store( $bean );

		$bean = R::load( 'bean', $id1 );

		asrt( $bean->title, 'abc' );

		$bean = R::load( 'bean', $id2 );

		asrt( $bean->title, 'abc2' );
	}

	/**
	 * Test Ghost beans....
	 * 
	 * @return void
	 */
	public function testGhostBeans()
	{
		testpack( 'Testing ghost beans' );

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id1 = R::store( $bean );

		R::trash( $bean );

		$bean = R::load( 'bean', $id1 );

		asrt( (int) $bean->id, 0 );
	}

	/**
	 * Test explicit flush.
	 * 
	 * @return void
	 */
	public function testExplicitCacheFlush()
	{
		testpack( 'Test cache flush (explicit)' );

		$logger = QueryLogger::getInstanceAndAttach( R::$adapter );

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id1 = R::store( $bean );

		$logger->clear();

		$bean = R::load( 'bean', $id1 );

		asrt( $bean->title, 'abc' );
		asrt( count( $logger->grep( 'SELECT *' ) ), 1 );

		$bean = R::load( 'bean', $id1 );

		asrt( count( $logger->grep( 'SELECT *' ) ), 1 );

		R::$writer->flushCache();

		$bean = R::load( 'bean', $id1 );

		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		R::$writer->flushCache();
		R::$writer->setUseCache( FALSE );
	}
}
