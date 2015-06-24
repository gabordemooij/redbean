<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Traverse
 *
 * @file    RedUNIT/Base/Traverse.php
 * @desc    Tests traversal functionality
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Traverse extends Base
{

	/**
	 * Very simple traverse case (one-level).
	 *
	 * @return void
	 */
	public function testSimplestTraversal()
	{
		R::nuke();
		$books = R::dispense( 'book', 10 );
		$i = 1;
		foreach( $books as $book ) {
			$book->title = 'Book ' . ( $i++ );
		}

		$books[5]->marked = TRUE;

		$shelf = R::dispense( 'shelf' );
		$shelf->ownBook = $books;

		$found = NULL;
		$shelf->traverse('ownBookList', function( $book ) use ( &$found ) {
			if ( $book->marked ) $found = $book;
		});

		asrt( ( $found->marked == TRUE ), TRUE );
		asrt( $found->title, 'Book 6' );
	}

	/**
	 * Tests basic traversal.
	 *
	 * @return void
	 */
	public function testBasicTraversal()
	{
		R::nuke();
		$pageA = R::dispense( 'page' )->setAttr( 'title', 'a' );
		$pageB = R::dispense( 'page' )->setAttr( 'title', 'b' );
		$pageC = R::dispense( 'page' )->setAttr( 'title', 'c' );
		$pageD = R::dispense( 'page' )->setAttr( 'title', 'd' );
		$pageE = R::dispense( 'page' )->setAttr( 'title', 'e' );
		$pageF = R::dispense( 'page' )->setAttr( 'title', 'f' );
		$pageG = R::dispense( 'page' )->setAttr( 'title', 'g' );
		$pageH = R::dispense( 'page' )->setAttr( 'title', 'h' );

		$pageA->ownPage = array( $pageB, $pageC );
		$pageB->ownPage = array( $pageD );
		$pageC->ownPage = array( $pageE, $pageF );
		$pageD->ownPage = array( $pageG );
		$pageF->ownPage = array( $pageH );

		R::store( $pageA );
		$pageA = $pageA->fresh();

		//also tests non-existant column handling by count().
		asrt( R::count( 'page', ' price = ? ', array( '5' ) ), 0);
		asrt( R::count( 'tag',  ' title = ? ', array( 'new' ) ), 0);

		$pageA->traverse( 'ownPageList', function( $bean ) {
			$bean->price = 5;
		});

		R::store( $pageA );

		asrt( R::count( 'page', ' price = ? ', array( '5' ) ), 7);
	}

	/**
	* Test traversing paths, ancestry.
	*
	* @return void
	*/
	public function testTraversePaths()
	{
		R::nuke();
		$pageA = R::dispense( 'page' )->setAttr( 'title', 'a' );
		$pageB = R::dispense( 'page' )->setAttr( 'title', 'b' );
		$pageC = R::dispense( 'page' )->setAttr( 'title', 'c' );
		$pageD = R::dispense( 'page' )->setAttr( 'title', 'd' );
		$pageE = R::dispense( 'page' )->setAttr( 'title', 'e' );
		$pageF = R::dispense( 'page' )->setAttr( 'title', 'f' );
		$pageG = R::dispense( 'page' )->setAttr( 'title', 'g' );
		$pageH = R::dispense( 'page' )->setAttr( 'title', 'h' );

		$pageA->ownPage = array( $pageB, $pageC );
		$pageB->ownPage = array( $pageD );
		$pageC->ownPage = array( $pageE, $pageF );
		$pageD->ownPage = array( $pageG );
		$pageF->ownPage = array( $pageH );

		R::store( $pageA );

		$parents = array();
		$pageF->traverse( 'page', function( $page ) use ( &$parents ) {
			$parents[] = $page->title;
		} );

		asrt( implode( ',', $parents ), 'c,a' );

		$parents = array();
		$pageH->traverse( 'page', function( $page ) use ( &$parents ) {
			$parents[] = $page->title;
		} );

		asrt( implode( ',', $parents ), 'f,c,a' );

		$parents = array();
		$pageG->traverse( 'page', function( $page ) use ( &$parents ) {
			$parents[] = $page->title;
		} );

		asrt( implode( ',', $parents ), 'd,b,a' );

		$path = array();
		$pageA->traverse( 'ownPageList', function( $page ) use ( &$path ) {
			$path[] = $page->title;
		} );

		asrt( implode( ',', $path ), 'b,d,g,c,e,f,h' );

		$path = array();
		$pageC->traverse( 'ownPageList', function( $page ) use ( &$path ) {
			$path[] = $page->title;
		} );

		asrt( implode( ',', $path ), 'e,f,h' );

		$path = array();
		$pageA->traverse( 'ownPageList', function( $page ) use ( &$path ) {
			$path[] = $page->title;
		}, 2 );

		asrt( implode( ',', $path ), 'b,d,c,e,f' );
	}

	/**
	 * Test traversal with embedded SQL snippets.
	 *
	 * @return void
	 */
	public function testTraversalWithSQL()
	{
		$tasks = R::dispense('task', 10);
		foreach( $tasks as $key => $task ) {
			$task->descr = 't'.$key;
		}
		$tasks[0]->ownTask = array( $tasks[1], $tasks[9], $tasks[7] );
		$tasks[1]->ownTask = array( $tasks[5] );
		$tasks[9]->ownTask = array( $tasks[3], $tasks[8] );
		$tasks[2]->ownTask = array( $tasks[4] );
		$tasks[7]->ownTask = array( $tasks[6] );
		R::storeAll( $tasks );
		$task = R::load('task', $tasks[0]->id);

		$todo = array();
		$task->with(' ORDER BY descr ASC ')->traverse('ownTaskList', function( $t ) use ( &$todo ) {
			$todo[] = $t->descr;
		} );

		asrt( implode( ',', $todo ), 't1,t5,t7,t6,t9,t3,t8' );

		$task = R::load( 'task', $tasks[0]->id );
		$todo = array();
		$task->withCondition( ' ( descr = ? OR descr = ? ) ', array( 't7','t6' ) )
			->traverse( 'ownTaskList', function( $task ) use( &$todo ){
				$todo[] = $task->descr;
			} );

		asrt( implode( ',', $todo ), 't7,t6' );
	}

	/**
	 * Test traversal with aliases.
	 *
	 * @return void
	 */
	public function testTraversalWithAlias()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$cats = R::dispense( 'category', 3 );
		$cats[0]->gname = 'SF';
		$cats[1]->gname = 'Fantasy';
		$cats[2]->gname = 'Horror';
		$book->genre = $cats[0];
		$book->name = 'Space Story';
		$cats[0]->genre = $cats[1];
		$cats[2]->genre = $cats[1];
		R::store( $book );

		$book2 = R::dispense( 'book' );
		$book2->genre = $cats[2];
		$book2->name = 'Ghost Story';
		R::store( $book2 );
		$fantasy = R::load( 'category', $cats[1]->id );

		$cats = array();
		$book = $book->fresh();
		$book->fetchAs( 'category' )->traverse( 'genre', function( $cat ) use ( &$cats ) {
			$cats[] = $cat->gname;
		} );
		asrt( implode( ',', $cats ), 'SF,Fantasy' );

		$catList = array();
		$fantasy->alias( 'genre' )
			->with( ' ORDER BY gname ASC ' )
			->traverse( 'ownCategory', function( $cat ) use ( &$catList ) {
			$catList[] = $cat->gname;
		} );
		asrt( implode( ',', $catList ), 'Horror,SF' );
	}

	/**
	 * Traverse can only work with own-lists, otherwise infinite loops.
	 *
	 * @return void
	 */
	public function testSharedTraversal()
	{
		$friend = R::dispense( 'friend' );
		try {
			$friend->traverse( 'sharedFriend', function( $friend ){ } );
			fail();
		} catch( RedException $e ) {
			pass();
		}
	}
}
