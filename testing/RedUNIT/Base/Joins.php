<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Joins
 *
 * @file    RedUNIT/Base/Joins.php
 * @desc    Tests joins in ownLists and trees.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Joins extends Base
{

	/**
	 * Tests joins with ownCount().
	 */
	public function testJoinsInCount()
	{
		R::nuke();
		$author = R::dispense( 'author' );
		$book = R::dispense( 'book' );
		$info = R::dispense( 'info' );
		$info->title = 'x';
		$author->xownBookList[] = $book;
		$book->info = $info;
		$book = R::dispense( 'book' );
		$info = R::dispense( 'info' );
		$info->title = 'y';
		$author->xownBookList[] = $book;
		$book->info = $info;
		R::store( $author );
		$author = $author->fresh();
		$books = $author->withCondition(' @joined.info.title != ? ', array('x'))->countOwn('book');
		asrt($books, 1);
		$books = $author->withCondition(' @joined.info.title != ? ', array('y'))->countOwn('book');
		asrt($books, 1);
		$books = $author->withCondition(' @joined.info.title IN (?,?) ', array('x','y'))->countOwn('book');
		asrt($books, 2);
	}

	/**
	 * Test Joins.
	 *
	 * @return void
	 */
	public function testJoins()
	{
		R::nuke();

		list($a1, $a2, $a3) = R::dispense('area', 3);
		list($p1, $p2) = R::dispense('person', 2);
		list($v1, $v2, $v3, $v4) = R::dispense('visit', 4);
		$a1->name = 'Belgium';
		$a2->name = 'Arabia';
		$a3->name = 'France';
		$v1->person = $p1;
		$v2->person = $p1;
		$v3->person = $p2;
		$v4->person = $p2;
		$v1->area = $a3;
		$v2->area = $a2;
		$v3->area = $a2;
		$v4->area = $a1;
		$v1->label = 'v1 to France';
		$v2->label = 'v2 to Arabia';
		$v3->label = 'v3 to Arabia';
		$v4->label = 'v4 to Belgium';
		R::storeAll( array($v1,$v2,$v3,$v4) );
		$visits = $p1->ownVisit;
		asrt( is_array( $visits ), TRUE );
		asrt( count( $visits ), 2 );
		$names = array();
		foreach( $visits as $visit ) {
			asrt( isset( $visit->label ), TRUE );
			asrt( isset( $visit->name ), FALSE );
			asrt( isset( $visit->visit_id ), FALSE );
			$names[] = $visit->label;
		}
		$labelList = implode( ',', $names );
		asrt( $labelList, 'v1 to France,v2 to Arabia' );
		$visits = $p1
			->with('ORDER BY @joined.area.name ASC')->ownVisit;
		asrt( is_array( $visits ), TRUE );
		asrt( count( $visits ), 2 );
		$names = array();
		foreach( $visits as $visit ) {
			asrt( isset( $visit->label ), TRUE );
			asrt( isset( $visit->name ), FALSE );
			asrt( isset( $visit->visit_id ), FALSE );
			$names[] = $visit->label;
		}
		$labelList = implode( ',', $names );
		asrt( $labelList, 'v2 to Arabia,v1 to France' );
	}

	/**
	 * Helper for the next test.
	 *
	 * @param array  $books      the books we are going to check
	 * @param string $numberList the numbers that are expected
	 *
	 * @return void
	 */
	private function checkBookNumbers( $books, $numberList )
	{
		$numbers = explode( ',', $numberList );
		asrt( is_array( $books ), TRUE );
		asrt( count( $books ), count( $numbers ) );
		$bookNumbers = '';
		$bookNumberArray = array();
		foreach( $books as $book ) {
			asrt( isset( $book->num ), TRUE );
			asrt( isset( $book->title), FALSE );
			$bookNumberArray[] = $book->num;
		}
		$bookNumbers = implode( ',', $bookNumberArray);
		asrt( $bookNumbers, $numberList );
	}

	/**
	 * Tests the more complicated scenarios for
	 * with-joins.
	 *
	 * @return void
	 */
	private function testComplexCombinationsJoins()
	{
		$author = R::dispense( 'author' );
		$books = R::dispense( 'book', 4 );
		$books[0]->num = 0;
		$books[1]->num = 1;
		$books[2]->num = 2;
		$books[3]->num = 3;
		$books[0]->info = R::dispense('info')->setAttr('title', 'Learning PHP');
		$books[1]->info = R::dispense('info')->setAttr('title', 'Learning PHP and JavaScript');
		$books[2]->info = R::dispense('info')->setAttr('title', 'Learning Cobol');
		$books[3]->info = R::dispense('info')->setAttr('title','Gardening for Beginners');
		$books[0]->category = R::dispense('category')->setAttr('title', 'computers');
		$books[1]->category = R::dispense('category')->setAttr('title', 'computers');
		$books[2]->category = R::dispense('category')->setAttr('title', 'computers');
		$books[3]->category = R::dispense('category')->setAttr('title','gardening');
		$author->ownBookList = $books;
		R::store($author);
		//Base test...
		$books = $author->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2,3' );
		//Just a basic Join...
		$books = $author->withCondition(' @joined.info.title LIKE ? ORDER BY book.num ASC ', array( '%PHP%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1' );
		//Mix Join and criteria
		$books = $author->withCondition(' @joined.info.title LIKE ? AND num > 0 ORDER BY book.num ASC ', array( '%PHP%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '1' );
		//Basic join
		$books = $author->withCondition(' @joined.info.title LIKE ? ORDER BY book.num ASC', array( '%ing%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2,3' );
		//Two joins
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.category.title = ? ORDER BY book.num ASC', array( '%ing%', 'computers' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2' );
		//Join the same type twice... and order
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.category.title = ? ORDER BY @joined.info.title ASC ', array( '%ing%', 'computers' ) )->ownBookList;
		$this->checkBookNumbers( $books, '2,0,1' );
		//Join the same type twice
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.info.title LIKE ? ORDER BY book.num ASC', array( '%ing%', '%Learn%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2' );
		//Join the same type 3 times and order
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.info.title LIKE ? ORDER BY @joined.info.title DESC', array( '%ing%', '%Learn%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '1,0,2' );
		//Join the same type 3 times and order and limit
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.info.title LIKE ? ORDER BY @joined.info.title DESC LIMIT 1', array( '%ing%', '%Learn%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '1' );
		//Other combinations I can think of...
		$books = $author->withCondition(' @joined.category.title LIKE ? ORDER BY @joined.info.title DESC', array( '%ing%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '3' );
		$books = $author->withCondition(' @joined.category.title LIKE ? AND num < 4 ORDER BY @joined.info.title DESC', array( '%ing%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '3' );
		//multiple ordering
		$books = $author->with(' ORDER BY @joined.category.title ASC, @joined.info.title ASC' )->ownBookList;
		$this->checkBookNumbers( $books, '2,0,1,3' );
		$books = $author->with(' ORDER BY @joined.category.title DESC, @joined.info.title ASC' )->ownBookList;
		$this->checkBookNumbers( $books, '3,2,0,1' );
		$books = $author->with(' ORDER BY @joined.category.title DESC, @joined.info.title ASC LIMIT 2' )->ownBookList;
		$this->checkBookNumbers( $books, '3,2' );
	}

	/**
	 * Tests the more complicated scenarios for
	 * with-joins.
	 *
	 * @return void
	 */
	public function testComplexInFrozenMode()
	{
		R::freeze( FALSE );
		$this->testComplexCombinationsJoins();
		R::freeze( TRUE );
		$this->testComplexCombinationsJoins();
		R::freeze( FALSE );
	}

	/**
	 * Tests R::setNarrowFieldMode() and
	 * OODBBean::ignoreJoinFeature().
	 */
	public function testSystemWideSettingsForJoins()
	{
		R::nuke();
		$author = R::dispense( 'author' );
		$book = R::dispense( 'book' );
		$info = R::dispense( 'info' );
		$info->title = 'x';
		$author->xownBookList[] = $book;
		$book->info = $info;
		R::store( $author );
		$author = $author->fresh();
		$books = $author->withCondition(' @joined.info.title != ? ', array('y1') )->xownBookList;
		$firstBook = reset( $books );
		asrt( isset( $firstBook->title ), FALSE );
		R::setNarrowFieldMode( FALSE );
		$author = $author->fresh();
		$books = $author->withCondition(' @joined.info.title != ? ', array('y2') )->xownBookList;
		$firstBook = reset( $books );
		asrt( isset( $firstBook->title ), TRUE );
		R::setNarrowFieldMode( TRUE );
	}
}
