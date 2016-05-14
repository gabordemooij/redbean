<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

/**
 * Update
 *
 * Tests basic update functionality - however this test suite
 * has grown to cover various other scenarios involving updates as
 * well, including setting of property filters (necessary for
 * spatial tools in MySQL), storiging INF value and more...
 *
 * @file    RedUNIT/Base/Update.php
 * @desc    Tests basic storage features through OODB class.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Update extends Base
{
	/**
	 * Test whether we can use SQL filters and
	 * whether they are being applied properly for
	 * different types of SELECT queries in the QueryWriter.
	 */
	public function testSQLFilters()
	{
		R::nuke();
		AQueryWriter::setSQLFilters(array(
			QueryWriter::C_SQLFILTER_READ => array(
				'book' => array( 'title' => ' LOWER(book.title) '),
			),
			QueryWriter::C_SQLFILTER_WRITE => array(
				'book' => array( 'title' => ' UPPER(?) '),
			),
		));

		$book = R::dispense( 'book' );
		$book->title = 'story';
		R::store( $book );
		asrt( R::getCell( 'SELECT title FROM book WHERE id = ?', array( $book->id ) ), 'STORY' );
		$book = $book->fresh();
		asrt( $book->title, 'story' );
		$library = R::dispense( 'library' );
		$library->sharedBookList[] = $book;
		R::store( $library );
		$library = $library->fresh();
		$books = $library->sharedBookList;
		$book = reset( $books );
		asrt( $book->title, 'story' );
		$otherBook = R::dispense('book');
		$otherBook->sharedBook[] = $book;
		R::store( $otherBook );
		$otherBook = $otherBook->fresh();
		$books = $otherBook->sharedBookList;
		$book = reset( $books );
		asrt( $book->title, 'story' );
		$links = $book->ownBookBookList;
		$link = reset( $links );
		$link->shelf = 'x13';
		AQueryWriter::setSQLFilters(array(
			QueryWriter::C_SQLFILTER_READ => array(
				'book' => array( 'title' => ' LOWER(book.title) '),
				'book_book' => array( 'shelf' => ' LOWER(book_book.shelf) '),
			),
			QueryWriter::C_SQLFILTER_WRITE => array(
				'book' => array( 'title' => ' UPPER(?) '),
				'book_book' => array( 'shelf' => ' UPPER(?) ')
			),
		));
		R::store( $link );
		asrt( R::getCell( 'SELECT shelf FROM book_book WHERE id = ?', array( $link->id ) ), 'X13' );
		$otherBook = $otherBook->fresh();
		unset($book->sharedBookList[$otherBook->id]);
		R::store( $book );
		AQueryWriter::setSQLFilters(array());
	}

	/**
	 * Test unsetting properties.
	 *
	 * @return void
	 */
	public function testUnsetUpdate()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->name = 'x';
		$book->price = 40;
		R::store( $book );
		$book = $book->fresh();
		$book->name = 'y';
		unset( $book->name );
		R::store( $book );
		$book = $book->fresh();
		asrt( $book->name, 'x' );
		asrt( (int) $book->price, 40 );
		$book->price = 30;
		R::store( $book );
		$book = $book->fresh();
		asrt( $book->name, 'x' );
		asrt( (int) $book->price, 30 );
		$book->price = 20;
		unset( $book->price );
		$book->name = 'y';
		R::store( $book );
		$book = $book->fresh();
		asrt( $book->name, 'y' );
		asrt( (int) $book->price, 30 );
	}

	/**
	 * Tests whether we can update or unset a parent bean
	 * with an alias without having to use fetchAs and
	 * without loading the aliased bean causing table-not-found
	 * errors.
	 */
	public function testUpdatingParentBeansWithAliases()
	{
		testpack( 'Test updating parent beans with aliases' );
		R::nuke();
		$trans  = R::dispense( 'transaction' );
		$seller = R::dispense( 'user' );
		$trans->seller = $seller;
		$id = R::store( $trans );
		R::freeze( TRUE );
		$trans = R::load( 'transaction', $id );
		//should not try to load seller, should not require fetchAs().
		try {
			$trans->seller = R::dispense( 'user' );
			pass();
		} catch( Exception $e ) {
			fail();
		}
		$trans = R::load( 'transaction', $id );
		//same for unset...
		try {
			unset( $trans->seller );
			pass();
		} catch ( Exception $e ) {
			fail();
		}
		R::freeze( FALSE );
		$account = R::dispense( 'user' );
		asrt( count( $account->alias( 'seller' )->ownTransaction ), 0 );
		$account->alias( 'seller' )->ownTransaction = R::dispense( 'transaction', 10 );
		$account->alias( 'boo' ); //try to trick me...
		$id = R::store( $account );
		R::freeze( true );
		$account = R::load( 'user', $id );
		asrt( count( $account->alias( 'seller' )->ownTransaction ), 10 );
		//you cannot unset a list
		unset( $account->alias( 'seller' )->ownTransaction );
		$id = R::store( $account );
		$account = R::load( 'user', $id );
		asrt( count( $account->alias( 'seller' )->ownTransaction ), 10 );
		$account->alias( 'seller' )->ownTransaction = array();
		$id = R::store( $account );
		$account = R::load( 'user', $id );
		asrt(count($account->alias( 'seller' )->ownTransaction), 0 );
		asrt(count($account->ownTransaction), 0 );
		R::freeze( FALSE );
		//but also make sure we don't cause extra column issue #335
		R::nuke();
		$building = R::dispense('building');
		$village  = R::dispense('village');
		$building->village = $village;
		R::store($building);
		$building = $building->fresh();
		$building->village = NULL;
		R::store($building);
		$building = $building->fresh();
		$columns = R::inspect('building');
		asrt( isset( $columns['village'] ), FALSE );
		asrt( isset( $building->village ), FALSE );
		R::nuke();
		$building = R::dispense('building');
		$village  = R::dispense('village');
		$building->village = $village;
		R::store($building);
		$building = $building->fresh();
		unset($building->village);
		R::store($building);
		$building = $building->fresh();
		$columns = R::inspect('building');
		asrt( isset( $columns['village'] ), FALSE );
		asrt( isset( $building->village ), FALSE );
		$building = R::dispense('building');
		$village  = R::dispense('village');
		$building->village = $village;
		R::store($building);
		$building = $building->fresh();
		$building->village = FALSE;
		R::store($building);
		$building = $building->fresh();
		$columns = R::inspect('building');
		asrt( isset( $columns['village'] ), FALSE );
		asrt( isset( $building->village ), FALSE );
	}

	/**
	 * All kinds of tests for basic CRUD.
	 *
	 * Does the data survive?
	 *
	 * @return void
	 */
	public function testUpdatingBeans()
	{
		testpack( 'Test basic support UUID/override ID default value' );
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		if ($this->currentlyActiveDriverID === 'mysql') {
			//otherwise UTF8 causes index overflow in mysql: SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes
			R::exec('alter table bean modify column id char(3);');
		} else {
			R::getWriter()->widenColumn( 'bean', 'id', R::getWriter()->scanType( 'abc' ) );
		}
		$bean->id = 'abc';
		R::store( $bean );
		asrt( $bean->id, 'abc' );
		testpack( 'Test Update' );
		try {
			R::store( array() );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		$page    = $redbean->dispense( "page" );
		$page->name = "old name";
		$id = $redbean->store( $page );
		asrt( $page->getMeta( 'tainted' ), FALSE );
		$page->setAttr( 'name', "new name" );
		asrt( $page->getMeta( 'tainted' ), TRUE );
		$id = $redbean->store( $page );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		// Null should == NULL after saving
		$page->rating = NULL;
		$newid = $redbean->store( $page );
		$page  = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( ( $page->rating === NULL ), TRUE );
		asrt( !$page->rating, TRUE );
		$page->rating = FALSE;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( (bool) $page->rating, FALSE );
		asrt( ( $page->rating == FALSE ), TRUE );
		asrt( !$page->rating, TRUE );
		$page->rating = TRUE;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( (bool) $page->rating, TRUE );
		asrt( ( $page->rating == TRUE ), TRUE );
		asrt( ( $page->rating == TRUE ), TRUE );
		$page->rating = NULL;
		R::store( $page );
		$page = R::load( 'page', $page->id );
		asrt( $page->rating, NULL );
		$page->rating = '1';
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( $page->rating, "1" );
		$page->rating = "0";
		$newid = $redbean->store( $page );
		asrt( $page->rating, "0" );
		$page->rating = 0;
		$newid = $redbean->store( $page );
		asrt( $page->rating, 0 );
		$page->rating = "0";
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( !$page->rating, TRUE );
		asrt( ( $page->rating == 0 ), TRUE );
		asrt( ( $page->rating == FALSE ), TRUE );
		$page->rating = 5;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( strval( $page->rating ), "5" );
		$page->rating = 300;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( strval( $page->rating ), "300" );
		$page->rating = -2;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( strval( $page->rating ), "-2" );
		$page->rating = 2.5;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( ( $page->rating == 2.5 ), TRUE );
		$page->rating = -3.3;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( ( $page->rating == -3.3 ), TRUE );
		$page->rating = "good";
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( $page->rating, "good" );
		$longtext = str_repeat( 'great! because..', 100 );
		$page->rating = $longtext;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( $page->rating, $longtext );
		// Test leading zeros
		$numAsString = "0001";
		$page->numasstring = $numAsString;
		$redbean->store( $page );
		$page = $redbean->load( "page", $id );
		asrt( $page->numasstring, "0001" );
		$page->numnotstring = "0.123";
		$redbean->store( $page );
		$page = $redbean->load( "page", $id );
		asrt( $page->numnotstring == 0.123, TRUE );
		$page->numasstring2 = "00.123";
		$redbean->store( $page );
		$page = $redbean->load( "page", $id );
		asrt( $page->numasstring2, "00.123" );
		try {
			$redbean->trash( array() );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		$redbean->trash( $page );
		asrt( (int) $pdo->GetCell( "SELECT count(*) FROM page" ), 0 );
	}

	/**
	 * Tests whether empty strings are preserved as such.
	 *
	 * @return void
	 */
	public function testEmptyStringShouldNotBeStoredAsInteger()
	{
		R::nuke();
		$bean = R::dispense('bean');
		$bean->str = '';
		R::store($bean);
		$bean = $bean->fresh();
		asrt( ( $bean->str === '' ), TRUE);
	}

	/**
	 * Test handling of infinity values.
	 *
	 * @return void
	 */
	public function testStoringInf()
	{
		R::nuke();
		$bean = R::dispense( 'bean' );
		$bean->inf = INF;
		R::store( $bean );
		$bean = $bean->fresh();
		asrt( ( $bean->inf === 'INF' ), TRUE );
		asrt( ( $bean->inf == 'INF' ), TRUE );
		$bean->modifyme = 'yes';
		R::store( $bean );
		$bean = $bean->fresh();
		asrt( ( $bean->inf === 'INF' ), TRUE );
		asrt( ( $bean->inf == 'INF' ), TRUE );
		$bean->modifyme = 'yes';
	}
}

