<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * RedUNIT_Base_Namedparams
 *
 * @file    RedUNIT/Base/Namedparams.php
 * @desc    Test whether you can use named parameters in SQL snippets.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Namedparams extends Base
{
	/**
	 * Test usage of named parameters in SQL snippets.
	 * Issue #299 on Github.
	 * 
	 * @return void
	 */
	public function testNamedParamsInSnippets()
	{
		testpack( 'Test whether we can use named parameters in SQL snippets.' );

		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->title = 'book';
		$book->sharedPage[] = $page;
		R::store($book);
		
		//should not give error like: Uncaught [HY093] - SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters
		$books = $page->withCondition(' title = :title ', array( ':title' => 'book' ) )->sharedBook;

		asrt( count( $books ), 1 );

		//should not give error...
		$books = $page->withCondition( ' title = :title ', array( ':title' => 'book' ) )->sharedBook;

		asrt( count( $books ), 1 );

		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->title = 'book';
		$book->comment = 'comment';
		$page->title = 'page';
		$book->ownPage[] = $page;
		R::store( $book );

		//should also not give error..
		$count = $book->countOwn( 'page' );

		asrt( $count, 1 );

		$book = $book->fresh();

		//should also not give error..
		$count = $book->withCondition( ' title = ? ', array( 'page' ) )->countOwn( 'page' );
		
		asrt( $count, 1 );

		$book = $book->fresh();

		//should also not give error..
		$count = $book->withCondition( ' title = :title ', array( ':title' => 'page' ) )->countOwn( 'page' );

		asrt( $count, 1 );

		$book = $book->fresh();

		$pages = $book->withCondition( ' title = :title ', array( ':title' => 'page' ) )->ownPage;

		asrt( count( $pages ), 1 );

		//test with duplicate slots...
		$page = reset( $pages );
		$page2 = R::dispense( 'page' );
		$page2->ownPage[] = $page;
		R::store( $page2 );
		$page2 = $page2->fresh();
		$pages = $page2->withCondition( ' title = :title ', array( ':title' => 'page' ) )->ownPage;
		asrt( count( $pages ), 1 );

		//test with find()
		$books = R::getRedBean()->find( 'book', 
				  array(
						'title' => array('book')), 
				  ' AND title = :title ', array(':title'=>'book'));

		asrt( count( $books ), 1 );

		$books = R::getRedBean()->find( 'book', 
				  array(
						'title'   => array('book', 'book2'), 
						'comment' => array('comment', 'comment2')),
				  ' AND title = :title ', array(':title'=>'book'));

		asrt( count( $books ), 1 );

		//just check numeric works as well...
		$books = R::getRedBean()->find( 'book', 
				  array(
						'title'   => array('book', 'book2'), 
						'comment' => array('comment', 'comment2')),
				  ' AND title = ? ', array('book'));

		asrt( count( $books ), 1 );

		//just extra check to verify glue works
		$books = R::getRedBean()->find( 'book', 
				  array(
						'title'   => array('book', 'book2'), 
						'comment' => array('comment', 'comment2')),
				  ' ORDER BY id ');

		asrt( count( $books ), 1 );
		
	}
}
