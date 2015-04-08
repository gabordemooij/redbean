<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Driver\RPDO as RPDO;
use RedBeanPHP\Logger\RDefault as RDefault;
use RedBeanPHP\RedException\Base as RedException;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;
use RedBeanPHP\QueryWriter\Base as QueryWriter;

/**
 * Misc
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

class Misc extends Blackhole
{
	/*
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'sqlite' );
	}

	/**
	 * Tests max parameter binding.
	 *
	 * @return void
	 */
	public function testIntegerBindingMax()
	{
		if ( defined( 'HHVM_VERSION' ) ) return; //not for hhvm...
		$driver = new RPDO( 'test-sqlite-53', 'user', 'pass' );
		$max = $driver->getIntegerBindingMax();
		asrt( $max, 2147483647 );
		$driver = new RPDO( 'cubrid', 'user', 'pass' );
		$max = $driver->getIntegerBindingMax();
		asrt( $max, 2147483647 );
		$driver = new RPDO( 'other', 'user', 'pass' );
		$max = $driver->getIntegerBindingMax();
		asrt( $max, PHP_INT_MAX );
	}

	/**
	* Should not be able to pass invalid mode (must be 0 or 1).
	*
	*/
	public function testInvalidDebugModeException()
	{
		try {
			R::debug( TRUE, 6 );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		R::debug( FALSE );
	}

	/**
	 * Adding a database twice no longer allowed, causes confusion
	 * and possible damage.
	 */
	public function testAddingTwice()
	{
		testpack( 'Test adding DB twice.' );

		try {
			R::addDatabase( 'sqlite', '' );
			fail();
		} catch ( RedException $ex ) {
			pass();
		}
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
		$bean = new OODBBean;
		$bean->getID();
		restore_error_handler();
		pass();
	}

	/**
	 * Tests setProperty.
	 *
	 * @return void
	 */
	public function testSetProperty()
	{
		$bean = R::dispense( 'bean' );
		$bean->item = 2;
		$bean->ownBean = R::dispense( 'bean', 2 );
		R::store( $bean );
		$bean = $bean->fresh();
		$bean->ownBean;

		$bean->setProperty( 'ownBean', array(), FALSE, FALSE );
		asrt( count( $bean->ownBean ), 0 );
		asrt( count( $bean->getMeta( 'sys.shadow.ownBean' ) ), 2 );
		asrt( $bean->isTainted(), TRUE );

		$bean->setProperty( 'ownBean', array(), TRUE, FALSE );
		asrt( count( $bean->ownBean ), 0 );
		asrt( count( $bean->getMeta( 'sys.shadow.ownBean' ) ), 0 );
		asrt( $bean->isTainted(), TRUE );

		$bean = $bean->fresh();
		$bean->setProperty( 'ownBean', array(), TRUE, FALSE );
		asrt( count( $bean->ownBean ), 0 );
		asrt( count( $bean->getMeta( 'sys.shadow.ownBean' ) ), 0 );
		asrt( $bean->isTainted(), FALSE );

		$bean = $bean->fresh();
		$bean->setProperty( 'ownBean', array(), TRUE, TRUE );
		asrt( count( $bean->ownBean ), 0 );
		asrt( count( $bean->getMeta( 'sys.shadow.ownBean' ) ), 0 );
		asrt( $bean->isTainted(), TRUE );
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

		$pdoDriver = new RPDO( R::getDatabaseAdapter()->getDatabase()->getPDO() );

		$customLogger = new CustomLogger;

		$pdoDriver->setDebugMode( TRUE, $customLogger );

		$pdoDriver->Execute( 'SELECT 123' );

		asrt( count( $customLogger->getLogMessage() ), 1 );

		$pdoDriver->setDebugMode( TRUE, NULL );
		asrt( ( $pdoDriver->getLogger() instanceof RDefault ), TRUE );

		testpack( 'Test bean->getProperties method' );

		$bean = R::dispense( 'bean' );

		$bean->property = 'hello';

		$props = $bean->getProperties();

		asrt( isset( $props['property'] ), TRUE );

		asrt( $props['property'], 'hello' );

	}

	/**
	 * Test Facade transactions.
	 *
	 * @return void
	 *
	 * @throws\Exception
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
					throw new\Exception();
				} );
			} );
		} catch (\Exception $e ) {
			pass();
		}
		asrt( R::count( 'bean' ), 0 );

		$bean = R::dispense( 'bean' );

		$bean->name = 'a';

		try {
			R::transaction( function () use ( $bean ) {
				R::transaction( function () use ( $bean ) {
					R::store( $bean );
					throw new\Exception();
				} );
			} );
		} catch (\Exception $e ) {
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
		} catch (\Exception $e ) {
			pass();
		}

		asrt( R::count( 'bean' ), 1 );

		R::freeze( FALSE );

		try {
			R::transaction( 'nope' );

			fail();
		} catch (\Exception $e ) {
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

		testpack( 'Misc Tests' );


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

		$band = new \Model_Band;

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

		testpack( 'Can we pass a\PDO object to Setup?' );

		$pdo = new\PDO( 'sqlite:test.db' );

		R::addDatabase( 'pdo', $pdo );
		R::selectDatabase( 'pdo' );

		R::getCell('SELECT 123;');

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
		$bean->setBeanHelper( new SimpleFacadeBeanHelper() );

		R::getRedBean()->setBeanHelper( new SimpleFacadeBeanHelper() );

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

	public function testConv2Beans()
	{
		$row1 = array('id' => 1, 'title'=>'test');
		$row2 = array('id' => 2, 'title'=>'test2');
		$beans = R::convertToBeans('page', array($row1, $row2));
		asrt(count($beans), 2);
		asrt($beans[2]->title, 'test2');
	}

	/**
	* Test the most important invalid bean combinations.
	*
	* @return void
	*/
	public function testInvalidType()
	{
		$invalid = array(
			'book_page', //no link beans
			'a_b_c', //no prefix
			'a b', //no space
			'bean@', //no invalid symbols
			'bean#', //no invalid symbols
			'bean$', //sometimes used in DB, not allowed
			'__bean',//no prefixes
			'.bean', //no object notation
			'bean-item', //no dash
			'beanOther'); //no camelcase (uppercase because of file system issues)

		foreach( $invalid as $j ) {
			try {
				R::dispense( $j );
				fail();
			} catch( RedException $e ) {
				pass();
			}
		}
	}

	/**
	* Test whether batch still works if no IDs have been passed.
	*
	* @return void
	*/
	public function testBatch0()
	{
		$zero = R::batch( 'page', array() );
		asrt( is_array( $zero ), TRUE );
		asrt( count( $zero ), 0 );
		$zero = R::batch( 'page', FALSE );
		asrt( is_array( $zero ), TRUE );
		asrt( count( $zero ), 0 );
		$zero = R::batch( 'page', NULL);
		asrt( is_array( $zero ), TRUE );
		asrt( count( $zero ), 0 );
	}

	/**
	* Test whether connection failure does not reveal
	* credentials.
	*
	* @return void
	*/
	public function testConnect()
	{
		$driver = new RPDO( 'dsn:invalid', 'usr', 'psst' );
		try {
			$driver->connect();
			fail();
		}
		catch( \PDOException $e ) {
			asrt( strpos( $e->getMessage(), 'invalid' ), FALSE );
			asrt( strpos( $e->getMessage(), 'usr' ), FALSE );
			asrt( strpos( $e->getMessage(), 'psst' ), FALSE );
		}
	}

	/**
	* Test whether we can create an instant database using
	* R::setup().
	*
	* Probably only works on *NIX systems.
	*
	* @return void
	*/
	public function testSetup()
	{
		$tmpDir = sys_get_temp_dir();
		R::setup();
	}

	/**
	 * Test camelCase to snake_case conversions.
	 *
	 * @return void
	 */
	public function testCamel2Snake()
	{
		asrt( QueryWriter::camelsSnake('bookPage'), 'book_page' );
		asrt( QueryWriter::camelsSnake('FTP'), 'ftp' );
		asrt( QueryWriter::camelsSnake('ACLRules'), 'acl_rules' );
		asrt( QueryWriter::camelsSnake('SSHConnectionProxy'), 'ssh_connection_proxy' );
		asrt( QueryWriter::camelsSnake('proxyServerFacade'), 'proxy_server_facade' );
		asrt( QueryWriter::camelsSnake('proxySSHClient'), 'proxy_ssh_client' );
		asrt( QueryWriter::camelsSnake('objectACL2Factory'), 'object_acl2_factory' );
		asrt( QueryWriter::camelsSnake('bookItems4Page'), 'book_items4_page' );
		asrt( QueryWriter::camelsSnake('book☀Items4Page'), 'book☀_items4_page' );
	}
}

/**
 * Custom Logger class.
 * For testing purposes.
 */
class CustomLogger extends RDefault
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
