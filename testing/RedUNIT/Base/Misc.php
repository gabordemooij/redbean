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

/**
 * RedUNIT_Base_Misc
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
	* Tests the R::inspect() method on the Facade.
	*
	* @return void
	*/	
	public function testInspect() {
	
		testpack( 'Test R::inspect() ');

		R::nuke();
		
		R::store( R::dispense('book')->setAttr('title', 'book') );
		
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

		R::freeze(FALSE);
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
			asrt( $adapter->getDatabase()->getPDO() instanceof\PDO, TRUE );
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

		R::begin();

		$bean = R::dispense( 'bean' );

		R::store( $bean );
		R::commit();

		asrt( R::count( 'bean' ), 1 );

		R::wipe( 'bean' );
		R::freeze( 1 );
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
		asrt( R::enum( 'country:South-Africa' )->name, 'SOUTH_AFRICA');
		asrt( R::enum( 'tester:T@E  S_t' )->name, 'T_E_S_T');
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
		asrt( implode( '', R::gatherLabels(R::enum('flavour'))), 'MOCCA' );
		
		R::store( $coffee );
		
		$coffee = $coffee->fresh();
		
		//test enum identity check - with alias
		asrt( $coffee->fetchAs('flavour')->taste->equals( R::enum('flavour:mocca') ), TRUE );
		asrt( $coffee->fetchAs('flavour')->taste->equals( R::enum('flavour:banana') ), FALSE );
		
		//now we have two flavours
		asrt( R::count('flavour'), 2 );
		asrt( implode( ',', R::gatherLabels(R::enum('flavour'))), 'BANANA,MOCCA' );
		
		$coffee->flavour = R::enum( 'flavour:mocca' );
		
		R::store($coffee);
		
		//same results, can we have multiple flavours?
		asrt( $coffee->fetchAs('flavour')->taste->equals( R::enum('flavour:mocca') ), TRUE );
		asrt( $coffee->fetchAs('flavour')->taste->equals( R::enum('flavour:banana') ), FALSE );
		asrt( $coffee->flavour->equals( R::enum('flavour:mocca') ), TRUE );
		
		//no additional mocca enum...
		asrt( R::count('flavour'), 2 );
		
		$drink = R::dispense( 'drink' );
		$drink->flavour = R::enum( 'flavour:choco' );
		R::store( $drink );
		
		//now we have three!
		asrt( R::count('flavour'), 3 );
		
		$drink = R::load( 'drink', $drink->id );
		
		asrt( $drink->flavour->equals( R::enum('flavour:mint') ), FALSE );
		asrt( $drink->flavour->equals( R::enum('flavour:choco') ), TRUE );
		
		asrt( R::count('flavour'), 4 );
		
		//trash should not affect flavour!
		R::trash( $drink );
		
		asrt( R::count('flavour'), 4 );
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
}



