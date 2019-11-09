<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\Logger\RDefault as RDefault;
use RedBeanPHP\Logger as Logger;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\Driver\RPDO as RPDO;
use RedBeanPHP\SimpleModel as SimpleModel;

/**
 * Misc
 *
 * @file    RedUNIT/Base/Misc.php
 * @desc    Various tests.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Misc extends Base
{
	/**
	 * Github issue:
	 * Remove $NULL to directly return NULL #625
	 * @@ -1097,8 +1097,7 @@ public function &__get( $property )
	 *		$this->all        = FALSE;
	 *		$this->via        = NULL;
	 *
	 * - $NULL = NULL;
	 * - return $NULL;
	 * + return NULL;
	 *
	 * leads to regression:
	 * PHP Stack trace:
	 * PHP 1. {main}() testje.php:0
	 * PHP 2. RedBeanPHP\OODBBean->__get() testje.php:22
	 * Notice: Only variable references should be returned by reference in rb.php on line 2529
	 */
	public function testReferencedGetInBeans()
	{
		$bean = R::dispense( 'bean' );
		//this will trigger notice if &__get() returns NULL instead of $NULL.#625
		$x = $bean->hello;
		pass();
		$x = $bean->reference;
		pass();
		$x = $bean->nullvalue;
		pass();
	}


	public static $setupPartialBeansTestDone = 0;
	/**
	 * Check partial beans at setup()
	 */
	 public function testPartialBeansAtSetup()
	 {
		 if (self::$setupPartialBeansTestDone) return; /* only needs to be tested once */
		 $currentDB = R::$currentDB;
		 $key  = 'partialBeanBase' . time();
		 $dsn  = 'sqlite:/tmp/test.txt';
		 $user = '';
		 $pass = '';
		 $frozen = FALSE;
		 $partialBeans = TRUE;
		 R::addDatabase( $key, $dsn, $user, $pass, $frozen, $partialBeans);
		 $redbean = R::getRedBean();
		 $wasItSet = $redbean->getCurrentRepository()->usePartialBeans( FALSE );
		 R::selectDatabase( $key );
		 $redbean = R::getRedBean();
		 $wasItSet = $redbean->getCurrentRepository()->usePartialBeans( FALSE );
		 asrt( $wasItSet, TRUE );
		 self::$setupPartialBeansTestDone = 1;
		 R::selectDatabase( $currentDB );
	 }

	/**
	 * Test whether we can set the 'auto clear'
	 * option in OODB.
	 *
	 * @return void
	 */
	public function testAutoClearHistory()
	{
		testpack( 'Auto clear history' );
		$book = R::dispense( 'book' );
		$book->pages = 100;
		$book->title = 'book';
		R::store( $book );
		$book = R::findOne( 'book' );
		asrt( $book->hasChanged( 'title' ), FALSE );
		$book->title = 'yes';
		R::store( $book );
		asrt( $book->hasChanged( 'title' ), TRUE );
		OODB::autoClearHistoryAfterStore( TRUE );
		$book = R::findOne( 'book' );
		asrt( $book->hasChanged( 'title' ), FALSE );
		$book->title = 'yes2';
		R::store( $book );
		asrt( $book->hasChanged( 'title' ), FALSE );
		OODB::autoClearHistoryAfterStore( FALSE );
		$book = R::findOne( 'book' );
		asrt( $book->hasChanged( 'title' ), FALSE );
		$book->title = 'yes';
		R::store( $book );
		asrt( $book->hasChanged( 'title' ), TRUE );
	}

	/**
	* Tests the R::inspect() method on the Facade.
	*
	* @return void
	*/
	public function testInspect() {

		testpack( 'Test R::inspect() ' );
		R::nuke();
		R::store( R::dispense( 'book' )->setAttr( 'title', 'book' ) );
		$info = R::inspect();
		asrt( count( $info ), 1 );
		asrt( strtolower( $info[0] ), 'book' );
		$info = R::inspect( 'book' );
		asrt( count( $info ), 2 );
		$keys = array_keys( $info );
		sort($keys);
		asrt( strtolower( $keys[0] ), 'id' );
		asrt( strtolower( $keys[1] ), 'title' );
	}

	/**
	 * Test whether we can use the tableExist() method in OODB
	 * instances directly to help us determine
	 * the existance of a table.
	 *
	 * @return void
	 */
	public function testTableExist()
	{
		R::nuke();
		R::store( R::dispense( 'book' ) );
		R::freeze( FALSE );
		asrt( R::getRedBean()->tableExists( 'book' ), TRUE );
		asrt( R::getRedBean()->tableExists( 'book2' ), FALSE );
		R::freeze( TRUE );
		asrt( R::getRedBean()->tableExists( 'book' ), TRUE );
		asrt( R::getRedBean()->tableExists( 'book2' ), FALSE );
		R::freeze( FALSE );
	}

	/**
	 * Normally the check() method is always called indirectly when
	 * dealing with beans. This test ensures we can call check()
	 * directly. Even though frozen repositories do not rely on
	 * bean checking to improve performance the method should still
	 * offer the same functionality when called directly.
	 *
	 * @return void
	 */
	public function testCheckDirectly()
	{
		$bean = new OODBBean;
		$bean->setProperty('id', 0);
		$bean->setMeta( 'type', 'book' );
		R::getRedBean()->check( $bean );
		$bean->setMeta( 'type', '.' );
		try {
			R::getRedBean()->check( $bean );
			fail();
		} catch ( \Exception $e ) {
			pass();
		}
		//check should remain the same even if frozen repo is used, method is public after all!
		//we dont want to break the API!
		R::freeze( TRUE );
		try {
			R::getRedBean()->check( $bean );
			fail();
		} catch ( \Exception $e ) {
			pass();
		}
		R::freeze( FALSE );
	}

	/**
	 * Test Backward compatibility writer ESC-method.
	 *
	 * @return void
	 */
	public function testLegacyCode()
	{
		testpack( 'Test Backward compatibility methods in writer.' );
		asrt( R::getWriter()->safeColumn( 'column', TRUE ), R::getWriter()->esc( 'column', TRUE ) );
		asrt( R::getWriter()->safeColumn( 'column', FALSE ), R::getWriter()->esc( 'column', FALSE ) );
		asrt( R::getWriter()->safeTable( 'table', TRUE ), R::getWriter()->esc( 'table', TRUE ) );
		asrt( R::getWriter()->safeTable( 'table', FALSE ), R::getWriter()->esc( 'table', FALSE ) );
	}

	/**
	 * Test beautification and array functions.
	 *
	 * @return void
	 */
	public function testBeauficationAndArrayFunctions()
	{
		$bean = R::dispense( 'bean' );
		$bean->isReallyAwesome = TRUE;
		asrt( isset( $bean->isReallyAwesome ), TRUE );
		asrt( isset( $bean->is_really_awesome ), TRUE );
		unset( $bean->is_really_awesome );
		asrt( isset( $bean->isReallyAwesome ), FALSE );
		asrt( isset( $bean->is_really_awesome ), FALSE );
	}

	/**
	 * Test beautification of column names.
	 *
	 * @return void
	 */
	public function testBeautifulColumnNames()
	{
		testpack( 'Beautiful column names' );
		$town = R::dispense( 'town' );
		$town->isCapital       = FALSE;
		$town->hasTrainStation = TRUE;
		$town->name            = 'BeautyVille';
		$houses = R::dispense( 'house', 2 );
		$houses[0]->isForSale = TRUE;
		$town->ownHouse = $houses;
		R::store( $town );
		$town = R::load( 'town', $town->id );
		asrt( ( $town->isCapital == FALSE ), TRUE );
		asrt( ( $town->hasTrainStation == TRUE ), TRUE );
		asrt( ( $town->name == 'BeautyVille' ), TRUE );
		testpack( 'Accept datetime objects.' );
		$cal = R::dispense( 'calendar' );
		$cal->when = new\DateTime( '2000-01-01', new\DateTimeZone( 'Pacific/Nauru' ) );
		asrt( $cal->when, '2000-01-01 00:00:00' );
		testpack( 'Affected rows test' );
		$currentDriver = $this->currentlyActiveDriverID;
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		$bean = $redbean->dispense( 'bean' );
		$bean->prop = 3; //make test run with strict mode as well
		$redbean->store( $bean );
		$adapter->exec( 'UPDATE bean SET prop = 2' );
		asrt( $adapter->getAffectedRows(), 1 );
		testpack( 'Testing Logger' );
		R::getDatabaseAdapter()->getDatabase()->setLogger( new RDefault );
		asrt( ( R::getDatabaseAdapter()->getDatabase()->getLogger() instanceof Logger ), TRUE );
		asrt( ( R::getDatabaseAdapter()->getDatabase()->getLogger() instanceof RDefault ), TRUE );
		$bean = R::dispense( 'bean' );
		$bean->property = 1;
		$bean->unsetAll( array( 'property' ) );
		asrt( $bean->property, NULL );
		asrt( ( $bean->setAttr( 'property', 2 ) instanceof OODBBean ), TRUE );
		asrt( $bean->property, 2 );
		asrt( preg_match( '/\d\d\d\d\-\d\d\-\d\d/', R::isoDate() ), 1 );
		asrt( preg_match( '/\d\d\d\d\-\d\d\-\d\d\s\d\d:\d\d:\d\d/', R::isoDateTime() ), 1 );
		$redbean = R::getRedBean();
		$adapter = R::getDatabaseAdapter();
		$writer  = R::getWriter();
		asrt( ( $redbean instanceof OODB ), TRUE );
		asrt( ( $adapter instanceof Adapter ), TRUE );
		asrt( ( $writer instanceof QueryWriter ), TRUE );
		R::setRedBean( $redbean );
		pass(); //cant really test this
		R::setDatabaseAdapter( $adapter );
		pass(); //cant really test this
		R::setWriter( $writer );
		pass(); //cant really test this
		$u1 = R::dispense( 'user' );
		$u1->name  = 'Gabor';
		$u1->login = 'g';
		$u2 = R::dispense( 'user' );
		$u2->name  = 'Eric';
		$u2->login = 'e';
		R::store( $u1 );
		R::store( $u2 );
		$list = R::getAssoc( 'select login,' . R::getWriter()->esc( 'name' ) . ' from ' . R::getWriter()->esc( 'user' ) . ' ' );
		asrt( $list['e'], 'Eric' );
		asrt( $list['g'], 'Gabor' );
		$painting = R::dispense( 'painting' );
		$painting->name = 'Nighthawks';
		$id = R::store( $painting );
		testpack( 'Testing SQL Error Types' );
		foreach ( $writer->typeno_sqltype as $code => $text ) {
			asrt( is_integer( $code ), TRUE );
			asrt( is_string( $text ), TRUE );
		}
		foreach ( $writer->sqltype_typeno as $text => $code ) {
			asrt( is_integer( $code ), TRUE );
			asrt( is_string( $text ), TRUE );
		}
		testpack( 'Testing Nowhere Pt. 1 (unfrozen)' );
		foreach (
			array(
				'exec', 'getAll', 'getCell', 'getAssoc', 'getRow', 'getCol'
			)
			as $method ) {
			R::$method( 'select * from nowhere' );
			pass();
		}
		testpack( 'Testing Nowhere Pt. 2 (frozen)' );
		R::freeze( TRUE );
		foreach (
			array(
				'exec', 'getAll', 'getCell', 'getAssoc', 'getRow', 'getCol'
			)
			as $method ) {
			try {
				R::$method( 'select * from nowhere' );
				fail();
			} catch ( SQL $e ) {
				pass();
			}
		}
		R::freeze( FALSE );
	}

	/**
	 * Test reflectional functions of database.
	 *
	 * @return void
	 */
	public function testDatabaseProperties()
	{
		testpack( 'Testing Database Properties' );
		$adapter = R::getDatabaseAdapter();
		if ( method_exists( R::getDatabaseAdapter()->getDatabase(), 'getPDO' ) ){
			asrt( $adapter->getDatabase()->getPDO() instanceof \PDO, TRUE );
		}
		asrt( strlen( $adapter->getDatabase()->getDatabaseVersion() ) > 0, TRUE );
		asrt( strlen( $adapter->getDatabase()->getDatabaseType() ) > 0, TRUE );
	}

	/**
	 * Test Transactions.
	 *
	 * @return void
	 */
	public function testTransactions()
	{
		testpack( 'transactions' );
		$false = R::begin();
		asrt( $false, FALSE );
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		R::commit();
		asrt( R::count( 'bean' ), 1 );
		R::trash( $bean );
		R::setAllowFluidTransactions( TRUE );
		asrt( R::begin(), TRUE );
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		asrt( R::commit(), TRUE );
		asrt( R::count( 'bean' ), 1 );
		R::trash( $bean );
		asrt( R::begin(), TRUE );
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		R::rollback();
		asrt( R::count( 'bean' ), 0 );
		R::setAllowFluidTransactions( FALSE );
		R::wipe('bean');
		R::freeze( TRUE );
		R::begin();
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		R::rollback();
		asrt( R::count( 'bean' ), 0 );
		R::freeze( FALSE );
		testpack( 'genSlots' );
		asrt( R::genSlots( array( 'a', 'b' ) ), '?,?' );
		asrt( R::genSlots( array( 'a' ) ), '?' );
		asrt( R::genSlots( array() ), '' );
		asrt( R::genSlots( array('a', 'b'), ' IN( %s ) ' ), ' IN( ?,? ) ' );
	}

	/**
	 * Test nested FUSE scenarios.
	 *
	 * @return void
	 */
	public function testFUSEnested()
	{
		testpack( 'FUSE models cant touch nested beans in update() - issue 106' );
		$spoon       = R::dispense( 'spoon' );
		$spoon->name = 'spoon for test bean';
		$deep        = R::dispense( 'deep' );
		$deep->name  = 'deepbean';
		$item        = R::dispense( 'item' );
		$item->val   = 'Test';
		$item->deep  = $deep;
		$test = R::dispense( 'test' );
		$test->item          = $item;
		$test->sharedSpoon[] = $spoon;
		$test->isnowtainted = TRUE;
		$id   = R::store( $test );
		$test = R::load( 'test', $id );
		asrt( $test->item->val, 'Test2' );
		$can   = reset( $test->ownCan );
		$spoon = reset( $test->sharedSpoon );
		asrt( $can->name, 'can for bean' );
		asrt( $spoon->name, 'S2' );
		asrt( $test->item->deep->name, '123' );
		asrt( count( $test->ownCan ), 1 );
		asrt( count( $test->sharedSpoon ), 1 );
		asrt( count( $test->sharedPeas ), 10 );
		asrt( count( $test->ownChip ), 9 );
	}

	/**
	 * Tests FUSE and lists, FUSE enforces no more than
	 * 3 sugar cubes in coffee.
	 *
	 * @return void
	 */
	public function testCoffeeWithSugarAndFUSE()
	{
		$coffee = R::dispense( 'coffee' );
		$coffee->size     = 'XL';
		$coffee->ownSugar = R::dispense( 'sugar', 5 );
		$id = R::store( $coffee );
		$coffee = R::load( 'coffee', $id );
		asrt( count( $coffee->ownSugar ), 3 );
		$coffee->ownSugar = R::dispense( 'sugar', 2 );
		$id     = R::store( $coffee );
		$coffee = R::load( 'coffee', $id );
		asrt( count( $coffee->ownSugar ), 2 );
		$cocoa = R::dispense( 'cocoa' );
		$cocoa->name = 'Fair Cocoa';
		list( $taste1, $taste2 ) = R::dispense( 'taste', 2 );
		$taste1->name = 'sweet';
		$taste2->name = 'bitter';
		$cocoa->ownTaste = array( $taste1, $taste2 );
		R::store( $cocoa );
		$cocoa->name = 'Koko';
		R::store( $cocoa );
		if ( method_exists( R::getDatabaseAdapter()->getDatabase(), 'getPDO' ) ) {
			$pdo    = R::getDatabaseAdapter()->getDatabase()->getPDO();
			$driver = new RPDO( $pdo );
			pass();
			asrt( $pdo->getAttribute(\PDO::ATTR_ERRMODE ),\PDO::ERRMODE_EXCEPTION );
			asrt( $pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE ),\PDO::FETCH_ASSOC );
			asrt( strval( $driver->GetCell( 'select 123' ) ), '123' );
		}
		$a = new SQL;
		$a->setSqlState( 'test' );
		$b = strval( $a );
		asrt( ( strpos( $b, '[test] - ' ) === 0 ), TRUE );
	}

	/**
	* ENUM Basic tests.
	*
	* @return void
	*/
	public function testENUMBasics() {
		asrt( R::enum( 'gender:male' )->name, 'MALE' );
		asrt( R::enum( 'country:South-Africa' )->name, 'SOUTH_AFRICA' );
		asrt( R::enum( 'tester:T@E  S_t' )->name, 'T_E_S_T' );
	}

	/**
	 * Test ENUM in Queries and with short hand notation.
	 *
	 * @return void
	 */
	public function testENUMInQuery()
	{
		testpack('Test ENUM in Query and test ENUM short notation');
		R::nuke();
		$coffee = R::dispense( 'coffee' );
		$coffee->taste = R::enum( 'flavour:mocca' );
		R::store( $coffee );
		$coffee = R::dispense( 'coffee' );
		$coffee->taste = R::enum( 'flavour:banana' );
		R::store( $coffee );
		$coffee = R::dispense( 'coffee' );
		$coffee->taste = R::enum( 'flavour:banana' );
		R::store( $coffee );
		//now we have two flavours
		asrt( R::count('flavour'), 2 );
		//use in query
		asrt( R::count( 'coffee', ' taste_id = ? ', array( R::enum( 'flavour:mocca' )->id ) ), 1);
		//use in quer with short notation
		asrt( R::count( 'coffee', ' taste_id = ? ', array( EID( 'flavour:mocca' ) ) ), 1);
		//use in query
		asrt( R::count( 'coffee', ' taste_id = ? ', array( R::enum( 'flavour:banana' )->id ) ), 2);
		//use in quer with short notation
		asrt( R::count( 'coffee', ' taste_id = ? ', array( EID( 'flavour:banana' ) ) ), 2);
		//use in query
		asrt( R::count( 'coffee', ' taste_id = ? ', array( R::enum( 'flavour:strawberry' )->id ) ), 0);
		//use in quer with short notation
		asrt( R::count( 'coffee', ' taste_id = ? ', array( EID( 'flavour:strawberry' ) ) ), 0);
	}

	/**
	 * Test ENUM functionality offered by Label Maker.
	 *
	 * @return void
	 */
	public function testENUM() {
		testpack('test ENUM');
		$coffee = R::dispense( 'coffee' );
		$coffee->taste = R::enum( 'flavour:mocca' );
		//did we create an enum?
		asrt( implode( '', R::gatherLabels( R::enum( 'flavour' ) ) ), 'MOCCA' );
		R::store( $coffee );
		$coffee = $coffee->fresh();
		//test enum identity check - with alias
		asrt( $coffee->fetchAs( 'flavour' )->taste->equals( R::enum('flavour:mocca') ), TRUE );
		asrt( $coffee->fetchAs( 'flavour' )->taste->equals( R::enum('flavour:banana') ), FALSE );
		//now we have two flavours
		asrt( R::count( 'flavour' ), 2 );
		asrt( implode( ',', R::gatherLabels( R::enum( 'flavour') ) ), 'BANANA,MOCCA' );
		$coffee->flavour = R::enum( 'flavour:mocca' );
		R::store($coffee);
		//same results, can we have multiple flavours?
		asrt( $coffee->fetchAs( 'flavour' )->taste->equals( R::enum( 'flavour:mocca' ) ), TRUE );
		asrt( $coffee->fetchAs( 'flavour' )->taste->equals( R::enum( 'flavour:banana' ) ), FALSE );
		asrt( $coffee->flavour->equals( R::enum( 'flavour:mocca' ) ), TRUE );
		//no additional mocca enum...
		asrt( R::count( 'flavour' ), 2 );
		$drink = R::dispense( 'drink' );
		$drink->flavour = R::enum( 'flavour:choco' );
		R::store( $drink );
		//now we have three!
		asrt( R::count('flavour'), 3 );
		$drink = R::load( 'drink', $drink->id );
		asrt( $drink->flavour->equals( R::enum('flavour:mint') ), FALSE );
		asrt( $drink->flavour->equals( R::enum('flavour:choco') ), TRUE );
		asrt( R::count( 'flavour' ), 4 );
		//trash should not affect flavour!
		R::trash( $drink );
		asrt( R::count( 'flavour' ), 4 );
	}

	/**
	 * Test trashAll().
	 */
	public function testMultiDeleteUpdate()
	{
		testpack( 'test multi delete and multi update' );
		$beans = R::dispenseLabels( 'bean', array( 'a', 'b' ) );
		$ids   = R::storeAll( $beans );
		asrt( (int) R::count( 'bean' ), 2 );
		R::trashAll( R::batch( 'bean', $ids ) );
		asrt( (int) R::count( 'bean' ), 0 );
		testpack( 'test assocManager check' );
		$rb = new OODB( R::getWriter() );
		try {
			$rb->getAssociationManager();
			fail();
		} catch ( RedException $e ) {
			pass();
		}
	}

	/**
	 * Test Bean identity equality.
	 */
	public function testBeanIdentityEquality() {
		$beanA = R::dispense( 'bean' );
		$beanB = R::dispense( 'bean' );
		$beanA->id = 1;
		$beanB->id = 1;
		asrt( $beanA->equals( $beanB ), TRUE );
		asrt( $beanB->equals( $beanA ), TRUE );
		asrt( $beanA->equals( $beanA ), TRUE );
		asrt( $beanB->equals( $beanB ), TRUE );
		$beanB->id = 2;
		asrt( $beanA->equals( $beanB ), FALSE );
		asrt( $beanB->equals( $beanA ), FALSE );
		$beanA->id = '2';
		asrt( $beanA->equals( $beanB ), TRUE );
		asrt( $beanB->equals( $beanA ), TRUE );
		$beanB = R::dispense( 'carrot' );
		$beanB->id = $beanA->id;
		asrt( $beanA->equals( $beanB ), FALSE );
		asrt( $beanB->equals( $beanA ), FALSE );
	}
  
	/**
	 * Test if adding SimpleModles to a shared list will auto unbox them.
	 */
	public function testSharedListsAutoUnbox() {
		$boxedBean = R::dispense( 'boxedbean' );
		$bean = R::dispense( 'bean' );
		$model = new SimpleModel();
		$model->loadBean($boxedBean);
		$bean->ownBoxedbeanList[] = $model;
		try {
			R::store( $bean );
			pass();
		} catch ( \Exception $e ) {
			fail();
		}
	}
}
