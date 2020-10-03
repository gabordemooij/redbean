<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\Observer as Observer;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

/**
 * Foreignkeys
 *
 * Tests whether foreign keys are correctly generated and whether
 * depending beans are correctly removed. Also tests auto resolving
 * types inferred by inspecting foreign keys.
 *
 * @file    RedUNIT/Base/Foreignkeys.php
 * @desc    Tests foreign key handling and dynamic foreign keys with
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Foreignkeys extends Base implements Observer
{
	/**
	 * To log the queries
	 *
	 * @var array
	 */
	private $queries = array();

	/**
	 * Test whether unique constraints are properly created using
	 * reflection.
	 *
	 * @return void
	 */
	public function testUniqueInspect()
	{
		$writer = R::getWriter();
		R::nuke();
		$book = R::dispense( 'book' );
		$category = R::dispense( 'category' );
		$book->sharedCategory[] = $category;
		R::store( $book );
		asrt( count( get_uniques_for_type('book_category') ), 1 );
		asrt( are_cols_in_unique( 'book_category', array( 'book_id', 'category_id' ) ), TRUE );
		R::nuke();
		$book = R::dispense( 'book' );
		$category = R::dispense( 'category' );
		$book->via( 'library' )->sharedCategory[] = $category;
		R::store( $book );
		asrt( count( get_uniques_for_type('book_category') ), 0 );
		asrt( are_cols_in_unique( 'book_category', array( 'book_id', 'category_id' ) ), FALSE );
		asrt( count( get_uniques_for_type('library') ), 1 );
		asrt( are_cols_in_unique( 'library', array( 'book_id', 'category_id' ) ), TRUE );
		AQueryWriter::clearRenames();
		R::nuke();
		$book = R::dispense( 'book' );
		$category = R::dispense( 'category' );
		$book->sharedCategory[] = $category;
		R::store( $book );
		asrt( count( get_uniques_for_type('book_category') ), 1 );
		asrt( are_cols_in_unique( 'book_category', array( 'book_id', 'category_id' ) ), TRUE );
		asrt( count( get_uniques_for_type('library') ), 0 );
		asrt( are_cols_in_unique( 'library', array( 'book_id', 'category_id' ) ), FALSE );
		R::nuke();
		$book = R::dispense( 'book' );
		$book2 = R::dispense( 'book' );
		$book->sharedBook[] = $book2;
		R::store( $book );
		asrt( count( get_uniques_for_type('book_book') ), 1 );
		asrt( are_cols_in_unique( 'book_book', array( 'book_id', 'book2_id' ) ), TRUE );
		try {
			$result = R::getWriter()->addUniqueConstraint( 'nonexistant', array( 'a', 'b' ) );
		} catch( \Exception $e ) {
			print_r( $e ); exit;
		}
		pass(); //dont crash!
		asrt( $result, FALSE );
	}

	/**
	 * Tests foreign keys but checks using ProxyWriter.
	 *
	 * @return void
	 */
	public function testFKInspect()
	{
		$faultyWriter = new \FaultyWriter( R::getDatabaseAdapter() );
		try {
			$null = \ProxyWriter::callMethod( $faultyWriter, 'getForeignKeyForTypeProperty', 'test', 'test' );
			pass();
		} catch( \Exception $e ) {
			fail();
		}
		asrt( is_null( $null ), TRUE );
		$writer = R::getWriter();
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->xownPage[] = $page;
		R::store( $book );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'page', 'book_id' );
		asrt( is_array( $keys ), TRUE );
		asrt( $keys['on_delete'], 'CASCADE' );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'page', 'id' );
		asrt( is_null( $keys ), TRUE );
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->ownPage[] = $page;
		R::store( $book );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'page', 'book_id' );
		asrt( is_array( $keys ), TRUE );
		asrt( $keys['on_delete'], 'SET NULL' );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'page', 'id' );
		asrt( is_null( $keys ), TRUE );
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->alias('magazine')->xownPage[] = $page;
		R::store( $book );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'page', 'magazine_id' );
		asrt( is_array( $keys ), TRUE );
		asrt( $keys['on_delete'], 'CASCADE' );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'page', 'book_id' );
		asrt( is_null( $keys ), TRUE );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'page', 'id' );
		asrt( is_null( $keys ), TRUE );
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->cover= $page;
		R::store( $book );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'book', 'cover_id' );
		asrt( is_array( $keys ), TRUE );
		asrt( $keys['on_delete'], 'SET NULL' );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'book', 'page_id' );
		asrt( is_null( $keys ), TRUE );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'book', 'id' );
		asrt( is_null( $keys ), TRUE );
		R::nuke();
		$book = R::dispense( 'book' );
		$category = R::dispense( 'category' );
		$book->sharedTag[] = $category;
		R::store( $book );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'book_category', 'book_id' );
		asrt( is_array( $keys ), TRUE );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'book_category', 'category_id' );
		asrt( is_array( $keys ), TRUE );
		$keys = \ProxyWriter::callMethod( $writer, 'getForeignKeyForTypeProperty', 'book_category', 'id' );
		asrt( is_null( $keys ), TRUE );
	}

	/**
	 * Test dependencies.
	 *
	 * @return void
	 */
	public function testDependency()
	{
		$can = $this->createBeanInCan( FALSE );
		asrt( R::count( 'bean' ), 1 );
		R::trash( $can );
		// Bean stays
		asrt( R::count( 'bean' ), 1 );
	}

	/**
	 * Test dependencies (variation).
	 *
	 * @return void
	 */
	public function testDependency2()
	{
		$can = $this->createBeanInCan( TRUE );
		asrt( R::count( 'bean' ), 1 );
		R::trash( $can );
		// Bean gone
		asrt( R::count( 'bean' ), 0 );
		$can = $this->createBeanInCan( FALSE );
		asrt( R::count( 'bean' ), 1 );
		R::trash( $can );
		// Bean stays, constraint removed
		asrt( R::count( 'bean' ), 0 );
		//need to recreate table to get rid of constraint!
		R::nuke();
		$can = $this->createBeanInCan( FALSE );
		asrt( R::count( 'bean' ), 1 );
		R::trash( $can );
		// Bean stays, constraint removed
		asrt( R::count( 'bean' ), 1 );
	}

	/**
	 * Tests dependencies (variation).
	 *
	 * @return void
	 */
	public function testDependency3()
	{
		R::nuke();
		$can = $this->createCanForBean();
		asrt( R::count( 'bean' ), 1 );
		R::trash( $can );
		asrt( R::count( 'bean' ), 1 );
	}

	/**
	 * Tests dependencies (variation).
	 *
	 * @return void
	 */
	public function testDependency4()
	{
		R::nuke();
		$can = $this->createBeanInCan( TRUE );
		R::store( $can );
		R::trash( $can );
		$can = $this->createCanForBean();
		asrt( R::count( 'bean' ), 1 );
		R::trash( $can );
		asrt( R::count( 'bean' ), 0 );
		$can = $this->createBeanInCan( TRUE );
		R::store( $can );
		R::trash( $can );
		$can = $this->createCanForBean();
		asrt( R::count( 'bean' ), 1 );
		R::trash( $can );
		asrt( R::count( 'bean' ), 0 );
	}

	/**
	 * Issue #171
	 * The index name argument is not unique in processEmbeddedBean etc.
	 *
	 * @return void
	 */
	public function testIssue171()
	{
		R::getDatabaseAdapter()->addEventListener( 'sql_exec', $this );
		$account = R::dispense( 'account' );
		$user    = R::dispense( 'user' );
		$player  = R::dispense( 'player' );
		$account->ownUser[] = $user;
		R::store( $account );
		asrt( strpos( implode( ',', $this->queries ), 'index_foreignkey_user_account' ) !== FALSE, TRUE );
		$this->queries = array();
		$account->ownPlayer[] = $player;
		R::store( $account );
		asrt( strpos( implode( ',', $this->queries ), 'index_foreignkey_player_accou' ) !== FALSE, TRUE );
	}

	/**
	 * Tests whether foreign keys are created correctly for certain
	 * relations.
	 *
	 * @return void
	 */
	public function testCreationOfForeignKeys()
	{
		$this->queries = array();
		$account = R::dispense( 'account' );
		$user    = R::dispense( 'user' );
		$player  = R::dispense( 'player' );
		$user->account = $account;
		R::store( $user );
		asrt( strpos( implode( ',', $this->queries ), 'index_foreignkey_user_account' ) !== FALSE, TRUE );
		$this->queries = array();
		$player->account = $account;
		R::store( $player );
		asrt( strpos( implode( ',', $this->queries ), 'index_foreignkey_player_accou' ) !== FALSE, TRUE );
	}

	/**
	 * Test helper method.
	 * Creates a bean in a can. The bean will get a reference
	 * to the can and can be made dependent.
	 *
	 * @return OODBBean $can
	 */
	private function createBeanInCan( $isExcl )
	{
		$can  = R::dispense( 'can' );
		$bean = R::dispense( 'bean' );
		$can->name   = 'bakedbeans';
		$bean->taste = 'salty';
		if ($isExcl) {
			$can->xownBean[] = $bean;
		} else {
			$can->ownBean[] = $bean;
		}
		R::store( $can );
		return $can;
	}

	/**
	 * Test helper method.
	 * Creates a bean in a can beginning with the bean. The bean will get a reference
	 * to the can and can be made dependent.
	 *
	 * @return OODBBean $can
	 */
	private function createCanForBean()
	{
		$can  = R::dispense( 'can' );
		$bean = R::dispense( 'bean' );
		$bean->can = $can;
		R::store( $bean );
		return $can;
	}

	/**
	 * Log queries
	 *
	 * @param string          $event
	 * @param Adapter $info
	 */
	public function onEvent( $event, $info )
	{
		$this->queries[] = $info->getSQL();
	}
}
