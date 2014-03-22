<?php 

namespace RedUNIT\Postgres;

use RedUNIT\Postgres as Postgres;
use RedBeanPHP\Facade as R;
 
/**
 * RedUNIT_Postgres_Parambind
 *
 * @file    RedUNIT/Postgres/Parambind.php
 * @desc    Tests\PDO parameter binding for Postgres.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Parambind extends Postgres
{
	/**
	 * Test parameter binding.
	 * 
	 * @return void
	 */
	public function testParamBindingWithPostgres()
	{
		testpack( "param binding pgsql" );

		$page = R::dispense( "page" );

		$page->name   = "abc";
		$page->number = 2;

		R::store( $page );

		R::exec( "insert into page (name) values(:name) ", array( ":name" => "my name" ) );
		R::exec( "insert into page (number) values(:one) ", array( ":one" => 1 ) );
		R::exec( "insert into page (number) values(:one) ", array( ":one" => "1" ) );
		R::exec( "insert into page (number) values(:one) ", array( ":one" => "1234" ) );
		R::exec( "insert into page (number) values(:one) ", array( ":one" => "-21" ) );

		pass();

		testpack( 'Test whether we can properly bind and receive NULL values' );

		$adapter = R::getDatabaseAdapter();

		asrt( $adapter->getCell( 'SELECT TEXT( :nil ) ', array( ':nil' => 'NULL' ) ), 'NULL' );
		asrt( $adapter->getCell( 'SELECT TEXT( :nil ) ', array( ':nil' => NULL ) ), NULL );
		asrt( $adapter->getCell( 'SELECT TEXT( ? ) ', array( 'NULL' ) ), 'NULL' );
		asrt( $adapter->getCell( 'SELECT TEXT( ? ) ', array( NULL ) ), NULL );
	}
}
