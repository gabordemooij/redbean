<?php

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException\SQL as SQL;

/**
 * Parambind
 *
 * Tests the parameter binding functionality in RedBeanPHP.
 * These test scenarios include for instance: NULL handling,
 * binding parameters in LIMIT clauses and so on.
 *
 * @file    RedUNIT/Mysql/Parambind.php
 * @desc    Tests\PDO parameter binding.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Parambind extends Mysql
{
	/**
	 * Test parameter binding with\PDO.
	 *
	 * @return void
	 */
	public function testPDOParameterBinding()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		R::getDatabaseAdapter()->getDatabase()->setUseStringOnlyBinding( TRUE );
		try {
			R::getAll( "select * from job limit ? ", array( 1 ) );
			fail();
		} catch (\Exception $e ) {
			pass();
		}
		try {
			R::getAll( "select * from job limit :l ", array( ":l" => 1 ) );
			fail();
		} catch (\Exception $e ) {
			pass();
		}
		try {
			R::exec( "select * from job limit ? ", array( 1 ) );
			fail();
		} catch (\Exception $e ) {
			pass();
		}
		try {
			R::exec( "select * from job limit :l ", array( ":l" => 1 ) );
			fail();
		} catch (\Exception $e ) {
			pass();
		}
		R::getDatabaseAdapter()->getDatabase()->setUseStringOnlyBinding( FALSE );
		try {
			R::getAll( "select * from job limit ? ", array( 1 ) );
			pass();
		} catch (\Exception $e ) {
			fail();
		}
		try {
			R::getAll( "select * from job limit :l ", array( ":l" => 1 ) );
			pass();
		} catch (\Exception $e ) {
			fail();
		}
		try {
			R::exec( "select * from job limit ? ", array( 1 ) );
			pass();
		} catch (\Exception $e ) {
			fail();
		}
		try {
			R::exec( "select * from job limit :l ", array( ":l" => 1 ) );
			pass();
		} catch (\Exception $e ) {
			fail();
		}
		testpack( "Test findOrDispense" );
		$person = R::findOrDispense( "person", " job = ? ", array( "developer" ) );
		asrt( ( count( $person ) > 0 ), TRUE );
		$person = R::findOrDispense( "person", " job = ? ", array( "musician" ) );
		asrt( ( count( $person ) > 0 ), TRUE );
		$musician = array_pop( $person );
		asrt( intval( $musician->id ), 0 );
		try {
			$adapter->exec( "an invalid query" );
			fail();
		} catch ( SQL $e ) {
			pass();
		}
		asrt( (int) $adapter->getCell( "SELECT 123" ), 123 );
		asrt( (int) $adapter->getCell( "SELECT ?", array( "987" ) ), 987 );
		asrt( (int) $adapter->getCell( "SELECT ?+?", array( "987", "2" ) ), 989 );
		asrt( (int) $adapter->getCell( "SELECT :numberOne+:numberTwo", array(
			":numberOne" => 42, ":numberTwo" => 50 ) ), 92 );
		$pair = $adapter->getAssoc( "SELECT 'thekey','thevalue' " );
		asrt( is_array( $pair ), TRUE );
		asrt( count( $pair ), 1 );
		asrt( isset( $pair["thekey"] ), TRUE );
		asrt( $pair["thekey"], "thevalue" );
		testpack( 'Test whether we can properly bind and receive NULL values' );
		asrt( $adapter->getCell( 'SELECT :nil ', array( ':nil' => 'NULL' ) ), 'NULL' );
		asrt( $adapter->getCell( 'SELECT :nil ', array( ':nil' => NULL ) ), NULL );
		asrt( $adapter->getCell( 'SELECT ? ', array( 'NULL' ) ), 'NULL' );
		asrt( $adapter->getCell( 'SELECT ? ', array( NULL ) ), NULL );
	}
}
