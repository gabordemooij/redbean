<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * RedUNIT_Base_With
 *
 * @file    RedUNIT/Base/With.php
 * @desc    Tests query modification of own-lists with prefix-with
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class With extends Base
{

	/**
	 * This test suite uses specific SQL, only suited for MySQL.
	 *
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql' );
	}
	
	/**
	 * Tests no-load modifier for lists.
	 * 
	 * @return void
	 */
	public function testNoLoad()
	{
		$book = R::dispense( array(
			 '_type' => 'book',
			 'title' => 'Book of Lorem Ipsum',
			 'ownPage' => array(
				  array(
						'_type' => 'page',
						'content' => 'Lorem Ipsum',
					)
			 ),
			 'sharedTag' => array(
				  array(
						'_type' => 'tag',
						'label' => 'testing'
				  )
			 )
		) );
		
		R::store( $book );
		$book = $book->fresh();
		asrt( R::count( 'book' ), 1 );
		asrt( count( $book->ownPage ), 1 );
		
		//now try with no-load
		$book = $book->fresh();
		asrt( count( $book->noLoad()->ownPage ),  0 );
		asrt( count( $book->noLoad()->sharedTag ),  0 );
		
		//now try to add with no-load
		$book = $book->fresh();
		$book->noLoad()->xownPageList[] = R::dispense( 'page' );
		$book->noLoad()->sharedTagList[] = R::dispense( 'tag' );
		R::store( $book );
		
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 2 );
		asrt( count( $book->sharedTagList ), 2 );
		
		//no-load overrides with and withCondition
		$book = $book->fresh();
		asrt( count( $book->with(' invalid sql ')->noLoad()->ownPage ),  0 );
		asrt( count( $book->withCondition(' invalid sql ')->noLoad()->sharedTag ),  0 );
		
		//no-load overrides all and alias
		$book = $book->fresh();
		asrt( count( $book->all()->noLoad()->ownPage ),  0 );
		asrt( count( $book->alias('nothing')->noLoad()->sharedTag ),  0 );
		
		//no-load gets cleared
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 2 );
		asrt( count( $book->sharedTagList ), 2 );

		//We cant clear with no-load accidentally?
		$book = $book->fresh();
		$book->noLoad()->ownPage = array();
		$book->noLoad()->sharedTagList = array();
		R::store( $book );

		asrt( count( $book->ownPage ), 2 );
		asrt( count( $book->sharedTagList ), 2 );

		//No-load does not have effect if list is already cached
		$book = $book->fresh();
		$book->ownPage;
		$book->sharedTag;
		asrt( count( $book->ownPage ), 2 );
		asrt( count( $book->sharedTagList ), 2 );

	}
	
	
	/**
	 * Test all().
	 * 
	 * @return void
	 */
	public function testAll() 
	{
		$book = R::dispense( 'book' );
		$book->ownPage = R::dispense( 'page', 10 );
		R::store( $book );
		asrt( count( $book->with( ' LIMIT 3 ' )->ownPage ), 3 );
		asrt( count( $book->ownPage ), 3 );
		asrt( count( $book->all()->ownPage ), 10 );
		asrt( count( $book->ownPage ), 10 );
		R::nuke();
		asrt( count( $book->ownPage ), 10 );
		asrt( count( $book->all()->ownPage ), 0 );
	}

	/**
	 * Test embedded SQL snippets using with and withCondition.
	 * 
	 * @return void
	 */
	public function testEmbeddedSQL()
	{
		list( $page1, $page2, $page3 ) = R::dispense( 'page', 3 );

		list( $ad1, $ad2, $ad3 ) = R::dispense( 'ad', 3 );

		$ad2->name   = 'shampoo';
		$page3->name = 'homepage';

		$page1->sharedAd = array( $ad1, $ad3 );
		$page2->sharedAd = array( $ad2, $ad3 );
		$page3->sharedAd = array( $ad3, $ad2, $ad1 );

		R::storeAll( array( $page1, $page2, $page3 ) );

		$page1 = R::load( 'page', $page1->id );

		asrt( 1, count( $page1->with( ' LIMIT 1 ' )->sharedAd ) );

		$page2 = R::load( 'page', $page2->id );

		$adsOnPage2 = $page2->withCondition( ' `name` = ? ', array( 'shampoo' ) )->sharedAd;

		asrt( 1, count( $adsOnPage2 ) );

		$ad = reset( $adsOnPage2 );

		asrt( $ad->name, 'shampoo' );

		$ad = R::load( 'ad', $ad->id );

		asrt( count( $ad->sharedPage ), 2 );

		$ad = R::load( 'ad', $ad->id );

		$homepage = reset( $ad->withCondition( ' `name` LIKE ? AND page.id > 0 ORDER BY id DESC ', array( '%ome%' ) )->sharedPage );

		asrt( $homepage->name, 'homepage' );
	}

	/**
	 * More variations...
	 * 
	 * @return void
	 */
	public function testEmbeddedSQLPart2()
	{
		list( $book1, $book2, $book3 ) = R::dispense( 'book', 3 );

		$book1->position = 1;
		$book2->position = 2;
		$book3->position = 3;

		$shelf = R::dispense( 'shelf' );

		$shelf->ownBook = array( $book1, $book2, $book3 );

		$id = R::store( $shelf );

		$shelf = R::load( 'shelf', $id );

		$books = $shelf->with( ' ORDER BY position ASC ' )->ownBook;

		$book1 = array_shift( $books );

		asrt( (int) $book1->position, 1 );

		$book2 = array_shift( $books );

		asrt( (int) $book2->position, 2 );

		$book3 = array_shift( $books );

		asrt( (int) $book3->position, 3 );

		$books = $shelf->with( ' ORDER BY position DESC ' )->ownBook;

		$book1 = array_shift( $books );

		asrt( (int) $book1->position, 3 );

		$book2 = array_shift( $books );

		asrt( (int) $book2->position, 2 );

		$book3 = array_shift( $books );

		asrt( (int) $book3->position, 1 );

		//R::debug(1);

		$shelf = R::load( 'shelf', $id );

		$books = $shelf->with( ' AND position > 2 ' )->ownBook;

		asrt( count( $books ), 1 );

		$shelf = R::load( 'shelf', $id );

		$books = $shelf->with( ' AND position < ? ', array( 3 ) )->ownBook;

		asrt( count( $books ), 2 );

		$shelf = R::load( 'shelf', $id );

		$books = $shelf->with( ' AND position = 1 ' )->ownBook;

		asrt( count( $books ), 1 );

		$shelf = R::load( 'shelf', $id );

		$books = $shelf->withCondition( ' position > -1 ' )->ownBook;

		asrt( count( $books ), 3 );

		// With-condition should not affect storing
		$shelf = R::load( 'shelf', $id );

		$books = $shelf->with( ' AND position = 1 ' )->ownBook;

		asrt( count( $books ), 1 );
		asrt( count( $shelf->ownBook ), 1 );

		$book = reset( $shelf->ownBook );

		$book->title = 'Trees and other Poems';

		R::store( $shelf );

		$books = $shelf->withCondition( ' position > -1 ' )->ownBook;

		asrt( count( $books ), 3 );
		asrt( count( $shelf->ownBook ), 3 );

		$shelf = R::load( 'shelf', $id );

		$books = $shelf->with( ' AND position = 1 ' )->ownBook;

		// Also with trashing -- just trash one!
		$shelf->ownBook = array();

		R::store( $shelf );

		$books = $shelf->withCondition( ' position > -1 ' )->ownBook;

		asrt( count( $books ), 2 );

		// With should cause a reload of a list
		$shelf = R::load( 'shelf', $id );

		$books = $shelf->with( ' AND position = 2 ' )->ownBook;

		asrt( count( $books ), 1 );

		$books = $shelf->withCondition( ' position > -1 ' )->ownBook;

		asrt( count( $books ), 2 );

		$book = reset( $books );

		$book->title = 'Venetian Music';

		// Should not affect storage (fact that we used with twice, unsetting prop)
		R::store( $shelf );

		$shelf = R::load( 'shelf', $id );

		asrt( count( $shelf->ownBook ), 2 );

		// Alias
		list( $game1, $game2, $game3 ) = R::dispense( 'game', 3 );

		list( $t1, $t2, $t3 ) = R::dispense( 'team', 3 );

		$t1->name = 'Bats';
		$t2->name = 'Tigers';
		$t3->name = 'Eagles';

		$game1->name  = 'a';
		$game1->team1 = $t1;
		$game1->team2 = $t2;

		$game2->name  = 'b';
		$game2->team1 = $t1;
		$game2->team2 = $t3;

		$game3->name  = 'c';
		$game3->team1 = $t2;
		$game3->team2 = $t3;

		R::storeAll( array( $game1, $game2, $game3 ) );

		$team1 = R::load( 'team', $t1->id );
		$team2 = R::load( 'team', $t2->id );
		$team3 = R::load( 'team', $t3->id );

		asrt( count( $team1->alias( 'team1' )->ownGame ), 2 );
		asrt( count( $team2->alias( 'team1' )->ownGame ), 1 );

		$team1 = R::load( 'team', $t1->id );
		$team2 = R::load( 'team', $t2->id );

		asrt( count( $team1->alias( 'team2' )->ownGame ), 0 );
		asrt( count( $team2->alias( 'team2' )->ownGame ), 1 );
		asrt( count( $team3->alias( 'team1' )->ownGame ), 0 );

		$team3 = R::load( 'team', $t3->id );

		asrt( count( $team3->alias( 'team2' )->ownGame ), 2 );

		$team1 = R::load( 'team', $t1->id );

		$games = $team1->alias( 'team1' )->ownGame;

		$game4 = R::dispense( 'game' );

		$game4->name  = 'd';
		$game4->team2 = $t3;

		$team1->alias( 'team1' )->ownGame[] = $game4;

		R::store( $team1 );

		$team1 = R::load( 'team', $t1->id );

		asrt( count( $team1->alias( 'team1' )->ownGame ), 3 );

		foreach ( $team1->ownGame as $g ) {
			if ( $g->name == 'a' ) $game = $g;
		}

		$game->name = 'match';

		R::store( $team1 );

		$team1 = R::load( 'team', $t1->id );

		asrt( count( $team1->alias( 'team1' )->ownGame ), 3 );

		$found = 0;
		foreach ( $team1->ownGame as $g ) {
			if ( $g->name == 'match' ) $found = 1;
		}

		if ( $found ) pass();

		$team1->ownGame = array();

		R::store( $team1 );

		$team1 = R::load( 'team', $t1->id );

		asrt( count( $team1->alias( 'team1' )->ownGame ), 0 );

		$team1->ownBook[] = $book1;

		R::store( $team1 );

		$team1 = R::load( 'team', $t1->id );

		asrt( count( $team1->alias( 'team1' )->ownGame ), 0 );
		asrt( count( $team1->ownBook ), 1 );
	}
	
	/**
	 * Test when to reload and when to NOT reload beans.
	 * Use UNSET to reload a parent bean. Use UNSET or
	 * a modifier (with, withCondition, all) to reload a list.
	 * Use noLoad() to obtain an empty list - does not reload
	 * but sets an empty array.
	 * 
	 * @return void
	 */
	public function testWhenToReload()
	{
		$book = R::dispense( 'book' );
		$book->ownPage = R::dispense( 'page', 3 );
		$book->author = R::dispense( 'author' );
		$book->coauthor = R::dispense( 'author' );
		R::store( $book );
		$book = $book->fresh();
		$firstPage = reset( $book->ownPage );
		$id = $firstPage->id;
		$book->ownPage[ $id ]->title = 'a';
		
		//Do not reload an own list after manipulations
		asrt( $book->ownPage[ $id ]->title, 'a' ); //dont reload!
		$book->ownPage[] = R::dispense( 'page' ); //dont reload!
		asrt( $book->ownPage[ $id ]->title, 'a' ); //dont reload!
		asrt( $book->ownPageList[ $id ]->title, 'a' ); //dont reload!
		asrt( $book->xownPageList[ $id ]->title, 'a' ); //dont reload!
		asrt( $book->xownPage[ $id ]->title, 'a' ); //dont reload!
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		unset( $book->ownPageList );
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		unset( $book->xownPageList );
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		unset( $book->xownPage );
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		unset( $book->ownPage );
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		$book->all()->ownPage;
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		$book->all()->xownPage;
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		$book->all()->ownPageList;
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		//now trigger reload
		$book->all()->xownPageList;
		asrt( count( $book->ownPageList ), 3 );
		$book->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->ownPageList ), 4 );
		
		
		//Do not reload an own list if told to not reload using noLoad()
		$book->noLoad()->with(' LIMIT 1 ')->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->noLoad()->all()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->noLoad()->alias('magazine')->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->noLoad()->withCondition('')->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		
		//even if modifiers proceed noLoad()
		$book->with(' LIMIT 1 ')->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->all()->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->alias('magazine')->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->withCondition('')->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		
		//even in combinations
		$book->all()->with(' LIMIT 1 ')->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->alias('magazine')->all()->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->alias('magazine')->with('LIMIT 1')->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		$book->alias('magazine')->withCondition('')->noLoad()->ownPage; //dont reload!
		asrt( count( $book->xownPage ), 0); //dont reload!
		
		//now test shared list
		$book->sharedTag = R::dispense( 'tag', 16 );
		asrt( count( $book->sharedTag ), 16 );
		$book->sharedTag[] = R::dispense( 'tag' );
		asrt( count( $book->sharedTag ), 17 ); //dont reload after adding
		$last = end( $book->sharedTagList );
		$id = $last->id;
		$book->sharedTag[ $id ]->title = 'b';
		asrt( count( $book->sharedTag ), 17 ); //dont reload after manipulation
		unset( $book->sharedTagList[ $id ] );
		asrt( count( $book->sharedTag ), 16 ); //dont reload after manipulation
		//now trigger reload
		unset( $book->sharedTagList );
		asrt( count( $book->sharedTag ), 0 );
		$book->sharedTag = R::dispense( 'tag', 16 );
		asrt( count( $book->sharedTag ), 16 );
		//now trigger reload
		unset( $book->sharedTag );
		asrt( count( $book->sharedTag ), 0 );
		$book->sharedTag = R::dispense( 'tag', 16 );
		asrt( count( $book->sharedTag ), 16 );
		//now trigger reload
		$book->all()->sharedTag;
		asrt( count( $book->sharedTag ), 0 );
		$book->sharedTag = R::dispense( 'tag', 16 );
		asrt( count( $book->sharedTag ), 16 );
		//now trigger reload
		$book->all()->sharedTagList;
		asrt( count( $book->sharedTag ), 0 );
		$book->sharedTag = R::dispense( 'tag', 16 );
		asrt( count( $book->sharedTag ), 16 );
		
		
		//Do not reload a sharedTag list if told to not reload using noLoad()
		$book->noLoad()->with(' LIMIT 1 ')->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->noLoad()->all()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->noLoad()->alias('magazine')->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->noLoad()->withCondition('')->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		
		//even if modifiers proceed noLoad()
		$book->with(' LIMIT 1 ')->noLoad()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->all()->noLoad()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->alias('magazine')->noLoad()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->withCondition('')->noLoad()->ownPage; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		
		//even in combinations
		$book->all()->with(' LIMIT 1 ')->noLoad()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->alias('magazine')->all()->noLoad()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->alias('magazine')->with('LIMIT 1')->noLoad()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		$book->alias('magazine')->withCondition('')->noLoad()->sharedTag; //dont reload!
		asrt( count( $book->sharedTag ), 0); //dont reload!
		
		//test do not reload parent bean
		$book->author->name = 'me';
		asrt( $book->author->name, 'me' );
		$book->fetchAs('author')->coauthor;
		asrt( $book->author->name, 'me' );
		$book->fetchAs('author')->author;
		asrt( $book->author->name, 'me' );
		$book->with(' LIMIT 1 ')->author;
		asrt( $book->author->name, 'me' );
		$book->withCondition('')->author;
		asrt( $book->author->name, 'me' );
		$book->all()->author;
		asrt( $book->author->name, 'me' );
		$book->noLoad()->author;
		asrt( $book->author->name, 'me' );
		$book->noLoad()->all()->author;
		asrt( $book->author->name, 'me' );
		$book->with('LIMIT 1')->noLoad()->all()->author;
		asrt( $book->author->name, 'me' );
		//now trigger reload
		unset( $book->author );
		asrt( $book->author->name, NULL );
		$book->author->name = 'me';
		asrt( $book->author->name, 'me' );
	}
	
	/**
	 * Tests whether modifiers are cleared after reading or
	 * writing a bean property.
	 * 
	 * @return void
	 */
	public function testClearanceOfModFlags()
	{
		//test base condition, retrieving list or parent should not set flags
		$book = R::dispense( 'book' );
		asrt( $book->getModFlags(), '' );
		$book->ownPage = R::dispense( 'page', 2 );
		asrt( $book->getModFlags(), '' );
		$book->xownPage = R::dispense( 'page', 2 );
		asrt( $book->getModFlags(), '' );
		$book->ownPageList = R::dispense( 'page', 2 );
		asrt( $book->getModFlags(), '' );
		$book->xownPageList = R::dispense( 'page', 2 );
		asrt( $book->getModFlags(), '' );
		$book->ownPage[] = R::dispense( 'page', 1 );
		asrt( $book->getModFlags(), '' );
		$book->xownPage[] = R::dispense( 'page', 1 );
		asrt( $book->getModFlags(), '' );
		$book->ownPageList[] = R::dispense( 'page', 1 );
		asrt( $book->getModFlags(), '' );
		$book->xownPageList[] = R::dispense( 'page', 1 );
		asrt( $book->getModFlags(), '' );
		$book->sharedPage = R::dispense( 'page', 2 );
		asrt( $book->getModFlags(), '' );
		$book->sharedPageList = R::dispense( 'page', 2 );
		asrt( $book->getModFlags(), '' );
		$book->sharedPage[] = R::dispense( 'page', 1 );
		asrt( $book->getModFlags(), '' );
		$book->sharedPageList[] = R::dispense( 'page', 1 );
		asrt( $book->getModFlags(), '' );
		$book->author = R::dispense( 'author' );
		asrt( $book->getModFlags(), '' );
		$book->title = 'title';
		
		//Test whether appropriate flags are set and whether they are cleared after
		//accessing a property.
		$modifiers = array('with'=>'w', 'withCondition'=>'w', 'alias'=>'a', 'fetchAs'=>'f', 'all'=>'r', 'noLoad'=>'n');
		$properties = array('ownPage', 'ownPageList', 'xownPage', 'xownPageList', 'sharedPage', 'sharedPageList', 'author', 'title');
		foreach( $modifiers as $modifier => $flag ) {
			foreach( $properties as $property ) {
				$book = R::dispense( 'book' );
				$book->$modifier('something');
				$flags = $book->getModFlags();
				$expect = $flag;
				asrt( $flags, $expect );
				$book->$property;
				$flags = $book->getModFlags();
				asrt( $flags, '' );
			}
		}
		//now test combinations and also test whether we can
		//clear modifiers manually using the clearModifiers() method.
		foreach( $modifiers as $modifier => $flag ) {
			foreach( $modifiers as $modifier2 => $flag2 ) {
				foreach( $properties as $property ) {
					$book = R::dispense( 'book' );
					$book->$modifier( 'something' )->$modifier2( 'something' );
					$flags = $book->getModFlags();
					$expect = array($flag, $flag2);
					$expect = array_unique( $expect );
					sort( $expect );
					$expect = implode( '', $expect );
					asrt( $flags, $expect );
					$book->$modifier( 'something' )->$modifier2( 'something' )->clearModifiers();
					$flags = $book->getModFlags();
					asrt( $flags, '' );
					$book->$modifier( 'something' )->$modifier2( 'something' )->clearModifiers();
					$book->$property;
					$flags = $book->getModFlags();
					asrt( $flags, '' );
				}
			}
		}
		
		$book = R::dispense( 'book' );
		$book->ownPage = R::dispense( 'page', 2 );
		$book->sharedPage = R::dispense( 'page', 2 );
		R::store( $book );
		$book = R::dispense( 'book' );
		$book->alias('magazine')->ownPage = R::dispense( 'page', 2 );
		R::store( $book );
		
		//test modifier with countOwn and countShared methods
		foreach( $modifiers as $modifier => $flag ) {
			$book = R::dispense( 'book' );
			if ($modifier === 'withCondition') $book->$modifier( ' 1 ' );
			elseif ($modifier === 'with') $book->$modifier( ' LIMIT 1 ' );
			elseif ($modifier === 'alias') $book->$modifier('magazine');
			else $book->$modifier('something');
			$flags = $book->getModFlags();
			$expect = $flag;
			asrt( $flags, $expect );
			$book->countOwn('page');
			$flags = $book->getModFlags();
			asrt( $flags, '' );
			if ($modifier === 'withCondition') $book->$modifier( ' 1 ' );
			elseif ($modifier === 'with') $book->$modifier( ' LIMIT 1 ' );
			elseif ($modifier === 'alias') $book->$modifier('magazine');
			else $book->$modifier('something');
			$flags = $book->getModFlags();
			$expect = $flag;
			asrt( $flags, $expect );
			$book->countShared('page');
			$flags = $book->getModFlags();
			asrt( $flags, '' );
			if ($modifier === 'withCondition') $book->$modifier( ' 1 ' );
			elseif ($modifier === 'with') $book->$modifier( ' LIMIT 1 ' );
			elseif ($modifier === 'alias') $book->$modifier('magazine');
			else $book->$modifier('something');
			$flags = $book->getModFlags();
			$expect = $flag;
			asrt( $flags, $expect );
			unset( $book->author );
			$flags = $book->getModFlags();
			asrt( $flags, '' );
		}
		
	}
}
