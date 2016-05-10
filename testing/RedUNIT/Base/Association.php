<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\RedException\SQL as SQL;

/**
 * Association
 *
 * Originally meant to test R::associate - which is no longer
 * used, this class tests all kinds of relations from
 * one-to-one to polymorph relations using the poly() method.
 *
 * @file    RedUNIT/Base/Association.php
 * @desc    Tests Association API (N:N associations)
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Association extends Base
{
	/**
	 * MySQL specific tests.
	 * Test MySQL specific issues with constraints.
	 *
	 * @return void
	 */
	public function testMySQL()
	{
		if ( $this->currentlyActiveDriverID !== 'mysql' ) {
			return;
		}
		testpack( 'Throw exception in case of issue with assoc constraint' );
		$bunny  = R::dispense( 'bunny' );
		$carrot = R::dispense( 'carrot' );
		$faultyWriter  = new \FaultyWriter( R::getToolBox()->getDatabaseAdapter() );
		$faultyOODB = new OODB( $faultyWriter );
		$faultyOODB->setBeanHelper( R::getRedBean()->getBeanHelper() );
		$faultyToolbox = new ToolBox( $faultyOODB, R::getToolBox()->getDatabaseAdapter(), $faultyWriter );
		$faultyAssociationManager = new AssociationManager( $faultyToolbox );
		$faultyWriter->setSQLState( '23000' );
		$faultyAssociationManager->associate( $bunny, $carrot );
		pass();
		$faultyWriter->setSQLState( '42S22' );
		R::nuke();
		try {
			$faultyAssociationManager->associate( $bunny, $carrot );
			fail();
		} catch ( SQL $exception ) {
			pass();
		}
	}

	/**
	 * Test fast-track deletion, i.e. bypassing FUSE.
	 * For link beans.
	 *
	 * @return void
	 */
	public function testFastTrackDeletion()
	{
		testpack( 'Test fast-track deletion' );
		$ghost = R::dispense( 'ghost' );
		$house = R::dispense( 'house' );
		$house->sharedGhost[] = $ghost;
		\Model_Ghost_House::$deleted = FALSE;
		R::getRedBean()->getAssociationManager()->unassociate( $house, $ghost );
		// No fast-track, assoc bean got trashed
		asrt( \Model_Ghost_House::$deleted, TRUE );
		\Model_Ghost_House::$deleted = FALSE;
		R::getRedBean()->getAssociationManager()->unassociate( $house, $ghost, TRUE );
		// Fast-track, assoc bean got deleted right away
		asrt( \Model_Ghost_House::$deleted, FALSE );
	}

	/**
	 * Test self-referential associations.
	 *
	 * @return void
	 */
	public function testCrossAssociation()
	{
		$ghost  = R::dispense( 'ghost' );
		$ghost2 = R::dispense( 'ghost' );
		R::getRedBean()->getAssociationManager()->associate( $ghost, $ghost2 );
		\Model_Ghost_Ghost::$deleted = FALSE;
		R::getRedBean()->getAssociationManager()->unassociate( $ghost, $ghost2 );
		// No fast-track, assoc bean got trashed
		asrt( \Model_Ghost_Ghost::$deleted, TRUE );
		\Model_Ghost_Ghost::$deleted = FALSE;
		R::getRedBean()->getAssociationManager()->unassociate( $ghost, $ghost2, TRUE );
		// Fast-track, assoc bean got deleted right away
		asrt( \Model_Ghost_Ghost::$deleted, FALSE );
	}

	/**
	 * Test limited support for polymorph associations.
	 * RedBeanPHP does not really feature polymorph relations since
	 * they are not really compatible with traditional relational databases.
	 * However a light-weight, basic implementation has been added for
	 * those circumstances where you can't live without...
	 * i.e... possible legacy systems and so on.
	 *
	 * @return void
	 */
	public function testPoly()
	{
		testpack( 'Testing poly' );
		$shoe = R::dispense( 'shoe' );
		$lace = R::dispense( 'lace' );
		$lace->color = 'white';
		$id = R::store( $lace );
		$shoe->itemType = 'lace';
		$shoe->item     = $lace;
		$id = R::store( $shoe );
		$shoe = R::load( 'shoe', $id );
		$x = $shoe->poly( 'itemType' )->item;
		asrt( $x->color, 'white' );
	}

	/**
	 * Test limited support for 1-to-1 associations.
	 * The rule is, one-to-ones are supposes to be in the same table,
	 * this is just for some legacy tables not designed to work
	 * with RedBeanPHP at all.
	 *
	 * @return void
	 */
	public function testOneToOne()
	{
		testpack( 'Testing one-to-ones' );
		$author = R::dispense( 'author' )->setAttr( 'name', 'a' );;
		$bio    = R::dispense( 'bio' )->setAttr( 'name', 'a' );
		R::storeAll( array( $author, $bio ) );
		$id1 = $author->id;
		$author = R::dispense( 'author' )->setAttr( 'name', 'b' );;
		$bio    = R::dispense( 'bio' )->setAttr( 'name', 'b' );
		R::storeAll( array( $author, $bio ) );
		$x = $author->one( 'bio' );
		$y = $bio->one('author');
		asrt( $x->name, $bio->name );
		asrt( $y->name, $author->name );
		asrt( $x->id, $bio->id );
		asrt( $y->id, $author->id );
		$id2 = $author->id;
		list( $a, $b ) = R::loadMulti( 'author,bio', $id1 );
		asrt( $a->name, $b->name );
		asrt( $a->name, 'a' );
		list( $a, $b ) = R::loadMulti( 'author,bio', $id2 );
		asrt( $a->name, $b->name );
		asrt( $a->name, 'b' );
		list( $a, $b ) = R::loadMulti( array( 'author', 'bio' ), $id1 );
		asrt( $a->name, $b->name );
		asrt( $a->name, 'a' );
		list( $a, $b ) = R::loadMulti( array( 'author', 'bio' ), $id2 );
		asrt( $a->name, $b->name );
		asrt( $a->name, 'b' );
		asrt( is_array( R::loadMulti( NULL, 1 ) ), TRUE );
		asrt( ( count( R::loadMulti( NULL, 1 ) ) === 0 ), TRUE );
	}

	/**
	 * Test single column bases unique constraints.
	 *
	 * @return void
	 */
	public function testSingleColUniqueConstraint()
	{
		testpack( 'Testing unique constraint on single column' );
		$book = R::dispense( 'book' );
		$book->title = 'bla';
		$book->extra = 2;
		$id = R::store( $book );
		R::getWriter()->addUniqueIndex( 'book', array( 'title' ) );
		$book = R::dispense( 'book' );
		$book->title = 'bla';
		$expected = NULL;
		try {
			R::store( $book );

			fail();
		} catch ( SQL $e ) {
			$expected = $e;
		}
		asrt( ( $expected instanceof SQL ), TRUE );
		asrt( R::count( 'book' ), 1 );
		$book = R::load( 'book', $id );
		// Causes failure, table will be rebuild
		$book->extra = 'CHANGE';
		$id2 = R::store( $book );
		$book2 = R::load( 'book', $id2 );
		$book = R::dispense( 'book' );
		$book->title = 'bla';
		try {
			R::store( $book );

			fail();
		} catch ( SQL $e ) {
			$expected = $e;
		}
		asrt( ( $expected instanceof SQL ), TRUE );
		asrt( R::count( 'book' ), 1 );
	}

	/**
	 * Test multiple assiociation.
	 *
	 * @return void
	 */
	public function testMultiAssociationDissociation()
	{
		$wines  = R::dispense( 'wine', 3 );
		$cheese = R::dispense( 'cheese', 3 );
		$olives = R::dispense( 'olive', 3 );
		R::getRedBean()->getAssociationManager()->associate( $wines, array_merge( $cheese, $olives ) );
		asrt( R::count( 'cheese' ), 3 );
		asrt( R::count( 'olive' ), 3 );
		asrt( R::count( 'wine' ), 3 );
		asrt( count( $wines[0]->sharedCheese ), 3 );
		asrt( count( $wines[0]->sharedOlive ), 3 );
		asrt( count( $wines[1]->sharedCheese ), 3 );
		asrt( count( $wines[1]->sharedOlive ), 3 );
		asrt( count( $wines[2]->sharedCheese ), 3 );
		asrt( count( $wines[2]->sharedOlive ), 3 );
		R::getRedBean()->getAssociationManager()->unassociate( $wines, $olives );
		asrt( count( $wines[0]->sharedCheese ), 3 );
		asrt( count( $wines[0]->sharedOlive ), 0 );
		asrt( count( $wines[1]->sharedCheese ), 3 );
		asrt( count( $wines[1]->sharedOlive ), 0 );
		asrt( count( $wines[2]->sharedCheese ), 3 );
		asrt( count( $wines[2]->sharedOlive ), 0 );
		R::getRedBean()->getAssociationManager()->unassociate( array( $wines[1] ), $cheese );
		asrt( count( $wines[0]->sharedCheese ), 3 );
		asrt( count( $wines[0]->sharedOlive ), 0 );
		asrt( count( $wines[1]->sharedCheese ), 0 );
		asrt( count( $wines[1]->sharedOlive ), 0 );
		asrt( count( $wines[2]->sharedCheese ), 3 );
		asrt( count( $wines[2]->sharedOlive ), 0 );
		R::getRedBean()->getAssociationManager()->unassociate( array( $wines[2] ), $cheese );
		asrt( count( $wines[0]->sharedCheese ), 3 );
		asrt( count( $wines[0]->sharedOlive ), 0 );
		asrt( count( $wines[1]->sharedCheese ), 0 );
		asrt( count( $wines[1]->sharedOlive ), 0 );
		asrt( count( $wines[2]->sharedCheese ), 0 );
		asrt( count( $wines[2]->sharedOlive ), 0 );
	}

	/**
	 * Tests error handling related to association.
	 * On database systems providing informative SQL STATE error codes
	 * RedBeanPHP should not mind non-existing tables or columns in
	 * fluid mode.
	 *
	 * @return void
	 */
	public function testErrorHandling()
	{
		R::nuke();
		list( $book, $page ) = R::dispenseAll( 'book,page' );
		$book->sharedPage[] = $page;
		R::store( $page );
		$redbean = R::getRedBean();
		$am = $redbean->getAssociationManager();
		//SQLite and CUBRID do not comply with ANSI SQLState codes.
		$catchAll = ( $this->currentlyActiveDriverID == 'sqlite' || $this->currentlyActiveDriverID === 'CUBRID' );
		try {
			$am->related( $book, 'page', 'invalid SQL' );
			if ($catchAll) pass(); else fail();
		} catch ( SQL $e ) {
			if ($catchAll) fail(); else pass();
		}
		try {
			$am->related( $book, 'cover');
			pass();
		} catch ( SQL $e ) {
			fail();
		}
		try {
			$am->related( R::dispense('cover'), 'book' );
			pass();
		} catch ( SQL $e ) {
			fail();
		}
	}
}
