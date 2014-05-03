<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Cross tables, self referential many-to-many relations
 * and aggregation techniques.
 *
 * @file    RedUNIT/Base/Cross.php
 * @desc    Tests associations within the same table (i.e. page_page2 alike)
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class Cross extends Base
{
	/**
	 * Test self referential N-M relations (page_page).
	 *
	 * @return void
	 */
	public function testSelfReferential()
	{
		$page = R::dispense( 'page' )->setAttr( 'title', 'a' );
		$page->sharedPage[] = R::dispense( 'page' )->setAttr( 'title', 'b' );
		R::store( $page );
		$page = $page->fresh();
		$page = reset( $page->sharedPage );
		asrt( $page->title, 'b' );
		$tables = array_flip( R::inspect() );
		asrt( isset( $tables['page_page'] ), true );
		$columns = R::inspect( 'page_page' );
		asrt( isset( $columns['page2_id'] ), true );
	}

	/**
	 * Test the unique constraint.
	 * Just want to make sure it is not too limiting and functions
	 * properly for typical RedBeanPHP usage.
	 *
	 * @return void
	 */
	public function testUnique()
	{
		R::nuke();
		$page1 = R::dispense( 'page' );
		$tag1 = R::dispense( 'tag' );
		$page2 = R::dispense( 'page' );
		$tag2 = R::dispense( 'tag' );
		$page3 = R::dispense( 'page' );
		$tag3 = R::dispense( 'tag' );
		$page1->sharedTag[] = $tag1;
		R::store( $page1 );
		//can we save all combinations with unique?
		asrt( R::count( 'pageTag' ), 1);
		$page1->sharedTag[] = $tag2;
		R::store( $page1 );
		asrt( R::count( 'pageTag' ), 2 );
		$page1->sharedTag[] = $tag3;
		$page2->sharedTag[] = $tag1;
		$page2->sharedTag[] = $tag2;
		$page2->sharedTag[] = $tag3;
		$page3->sharedTag[] = $tag1;
		$page3->sharedTag[] = $tag2;
		$page3->sharedTag[] = $tag3;
		R::storeAll( array( $page1, $page2, $page3 ) );
		asrt( R::count('pageTag'), 9 );
		$page1 = $page1->fresh();
		$page1->sharedTag[] = $tag3;
		R::store( $page1 );
		//cant add violates unique
		asrt( R::count( 'pageTag' ), 9 );
	}

	/**
	 * Shared lists can only be formed using types.
	 * If you happen to have two beans of the same type you can still
	 * have a shared list but not with a sense of direction.
	 * I.e. quest->sharedQuest returns all the quests that follow the first one,
	 * but also the ones that are followed by the first one.
	 * If you want to have some sort of direction; i.e. one quest follows another one
	 * you'll have to use an alias: quest->target, but now you can't use the shared list
	 * anymore because it will start looking for a type named 'target' (which is just an alias)
	 * for quest, but it cant find that table and it's not possible to 'keep remembering'
	 * the alias throughout the system.
	 *
	 * The aggr() method solves this inconvenience.
	 * Aggr iterates through the list identified by its first argument ('target' -> ownQuestTargetList)
	 * and fetches every ID of the target (quest_target.target_id), loads these beans in batch for
	 * optimal performance, puts them back in the beans (questTarget->target) and returns the
	 * references.
	 *
	 * @return void
	 */
	public function testAggregationInSelfRefNM()
	{
		R::nuke();
		$quest1 = R::dispense( 'quest' );
		$quest1->name = 'Quest 1';
		$quest2 = R::dispense( 'quest' );
		$quest2->name = 'Quest 2';
		$quest3 = R::dispense( 'quest' );
		$quest3->name = 'Quest 3';
		$quest4 = R::dispense( 'quest' );
		$quest4->name = 'Quest 4';

		$quest1->link( 'questTarget' )->target = $quest2;
		$quest1->link( 'questTarget' )->target = $quest3;
		$quest3->link( 'questTarget' )->target = $quest4;
		$quest3->link( 'questTarget' )->target = $quest1;

		R::storeAll( array( $quest1, $quest3 ) );

		//There should be 4 links
		asrt( (int) R::count('questTarget'), 4 );

		$quest1  = $quest1->fresh();
		$targets = $quest1->aggr( 'ownQuestTargetList', 'target', 'quest' );

		//can we aggregate the targets over the link type?
		asrt( count( $targets), 2 );

		//are the all valid beans?
		foreach( $targets as $target ) {
			//are they beans?
			asrt( ( $target instanceof OODBBean ), TRUE );
			//are they fetched as quest?
			asrt( ( $target->getMeta( 'type' ) ), 'quest' );
		}

		//list target should already have been loaded, nuke has no effect
		R::nuke();
		$links = $quest1->ownQuestTargetList;

		//are the links still there, have they been set in the beans as well?
		asrt( count( $links ), 2);

		//are they references instead of copies, changes in the aggregation set should affect the beans in links!
		foreach( $targets as $target ) {
			$target->name .= 'b';
			asrt( substr( $target->name, -1 ), 'b' );
		}

		//do the names end with a 'b' here as well ? i.e. have they been changed through references?
		foreach( $links as $link ) {
			asrt( substr( $target->name, -1 ), 'b' );
		}

		//now test the effect on existing shadow...
		R::nuke();
		$quest1 = R::dispense('quest');
		$quest1->name = 'Quest 1';
		$quest2 = R::dispense('quest');
		$quest2->name = 'Quest 2';
		$quest3 = R::dispense('quest');
		$quest3->name = 'Quest 3';
		$quest4 = R::dispense('quest');
		$quest4->name = 'Quest 4';

		$quest1->link( 'questTarget' )->target = $quest2;
		$quest1->link( 'questTarget' )->target = $quest3;

		R::store($quest1);
		asrt( (int) R::count( 'questTarget' ), 2 );

		//now lets first build a shadow
		$quest1->link( 'questTarget' )->target = $quest4;

		//$quest1 = $quest1->fresh();
		$targets = $quest1->aggr( 'ownQuestTargetList', 'target', 'quest' );

		//targets should not include the new bean...
		asrt( count($targets), 2 );

		//this should not overwrite the existing beans
		R::store($quest1);
		asrt( (int) R::count( 'questTarget' ), 3 );
	}

	/**
	 * Test aggr without the aliasing.
	 *
	 * @return void
	 */
	 public function testAggrBasic()
	 {
		R::nuke();
		$book  = R::dispense( 'book' );
		$page1 = R::dispense( 'page' );
		$page1->name = 'Page 1';
		$text1 = R::dispense('text');
		$text1->content = 'Text 1';
		$page1->text = $text1;
		$book->xownPageList[] = $page1;
		$page2 = R::dispense( 'page' );
		$page2->name = 'Page 2';
		$text2 = R::dispense( 'text' );
		$text2->content = 'Text 2';
		$page2->text = $text2;
		$book->xownPageList[] = $page2;
		R::store( $book );
		$book  = $book->fresh();
		$texts = $book->aggr( 'ownPageList', 'text' );
		R::nuke();
		asrt( count( $texts ), 2 );
		foreach( $texts as $text ) {
			asrt( ( $text instanceof OODBBean ), TRUE );
		}
		$pages = $book->ownPageList;
		asrt( count( $pages ), 2 );
		asrt( R::count( 'page' ), 0 );
		foreach( $pages as $page ) {
			asrt( ( $page instanceof OODBBean ), TRUE );
			$text = $page->text;
			asrt( ( $text instanceof OODBBean ), TRUE );
			$text->content = 'CHANGED';
		}
		foreach( $texts as $text ) {
			asrt( $text->content, 'CHANGED', TRUE );
		}
	 }

	/**
	 * Test aggr with basic aliasing.
	 *
	 * @return void
	 */
	 public function testAggrWithOnlyAlias()
	 {
		R::nuke();
		$book = R::dispense( 'book' );
		$page1 = R::dispense( 'page' );
		$page1->name = 'Page 1';
		$text1 = R::dispense( 'text' );
		$text1->content = 'Text 1';
		$page1->content = $text1;
		$book->xownPageList[] = $page1;
		$page2 = R::dispense( 'page' );
		$page2->name = 'Page 2';
		$text2 = R::dispense( 'text' );
		$text2->content = 'Text 2';
		$page2->content = $text2;
		$book->xownPageList[] = $page2;
		R::store( $book );
		$book = $book->fresh();
		$texts = $book->aggr( 'ownPageList', 'content', 'text' );
		R::nuke();
		asrt( count( $texts ), 2 );
		foreach( $texts as $text ) {
			asrt( ( $text instanceof OODBBean), TRUE );
		}
		$pages = $book->ownPageList;
		asrt( count( $pages ), 2 );
		asrt( R::count( 'page' ), 0 );
		foreach( $pages as $page ) {
			asrt( ( $page instanceof OODBBean ), TRUE );
			$text = $page->content;
			asrt( ( $text instanceof OODBBean ), TRUE );
			$text->content = 'CHANGED';
		}
		foreach( $texts as $text ) {
			asrt( $text->content, 'CHANGED', TRUE );
		}
	 }
}
