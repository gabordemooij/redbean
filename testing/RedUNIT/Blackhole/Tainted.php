<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;

/**
 * Tainted
 *
 * @file    RedUNIT/Blackhole/Tainted.php
 * @desc    Tests tainted flag for OODBBean objects.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Tainted extends Blackhole
{
	/**
	 * Test whether we can detect a change using hasChanged().
	 *
	 * @return void
	 */
	public function testHasChangedList()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		asrt( $book->hasListChanged( 'ownPage' ), FALSE );
		$book->ownPage[] = $page;
		asrt( $book->hasListChanged( 'ownPage' ), TRUE );
		R::store( $book );
		$book = $book->fresh();
		asrt( $book->hasListChanged( 'ownPage' ), FALSE );
		$page = R::dispense( 'page' );
		$book->ownPageList[] = $page;
		asrt( $book->hasListChanged( 'ownPage' ), TRUE );
		R::store( $book );
		$book = $book->fresh();
		asrt( $book->hasListChanged( 'ownPage' ), FALSE );
		asrt( count( $book->ownPageList ), 2 );
		array_pop( $book->ownPageList );
		asrt( count( $book->ownPageList ), 1 );
		asrt( $book->hasListChanged( 'ownPage' ), TRUE );
		array_pop( $book->ownPageList );
		asrt( count( $book->ownPageList ), 0 );
		asrt( $book->hasListChanged( 'ownPage' ), TRUE );
		$book = $book->fresh();
		asrt( $book->hasListChanged( 'ownPage' ), FALSE );
		asrt( count( $book->ownPageList ), 2 );
		$otherPage = R::dispense( 'page' );
		array_pop( $book->ownPageList );
		$book->ownPageList[] = $otherPage;
		asrt( count( $book->ownPageList ), 2 );
		asrt( $book->hasListChanged( 'ownPage' ), TRUE );
		$book = $book->fresh();
		$firstPage = reset( $book->ownPageList );
		$firstPage->content = 'abc';
		asrt( $book->hasListChanged( 'ownPage' ), FALSE );
		$book = $book->fresh();
		asrt( $book->hasListChanged( 'ownPage' ), FALSE );
		$lastPage = end( $book->ownPageList );
		$lastPage->ownText[] = R::dispense( 'text' );
		asrt( $book->hasListChanged( 'ownPage' ), FALSE );
	}

	/**
	 * Tests whether we can clear the history of a bean.
	 *
	 * @return void
	 */
	public function testClearHist()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		asrt( $book->hasChanged( 'title' ), FALSE );
		$book->title = 'book';
		asrt( $book->hasChanged( 'title' ), TRUE );
		R::store( $book );
		asrt( $book->hasChanged( 'title' ), TRUE );
		$book->clearHistory();
		asrt( $book->hasChanged( 'title' ), FALSE );
	}

	/**
	 * Test tainted.
	 *
	 * @return void
	 */
	public function testTainted()
	{
		testpack( 'Original Tainted Tests' );

		$redbean = R::getRedBean();

		$spoon = $redbean->dispense( "spoon" );

		asrt( $spoon->getMeta( "tainted" ), TRUE );

		$spoon->dirty = "yes";

		asrt( $spoon->getMeta( "tainted" ), TRUE );

		testpack( 'Tainted List test' );

		$note = R::dispense( 'note' );

		$note->text = 'abc';

		$note->ownNote[] = R::dispense( 'note' )->setAttr( 'text', 'def' );

		$id = R::store( $note );

		$note = R::load( 'note', $id );

		asrt( $note->isTainted(), FALSE );

		// Shouldn't affect tainted
		$note->text;

		asrt( $note->isTainted(), FALSE );

		$note->ownNote;

		asrt( $note->isTainted(), TRUE );

		testpack( 'Tainted Test Old Value' );

		$text = $note->old( 'text' );

		asrt( $text, 'abc' );

		asrt( $note->hasChanged( 'text' ), FALSE );

		$note->text = 'xxx';

		asrt( $note->hasChanged( 'text' ), TRUE );

		$text = $note->old( 'text' );

		asrt( $text, 'abc' );

		testpack( 'Tainted Non-exist' );

		asrt( $note->hasChanged( 'text2' ), FALSE );

		testpack( 'Misc Tainted Tests' );

		$bean = R::dispense( 'bean' );

		$bean->hasChanged( 'prop' );

		$bean->old( 'prop' );
	}
}
