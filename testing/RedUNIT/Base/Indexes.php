<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Indexes
 *
 * @file    RedUNIT/Base/Indexes.php
 * @desc    Tests whether indexes are applied properly.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Indexes extends Base {

	/**
	 * Tests whether a regular index is created properly.
	 *
	 * @return void
	 */
	public function testIndexCreation()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->ownPageList[] = $page;
		R::store( $book );
		$indexes = getIndexes( 'page' );
		asrt( in_array( 'index_foreignkey_page_book', $indexes ), TRUE );
	}

	/**
	 * Tests indexes on parent beans.
	 *
	 * @return void
	 */
	public function testIndexCreationParentBean()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$page->book = $book;
		R::store( $page );
		$indexes = getIndexes( 'page' );
		asrt( in_array( 'index_foreignkey_page_book', $indexes ), TRUE );
	}

	/**
	 * Tests indexes on link tables.
	 *
	 * @return void
	 */
	public function testIndexCreationMany2Many()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$category = R::dispense( 'category' );
		$book->sharedCategoryList[] = $category;
		R::store( $book );
		$indexes = getIndexes( 'book_category' );
		asrt( in_array( 'index_foreignkey_book_category_book', $indexes ), TRUE );
		asrt( in_array( 'index_foreignkey_book_category_category', $indexes ), TRUE );
		R::nuke();
		R::nuke();
		$book = R::dispense( 'book' );
		$category = R::dispense( 'category' );
		$category->sharedBookList[] = $book;
		R::store( $category );
		$indexes = getIndexes( 'book_category' );
		asrt( in_array( 'index_foreignkey_book_category_book', $indexes ), TRUE );
		asrt( in_array( 'index_foreignkey_book_category_category', $indexes ), TRUE );
	}

	/**
	 * Tests indexes on aliases.
	 *
	 * @return void
	 */
	public function testIndexCreationAlias()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$author = R::dispense( 'author' );
		$book->coAuthor = $author;
		R::store( $book );
		$indexes = getIndexes( 'book' );
		asrt( in_array( 'index_foreignkey_book_co_author', $indexes ), TRUE );
		R::nuke();
		$project = R::dispense( 'project' );
		$person = R::dispense( 'person' );
		$person->alias( 'teacher' )->ownProject[] = $project;
		$person2 = R::dispense( 'person' );
		$person2->alias( 'student' )->ownProject[] = $project;
		R::store( $person );
		$indexes = getIndexes( 'project' );
		asrt( in_array( 'index_foreignkey_project_teacher', $indexes ), TRUE );
		R::store( $person2 );
		$indexes = getIndexes( 'project' );
		asrt( in_array( 'index_foreignkey_project_student', $indexes ), TRUE );
	}

	/**
	 * Tests index fails.
	 *
	 * @return void
	 */
	public function testIndexCreationFail()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->author_id = 'a';
		R::store( $book );
		$indexes = getIndexes( 'book' );
		//should just work fine
		asrt( in_array( 'index_foreignkey_book_author', $indexes ), TRUE );
		//these should just pass, no indexes but no errors as well
		R::getWriter()->addIndex( 'book', 'bla', 'nonexist' );
		pass();
		R::getWriter()->addIndex( 'book', '@#$', 'nonexist' );
		pass();
		R::getWriter()->addIndex( 'nonexist', 'bla', 'nonexist' );
		pass();
		$indexesAfter = getIndexes( 'book' );
		asrt( count( $indexesAfter ), count( $indexes ) );
	}
}
