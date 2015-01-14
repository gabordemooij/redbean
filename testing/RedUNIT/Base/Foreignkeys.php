<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\Observer as Observer;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Adapter as Adapter;

/**
 * Foreignkeys
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
	 * Tests index information methods.
	 *
	 * @return void
	 */
	public function testIndexInfo()
	{
		global $currentDriver;
		R::nuke();
		$writer = R::getWriter();
		$book = R::dispense( 'book' );
		$book->ownPageList[] = R::dispense( 'page' );
		R::store( $book );
		$map = \ProxyWriter::callMethod( $writer, 'getIndexListForType', 'page' );
		asrt( is_array( $map ), TRUE );
		asrt( ( count( $map ) > 0 ), TRUE );
		foreach( $map as $columns ) {
			foreach( $columns as $column ) {
				asrt( in_array( $column, array( 'id', 'book_id' ) ), TRUE );
			}
		}
		$book->sharedCategory[] = R::dispense( 'category' );
		R::store( $book );
		$book->sharedCategory[] = R::dispense( 'category' );
		R::store( $book );
		$book->link('book_category', array( 'note' => 'test' ) )->category = R::dispense( 'category' );
		R::store( $book );
		$map = \ProxyWriter::callMethod( $writer, 'getIndexListForType', 'book_category' );
		foreach( $map as $columns ) {
			foreach( $columns as $column ) {
				asrt( in_array( $column, array( 'id', 'book_id', 'category_id' ) ), TRUE );
			}
		}
		asrt( \ProxyWriter::callMethod( $writer, 'isIndexed', 'book_category', 'book_id' ), TRUE );
		asrt( \ProxyWriter::callMethod( $writer, 'isIndexed', 'book_category', 'category_id' ), TRUE );
		asrt( \ProxyWriter::callMethod( $writer, 'isIndexed', 'book_category', 'strange_id' ), FALSE );
		$columns = R::inspect( 'book_category' );
		asrt( isset( $columns['note'] ), TRUE );
		asrt( \ProxyWriter::callMethod( $writer, 'isIndexed', 'book_category', 'note' ), FALSE );
		asrt( \ProxyWriter::callMethod( $writer, 'isIndexed', 'page', 'book_id' ), TRUE );
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
