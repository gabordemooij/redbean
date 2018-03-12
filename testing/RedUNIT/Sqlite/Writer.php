<?php

namespace RedUNIT\Sqlite;

use RedUNIT\Sqlite as Sqlite;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\QueryWriter\SQLiteT as SQLiteT;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\RedException as RedException;

/**
 * Writer
 *
 * Tests for SQLite Query Writer.
 * This test class contains Query Writer specific tests.
 * Use this class to add tests to test Query Writer specific
 * behaviours, quirks and issues.
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
	 * Test whether optimizations do not have effect on Writer query outcomes.
	 *
	 * @return void
	 */
	public function testWriterSpeedUp()
	{
		R::nuke();
		$id = R::store( R::dispense( 'book' ) );
		$writer = R::getWriter();
		$count1 = $writer->queryRecordCount( 'book', array( 'id' => $id ), ' id = :id ', array( ':id' => $id ) );
		$count2 = $writer->queryRecordCount( 'book', array( ), ' id = :id ', array( ':id' => $id ) );
		$count3 = $writer->queryRecordCount( 'book', NULL, ' id = :id ', array( ':id' => $id ) );
		$count4 = $writer->queryRecordCount( 'book', array( 'id' => $id ) );
		asrt( $count1, $count2 );
		asrt( $count2, $count3 );
		asrt( $count3, $count4 );
		R::nuke();
		$books = R::dispenseAll( 'book*4' );
		$ids = R::storeAll( $books[0] );
		$writer->deleteRecord( 'book', array( 'id' => $ids[0] ) );
		$writer->deleteRecord( 'book', array( 'id' => $ids[1] ), ' id = :id ', array( ':id' => $ids[1] ) );
		$writer->deleteRecord( 'book', NULL, ' id = :id ', array( ':id' => $ids[2] ) );
		$writer->deleteRecord( 'book', array(), ' id = :id ', array( ':id' => $ids[3] ) );
		asrt( R::count( 'book' ), 0 );
		R::nuke();
		$id = R::store( R::dispense( 'book' ) );
		$record = $writer->queryRecord( 'book', array( 'id' => $id ) );
		asrt( is_array( $record ), TRUE );
		asrt( is_array( $record[0] ), TRUE );
		asrt( isset( $record[0]['id'] ), TRUE );
		asrt( (int) $record[0]['id'], $id );
		$record = $writer->queryRecord( 'book', array( 'id' => $id ), ' id = :id ', array( ':id' => $id ) );
		asrt( is_array( $record ), TRUE );
		asrt( is_array( $record[0] ), TRUE );
		asrt( isset( $record[0]['id'] ), TRUE );
		asrt( (int) $record[0]['id'], $id );
		$record = $writer->queryRecord( 'book', NULL, ' id = :id ', array( ':id' => $id ) );
		asrt( is_array( $record ), TRUE );
		asrt( is_array( $record[0] ), TRUE );
		asrt( isset( $record[0]['id'] ), TRUE );
		asrt( (int) $record[0]['id'], $id );
		$record = $writer->queryRecord( 'book', array(), ' id = :id ', array( ':id' => $id ) );
		asrt( is_array( $record ), TRUE );
		asrt( is_array( $record[0] ), TRUE );
		asrt( isset( $record[0]['id'] ), TRUE );
		asrt( (int) $record[0]['id'], $id );
	}

	/**
	 * Tests wheter we can write a deletion query
	 * for SQLite using NO conditions but only an
	 * additional SQL snippet.
	 *
	 * @return void
	 */
	public function testWriteDeleteQuery()
	{
		$queryWriter = R::getWriter();
		asrt( ( $queryWriter instanceof SQLiteT ), TRUE );
		R::nuke();
		$bean = R::dispense( 'bean' );
		$bean->name = 'a';
		$id = R::store( $bean );
		asrt( R::count( 'bean' ), 1 );
		$queryWriter->deleteRecord( 'bean', array(), $addSql = ' id = :id ', $bindings = array( ':id' => $id ) );
		asrt( R::count( 'bean' ), 0 );
	}

	/**
	 * Tests wheter we can write a counting query
	 * for SQLite using conditions and an additional SQL snippet.
	 *
	 * @return void
	 */
	public function testWriteCountQuery()
	{
		$queryWriter = R::getWriter();
		asrt( ( $queryWriter instanceof SQLiteT ), TRUE );
		R::nuke();
		$bean = R::dispense( 'bean' );
		$bean->name = 'a';
		R::store( $bean );
		$bean = R::dispense( 'bean' );
		$bean->name = 'b';
		R::store( $bean );
		$bean = R::dispense( 'bean' );
		$bean->name = 'b';
		R::store( $bean );
		$count = $queryWriter->queryRecordCount( 'bean', array( 'name' => 'b' ), $addSql = ' id > :id ', $bindings = array( ':id' => 0 ) );
		asrt( $count, 2 );
	}

	/**
	 * Tests whether we can write a SQLite join and
	 * whether the correct exception is thrown in case
	 * of an invalid join.
	 *
	 * @return void
	 */
	public function testWriteJoinSnippets()
	{
		$queryWriter = R::getWriter();
		asrt( ( $queryWriter instanceof SQLiteT ), TRUE );
		$snippet = $queryWriter->writeJoin( 'book', 'page' ); //default must be LEFT
		asrt( is_string( $snippet ), TRUE );
		asrt( ( strlen( $snippet ) > 0 ), TRUE );
		asrt( ' LEFT JOIN `page` ON `page`.id = `book`.page_id ', $snippet );
		$snippet = $queryWriter->writeJoin( 'book', 'page', 'LEFT' );
		asrt( is_string( $snippet ), TRUE );
		asrt( ( strlen( $snippet ) > 0 ), TRUE );
		asrt( ' LEFT JOIN `page` ON `page`.id = `book`.page_id ', $snippet );
		$snippet = $queryWriter->writeJoin( 'book', 'page', 'RIGHT' );
		asrt( is_string( $snippet ), TRUE );
		asrt( ( strlen( $snippet ) > 0 ), TRUE );
		asrt( ' RIGHT JOIN `page` ON `page`.id = `book`.page_id ', $snippet );
		$snippet = $queryWriter->writeJoin( 'book', 'page', 'INNER' );
		asrt( ' INNER JOIN `page` ON `page`.id = `book`.page_id ', $snippet );
		$exception = NULL;
		try {
			$snippet = $queryWriter->writeJoin( 'book', 'page', 'MIDDLE' );
		}
		catch(\Exception $e) {
			$exception = $e;
		}
		asrt( ( $exception instanceof RedException ), TRUE );
		$errorMessage = $exception->getMessage();
		asrt( is_string( $errorMessage ), TRUE );
		asrt( ( strlen( $errorMessage ) > 0 ), TRUE );
		asrt( $errorMessage, 'Invalid JOIN.' );
	}

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

		asrt( $writer->scanType( 2147483648 - 1 ), SQLiteT::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( 2147483648 ), SQLiteT::C_DATATYPE_TEXT );

		asrt( $writer->scanType( -2147483648 + 1), SQLiteT::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( -2147483648 ), SQLiteT::C_DATATYPE_TEXT );

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

	/**
	 * Test renewed error handling in SQLite.
	 * In fluid mode ignore table/column not exists (HY000 + code 1).
	 * In frozen mode ignore nothing.
	 *
	 * @return void
	 */
	public function testErrorHandling()
	{
		R::nuke();
		R::store( R::dispense( 'book' ) );
		R::freeze( FALSE );
		R::find( 'book2', ' id > 0' );
		pass();
		R::find( 'book', ' id2 > ?' );
		pass();
		$exception = NULL;
		try {
			R::find( 'book', ' id = ?', array( 0, 1 ) );
		} catch( \Exception $e ) {
			$exception = $e;
		}
		asrt( ( $exception instanceof SQL ), TRUE );
		R::freeze( TRUE );
		$exception = NULL;
		try {
			R::find( 'book2', ' id > 0' );
		} catch( \Exception $e ) {
			$exception = $e;
		}
		asrt( ( $exception instanceof SQL ), TRUE );
		$exception = NULL;
		try {
			R::find( 'book', ' id2 > 0' );
		} catch( \Exception $e ) {
			$exception = $e;
		}
		asrt( ( $exception instanceof SQL ), TRUE );
	}
}
