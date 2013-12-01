<?php
/**
 * RedUNIT_Base_Preloading
 *
 * @file    RedUNIT/Base/Preloading.php
 * @desc    Tests eager loading for parent beans.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Preloading extends RedUNIT_Base
{
	
	/**
	 * Test whether we can still preload in case we renamed an association.
	 * This is a very theoretical test since the preloader simply relies
	 * on the Association Manager in the end, so this should never cause
	 * trouble, but why not have a test to ensure this anyway?
	 * 
	 * @return void
	 */
	public function testPreloadingWithVia()
	{
		testpack( 'Can we preload with via()?' );
		
		R::nuke();
		R::renameAssociation( 'person_village', 'people' );
		$village = R::dispense( 'village' );
		$john = R::dispense( 'person' )->setAttr( 'name', 'John' );
		$people = R::dispense( 'people' );
		$people->village = $village;
		$people->person = $john;
		R::store( $people );
		$village = $village->fresh();

		R::preload( $village, 
			array( 'sharedPerson' =>
				array( 'person' ) 
			)
		);
		R::nuke();
		asrt( count( $village->sharedPerson ), 1 );
	}
	
	/**
	 * Tests wheter we can use aliased own-lists when preloading.
	 * To load an aliased own-list use the slash (/) to indicate the alias.
	 * 
	 * Example: 
	 * 
	 * R::preload( $pete,
	 *		array( 'ownBook/illustrator' => 
	 *			array( 'book' )
	 *		)
	 * );
	 * 
	 * Preloads all books where $pete has been the illustrator.
	 * This is the preload equivalent of:
	 * 
	 * $pete->alias( 'illustrator' )->ownBook;
	 * 
	 * Here we test the preload variant.
	 */
	public function testPreloadingAliasedOwnList()
	{
		testpack('Test whether we can use aliasing in preloading own-lists' );

		list( $church, $castle ) = R::dispense( 'building', 2 );
		$priest = R::dispense( 'person' );
		$priest->myrole = 'priest';
		$church->ownership = $priest;
		$castle->ownership = $priest;
		
		R::store( $church );
		R::store( $castle );
		
		$priest = $priest->fresh();

		R::preload( $priest, array(
			 'ownBuilding/ownership' => 'building',
		) );

		R::nuke();
		asrt( count( $priest->ownBuilding ), 2 );

		//Same as above but also with an SQL condition...

		list( $church, $castle ) = R::dispense( 'building', 2 );
		$priest = R::dispense( 'person' );
		$priest->myrole = 'priest';
		$church->ownership = $priest;
		$castle->ownership = $priest;
		
		$castle->isMonument = 'yes';
		R::store( $church );
		R::store( $castle );
		
		$priest = $priest->fresh();

		R::preload( $priest, array(
			'ownBuilding/ownership' => 
				array( 'building',
					array( ' is_monument = ? ', 
						array( 'yes' )
					)
				)
			)
		);

		R::nuke();
		asrt( count( $priest->ownBuilding ), 1 );
	}

	/**
	 * Tests whether we can filter parent beans using SQL snippets when
	 * preloading. Sometimes you want to be able to only load certain parents when
	 * preloading. This is normally not an issue because a single bean can only have one
	 * parent, but when using the preloading you are forced to load all parens and this may
	 * not be the desired outcome. You might be interested in only a subset of parents.
	 * This test checks if you can use SQL snippets to filter parents.
	 * 
	 * Also tests double filtering for parents, i.e. when filtering parents their parents
	 * should not cause all previous parents to load.
	 * 
	 * @return void
	 */
	public function testParentPreloadWithSQL()
	{
		testpack( 'Test whether we can preload a parent with an SQL snippet.' );
		
		R::nuke();
		list( $bookX, $bookY ) = R::dispense( 'book', 2 );

		$bookX->title = 'x';
		$bookY->title = 'y';

		$pages = R::dispense( 'page', 5 );

		foreach( $pages as $page ) {
			$page->belongsto = 'x';
		}

		$bookX->ownPage = $pages;

		$pages = R::dispense('page',5);

		foreach( $pages as $page ) {
			$page->belongsto = 'y';
		}

		$bookY->ownPage = $pages;

		R::storeAll( array( $bookX, $bookY ) );

		$pages = R::find( 'page' );
		R::preload( $pages, array( 'book' => array( 'book', array( ' title = ? ', array( 'x' ) ) ) ) );
		R::nuke();

		foreach( $pages as $page ) {
			if ( $page->belongsto === 'x' ) {
				asrt( ($page->book->id > 0 ), true );
				asrt( ($page->book->title ), 'x' );
			} elseif( $page->belongsto === 'y' ) {
				asrt(( $page->book->id === 0 ), true );
				asrt( (string) $page->book_id, (string) $bookY->id );
			} 
		}

		//Now we test double filtering and a combination of parent- and child
		//preloading...

		list( $author1, $author2 )             = R::dispense( 'author', 2 );
		list( $bookA, $bookB, $bookC )         = R::dispense( 'book', 3 );
		list( $page1, $page2, $page3, $page4 ) = R::dispense( 'page', 4 );

		$author1->myname = 1;
		$author2->myname = 2;

		$bookA->writer = $author1;
		$bookB->writer = $author2;
		$bookC->writer = $author1;

		$bookA->title = 'a';
		$bookB->title = 'b';
		$bookC->title = 'c';

		$page1->book = $bookA;
		$page2->book = $bookB;
		$page3->book = $bookA;
		$page4->book = $bookC;

		$page1->num = 1;
		$page2->num = 2;
		$page3->num = 3;
		$page4->num = 4;

		$page1->ownText = R::dispense( 'text', 10 );
		$page2->ownText = R::dispense( 'text', 10 );
		$page3->ownText = R::dispense( 'text', 10 );
		$page4->ownText = R::dispense( 'text', 10 );

		$page1->sharedTag = R::dispense( 'tag', 2 );

		R::storeAll( array( $page1, $page2, $page3, $page4 ) );

		$pages = R::find('page');

		R::preload($pages, array(
			 'ownText'     => 'text', 
			 'book'        => 
				array( 'book', 
					array( ' title = ?  OR title = ? ', 
						 array( 'a', 'b' ) ) ),
			 'sharedTag'   => 
				array( 'tag',  
					array( ' tag.id < 9999 ' ) 
				),
			 'book.writer' => 
				array( 'author', 
					array( ' myname = 2 ' ) 
				) //this *SHOULD NOT* cause the other book to be loaded as well.
		));
		
		R::nuke();
		foreach( $pages as $page ) {
			
			asrt( count( $page->ownText ), 10 ); //have ALL texts been preloaded ?
			if ( (string) $page->num === '1' ) asrt( count( $page->sharedTag ), 2 ); //All tags?
			
			$book = $page->book;
			if ( (string) $page->num === '4' ) asrt( $book->title, NULL ); //not c
			if ( (string) $page->num === '3' ) asrt( $book->title, 'a' );
			if ( (string) $page->num === '2' ) asrt( $book->title, 'b' );
			if ( (string) $page->num === '1' ) asrt( $book->title, 'a' );
			if ( (string) $book->title === 'b') asrt( (string) $book->writer->myname, '2' ); 
			if ( (string) $book->title === 'a') asrt( $book->writer->id, 0 ); 
			if ( (string) $book->title === 'c') fail(); 
		}
		
	}
	
	/**
	 * Test double filtering own-lists and shared lists.
	 * If we load own-lists of own-lists this should not cause list members previously
	 * filtered out to get loaded anyway. Same holds for shared lists.
	 * This is called double filtering and it's a real power feature on the edge of
	 * the RedBeanPHP scope.
	 * 
	 * @return void
	 */
	public function testDoubleFilteringWithSQL()
	{

		$village = R::dispense( 'village' );
		list( $redArmy, $greenArmy, $yellowArmy ) = R::dispense( 'army', 3 );
		$redArmy->color = 'red';
		$greenArmy->color = 'green';
		$yellowArmy->color = 'yellow';
		list( $redFlag, $greenFlag, $yellowFlag, $whiteFlag ) = R::dispense( 'flag', 4 );
		$redFlag->color = 'red';
		$greenFlag->color = 'green';
		$yellowFlag->color = 'yellow';
		$whiteFlag->color = 'white';
		list( $church, $castle, $tavern ) = R::dispense( 'building', 3 );
		$church->kind = 'church';
		$castle->kind = 'castle';
		$tavern->kind = 'tavern';
		list( $monk, $knight, $bartender ) = R::dispense( 'person', 3 );
		$monk->myrole = 'monk';
		$knight->myrole = 'knight';
		$bartender->myrole = 'bartender';
		list( $tagIntersting, $tagNew ) = R::dispense( 'tag', 2 );
		$tagIntersting->label = 'interesting';
		$tagNew->label = 'new';

		$yellowArmy->sharedTag[] = $tagIntersting;
		$greenArmy->sharedTag[] = $tagIntersting;

		$redArmy->ownFlag = array( $redFlag );
		$greenArmy->ownFlag = array( $greenFlag, $whiteFlag );
		$yellowArmy->ownFlag = array( $yellowFlag );

		$monk->sharedTag[] = $tagNew;

		$church->ownPerson[] = $monk;
		$castle->ownPerson = array($knight, $monk);
		$tavern->ownPerson[] = $bartender;

		$village->sharedArmy = array($redArmy, $greenArmy, $yellowArmy);
		$village->ownBuilding = array($castle, $church, $tavern);

		R::store($village);
		$village = $village->fresh();

		R::preload($village, 
			array(
				'ownBuilding' => array( 'building', 
					array(' kind = ? ', 
						array( 'church' )
					)
				),
				'*.ownPerson' => 
					array( 'person', 
						array(' myrole = ? ', 
							array('monk' )
						)
					),
				'sharedArmy'  => 
					array( 'army', 
						array(' color = ? ', 
							 array('green')
						)
					),
				'sharedArmy.ownFlag'   => 
					array( 'flag', 
						array( ' color = ? OR color = ? ', 
							 array( 'green', 'white' )
						)
					),
				'sharedArmy.sharedTag' => 
					array( 'tag',  
						array(' label = ? ', 
							 array( 'interesting' )
						)
					),
				)
			);

		R::nuke();

		asrt( count($village->ownBuilding), 1 );
		$building = reset($village->ownBuilding );
		asrt( count($building->ownPerson), 1 );
		$monk = reset($building->ownPerson );
		asrt( $monk->myrole, 'monk' );
		
		asrt( count($village->sharedArmy ), 1 );
		$army = reset( $village->sharedArmy );
		asrt( $army->color, 'green' );
		asrt( count($army->ownFlag ), 2 );
		asrt( count($army->sharedTag ), 1 );
	}
	
	/**
	 * Test for issue #328 (excerpt): 
	 * Previously, if we wanted to use the array form without an SQL snippet, it will fail:
	 *	R::preload($beans, array('manager' => array('people')));
	 * It fixes the ability to have conditional snippets without bindings:
	 *	R::preload($beans, array('ownManager' => array('manager', array("name = 'john'"))))
	 * 
	 * @return void
	 */
	public function testAPreloadWithMissingParams()
	{	
		testpack( 'Test flexible method signature (issue #328).' );

		R::nuke();
		
		$beans = R::dispense( 'user', 2 );
		foreach( $beans as $bean ) {
			$bean->people = R::dispense( 'manager' );
		}
		
		R::storeAll( $beans );
		R::preload( $beans, array( 'manager' => array( 'people' ) ) );
		R::nuke();
		$beans = R::dispense( 'company', 2 );
		foreach( $beans as $company ) {
			$company->ownManager[] = R::dispense( 'manager' )->setAttr( 'name', 'John' );
		}
		$ids   = R::storeAll( $beans );
		$beans = R::batch( 'company', $ids );
		R::preload( $beans, array( 'ownManager' => array( 'manager', array( "name = 'John'" ) ) ) );
		
		$bean    = reset( $beans );
		$manager = reset( $bean->ownManager );
		asrt( $manager->name, 'John' );
		
	}
	
	/**
	 * Test Preload save.
	 * 
	 * @return void 
	 */
	public function testPreloadSave()
	{
		testpack( 'Can we save in preload ?' );

		$publisher = R::dispense( 'publisher' );

		list( $book, $magazine ) = R::dispense( 'book', 2 );

		$book->ownPage       = R::dispense( 'page', 3 );

		$magazine->ownPage   = R::dispense( 'page', 2 );
		$magazine->title     = 'magazine';

		$firstPage           = reset( $book->ownPage );
		$secondPage          = reset( $magazine->ownPage );

		$book->publisher     = $publisher;

		$magazine->publisher = $publisher;

		R::storeAll( array( $book, $magazine ) );

		$firstPage  = $firstPage->fresh();
		$secondPage = $secondPage->fresh();

		$pages      = array( $firstPage, $secondPage );

		R::each( $pages, 'book,*.publisher', function ( $page, $book, $publisher ) {
			R::storeAll( array( $page, $book, $publisher ) );
		} );

		asrt( R::count( 'publisher' ), 1 );
		asrt( R::count( 'book' ), 2 );
		asrt( R::count( 'page' ), 5 );

		testpack( 'Can we re-preload ?' );

		$books = $publishers = array();

		R::each( $pages, 'book,*.publisher', function ( $page, $book, $publisher ) use ( &$pages, &$books, &$publishers ) {
			$books[]      = $book;
			$publishers[] = $publisher;
		} );

		asrt( count( $books ), 2 );

		$foundMagazine = NULL;
		foreach ( $books as $book ) {
			if ( $book->title === 'magazine' ) {
				$foundMagazine = $book;
			}
		}

		asrt( ( $foundMagazine instanceof RedBean_OODBBean ), TRUE );

		asrt( count( $foundMagazine->ownPage ), 2 );

		testpack( 'Does deleting still work after preloading ?' );

		$firstPageOfMag = reset( $foundMagazine->ownPage );

		$firstID        = $firstPageOfMag->id;

		unset( $foundMagazine->ownPage[$firstID] );

		R::store( $foundMagazine );

		asrt( count( $foundMagazine->ownPage ), 1 );
	}

	/**
	 * Test shadow updates.
	 * 
	 * @return void
	 */
	public function testShadowUpdate()
	{
		testpack( 'Is the shadow updated ?' );

		$book = R::dispense( 'book' );

		$book->ownPage = R::dispense( 'page', 3 );

		$firstPage = reset( $book->ownPage );

		R::store( $book );

		$book = $book->fresh();

		R::preload( array( $book ), array( 'ownPage' => 'page' ) );

		R::store( $book );

		$book = $book->fresh();

		// Don't we lose beans when saving?
		asrt( count( $book->ownPage ), 3 );

		$book = $book->fresh();

		R::preload( array( $book ), array( 'ownPage' => 'page' ) );

		// Can we delete bean from list if bean has been preloaded ?
		unset( $book->ownPage[$firstPage->id] );

		R::store( $book );

		$book = $book->fresh();

		asrt( count( $book->ownPage ), 2 );

		$book = $book->fresh();

		R::preload( array( $book ), array( 'ownPage' => 'page' ) );

		// Can we add a page?
		$book->ownPage[] = R::dispense( 'page' );

		R::store( $book );

		$book = $book->fresh();

		asrt( count( $book->ownPage ), 3 );
	}

	/**
	 * Test list manipulation i.c.w preloading.
	 * 
	 * @return void
	 */
	public function testListManipulationAndPreloader()
	{
		$book = R::dispense( 'book' );

		$book->sharedPage = R::dispense( 'page', 3 );

		$firstPage = reset( $book->sharedPage );

		R::store( $book );

		$book = $book->fresh();

		R::preload( array( $book ), array( 'sharedPage' => 'page' ) );

		R::store( $book );

		$book = $book->fresh();

		// Don't we lose beans when saving?
		asrt( count( $book->sharedPage ), 3 );

		$book = $book->fresh();

		R::preload( array( $book ), array( 'sharedPage' => 'page' ) );

		// Can we delete bean from list if bean has been preloaded ?
		unset( $book->sharedPage[$firstPage->id] );

		R::store( $book );

		$book = $book->fresh();

		asrt( count( $book->sharedPage ), 2 );

		$book = $book->fresh();

		R::preload( array( $book ), array( 'sharedPage' => 'page' ) );

		// Can we add a page?
		$book->sharedPage[] = R::dispense( 'page' );

		R::store( $book );

		$book = $book->fresh();

		asrt( count( $book->sharedPage ), 3 );
	}

	/**
	 * Test no preloader.
	 * 
	 * @return void
	 */
	public function testNoPreload()
	{
		$books = R::dispense( 'book', 3 );

		$i = 0;

		foreach ( $books as $book ) {
			$i++;

			$book->name      = $i;

			$book->ownPage[] = R::dispense( 'page' )->setAttr( 'name', $i );

			$book->author    = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->coauthor  = R::dispense( 'author' )->setAttr( 'name', $i );
		}

		R::storeAll( $books );

		$books = R::find( 'book' );

		R::nuke();

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->author->id, 0 );
		}
	}

	/**
	 * Test basic preloader.
	 * 
	 * @return void
	 */
	public function testPreload()
	{
		$books = R::dispense( 'book', 3 );

		$i = 0;

		foreach ( $books as $book ) {
			$i++;

			$book->name      = $i;

			$book->ownPage[] = R::dispense( 'page' )->setAttr( 'name', $i );

			$book->author    = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->coauthor  = R::dispense( 'author' )->setAttr( 'name', $i );
		}

		R::storeAll( $books );

		$books = R::find( 'book' );

		R::preload( $books, array( 'author' ) );

		R::nuke();

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->author->name, strval( ++$i ) );
		}
	}

	/**
	 * Test preloader and aliasing.
	 * 
	 * @return void
	 */
	public function testAliasedPreload()
	{
		$books = R::dispense( 'book', 3 );

		$i = 0;

		foreach ( $books as $book ) {
			$i++;

			$book->name      = $i;

			$book->ownPage[] = R::dispense( 'page' )->setAttr( 'name', $i );

			$book->author    = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->coauthor  = R::dispense( 'author' )->setAttr( 'name', $i );
		}

		R::storeAll( $books );

		$books = R::find( 'book' );

		R::preload( $books, array( 'coauthor' => 'author' ) );

		R::nuke();

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->fetchAs( 'author' )->coauthor->name, strval( ++$i ) );
		}
	}

	/**
	 * Test various preloading combinations.
	 * 
	 * @return void
	 */
	public function testCombinedAndMultiple()
	{
		$books = R::dispense( 'book', 3 );

		$i = 0;

		foreach ( $books as $book ) {
			$i++;

			$book->name       = $i;

			$book->ownPage[]  = R::dispense( 'page' )->setAttr( 'name', $i );

			$book->author     = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->coauthor   = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->collection = R::dispense( 'collection' )->setAttr( 'name', $i );
		}

		R::storeAll( $books );

		$books = R::find( 'book' );

		R::preload( $books, array( 'coauthor' => 'author', 'author', 'collection' ) );

		R::nuke();

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->author->name, strval( ++$i ) );
		}

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->fetchAs( 'author' )->coauthor->name, strval( ++$i ) );
		}

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->collection->name, strval( ++$i ) );
		}

		// Crud
		$books = R::dispense( 'book', 3 );

		$i = 0;

		foreach ( $books as $book ) {
			$i++;

			$book->name       = $i;

			$book->ownPage[]  = R::dispense( 'page' )->setAttr( 'name', $i );

			$book->author     = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->coauthor   = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->collection = R::dispense( 'collection' )->setAttr( 'name', $i );
		}

		R::storeAll( $books );

		$books = R::find( 'book' );

		R::preload( $books, array( 'coauthor' => 'author', 'author', 'collection' ) );

		$i = 0;
		foreach ( $books as $book ) {
			$book->author->name .= 'nth';
		}

		$i = 0;
		foreach ( $books as $book ) {
			$book->fetchAs( 'author' )->coauthor->name .= 'nth';
		}

		$i = 0;
		foreach ( $books as $book ) {
			$book->collection->name .= 'nth';
		}

		R::storeAll( $books );

		$books = R::find( 'books' );

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->author->name, strval( ++$i ) . 'nth' );
		}

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->fetchAs( 'author' )->coauthor->name, strval( ++$i ) . 'nth' );
		}

		$i = 0;
		foreach ( $books as $book ) {
			asrt( $book->collection->name, strval( ++$i ) . 'nth' );
		}
	}

	/**
	 * Test preloading with multiple items having the same parent.
	 * 
	 * @return void
	 */
	public function testMultipleSameParents()
	{
		$author = R::dispense( 'author' );

		$author->setAttr( 'name', 'John' );

		R::store( $author );

		$books = R::dispense( 'book', 3 );

		$books[0]->title = 'First book';
		$books[1]->title = 'Second book';
		$books[2]->title = 'Third book';

		$author->ownBook = $books;

		R::store( $author );

		$collection = R::findAll( 'book' );

		R::preload( $collection, array( 'author' ) );

		R::nuke();

		foreach ( $collection as $item ) {
			asrt( $item->author->name, 'John' );
		}
	}

	public function testNested()
	{
		$authors = R::dispense( 'author', 2 );

		foreach ( $authors as $author ) {
			$author->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText[] = R::dispense( 'text', 1 );
				}
			}
		}

		R::storeAll( $authors );

		$texts = R::find( 'text' );

		R::nuke();

		$text = reset( $texts );

		asrt( (int) ( $text->page->id ), 0 );
	}

	/**
	 * Test nested preloading.
	 * 
	 * @return void
	 */
	public function testNestedPreload()
	{
		$authors = R::dispense( 'author', 2 );
		foreach ( $authors as $author ) {
			$author->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText = R::dispense( 'text', 2 );
				}
			}
		}

		R::storeAll( $authors );

		$texts = R::find( 'text' );

		R::preload( $texts, array( 'page', 'page.book', 'page.book.author' ) );

		R::nuke();

		$text = reset( $texts );

		asrt( ( $text->page->id ) > 0, TRUE );
		asrt( ( $text->page->book->id ) > 0, TRUE );
		asrt( ( $text->page->book->author->id ) > 0, TRUE );

	}

	/**
	 * Test nested preloading with aliasing.
	 * 
	 * @return void
	 */
	public function testNestedPreloadAlias()
	{
		$authors = R::dispense( 'author', 2 );
		foreach ( $authors as $author ) {
			$author->alias( 'coauthor' )->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->alias( 'coauthor' )->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText = R::dispense( 'text', 2 );
				}
			}
		}

		R::storeAll( $authors );
		
		$texts = R::find( 'text' );

		R::preload( $texts, array( 'page', 'page.book', 'page.book.coauthor' => 'author' ) );

		R::nuke();

		$text = reset( $texts );

		asrt( ( $text->page->id ) > 0, TRUE );
		asrt( ( $text->page->book->id ) > 0, TRUE );
		asrt( ( $text->page->book->fetchAs( 'author' )->coauthor->id ) > 0, TRUE );
	}

	/**
	 * Test preloading own list with short notation.
	 * 
	 * @return void
	 */
	public function testPreloadOwnlistShort()
	{
		$tree = R::dispense( 'tree' );

		$tree->ownLeaf = R::dispense( 'leaf', 3 );

		$id = R::store( $tree );

		$tree = R::load( 'tree', $id );

		R::preload( $tree, 'ownLeaf|leaf' );

		R::nuke();

		asrt( count( $tree->ownLeaf ), 3 );
	}

	/**
	 * Test preloading own list.
	 * (also short notation)
	 * 
	 * @return void
	 */
	public function testPreloadOwnlist()
	{
		$authors = R::dispense( 'author', 2 );
		foreach ( $authors as $author ) {
			$author->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText = R::dispense( 'text', 2 );
				}
			}
		}

		R::storeAll( $authors );

		$authors = R::find( 'author' );

		R::preload( $authors, 'ownBook|book,ownBook.ownPage|page,ownBook.ownPage.ownText|text' );

		R::nuke();

		$author = reset( $authors );

		asrt( count( $author->ownBook ), 2 );

		$book = reset( $author->ownBook );

		asrt( count( $book->ownPage ), 2 );

		$page = reset( $book->ownPage );

		asrt( count( $page->ownText ), 2 );
	}

	/**
	 * test preloading of shared lists.
	 * 
	 * @return void
	 */
	public function testPreloadSharedLists()
	{
		list( $a1, $a2, $a3 ) = R::dispense( 'army', 3 );
		list( $v1, $v2, $v3 ) = R::dispense( 'village', 3 );

		$v1->name = 'a';
		$v2->name = 'b';
		$v3->name = 'c';

		$a1->name = 'one';
		$a2->name = 'two';
		$a3->name = 'three';

		$a1->sharedVillage = array( $v1, $v3 );
		$a2->sharedVillage = array( $v3, $v1, $v2 );
		$a3->sharedVillage = array();

		list( $g, $e ) = R::dispense( 'people', 2 );

		$g->nature = 'good';
		$e->nature = 'evil';

		$v1->sharedPeople = array( $g );
		$v2->sharedPeople = array( $e, $g );
		$v3->sharedPeople = array( $g );

		R::storeAll( array( $a1, $a2, $a3 ) );

		$armies = R::find( 'army' );

		R::each(
			$armies,
			array( 'sharedVillage' => 'village', 'sharedVillage.sharedPeople' => 'people' ),
			function ( $army, $villages, $people ) {
				if ( $army->name == 'one' ) {
					$names = array();

					foreach ( $villages as $village ) {
						$names[] = $village->name;
					}

					sort( $names );

					$names = implode( ',', $names );

					asrt( $names, 'a,c' );
				}

				if ( $army->name == 'two' ) {
					$names = array();

					foreach ( $villages as $village ) {
						$names[] = $village->name;
					}

					sort( $names );

					$names = implode( ',', $names );

					asrt( $names, 'a,b,c' );
				}

				if ( $army->name == 'three' ) {
					asrt( count( $villages ), 0 );
				}
			}
		);

		R::nuke();

		foreach ( $armies as $army ) {
			$villages = $army->sharedVillage;

			$ppl = array();

			foreach ( $villages as $village ) {
				$ppl = array_merge( $ppl, $village->sharedPeople );
			}

			if ( $army->name == 'one' ) {
				asrt( count( $villages ), 2 );
				asrt( count( $ppl ), 2 );

				foreach ( $ppl as $p ) {
					if ( $p->nature !== 'good' ) fail();
				}

				$names = array();

				foreach ( $villages as $village ) {
					$names[] = $village->name;
				}

				sort( $names );

				$names = implode( ',', $names );

				asrt( $names, 'a,c' );

				$natures = array();

				foreach ( $ppl as $p ) {
					$natures[] = $p->nature;
				}

				sort( $natures );

				$natures = implode( ',', $natures );

				asrt( $natures, 'good,good' );
			}

			if ( $army->name == 'two' ) {
				asrt( count( $villages ), 3 );
				asrt( count( $ppl ), 4 );

				$names = array();

				foreach ( $villages as $village ) {
					$names[] = $village->name;
				}

				sort( $names );

				$names = implode( ',', $names );

				asrt( $names, 'a,b,c' );

				$natures = array();

				foreach ( $ppl as $p ) {
					$natures[] = $p->nature;
				}

				sort( $natures );

				$natures = implode( ',', $natures );

				asrt( $natures, 'evil,good,good,good' );
			}

			if ( $army->name == 'three' ) {
				asrt( count( $villages ), 0 );
				asrt( count( $ppl ), 0 );
			}
		}

		// Now test with empty beans
		$authors = R::dispense( 'author', 2 );

		R::storeAll( $authors );

		$authors = R::find( 'author' );

		R::preload( $authors, array( 'ownBook' => 'book', 'ownBook.ownPage' => 'page', 'ownBook.ownPage.ownText' => 'text' ) );

		$author = reset( $authors );

		asrt( count( $author->ownBook ), 0 );

		$texts = R::dispense( 'text', 2 );

		R::storeAll( $texts );

		$texts = R::find( 'text' );

		R::preload( $texts, array( 'page', 'page.book' ) );

		$text = reset( $texts );

		asrt( $text->page, NULL );
	}

	/**
	 * Test preloading with closure (each). 
	 *
	 * @return void
	 */
	public function testClosure()
	{
		$books = R::dispense( 'book', 3 );

		$i = 0;

		foreach ( $books as $book ) {
			$i++;

			$book->name = $i;

			$book->ownPage[] = R::dispense( 'page' )->setAttr( 'name', $i );

			$book->author = R::dispense( 'author' )->setAttr( 'name', $i );

			$book->coauthor = R::dispense( 'author' )->setAttr( 'name', $i );
		}

		R::storeAll( $books );

		$books = R::find( 'book' );

		$hasNuked = FALSE;

		R::preload(
			$books,
			'author',
			function ( $book, $author ) use ( &$hasNuked ) {
				if ( !$hasNuked ) {
					R::nuke();

					$hasNuked = TRUE;
				}

				asrt( $book->getMeta( 'type' ), 'book' );
				asrt( $author->getMeta( 'type' ), 'author' );
			}
		);
	}

	/**
	 * Test preloading with closure and abbreviations (*).
	 * 
	 * @return void
	 */
	public function testClosureAbbreviations()
	{
		$authors = R::dispense( 'author', 2 );
		foreach ( $authors as $author ) {
			$author->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText = R::dispense( 'text', 2 );
				}
			}
		}

		R::storeAll( $authors );

		$texts = R::find( 'text' );

		$hasNuked = FALSE;

		R::preload(
			$texts,
			'page,*.book,*.author',
			function ( $text, $page, $book, $author ) use ( &$hasNuked ) {
				if ( !$hasNuked ) {
					R::nuke();

					$hasNuked = TRUE;
				}

				asrt( $text->getMeta( 'type' ), 'text' );
				asrt( $page->getMeta( 'type' ), 'page' );
				asrt( $book->getMeta( 'type' ), 'book' );
				asrt( $author->getMeta( 'type' ), 'author' );
			}
		);
	}

	/**
	 * Test preloading with same-level abbreviations (&)
	 * 
	 * @return void
	 */
	public function testClosureAbbreviationsSameLevel()
	{
		$authors = R::dispense( 'author', 2 );
		foreach ( $authors as $author ) {
			$author->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText = R::dispense( 'text', 2 );
				}
			}
		}

		foreach ( $authors as $author ) {
			foreach ( $author->ownBook as $book ) {
				$book->shelf = R::dispense( 'shelf' )->setAttr( 'name', 'abc' );
			}
		}

		R::storeAll( $authors );

		$texts = R::find( 'text' );

		$hasNuked = FALSE;

		R::preload(
			$texts,
			'page,*.book,*.author,&.shelf',
			function ( $text, $page, $book, $author, $shelf ) use ( &$hasNuked ) {
				if ( !$hasNuked ) {
					R::nuke();
					$hasNuked = TRUE;
				}
				asrt( $text->getMeta( 'type' ), 'text' );
				asrt( $page->getMeta( 'type' ), 'page' );
				asrt( ( $page->id > 0 ), TRUE );
				asrt( $book->getMeta( 'type' ), 'book' );
				asrt( ( $book->id > 0 ), TRUE );
				asrt( $author->getMeta( 'type' ), 'author' );
				asrt( $shelf->getMeta( 'type' ), 'shelf' );
			}
		);
	}

	/**
	 * Test preloading with closure and abbr and own list.
	 * 
	 * @return void
	 */
	public function testClosureAbbreviationOwnlist()
	{
		$authors = R::dispense( 'author', 2 );
		foreach ( $authors as $author ) {
			$author->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText = R::dispense( 'text', 2 );
				}
			}
		}

		R::storeAll( $authors );

		$pages    = R::find( 'page' );

		$hasNuked = FALSE;

		R::preload(
			$pages,
			array( 'book', '*.author', 'ownText' => 'text' ),
			function ( $page, $book, $author, $texts ) use ( &$hasNuked ) {
				if ( !$hasNuked ) {
					R::nuke();

					$hasNuked = TRUE;
				}

				asrt( $page->getMeta( 'type' ), 'page' );

				asrt( ( $page->id > 0 ), TRUE );

				asrt( $book->getMeta( 'type' ), 'book' );

				asrt( ( $book->id > 0 ), TRUE );

				asrt( $author->getMeta( 'type' ), 'author' );

				asrt( ( $author->id > 0 ), TRUE );

				asrt( is_array( $texts ), TRUE );

				asrt( count( $texts ), 2 );

				$first = reset( $texts );

				asrt( $first->getMeta( 'type' ), 'text' );
			}
		);
	}

	/**
	 * Test variations.
	 * 
	 * @return void
	 */
	public function testVariations()
	{
		$authors = R::dispense( 'author', 2 );
		foreach ( $authors as $author ) {
			$author->ownBook = R::dispense( 'book', 2 );

			foreach ( $author->ownBook as $book ) {
				$book->ownPage = R::dispense( 'page', 2 );
				$book->cover   = R::dispense( 'cover', 1 );

				foreach ( $book->ownPage as $page ) {
					$page->ownText = R::dispense( 'text', 2 );
				}

				foreach ( $book->ownPage as $page ) {
					$page->ownPicture = R::dispense( 'picture', 3 );
				}
			}
		}

		R::storeAll( $authors );

		$texts = R::find( 'text' );

		$hasNuked = FALSE;

		$i = 0;

		R::each(
			$texts,
			array(
				'page',
				'*.ownPicture' => 'picture',
				'&.book',
				'*.cover',
				'&.author'
			),
			function ( $t, $p, $x, $b, $c, $a ) use ( &$hasNuked, &$i ) {
				if ( !$hasNuked ) {
					R::nuke();

					$hasNuked = TRUE;
				}
				$i++;
				asrt( count( $x ), 3 );

				asrt( ( $p->id > 0 ), TRUE );
				asrt( ( $c->id > 0 ), TRUE );
				asrt( ( $t->id > 0 ), TRUE );
				asrt( ( $b->id > 0 ), TRUE );
				asrt( ( $a->id > 0 ), TRUE );

				asrt( $t->getMeta( 'type' ), 'text' );
				asrt( $p->getMeta( 'type' ), 'page' );
				asrt( $c->getMeta( 'type' ), 'cover' );
				asrt( $b->getMeta( 'type' ), 'book' );
				asrt( $a->getMeta( 'type' ), 'author' );

				$x1 = reset( $x );

				asrt( $x1->getMeta( 'type' ), 'picture' );
			}
		);

		// Follows the first parameter
		asrt( $i, 16 );
	}

	/**
	 * Test complex variations.
	 * 
	 * @return void
	 */
	public function testVariations2()
	{
		// Extra, test in combination with writer cache
		R::$writer->setUseCache( TRUE );

		$villages = R::dispense( 'village', 3 );

		foreach ( $villages as $v ) {
			$v->ownBuilding = R::dispense( 'building', 3 );
		}

		foreach ( $villages as $v ) {
			foreach ( $v->ownBuilding as $b ) {
				$b->ownFurniture = R::dispense( 'furniture', 2 );
			}
		}

		$armies = R::dispense( 'army', 3 );

		$villages[0]->sharedArmy = array( $armies[1], $armies[2] );
		$villages[1]->sharedArmy = array( $armies[0], $armies[1] );
		$villages[2]->sharedArmy = array( $armies[2] );

		$soldiers = R::dispense( 'soldier', 4 );

		$armies[0]->sharedSoldier = array( $soldiers[0], $soldiers[1], $soldiers[2] );
		$armies[1]->sharedSoldier = array( $soldiers[2], $soldiers[1] );
		$armies[2]->sharedSoldier = array( $soldiers[2] );

		$counter = 0;

		foreach ( $villages as $v ) {
			$v->name = $counter++;
		}

		$counter = 0;

		foreach ( $armies as $a ) {
			$a->name = $counter++;
		}

		$counter = 0;

		foreach ( $soldiers as $s ) {
			$s->name = $counter++;
		}

		$buildings = R::dispense( 'building', 4 );

		$villages[0]->ownBuilding = array( $buildings[0] );
		$villages[1]->ownBuilding = array( $buildings[1], $buildings[2] );
		$villages[2]->ownBuilding = array( $buildings[3] );

		$counter = 0;

		foreach ( $buildings as $b ) $b->name = $counter++;

		$books = R::dispense( 'book', 5 );

		$counter = 0;

		foreach ( $books as $b ) $b->name = $counter++;

		$buildings[0]->ownBook = array( $books[0], $books[1] );
		$buildings[1]->ownBook = array( $books[2] );
		$buildings[2]->ownBook = array( $books[3], $books[4] );

		$world = R::dispense( 'world' );

		$world->name = 'w1';

		$villages[1]->world = $world;

		R::storeAll( $villages );

		$towns = R::find( 'village' );

		$counter = 0;

		R::each(
			$towns,
			array(
				'sharedArmy'               => 'army',
				'sharedArmy.sharedSoldier' => 'soldier',
				'ownBuilding'              => 'building',
				'ownBuilding.ownBook'      => 'book',
				'world'
			),
			function ( $t, $a, $s, $b, $x, $w ) use ( &$counter ) {
				if ( $counter === 0 ) {
					asrt( $w, NULL );

					asrt( (string) $t->name, '0' );

					asrt( count( $t->sharedArmy ), 2 );

					$list = array();
					foreach ( $a as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '1,2' );

					$list = array();

					foreach ( $s as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '1,2' );

					$list = array();
					foreach ( $b as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0' );

					$list = array();
					foreach ( $x as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0,1' );

					$first = reset( $a );

					asrt( $first->getMeta( 'type' ), 'army' );

					$first = reset( $s );

					asrt( $first->getMeta( 'type' ), 'soldier' );

					$first = reset( $b );

					asrt( $first->getMeta( 'type' ), 'building' );

					$first = reset( $x );

					asrt( $first->getMeta( 'type' ), 'book' );
				} elseif ( $counter === 1 ) {
					asrt( $w->name, 'w1' );

					asrt( (string) $t->name, '1' );

					asrt( count( $t->sharedArmy ), 2 );

					$list = array();
					foreach ( $a as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0,1' );

					$list = array();
					foreach ( $s as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0,1,2' );

					$list = array();
					foreach ( $b as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '1,2' );

					$list = array();
					foreach ( $x as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '2,3,4' );

					$first = reset( $a );

					asrt( $first->getMeta( 'type' ), 'army' );

					$first = reset( $s );

					asrt( $first->getMeta( 'type' ), 'soldier' );

					$first = reset( $b );

					asrt( $first->getMeta( 'type' ), 'building' );

					$first = reset( $x );

					asrt( $first->getMeta( 'type' ), 'book' );
				} elseif ( $counter === 2 ) {
					asrt( $w, NULL );

					asrt( (string) $t->name, '2' );

					asrt( count( $t->sharedArmy ), 1 );

					$list = array();
					foreach ( $a as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '2' );

					$list = array();
					foreach ( $s as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '2' );

					$list = array();
					foreach ( $b as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '3' );

					asrt( count( $x ), 0 );

					$first = reset( $a );

					asrt( $first->getMeta( 'type' ), 'army' );

					$first = reset( $s );

					asrt( $first->getMeta( 'type' ), 'soldier' );

					$first = reset( $b );

					asrt( $first->getMeta( 'type' ), 'building' );
				}

				$counter++;
			}
		);
	}

	/**
	 * Test preloading variations with caching.
	 * 
	 * @return void
	 */
	public function testPreloadingVariationsAndCache()
	{
		R::$writer->setUseCache( FALSE );

		$books = R::dispense( 'book', 4 );

		foreach ( $books as $b ) $b->ownPage = R::dispense( 'page', 2 );

		foreach ( $books as $b ) $b->sharedAd = R::dispense( 'ad', 2 );

		R::storeAll( $books );

		$books = R::find( 'book' );

		R::preload(
			$books,
			array(
				'ownPage'  => array( 'page', array( ' AND page.id > 0 LIMIT ? ', array( 2 ) ) ),
				'sharedAd' => array( 'ad', array( ' AND ad.id > 0 LIMIT ? ', array( 4 ) ) )
			)
		);

		asrt( count( $books[1]->ownPage ), 2 );
		asrt( count( $books[1]->sharedAd ), 2 );

		asrt( count( $books[2]->ownPage ), 0 );
		asrt( count( $books[2]->sharedAd ), 2 );

		asrt( count( $books[3]->ownPage ), 0 );
		asrt( count( $books[3]->sharedAd ), 0 );
	}

	/**
	 * Test variations and cache 2.
	 * 
	 * @return void
	 */
	public function testPreloadingVariationsAndCache2()
	{
		R::$writer->setUseCache( FALSE );

		$villages = R::dispense( 'village', 3 );

		foreach ( $villages as $v ) {
			$v->ownBuilding = R::dispense( 'building', 3 );
		}

		foreach ( $villages as $v ) {
			foreach ( $v->ownBuilding as $b ) {
				$b->ownFurniture = R::dispense( 'furniture', 2 );
			}
		}

		$armies = R::dispense( 'army', 3 );

		$villages[0]->sharedArmy = array( $armies[1], $armies[2] );
		$villages[1]->sharedArmy = array( $armies[0], $armies[1] );
		$villages[2]->sharedArmy = array( $armies[2] );

		$soldiers = R::dispense( 'soldier', 4 );

		$armies[0]->sharedSoldier = array( $soldiers[0], $soldiers[1], $soldiers[2] );
		$armies[1]->sharedSoldier = array( $soldiers[2], $soldiers[1] );
		$armies[2]->sharedSoldier = array( $soldiers[2] );

		$counter = 0;

		foreach ( $villages as $v ) $v->name = $counter++;

		$counter = 0;

		foreach ( $armies as $a ) $a->name = $counter++;

		$counter = 0;

		foreach ( $soldiers as $s ) $s->name = $counter++;

		$buildings = R::dispense( 'building', 4 );

		$villages[0]->ownBuilding = array( $buildings[0] );
		$villages[1]->ownBuilding = array( $buildings[1], $buildings[2] );
		$villages[2]->ownBuilding = array( $buildings[3] );

		$counter = 0;

		foreach ( $buildings as $b ) $b->name = $counter++;

		$books = R::dispense( 'book', 5 );

		$counter = 0;

		foreach ( $books as $b ) $b->name = $counter++;

		$buildings[0]->ownBook = array( $books[0], $books[1] );
		$buildings[1]->ownBook = array( $books[2] );
		$buildings[2]->ownBook = array( $books[3], $books[4] );

		$world = R::dispense( 'world' );

		$world->name = 'w1';

		$villages[1]->world = $world;

		R::storeAll( $villages );

		$towns = R::find( 'village' );

		$counter = 0;

		R::each(
			$towns,
			array(
				'sharedArmy'               => 'army',
				'sharedArmy.sharedSoldier' => array( 'soldier', array( ' ORDER BY soldier.name DESC ', array() ) ),
				'ownBuilding'              => array( 'building', array( ' ORDER BY building.name DESC ', array() ) ),
				'ownBuilding.ownBook'      => 'book',
				'world'
			),
			function ( $t, $a, $s, $b, $x, $w ) use ( &$counter ) {
				if ( $counter === 0 ) {
					asrt( $w, NULL );

					asrt( (string) $t->name, '0' );

					asrt( count( $t->sharedArmy ), 2 );

					$list = array();
					foreach ( $a as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '1,2' );

					$list = array();
					foreach ( $s as $item ) {
						$list[] = $item->name;
					}

					asrt( implode( ',', $list ), '2,1' );

					$list = array();

					foreach ( $b as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0' );

					$list = array();
					foreach ( $x as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0,1' );

					$first = reset( $a );

					asrt( $first->getMeta( 'type' ), 'army' );

					$first = reset( $s );

					asrt( $first->getMeta( 'type' ), 'soldier' );

					$first = reset( $b );

					asrt( $first->getMeta( 'type' ), 'building' );

					$first = reset( $x );

					asrt( $first->getMeta( 'type' ), 'book' );
				} elseif ( $counter === 1 ) {
					asrt( $w->name, 'w1' );

					asrt( (string) $t->name, '1' );

					asrt( count( $t->sharedArmy ), 2 );

					$list = array();
					foreach ( $a as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0,1' );

					$list = array();
					foreach ( $s as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '0,1,2' );

					$list = array();
					foreach ( $b as $item ) {
						$list[] = $item->name;
					}

					asrt( implode( ',', $list ), '2,1' );

					$list = array();

					foreach ( $x as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '2,3,4' );

					$first = reset( $a );

					asrt( $first->getMeta( 'type' ), 'army' );

					$first = reset( $s );

					asrt( $first->getMeta( 'type' ), 'soldier' );

					$first = reset( $b );

					asrt( $first->getMeta( 'type' ), 'building' );

					$first = reset( $x );
					asrt( $first->getMeta( 'type' ), 'book' );
				} elseif ( $counter === 2 ) {
					asrt( $w, NULL );

					asrt( (string) $t->name, '2' );

					asrt( count( $t->sharedArmy ), 1 );

					$list = array();
					foreach ( $a as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '2' );

					$list = array();
					foreach ( $s as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '2' );

					$list = array();
					foreach ( $b as $item ) {
						$list[] = $item->name;
					}

					sort( $list );

					asrt( implode( ',', $list ), '3' );

					asrt( count( $x ), 0 );

					$first = reset( $a );

					asrt( $first->getMeta( 'type' ), 'army' );

					$first = reset( $s );

					asrt( $first->getMeta( 'type' ), 'soldier' );

					$first = reset( $b );

					asrt( $first->getMeta( 'type' ), 'building' );
				}

				$counter++;
			}
		);
	}
}
