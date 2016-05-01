<?php

namespace RedUNIT\Sqlite;

use RedUNIT\Sqlite as Sqlite;
use RedBeanPHP\Facade as R;

/**
 * Parambind
 *
 * Tests the parameter binding functionality in RedBeanPHP.
 * These test scenarios include for instance: NULL handling,
 * binding parameters in LIMIT clauses and so on.
 *
 * @file    RedUNIT/Sqlite/Parambind.php
 * @desc    Tests\PDO parameter binding.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Parambind extends Sqlite
{
	/**
	 * Test parameter binding with SQLite.
	 *
	 * @return void
	 */
	public function testParamBindWithSQLite()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		asrt( (int) $adapter->getCell( "SELECT 123" ), 123 );
		asrt( (int) $adapter->getCell( "SELECT ?", array( "987" ) ), 987 );
		asrt( (int) $adapter->getCell( "SELECT ?+?", array( "987", "2" ) ), 989 );
		asrt( (int) $adapter->getCell(
			"SELECT :numberOne+:numberTwo",
			array(
				":numberOne" => 42,
				":numberTwo" => 50 )
			),
			92
		);
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
