<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\QueryWriter\SQLiteT as SQLiteT;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\QueryWriter\MySQL as MySQL;
use RedBeanPHP\QueryWriter\PostgreSQL as PostgreSQL;
use RedBeanPHP\QueryWriter\CUBRID as CUBRID;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;

/**
 * Database
 *
 * Tests basic RedBeanPHP database functionality.
 *
 * @file    RedUNIT/Base/Database.php
 * @desc    Tests basic database behaviors
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Database extends Base
{
	/**
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite', 'CUBRID' );
	}

	/**
	 * Test the (protected) database capability checker method
	 * of the RedBeanPHP PDO driver (RPDO).
	 */
	public function testDatabaseCapabilityChecker()
	{
		$capChecker = new \DatabaseCapabilityChecker( R::getDatabaseAdapter()->getDatabase()->getPDO() );
		$result = $capChecker->checkCapability('creativity');
		asrt( $result, FALSE ); /* nope, no strong AI yet.. */
	}

	/**
	 * Test whether we can obtain the PDO object from the
	 * database driver for custom database operations.
	 *
	 * @return void
	 */
	public function testGetPDO()
	{
		$driver = R::getDatabaseAdapter();
		asrt( ( $driver instanceof DBAdapter), TRUE );
		$pdo = $driver->getDatabase()->getPDO();
		asrt( ( $pdo instanceof \PDO ), TRUE );
		$pdo2 = R::getPDO();
		asrt( ( $pdo2 instanceof \PDO ), TRUE );
		asrt( ( $pdo === $pdo2 ), TRUE );
	}

	/**
	 * Test setter maximum integer bindings.
	 *
	 * @return void
	 */
	public function testSetMaxBind()
	{
		$driver = R::getDatabaseAdapter()->getDatabase();
		$old = $driver->setMaxIntBind( 10 );
		//use SQLite to confirm...
		if ( $this->currentlyActiveDriverID === 'sqlite' ) {
			$type = R::getCell( 'SELECT typeof( ? ) ', array( 11 ) );
			asrt( $type, 'text' );
			$type = R::getCell( 'SELECT typeof( ? ) ', array( 10 ) );
			asrt( $type, 'integer' );
			$type = R::getCell( 'SELECT typeof( ? ) ', array( 9 ) );
			asrt( $type, 'integer' );
		}
		$new = $driver->setMaxIntBind( $old );
		asrt( $new, 10 );
		try {
			$driver->setMaxIntBind( '10' );
			fail();
		} catch( RedException $e ) {
			pass();
		}
		$new = $driver->setMaxIntBind( $old );
		asrt( $new, $old );
		$new = $driver->setMaxIntBind( $old );
		asrt( $new, $old );
	}

	/**
	 * Can we use colons in SQL?
	 *
	 * @return void
	 */
	public function testColonsInSQL()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->title = 'About :';
		R::store( $book );
		pass();
		$book = R::findOne( 'book', ' title LIKE :this ', array(
			':this' => 'About :'
		) );
		asrt( ( $book instanceof OODBBean ), TRUE );
		//without the colon?
		$book = R::findOne( 'book', ' title LIKE :this ', array(
			'this' => 'About :'
		) );
		asrt( ( $book instanceof OODBBean ), TRUE );
		$book = R::findOne( 'book', ' title LIKE :this ', array(
			':this' => '%:%'
		) );
		asrt( ( $book instanceof OODBBean ), TRUE );
		$book = R::findOne( 'book', ' title LIKE :this OR title LIKE :that', array(
			'this' => '%:%', ':that' => 'That'
		) );
		asrt( ( $book instanceof OODBBean ), TRUE );
		$records = R::getAll('SELECT * FROM book WHERE title LIKE :this', array( ':this' => 'About :' ) );
		asrt( count( $records ), 1 );
		$records = R::getAll('SELECT * FROM book WHERE title LIKE :this', array( 'this' => 'About :' ) );
		asrt( count( $records ), 1 );
		$records = R::getAll('SELECT * FROM book WHERE title LIKE :this OR title LIKE :that', array( ':this' => 'About :', ':that' => 'That' ) );
		asrt( count( $records ), 1 );
		$records = R::getRow('SELECT * FROM book WHERE title LIKE :this', array( ':this' => 'About :' ) );
		asrt( count( $records ), 2 );
		$records = R::getRow('SELECT * FROM book WHERE title LIKE :this', array( 'this' => 'About :' ) );
		asrt( count( $records ), 2 );
		$records = R::getRow('SELECT * FROM book WHERE title LIKE :this OR title LIKE :that', array( ':this' => 'About :', ':that' => 'That' ) );
		asrt( count( $records ), 2 );
	}

	/**
	 * Test setting direct PDO.
	 * Not much to test actually.
	 *
	 * @return void
	 */
	public function testDirectPDO()
	{
		$pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
		R::getDatabaseAdapter()->getDatabase()->setPDO( $pdo );
		pass();
	}

	/**
	 * Test for testConnection() method.
	 *
	 * @return void
	 */
	public function testConnectionTester()
	{
		asrt( R::testConnection(), TRUE );
	}

	/**
	 * Tests the various ways to fetch (select queries)
	 * data using adapter methods in the facade.
	 * Also tests the new R::getAssocRow() method,
	 * as requested in issue #324.
	 */
	public function testFetchTypes()
	{
		R::nuke();

		$page = R::dispense( 'page' );
		$page->a = 'a';
		$page->b = 'b';
		R::store( $page );

		$page = R::dispense( 'page' );
		$page->a = 'c';
		$page->b = 'd';
		R::store( $page );

		$expect = '[{"id":"1","a":"a","b":"b"},{"id":"2","a":"c","b":"d"}]';
		asrt( json_encode( R::getAll( 'SELECT * FROM page' ) ), $expect );

		$expect = '{"1":"a","2":"c"}';
		asrt( json_encode( R::getAssoc( 'SELECT id, a FROM page' ) ), $expect );

		$expect = '{"1":{"a":"a","b":"b"},"2":{"a":"c","b":"d"}}';
		asrt( json_encode( R::getAssoc( 'SELECT id, a, b FROM page' ) ), $expect );

		$expect = '[{"id":"1","a":"a"},{"id":"2","a":"c"}]';
		asrt( json_encode( R::getAssocRow( 'SELECT id, a FROM page' ) ), $expect );

		$expect = '[{"id":"1","a":"a","b":"b"},{"id":"2","a":"c","b":"d"}]';
		asrt( json_encode( R::getAssocRow( 'SELECT id, a, b FROM page' ) ), $expect );

		$expect = '{"id":"1","a":"a","b":"b"}';
		asrt( json_encode( R::getRow( 'SELECT * FROM page WHERE id = 1' ) ), $expect );

		$expect = '"a"';
		asrt( json_encode( R::getCell( 'SELECT a FROM page WHERE id = 1' ) ), $expect );

		$expect = '"b"';
		asrt( json_encode( R::getCell( 'SELECT b FROM page WHERE id = 1') ), $expect );

		$expect = '"c"';
		asrt( json_encode( R::getCell('SELECT a FROM page WHERE id = 2') ), $expect );

		$expect = '["a","c"]';
		asrt( json_encode( R::getCol( 'SELECT a FROM page' ) ), $expect );

		$expect = '["b","d"]';
		asrt( json_encode( R::getCol('SELECT b FROM page') ), $expect );
	}

	/**
	 * Tests whether we can store an empty bean.
	 * An empty bean has no properties, only ID. Normally we would
	 * skip the ID field in an INSERT, this test forces the driver
	 * to specify a value for the ID field. Different writers have to
	 * use different values: Mysql uses NULL to insert a new auto-generated ID,
	 * while Postgres has to use DEFAULT.
	 */
	public function testEmptyBean()
	{
		testpack( 'Test Empty Bean Storage.' );
		R::nuke();
		$bean = R::dispense( 'emptybean' );
		$id = R::store( $bean );
		asrt( ( $id > 0 ), TRUE );
		asrt( R::count( 'emptybean' ), 1 );
		$bean = R::dispense( 'emptybean' );
		$id = R::store( $bean );
		asrt( ( $id > 0 ), TRUE );
		asrt( R::count( 'emptybean' ), 2 );
		//also test in frozen mode
		R::freeze( TRUE );
		$bean = R::dispense( 'emptybean' );
		$id = R::store( $bean );
		asrt( ( $id > 0 ), TRUE );
		asrt( R::count( 'emptybean' ), 3 );
		R::freeze( FALSE );
	}

	/**
	 * Test the database driver and low level functions.
	 *
	 * @return void
	 */
	public function testDriver()
	{
		$currentDriver = $this->currentlyActiveDriverID;

		R::store( R::dispense( 'justabean' ) );

		$adapter = new TroubleDapter( R::getToolBox()->getDatabaseAdapter()->getDatabase() );
		$adapter->setSQLState( 'HY000' );
		$writer  = new SQLiteT( $adapter );
		$redbean = new OODB( $writer );
		$toolbox = new ToolBox( $redbean, $adapter, $writer );

		// We can only test this for a known driver...
		if ( $currentDriver === 'sqlite' ) {
			try {
				$redbean->find( 'bean' );

				pass();
			} catch (\Exception $e ) {
				var_dump( $e->getSQLState() );

				fail();
			}
		}

		$adapter->setSQLState( -999 );

		try {
			$redbean->find( 'bean' );

			fail();
		} catch (\Exception $e ) {
			pass();
		}

		try {
			$redbean->wipe( 'justabean' );

			fail();
		} catch (\Exception $e ) {
			pass();
		}

		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$page = $redbean->dispense( "page" );

		try {
			$adapter->exec( "an invalid query" );
			fail();
		} catch ( SQL $e ) {
			pass();
		}

		// Special data type description should result in magic number 99 (specified)
		if ( $currentDriver == 'mysql' ) {
			asrt( $writer->code( MySQL::C_DATATYPE_SPECIAL_DATE ), 99 );
		}

		if ( $currentDriver == 'pgsql' ) {
			asrt( $writer->code( PostgreSQL::C_DATATYPE_SPECIAL_DATE ), 99 );
		}

		if ( $currentDriver == 'CUBRID' ) {
			asrt( $writer->code( CUBRID::C_DATATYPE_SPECIAL_DATE ), 99 );
		}

		asrt( (int) $adapter->getCell( "SELECT 123" ), 123 );

		$page->aname = "my page";

		$id = (int) $redbean->store( $page );

		asrt( (int) $page->id, 1 );
		asrt( (int) $pdo->GetCell( "SELECT count(*) FROM page" ), 1 );
		asrt( $pdo->GetCell( "SELECT aname FROM page LIMIT 1" ), "my page" );
		asrt( (int) $id, 1 );

		$page = $redbean->load( "page", 1 );

		asrt( $page->aname, "my page" );
		asrt( ( (bool) $page->getMeta( "type" ) ), TRUE );
		asrt( isset( $page->id ), TRUE );
		asrt( ( $page->getMeta( "type" ) ), "page" );
		asrt( (int) $page->id, $id );
	}

	/**
	 * Test selecting.
	 *
	 * @return void
	 */
	public function testSelects()
	{
		$rooms = R::dispense( 'room', 2 );

		$rooms[0]->kind   = 'suite';
		$rooms[1]->kind   = 'classic';
		$rooms[0]->number = 6;
		$rooms[1]->number = 7;

		R::store( $rooms[0] );
		R::store( $rooms[1] );

		$rooms = R::getAssoc('SELECT * FROM room WHERE id < -999');
		asrt(is_array($rooms), TRUE);
		asrt(count($rooms), 0);

		$rooms = R::getAssoc( 'SELECT ' . R::getWriter()->esc( 'number' ) . ', kind FROM room ORDER BY kind ASC' );

		foreach ( $rooms as $key => $room ) {
			asrt( ( $key === 6 || $key === 7 ), TRUE );
			asrt( ( $room == 'classic' || $room == 'suite' ), TRUE );
		}

		$rooms = R::getDatabaseAdapter()->getAssoc( 'SELECT kind FROM room' );
		foreach ( $rooms as $key => $room ) {
			asrt( ( $room == 'classic' || $room == 'suite' ), TRUE );
			asrt( $room, $key );
		}

		$rooms = R::getAssoc( 'SELECT `number`, kind FROM rooms2 ORDER BY kind ASC' );

		asrt( count( $rooms ), 0 );
		asrt( is_array( $rooms ), TRUE );

		// GetCell should return NULL in case of exception
		asrt( NULL, R::getCell( 'SELECT dream FROM fantasy' ) );
	}
}

/**
 * Malfunctioning database adapter to test exceptions.
 */
class TroubleDapter extends DBAdapter
{
	private $sqlState;

	public function setSQLState( $sqlState )
	{
		$this->sqlState = $sqlState;
	}

	public function get( $sql, $values = array() )
	{
		$exception = new SQL( 'Just a trouble maker' );
		$exception->setSQLState( $this->sqlState );
		$exception->setDriverDetails( array(0,1,0) );
		throw $exception;
	}

	public function getRow( $sql, $aValues = array() )
	{
		$this->get( $sql, $aValues );
	}

	public function exec( $sql, $aValues = array(), $noEvent = FALSE )
	{
		$this->get( $sql, $aValues );
	}
}

