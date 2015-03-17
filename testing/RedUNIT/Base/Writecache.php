<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Writecache
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
class Writecache extends Base
{

	/**
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite', 'CUBRID' );
	}

	/**
	 * Test whether cache size remains constant (per type).
	 * Avoiding potential memory leaks. (Issue #424).
	 *
	 * @return void
	 */
	public function testCacheSize()
	{
		R::nuke();
		R::useWriterCache( TRUE );
		$writer = R::getWriter();
		$bean = R::dispense( 'bean' );
		$bean->prop = 1;
		R::store( $bean );
		$writer->flushCache( 20 );
		$count = $writer->flushCache();
		asrt( $count, 0 );
		R::find( 'bean', ' prop < ? ', array( 1 ) );
		$count = $writer->flushCache();
		asrt( $count, 2 );
		R::find( 'bean', ' prop < ? ', array( 2 ) );
		$count = $writer->flushCache();
		asrt( $count, 5 );
		R::find( 'bean', ' prop < ? ', array( 2 ) );
		$count = $writer->flushCache();
		asrt( $count, 5 );
		for( $i = 0; $i < 40; $i ++ ) {
			R::find( 'bean', ' prop < ? ', array( $i ) );
		}
		$count = $writer->flushCache();
		asrt( $count, 85 );
		for( $i = 0; $i < 120; $i ++ ) {
			R::find( 'bean', ' prop < ? ', array( $i ) );
		}
		$count = $writer->flushCache( 1 );
		asrt( $count, 85 );
		for( $i = 0; $i < 20; $i ++ ) {
			R::find( 'bean', ' prop < ? ', array( $i ) );
		}
		$count = $writer->flushCache( 20 );
		asrt( $count, 9 );
	}

	/**
	 * When using fetchAs(), Query Cache does not recognize objects
	 * that have been previously fetched, see issue #400.
	 */
	public function testCachingAndFetchAs()
	{
		testpack( 'Testing whether you can cache multiple records of the same type' );
		R::debug( true, 1 );
		$logger = R::getDatabaseAdapter()->getDatabase()->getLogger();
		R::nuke();
		$coauthor1 = R::dispense( 'author' );
		$coauthor1->name = 'John';
		$book = R::dispense( 'book' );
		$book->title = 'a Funny Tale';
		$book->coauthor = $coauthor1;
		$id = R::store( $book );
		$coauthor = R::dispense( 'author' );
		$coauthor->name = 'Pete';
		$book = R::dispense( 'book' );
		$book->title = 'a Funny Tale 2';
		$book->coauthor = $coauthor;
		$id = R::store( $book );
		$book = R::dispense( 'book' );
		$book->title = 'a Funny Tale 3';
		$book->coauthor = $coauthor1;
		$id = R::store( $book );
		$books = R::find( 'book' );
		$logger->clear();
		$authors = array();
		$authorsByName = array();
		foreach($books as $book) {
			$coAuthor = $book->with( ' ORDER BY title ASC ' )
				->fetchAs( 'author' )->coauthor;
			$authors[] = $coAuthor->name;
			$authorsByName[ $coAuthor->name ] = $coAuthor;
		}
		asrt( count( $logger->grep( 'SELECT' ) ), 2 ); //must be 2! 3 if cache does not work!
		asrt( count( $authors ), 3 );
		asrt( isset( $authorsByName[ 'John' ] ), TRUE );
		asrt( isset( $authorsByName[ 'Pete' ] ), TRUE );
		$logger->clear();
		$authors = array();
		$authorsByName = array();
		foreach($books as $book) {
			$coAuthor = $book->with( ' ORDER BY title DESC ' )
				->fetchAs( 'author' )->coauthor;
			$authors[] = $coAuthor->name;
			$authorsByName[ $coAuthor->name ] = $coAuthor;
		}
		asrt( count( $logger->grep( 'SELECT' ) ), 0 ); //must be 0!
		asrt( count( $authors ), 3 );
		asrt( isset( $authorsByName[ 'John' ] ), TRUE );
		asrt( isset( $authorsByName[ 'Pete' ] ), TRUE );
	}

	/**
	 * Test effects of cache.
	 *
	 * @return void
	 */
	public function testCachingEffects()
	{
		testpack( 'Testing WriteCache Query Writer Cache' );

		R::setNarrowFieldMode( FALSE );
		R::useWriterCache( FALSE );

		R::debug( true, 1 );
		$logger = R::getDatabaseAdapter()->getDatabase()->getLogger();

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
		R::getWriter()->setUseCache( TRUE );

		$logger->clear();

		$book = R::find( 'book' );
		$book = R::find( 'book' );

		asrt( count( $logger->grep( 'SELECT' ) ), 1 );

		R::getWriter()->setUseCache( FALSE );

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
		R::getWriter()->setUseCache( TRUE );

		$logger->clear();

		R::batch( 'book', array( $id ) );

		$book = R::findOne( 'book', ' id = ? ', array( $id ) );

		$book->ownPage;

		$book = R::findOne( 'book', ' id = ? ', array( $id ) );

		$book->ownPage;

		asrt( count( $logger->grep( 'SELECT' ) ), 3 );

		R::getWriter()->setUseCache( FALSE );

		// Test auto flush
		$logger->clear();

		$book = R::findOne( 'book' );

		$book->name = 'X';

		R::store( $book );

		$book = R::findOne( 'book' );

		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		// With cache
		R::getWriter()->setUseCache( TRUE );

		$logger->clear();

		$book = R::findOne( 'book' );

		$book->name = 'Y';

		// Will flush
		R::store( $book );

		$book = R::findOne( 'book' );

		// Now the same, auto flushed
		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		R::getWriter()->setUseCache( FALSE );

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
		R::getWriter()->setUseCache( TRUE );

		$logger->clear();

		$book = R::findOne( 'book' );

		R::trash( $garbage );

		$book = R::findOne( 'book' );

		// Now the same, auto flushed
		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		R::getWriter()->setUseCache( FALSE );

		R::store( R::dispense( 'garbage' ) );

		$garbage = R::findOne( 'garbage' );

		// With cache
		R::getWriter()->setUseCache( TRUE );

		$logger->clear();

		$book = R::findOne( 'book' );

		R::getWriter()->queryRecord( 'garbage', array( 'id' => array( $garbage->id ) ) );

		$book = R::findOne( 'book' );

		// Now the same, auto flushed
		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		$page = R::dispense('page');
		$book->sharedPage[] = $page;

		R::store( $book );

		$logger->clear();
		$link = R::getWriter()->queryRecordLink( 'book', 'page', $book->id, $page->id );

		asrt( count( $logger->grep( 'SELECT' ) ), 1 );

		$link = R::getWriter()->queryRecordLink( 'book', 'page', $book->id, $page->id );

		asrt( count( $logger->grep( 'SELECT' ) ), 1 );

		R::getWriter()->setUseCache( FALSE );

		$link = R::getWriter()->queryRecordLink( 'book', 'page', $book->id, $page->id );

		asrt( count( $logger->grep( 'SELECT' ) ), 2 );

		R::getWriter()->setUseCache( TRUE );
		R::setNarrowFieldMode( TRUE );
	}

	/**
	 * Try to fool the cache :)
	 *
	 * @return void
	 */
	public function testRegressions()
	{
		testpack( 'Testing possible regressions: Try to fool the cache' );

		$str = 'SELECT * FROM ' . R::getWriter()->esc( 'bean', TRUE ) . ' WHERE ( ' . R::getWriter()->esc( 'id', TRUE ) . '  IN ( 1)  ) ';

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
		$str = 'SELECT * FROM ' . R::getWriter()->esc( 'bean', TRUE ) . ' -- keep-cache';

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
		$str = 'SELECT * FROM ' . R::getWriter()->esc( 'bean', TRUE ) . ' -- keep-cache';

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

		R::setNarrowFieldMode( FALSE );
		R::debug( true, 1 );
		$logger = R::getDatabaseAdapter()->getDatabase()->getLogger();

		$bean = R::dispense( 'bean' );

		$bean->title = 'abc';

		$id1 = R::store( $bean );

		$logger->clear();

		$bean = R::load( 'bean', $id1 );

		asrt( $bean->title, 'abc' );
		asrt( count( $logger->grep( 'SELECT *' ) ), 1 );

		$bean = R::load( 'bean', $id1 );

		asrt( count( $logger->grep( 'SELECT *' ) ), 1 );

		R::getWriter()->flushCache();

		$bean = R::load( 'bean', $id1 );

		asrt( count( $logger->grep( 'SELECT *' ) ), 2 );

		R::getWriter()->flushCache();
		R::getWriter()->setUseCache( FALSE );
		R::setNarrowFieldMode( TRUE );
	}
}
