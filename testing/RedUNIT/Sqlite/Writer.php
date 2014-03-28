<?php 

namespace RedUNIT\Sqlite;

use RedUNIT\Sqlite as Sqlite;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\QueryWriter\SQLiteT as SQLiteT;
use RedBeanPHP\RedException\SQL as SQL; 

/**
 * RedUNIT_Sqlite_Writer
 *
 * @file    RedUNIT/Sqlite/Writer.php
 * @desc    Tests writer specific functions.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Writer extends Sqlite
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
			asrt( $writer->code( $key ), $type );
		}

		asrt( $writer->code( "unknown" ), 99 );

		asrt( $writer->scanType( FALSE ), SQLiteT::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( NULL ), SQLiteT::C_DATATYPE_INTEGER );

		asrt( $writer->scanType( 2 ), SQLiteT::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( 255 ), SQLiteT::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( 256 ), SQLiteT::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( -1 ), SQLiteT::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( 1.5 ), SQLiteT::C_DATATYPE_NUMERIC );

		asrt( $writer->scanType( INF ), SQLiteT::C_DATATYPE_TEXT );

		asrt( $writer->scanType( "abc" ), SQLiteT::C_DATATYPE_TEXT );

		asrt( $writer->scanType( '2010-10-10' ), SQLiteT::C_DATATYPE_NUMERIC );
		asrt( $writer->scanType( '2010-10-10 10:00:00' ), SQLiteT::C_DATATYPE_NUMERIC );

		asrt( $writer->scanType( str_repeat( "lorem ipsum", 100 ) ), SQLiteT::C_DATATYPE_TEXT );

		$writer->widenColumn( "testtable", "c1", 2 );

		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols["c1"] ), 2 );

		//$id = $writer->insertRecord("testtable", array("c1"), array(array("lorem ipsum")));
		$id  = $writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "lorem ipsum" ) ) );
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

		$redbean = $toolbox->getRedBean();

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

		$book    = $redbean->dispense( "book" );
		$author1 = $redbean->dispense( "author" );
		$author2 = $redbean->dispense( "author" );

		$book->title = "My First Post";

		$author1->name = "Derek";
		$author2->name = "Whoever";

		$a->associate( $book, $author1 );
		$a->associate( $book, $author2 );

		pass();

		testpack( "Test Association Issue Group keyword (Issues 9 and 10)" );

		$group       = $redbean->dispense( "group" );
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
			print_r( $e );

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
	public function testVaria2()
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
	 * Test special data types.
	 * 
	 * @return void
	 */
	public function testSpecialDataTypes()
	{
		testpack( 'Special data types' );

		$bean = R::dispense( 'bean' );

		$bean->date = 'someday';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'TEXT' );

		$bean = R::dispense( 'bean' );

		$bean->date = '2011-10-10';

		R::nuke();

		$bean = R::dispense( 'bean' );

		$bean->date = '2011-10-10';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'NUMERIC' );
	}
}
