<?php
/**
 * RedUNIT_Blackhole_Misc
 *
 * @file    RedUNIT/Blackhole/Misc.php
 * @desc    Tests various features that do not rely on a database connection.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole_Misc extends RedUNIT_Blackhole
{
	/*
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'sqlite' );
	}

	/**
	 * Tests whether getID never produces a notice.
	 * 
	 * @return void
	 */
	public function testGetIDShouldNeverPrintNotice() 
	{
		set_error_handler(function($err, $errStr){
			die('>>>>FAIL :'.$err.' '.$errStr);
		});
		$bean = new RedBean_OODBBean;
		$bean->getID();
		restore_error_handler();
		pass();
	}

	/**
	 * Tests beansToArray().
	 * 
	 * @return void 
	 */
	public function testBeansToArray() 
	{	
		testpack('Test R::beansToArray method');
	
		$bean1 = R::dispense( 'bean' );
		$bean1->name = 'hello';
		$bean2 = R::dispense( 'bean' );
		$bean2->name = 'world';

		$beans = array( $bean1, $bean2 );
		$array = R::beansToArray( $beans );
		asrt( $array[0]['name'], 'hello' );
		asrt( $array[1]['name'], 'world' );
	}
	
	/**
	 * Test debugging with custom logger.
	 * 
	 * @return void
	 */
	public function testDebugCustomLogger()
	{
		testpack( 'Test debug mode with custom logger' );

		$pdoDriver = new RedBean_Driver_PDO( R::getDatabaseAdapter()->getDatabase()->getPDO() );

		$customLogger = new CustomLogger;

		$pdoDriver->setDebugMode( TRUE, $customLogger );

		$pdoDriver->Execute( 'SELECT 123' );

		asrt( count( $customLogger->getLogMessage() ), 1 );

		$pdoDriver->setDebugMode( TRUE, NULL );
		asrt( ( $pdoDriver->getLogger() instanceof RedBean_Logger_Default ), TRUE );

		testpack( 'Test bean->getProperties method' );

		$bean = R::dispense( 'bean' );

		$bean->property = 'hello';

		$props = $bean->getProperties();

		asrt( isset( $props['property'] ), TRUE );

		asrt( $props['property'], 'hello' );

		testpack( 'Test snake_case vs CamelCase with Query Builder' );

		list( $sql, $params ) = R::$f->begin()->camelCase()->getQuery();

		asrt( trim( $sql ), 'camel case' );

		list( $sql, $params ) = R::$f->begin()->personASTeacher()->getQuery();

		asrt( trim( $sql ), 'person as teacher' );

		list( $sql, $params ) = R::$f->begin()->JOIN()->getQuery();

		asrt( trim( $sql ), 'join' );

		RedBean_SQLHelper::useCamelCase( FALSE );

		list( $sql, $params ) = R::$f->begin()->camelCase()->getQuery();

		asrt( trim( $sql ), 'camelCase' );
	}

	/**
	 * Test with plus query builder.
	 * 
	 * @return void
	 */
	public function testWithWithConditionQueryBuilder()
	{
		testpack( 'Test with- and withCondition with Query Builder' );

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );

		$page->num = 1;

		$book->ownPage[] = $page;

		$page = R::dispense( 'page' );

		$page->num = 2;

		$book->ownPage[] = $page;

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 2 );

		$book = R::load( 'book', $id );
		
		asrt( count( $book->withCondition( ' num >  1')->ownPage ), 1 );

		$book = R::load( 'book', $id );

		asrt( count( $book->withCondition( R::$f->begin()->num( ' < ?' )->put( 2 ) )->ownPage ), 1 );

		$book = R::load( 'book', $id );

		asrt( count( $book->with( R::$f->begin()->limit( ' 1 ' ) )->ownPage ), 1 );

		$book = R::load( 'book', $id );

		asrt( count( $book->withCondition( R::$f->begin()->num( ' < 3' ) )->ownPage ), 2 );

		$book = R::load( 'book', $id );

		asrt( count( $book->with( R::$f->begin()->limit( ' 2 ' ) )->ownPage ), 2 );

		testpack( 'Transaction suppr. in fluid mode' );

		R::freeze( FALSE );

		asrt( R::begin(), FALSE );
		asrt( R::commit(), FALSE );
		asrt( R::rollback(), FALSE );

		R::freeze( TRUE );

		asrt( R::begin(), TRUE );
		asrt( R::commit(), TRUE );

		R::freeze( FALSE );
	}

	/**
	 * Test Facade transactions.
	 * 
	 * @return void
	 * 
	 * @throws Exception
	 */
	public function testTransactionInFacade()
	{
		testpack( 'Test transaction in facade' );

		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		R::store( $bean );

		R::trash( $bean );

		R::freeze( TRUE );

		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		R::store( $bean );

		asrt( R::count( 'bean' ), 1 );

		R::trash( $bean );

		asrt( R::count( 'bean' ), 0 );

		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		$id = R::transaction( function() use( &$bean ) {
			return R::transaction( function() use( &$bean ) {
				return R::store( $bean );
			} );
		} );
		
		asrt( (int) $id, (int) $bean->id );
		
		R::trash( $bean );
		
		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		$id = R::transaction( function() use( &$bean ) {
			return R::store( $bean );
		} );
		
		asrt( (int) $id, (int) $bean->id );

		R::trash( $bean );
		
		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		
		try {
			R::transaction( function () use ( $bean ) {
				R::store( $bean );

				R::transaction( function () {
					throw new Exception();
				} );
			} );
		} catch ( Exception $e ) {
			pass();
		}
		asrt( R::count( 'bean' ), 0 );

		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		try {
			R::transaction( function () use ( $bean ) {
				R::transaction( function () use ( $bean ) {
					R::store( $bean );
					throw new Exception();
				} );
			} );
		} catch ( Exception $e ) {
			pass();
		}

		asrt( R::count( 'bean' ), 0 );

		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		try {
			R::transaction( function () use ( $bean ) {
				R::transaction( function () use ( $bean ) {
					R::store( $bean );
				} );
			} );
		} catch ( Exception $e ) {
			pass();
		}

		asrt( R::count( 'bean' ), 1 );

		R::freeze( FALSE );

		try {
			R::transaction( 'nope' );

			fail();
		} catch ( Exception $e ) {
			pass();
		}

		testpack( 'Test Camelcase 2 underscore' );

		$names = array(
			'oneACLRoute'              => 'one_acl_route',
			'ALLUPPERCASE'             => 'alluppercase',
			'clientServerArchitecture' => 'client_server_architecture',
			'camelCase'                => 'camel_case',
			'peer2peer'                => 'peer2peer',
			'fromUs4You'               => 'from_us4_you',
			'lowercase'                => 'lowercase',
			'a1A2b'                    => 'a1a2b',
		);

		$bean = R::dispense( 'bean' );

		foreach ( $names as $name => $becomes ) {
			$bean->$name = 1;

			asrt( isset( $bean->$becomes ), TRUE );
		}

		testpack( 'Test debugger check.' );

		$old = R::$adapter;

		R::$adapter = NULL;

		try {
			R::debug( TRUE );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		R::$adapter = $old;

		R::debug( FALSE );

		testpack( 'Misc Tests' );

		try {
			$candy = R::dispense( 'CandyBar' );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		$candy = R::dispense( 'candybar' );

		$s = strval( $candy );

		asrt( $s, 'candy!' );

		$obj = new stdClass;

		$bean = R::dispense( 'bean' );

		$bean->property1 = 'property1';

		$bean->exportToObj( $obj );

		asrt( $obj->property1, 'property1' );

		R::debug( 1 );

		flush();
		ob_start();

		R::exec( 'SELECT 123' );

		$out = ob_get_contents();

		ob_end_clean();
		flush();

		pass();

		asrt( ( strpos( $out, 'SELECT 123' ) !== FALSE ), TRUE );

		R::debug( 0 );

		flush();
		ob_start();

		R::exec( 'SELECT 123' );

		$out = ob_get_contents();
		ob_end_clean();

		flush();

		pass();

		asrt( $out, '' );

		R::debug( 0 );

		pass();

		testpack( 'test to string override' );

		$band = R::dispense( 'band' );

		$str = strval( $band );

		asrt( $str, 'bigband' );

		testpack( 'test whether we can use isset/set in model' );

		$band->setProperty( 'property1', 123 );

		asrt( $band->property1, 123 );

		asrt( $band->checkProperty( 'property1' ), TRUE );
		asrt( $band->checkProperty( 'property2' ), FALSE );

		$band = new Model_Band;

		$bean = R::dispense( 'band' );

		$bean->property3 = 123;

		$band->loadBean( $bean );

		$bean->property4 = 345;

		$band->setProperty( 'property1', 123 );

		asrt( $band->property1, 123 );

		asrt( $band->checkProperty( 'property1' ), TRUE );
		asrt( $band->checkProperty( 'property2' ), FALSE );

		asrt( $band->property3, 123 );
		asrt( $band->property4, 345 );

		testpack( 'Test blackhole DSN and setup()' );

		R::setup( 'blackhole:database' );

		pass();

		asrt( isset( R::$toolboxes['default'] ), TRUE );

		try {
			( R::$toolboxes['default']->getDatabaseAdapter()->getDatabase()->connect() );

			fail();
		} catch ( PDOException $e ) {
			pass();

			/**
			 * Make sure the message is non-descriptive - avoid revealing
			 * security details if user hasn't configured error reporting improperly.
			 */
			asrt( $e->getMessage(), 'Could not connect to database (?).' );
		}

		R::setup( 'blackhole:dbname=mydatabase;password=dontshowthisone' );

		pass();

		asrt( isset( R::$toolboxes['default'] ), TRUE );
		try {
			( R::$toolboxes['default']->getDatabaseAdapter()->getDatabase()->connect() );

			fail();
		} catch ( PDOException $e ) {
			pass();

			/**
			 * Make sure the message is non-descriptive - avoid revealing
			 * security details if user hasn't configured error reporting improperly.
			 */
			asrt( $e->getMessage(), 'Could not connect to database (mydatabase).' );
		}

		testpack( 'Can we pass a PDO object to Setup?' );

		$pdo = new PDO( 'sqlite:test.db' );

		$toolbox = RedBean_Setup::kickstart( $pdo );

		asrt( ( $toolbox instanceof RedBean_ToolBox ), TRUE );

		asrt( ( $toolbox->getDatabaseAdapter() instanceof RedBean_Adapter ), TRUE );
		asrt( ( $toolbox->getDatabaseAdapter()->getDatabase()->getPDO() instanceof PDO ), TRUE );

		testpack( 'Test array interface of beans' );

		$bean = R::dispense( 'bean' );

		$bean->hello = 'hi';
		$bean->world = 'planet';

		asrt( $bean['hello'], 'hi' );

		asrt( isset( $bean['hello'] ), TRUE );
		asrt( isset( $bean['bye'] ), FALSE );

		$bean['world'] = 'sphere';

		asrt( $bean->world, 'sphere' );

		foreach ( $bean as $key => $el ) {
			if ( $el == 'sphere' || $el == 'hi' || $el == 0 ) {
				pass();
			} else {
				fail();
			}

			if ( $key == 'hello' || $key == 'world' || $key == 'id' ) {
				pass();
			} else {
				fail();
			}
		}

		asrt( count( $bean ), 3 );

		unset( $bean['hello'] );

		asrt( count( $bean ), 2 );

		asrt( count( R::dispense( 'countable' ) ), 1 );

		// Otherwise untestable...
		$bean->setBeanHelper( new RedBean_BeanHelper_Facade() );

		R::$redbean->setBeanHelper( new RedBean_BeanHelper_Facade() );

		pass();

		// Test whether properties like owner and shareditem are still possible
		testpack( 'Test Bean Interface for Lists' );

		$bean = R::dispense( 'bean' );

		// Must not be list, because first char after own is lowercase
		asrt( is_array( $bean->owner ), FALSE );

		// Must not be list, because first char after shared is lowercase
		asrt( is_array( $bean->shareditem ), FALSE );

		asrt( is_array( $bean->own ), FALSE );
		asrt( is_array( $bean->shared ), FALSE );

		asrt( is_array( $bean->own_item ), FALSE );
		asrt( is_array( $bean->shared_item ), FALSE );

		asrt( is_array( $bean->{'own item'} ), FALSE );
		asrt( is_array( $bean->{'shared Item'} ), FALSE );
	}
}

/**
 * Custom Logger class.
 * For testing purposes.
 */
class CustomLogger extends RedBean_Logger_Default
{

	private $log;

	public function getLogMessage()
	{
		return $this->log;
	}

	public function log()
	{
		$this->log = func_get_args();
	}
}
