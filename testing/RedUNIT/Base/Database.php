<?php
/**
 * RedUNIT_Base_Database
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
class RedUNIT_Base_Database extends RedUNIT_Base
{
	/**
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite', 'CUBRID' );
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

		$adapter = new TroubleDapter( R::$toolbox->getDatabaseAdapter()->getDatabase() );

		$adapter->setSQLState( 'HY000' );

		$writer  = new RedBean_QueryWriter_SQLiteT( $adapter );
		$redbean = new RedBean_OODB( $writer );
		$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );

		// We can only test this for a known driver...
		if ( $currentDriver === 'sqlite' ) {
			try {
				$redbean->find( 'bean' );

				pass();
			} catch ( Exception $e ) {
				var_dump( $e->getSQLState() );

				fail();
			}
		}

		$adapter->setSQLState( -999 );

		try {
			$redbean->find( 'bean' );

			fail();
		} catch ( Exception $e ) {
			pass();
		}

		$beanA = R::dispense( 'bean' );
		$beanB = R::dispense( 'bean' );

		R::storeAll( array( $beanA, $beanB ) );

		$associationManager = new RedBean_AssociationManager( $toolbox );

		$adapter->setSQLState( 'HY000' );

		// We can only test this for a known driver...
		if ( $currentDriver === 'sqlite' ) {
			try {
				$associationManager->areRelated( $beanA, $beanB );

				pass();
			} catch ( Exception $e ) {
				fail();
			}
		}

		$adapter->setSQLState( -999 );

		try {
			$associationManager->areRelated( $beanA, $beanA );

			fail();
		} catch ( Exception $e ) {
			pass();
		}

		try {
			$redbean->wipe( 'justabean' );

			fail();
		} catch ( Exception $e ) {
			pass();
		}

		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$page = $redbean->dispense( "page" );

		try {
			$adapter->exec( "an invalid query" );
			fail();
		} catch ( RedBean_Exception_SQL $e ) {
			pass();
		}

		// Special data type description should result in magic number 99 (specified)
		if ( $currentDriver == 'mysql' ) {
			asrt( $writer->code( RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE ), 99 );
		}

		if ( $currentDriver == 'pgsql' ) {
			asrt( $writer->code( RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_DATE ), 99 );
		}

		if ( $currentDriver == 'CUBRID' ) {
			asrt( $writer->code( RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIAL_DATE ), 99 );
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
		
		$rooms = R::getAssoc( 'SELECT ' . R::$writer->esc( 'number' ) . ', kind FROM room ORDER BY kind ASC' );

		foreach ( $rooms as $key => $room ) {
			asrt( ( $key === 6 || $key === 7 ), TRUE );
			asrt( ( $room == 'classic' || $room == 'suite' ), TRUE );
		}

		$rooms = R::$adapter->getAssoc( 'SELECT kind FROM room' );
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
class TroubleDapter extends RedBean_Adapter_DBAdapter
{
	private $sqlState;

	public function setSQLState( $sqlState )
	{
		$this->sqlState = $sqlState;
	}

	public function get( $sql, $values = array() )
	{
		$exception = new RedBean_Exception_SQL( 'Just a trouble maker' );
		$exception->setSQLState( $this->sqlState );
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


