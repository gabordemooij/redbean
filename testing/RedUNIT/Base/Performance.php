<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;

/**
 * Performance
 *
 * This performance test is used with runperf and xdebug profiler.
 *
 * @file    RedUNIT/Base/Performance.php
 * @desc    Performance testing.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Performance extends Base
{
	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setup()
	{
		R::nuke();

		//Prepare structure
		$book = R::dispense( 'book' );
		$book->title = 'book';
		$pages = R::dispense( 'page', 10 );
		foreach( $pages as $page ) {
			$page->content = 'lorem ipsum';
			$page->title = 'data';
			$page->sequence = 'data';
			$page->order = 'data';
			$page->columns = 'data';
			$page->paragraphs = 'data';
			$page->paragraphs1 = 'data';
			$page->paragraphs2 = 'data';
			$page->paragraphs3 = 'data';
			$page->paragraphs4 = 'data';
		}
		$book->xownPageList = $pages;
		$tags = R::dispense( 'tag', 6 );
		foreach( $tags as $tag ) {
			$tag->label = 'tag';
		}
		$book->sharedTagList = $tags;
		R::store( $book );
	}

	/**
	 * CRUD performance.
	 *
	 * @return void
	 */
	public function crud()
	{
		R::freeze( TRUE );

		$book = R::dispense( 'book' );
		$book->title = 'Book';
		$page = R::dispense('page');
		$page->content = 'Content';
		$page->title = 'data';
		$page->sequence = 'data';
		$page->order = 'data';
		$page->columns = 'data';
		$page->paragraphs = 'data';
		$page->paragraphs1 = 'data';
		$page->paragraphs2 = 'data';
		$page->paragraphs3 = 'data';
		$page->paragraphs4 = 'data';
		$tag = R::dispense('tag');
		$tag->label = 'Tag ';
		$book->noLoad()->ownPage[] = $page;
		$book->noLoad()->sharedTag[] = $tag;
		R::store( $book );
		$book = $book->fresh();
		$book->ownPage;
		$book->sharedTag;
		R::trash( $book );

	}

	/**
	 * CRUD performance Array Access.
	 *
	 * @return void
	 */
	public function crudaa()
	{
		R::freeze( TRUE );

		$book = R::dispense( 'book' );
		$book['title'] = 'Book';
		$page = R::dispense('page');
		$page['content'] = 'Content';
		$page['title'] = 'data';
		$page['sequence'] = 'data';
		$page['order'] = 'data';
		$page['columns'] = 'data';
		$page['paragraphs'] = 'data';
		$page['paragraphs1'] = 'data';
		$page['paragraphs2'] = 'data';
		$page['paragraphs3'] = 'data';
		$page['paragraphs4'] = 'data';
		$tag = R::dispense('tag');
		$tag['label'] = 'Tag ';
		$book->ownPage[] = $page;
		$book->noLoad()->sharedTag[] = $tag;
		R::store( $book );
		$book = $book->fresh();
		$book->ownPage;
		$book->sharedTag;
		R::trash( $book );

	}
}