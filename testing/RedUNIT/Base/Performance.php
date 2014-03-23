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
		echo 'Preparing database.'.PHP_EOL;
		R::nuke();
		
		//Prepare structure
		$book = R::dispense( 'book' );
		$book->title = 'book';
		$pages = R::dispense( 'page', 10 );
		foreach( $pages as $page ) {
			$page->content = 'lorem ipsum';
		}
		$book->xownPageList = $pages;
		$tags = R::dispense( 'tag', 6 );
		foreach( $tags as $tag ) {
			$tag->label = 'tag';
		}
		$book->sharedTagList = $tags;
		R::store( $book );
		echo 'READY.'.PHP_EOL;
	}
	
	/**
	 * CRUD performance.
	 * 
	 * @return void
	 */
	public function crud()
	{
		R::freeze( TRUE );
		
		for( $i = 0; $i < 100; $i++ ) {
			$book = R::dispense( 'book' );
			$book->title = 'Book '.$i;
			$page = R::dispense('page');
			$page->content = 'Content '.$i;
			$tag = R::dispense('tag');
			$tag->label = 'Tag '.$i;
			$book->noLoad()->ownPage[] = $page;
			$book->noLoad()->sharedTag[] = $tag;
			R::store( $book );
			$book = $book->fresh();
			$book->ownPage;
			$book->sharedTag;
			R::trash( $book );
		}
	}
}