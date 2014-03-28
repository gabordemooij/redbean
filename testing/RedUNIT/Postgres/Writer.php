<?php

namespace RedUNIT\Postgres;

use RedUNIT\Postgres as Postgres;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\QueryWriter\PostgreSQL as PostgreSQL;
use RedBeanPHP\RedException\SQL as SQL; 

/**
 * RedUNIT_Postgres_Writer
 *
 * @file    RedUNIT/Postgres/Writer.php
 * @desc    A collection of writer specific tests.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Writer extends Postgres
{
	/**
	 * Test scanning and coding.
	 * 
	 * @return void
	 */
	public function testScanningAndCoding()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$a = new AssociationManager( $toolbox );

		$adapter->exec( "DROP TABLE IF EXISTS testtable" );

		asrt( in_array( "testtable", $writer->getTables() ), FALSE );

		$writer->createTable( "testtable" );

		asrt( in_array( "testtable", $writer->getTables() ), TRUE );

		asrt( count( array_keys( $writer->getColumns( "testtable" ) ) ), 1 );

		asrt( in_array( "id", array_keys( $writer->getColumns( "testtable" ) ) ), TRUE );
		asrt( in_array( "c1", array_keys( $writer->getColumns( "testtable" ) ) ), FALSE );

		$writer->addColumn( "testtable", "c1", 1 );

		asrt( count( array_keys( $writer->getColumns( "testtable" ) ) ), 2 );

		asrt( in_array( "c1", array_keys( $writer->getColumns( "testtable" ) ) ), TRUE );

		foreach ( $writer->sqltype_typeno as $key => $type ) {
			if ( $type < 100 ) {
				asrt( $writer->code( $key, TRUE ), $type );
			} else {
				asrt( $writer->code( $key ), 99 );
			}
		}

		asrt( $writer->code( PostgreSQL::C_DATATYPE_SPECIAL_DATETIME ), 99 );

		asrt( $writer->code( "unknown" ), 99 );

		asrt( $writer->scanType( FALSE ), 3 );

		asrt( $writer->scanType( NULL ), 0 );

		asrt( $writer->scanType( 2 ), 0 );

		asrt( $writer->scanType( 255 ), 0 );
		asrt( $writer->scanType( 256 ), 0 );

		asrt( $writer->scanType( -1 ), 0 );

		asrt( $writer->scanType( 1.5 ), 1 );

		asrt( $writer->scanType( INF ), 1 );

		asrt( $writer->scanType( "abc" ), 3 );

		asrt( $writer->scanType( "2001-10-10", TRUE ), PostgreSQL::C_DATATYPE_SPECIAL_DATE );

		asrt( $writer->scanType( "2001-10-10 10:00:00", TRUE ), PostgreSQL::C_DATATYPE_SPECIAL_DATETIME );

		asrt( $writer->scanType( "2001-10-10 10:00:00" ), 3 );

		asrt( $writer->scanType( "2001-10-10" ), 3 );

		asrt( $writer->scanType( str_repeat( "lorem ipsum", 100 ) ), 3 );

		$writer->widenColumn( "testtable", "c1", 3 );

		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols["c1"] ), 3 );
		
		$writer->addColumn( "testtable", "special", PostgreSQL::C_DATATYPE_SPECIAL_DATE );
		
		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special'], TRUE ), PostgreSQL::C_DATATYPE_SPECIAL_DATE );
		
		asrt( $writer->code( $cols['special'], FALSE ), PostgreSQL::C_DATATYPE_SPECIFIED );
		
		$writer->addColumn( "testtable", "special2", PostgreSQL::C_DATATYPE_SPECIAL_DATETIME );
		
		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special2'], TRUE ), PostgreSQL::C_DATATYPE_SPECIAL_DATETIME );
		
		asrt( $writer->code( $cols['special'], FALSE ), PostgreSQL::C_DATATYPE_SPECIFIED );

		//$id = $writer->insertRecord("testtable", array("c1"), array(array("lorem ipsum")));

		$id = $writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "lorem ipsum" ) ) );

		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );

		asrt( $row[0]["c1"], "lorem ipsum" );

		$writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "ipsum lorem" ) ), $id );

		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );

		asrt( $row[0]["c1"], "ipsum lorem" );

		$writer->deleteRecord( "testtable", array( "id" => array( $id ) ) );

		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );

		asrt( empty( $row ), TRUE );
	}

	/**
	 * (FALSE should be stored as 0 not as '')
	 * 
	 * @return void
	 */
	public function testZeroIssue()
	{
		testpack( "Zero issue" );

		$toolbox = R::getToolBox();
		$redbean = $toolbox->getRedBean();

		$bean = $redbean->dispense( "zero" );

		$bean->zero  = FALSE;
		$bean->title = "bla";

		$redbean->store( $bean );

		asrt( count( $redbean->find( "zero", array(), " zero = 0 " ) ), 1 );

		testpack( "Test ANSI92 issue in clearrelations" );

		$a = new AssociationManager( $toolbox );

		$book    = $redbean->dispense( "book" );
		$author1 = $redbean->dispense( "author" );
		$author2 = $redbean->dispense( "author" );

		$book->title = "My First Post";

		$author1->name = "Derek";
		$author2->name = "Whoever";

		set1toNAssoc( $a, $book, $author1 );
		set1toNAssoc( $a, $book, $author2 );

		pass();
	}

	/**
	 * Various.
	 * Tests whether writer correctly handles keyword 'group' and SQL state 23000 issue.
	 * These tests remain here to make sure issues 9 and 10 never happen again.
	 * However this bug will probably never re-appear due to changed architecture.
	 * 
	 * @return void
	 */
	public function testIssue9and10()
	{
		$toolbox = R::getToolBox();
		$redbean = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();

		$a = new AssociationManager( $toolbox );

		$book = $redbean->dispense( "book" );

		$author1 = $redbean->dispense( "author" );
		$author2 = $redbean->dispense( "author" );

		$book->title = "My First Post";

		$author1->name = "Derek";
		$author2->name = "Whoever";

		$a->associate( $book, $author1 );
		$a->associate( $book, $author2 );

		pass();

		testpack( "Test Association Issue Group keyword (Issues 9 and 10)" );

		R::nuke();

		$group = $redbean->dispense( "group" );

		$group->name = "mygroup";

		$redbean->store( $group );

		try {
			$a->associate( $group, $book );

			pass();
		} catch ( SQL $e ) {
			fail();
		}

		// Test issue SQL error 23000
		try {
			$a->associate( $group, $book );

			pass();
		} catch ( SQL $e ) {
			fail();
		}

		asrt( (int) $adapter->getCell( "select count(*) from book_group" ), 1 ); //just 1 rec!
	}

	/**
	 * Test various.
	 * Test various somewhat uncommon trash/unassociate scenarios.
	 * (i.e. unassociate unrelated beans, trash non-persistant beans etc).
	 * Should be handled gracefully - no output checking.
	 * 
	 * @return void
	 */
	public function testVaria()
	{
		$toolbox = R::getToolBox();
		$redbean = $toolbox->getRedBean();

		$a = new AssociationManager( $toolbox );

		$book    = $redbean->dispense( "book" );
		$author1 = $redbean->dispense( "author" );
		$author2 = $redbean->dispense( "author" );

		$book->title = "My First Post";

		$author1->name = "Derek";
		$author2->name = "Whoever";

		$a->unassociate( $book, $author1 );
		$a->unassociate( $book, $author2 );

		pass();

		$redbean->trash( $redbean->dispense( "bla" ) );

		pass();

		$bean = $redbean->dispense( "bla" );

		$bean->name = 1;
		$bean->id   = 2;

		$redbean->trash( $bean );

		pass();
	}

	/**
	 * Test special types.
	 * 
	 * @return void
	 */
	public function testTypes()
	{
		testpack( 'Special data types' );

		$bean = R::dispense( 'bean' );

		$bean->date = 'someday';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'text' );

		$bean = R::dispense( 'bean' );

		$bean->date = '2011-10-10';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'text' );
	}

	/**
	 * Test dates.
	 * 
	 * @return void
	 */
	public function testTypesDates()
	{
		$bean = R::dispense( 'bean' );

		$bean->date = '2011-10-10';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'date' );
	}

	/**
	 * Datetime.
	 * 
	 * @return void
	 */
	public function testTypesDateTimes()
	{
		$bean = R::dispense( 'bean' );

		$bean->date = '2011-10-10 10:00:00';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'timestamp without time zone' );
	}

	/**
	 * Test spatial data types.
	 * 
	 * @return void
	 */
	public function testTypesPoints()
	{
		$bean = R::dispense( 'bean' );

		$bean->point = '(92,12)';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['point'], 'point' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->point, '(92,12)' );

		$bean->note = 'taint';

		R::store( $bean );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->point, '(92,12)' );
	}

	/**
	 * Test points.
	 * 
	 * @return void
	 */
	public function testTypesDecPoints()
	{
		$bean = R::dispense( 'bean' );

		$bean->point = '(9.2,1.2)';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['point'], 'point' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->point, '(9.2,1.2)' );

		$bean->note = 'taint';

		R::store( $bean );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->point, '(9.2,1.2)' );
	}

	/**
	 * Test multi points.
	 * 
	 * @return void
	 */
	public function testTypesMultiDecPoints()
	{
		$bean = R::dispense( 'bean' );

		$bean->line = '[(1.2,1.4),(2.2,34)]';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['line'], 'lseg' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->line, '[(1.2,1.4),(2.2,34)]' );

		$bean->note = 'taint';

		R::store( $bean );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->line, '[(1.2,1.4),(2.2,34)]' );
	}

	/**
	 * More points...
	 * 
	 * @return void
	 */
	public function testTypesWeirdPoints()
	{
		$bean = R::dispense( 'bean' );

		$bean->circle = '<(9.2,1.2),7.9>';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['circle'], 'circle' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->circle, '<(9.2,1.2),7.9>' );

		$bean->note = 'taint';

		R::store( $bean );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->circle, '<(9.2,1.2),7.9>' );
	}

	/**
	 * Test money data type.
	 * 
	 * @return void
	 */
	public function testTypesMoney()
	{
		$bean = R::dispense( 'bean' );

		$bean->money = '$123.45';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['money'], 'money' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->money, '$123.45' );

		$bean->note = 'taint';

		R::store( $bean );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->money, '$123.45' );
		
		$bean->money = '$123,455.01';
		
		R::store($bean);
		
		$bean = $bean->fresh();
		
		asrt( $bean->money, '$123,455.01' );
		
	}

	/**
	 * Test negative money data type.
	 * 
	 * @return void
	 */
	public function testTypesNegativeMoney()
	{
		$bean = R::dispense( 'bean' );

		$bean->money = '-$123.45';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['money'], 'money' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->money, '-$123.45' );

		$bean->note = 'taint';

		R::store( $bean );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->money, '-$123.45' );
	}
	
	/**
	 * Issue #340
	 * Redbean is currently picking up bcrypt hashed passwords
	 * (which look like this: $2y$12$85lAS....SnpDNVGPAC7w0G) 
	 * as PostgreSQL money types. 
	 * Then, once R::store is called on the bean, it chokes and throws the following error:
	 * PHP Fatal error: Uncaught [22P02] - SQLSTATE[22P02]: Invalid text representation: 7 ERROR: 
	 * invalid input syntax for type money: ....
	 * 
	 * @return void
	 */
	public function testTypesInvalidMoney()
	{
		$bean = R::dispense( 'bean' );

		$bean->nomoney = '$2y$12$85lAS';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['nomoney'], 'text' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->nomoney, '$2y$12$85lAS' );

		$bean->note = 'taint';

		R::store( $bean );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->nomoney, '$2y$12$85lAS' );
	}

	/**
	 * Test types of strings.
	 * 
	 * @return void
	 */
	public function testTypesStrings()
	{
		$bean = R::dispense( 'bean' );

		$bean->data = 'abcdefghijk';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['data'], 'text' );

		$bean = R::load( 'bean', $bean->id );

		asrt( $bean->data, 'abcdefghijk' );

		$bean->data = '(1,2)';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['data'], 'text' );

		$bean->data = '[(1.2,1.4),(2.2,34)]';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['data'], 'text' );

		$bean->data = '<(9.2,1.2),7.9>';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['data'], 'text' );

		$bean->data = '$25';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['data'], 'text' );

		$bean->data = '2012-10-10 10:00:00';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['data'], 'text' );
	}
}
