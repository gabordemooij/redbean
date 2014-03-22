<?php 

namespace RedUNIT\Mysql;

use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\QueryWriter\MySQL as MySQL;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\RedException as RedException; 

/**
 * RedUNIT_Mysql_Writer
 *
 * @file    RedUNIT/Mysql/Writer.php
 * @desc    A collection of database specific writer functions.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Writer extends \RedUNIT\Mysql
{
	/**
	 * Test scanning and coding of values.
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

		$a       = new AssociationManager( $toolbox );

		$adapter->exec( "DROP TABLE IF EXISTS testtable" );

		asrt( in_array( "testtable", $adapter->getCol( "show tables" ) ), FALSE );

		$writer->createTable( "testtable" );

		asrt( in_array( "testtable", $adapter->getCol( "show tables" ) ), TRUE );

		asrt( count( array_diff( $writer->getTables(), $adapter->getCol( "show tables" ) ) ), 0 );
		asrt( count( array_keys( $writer->getColumns( "testtable" ) ) ), 1 );

		asrt( in_array( "id", array_keys( $writer->getColumns( "testtable" ) ) ), TRUE );
		asrt( in_array( "c1", array_keys( $writer->getColumns( "testtable" ) ) ), FALSE );

		$writer->addColumn( "testtable", "c1", MySQL::C_DATATYPE_UINT32 );

		asrt( count( array_keys( $writer->getColumns( "testtable" ) ) ), 2 );

		asrt( in_array( "c1", array_keys( $writer->getColumns( "testtable" ) ) ), TRUE );

		foreach ( $writer->sqltype_typeno as $key => $type ) {
			if ( $type < 100 ) {
				asrt( $writer->code( $key, TRUE ), $type );
			} else {
				asrt( $writer->code( $key, TRUE ), 99 );
			}
		}

		asrt( $writer->code( MySQL::C_DATATYPE_SPECIAL_DATETIME ), 99 );

		asrt( $writer->code( "unknown" ), 99 );

		asrt( $writer->scanType( FALSE ), 0 );
		asrt( $writer->scanType( NULL ), 0 );

		asrt( $writer->scanType( 2 ), 2 );
		asrt( $writer->scanType( 255 ), 2 ); //no more uint8
		asrt( $writer->scanType( 256 ), 2 );

		asrt( $writer->scanType( -1 ), 3 );
		asrt( $writer->scanType( 1.5 ), 3 );

		asrt( $writer->scanType( INF ), 4 );

		asrt( $writer->scanType( "abc" ), 4 );

		asrt( $writer->scanType( str_repeat( 'abcd', 100000 ) ), MySQL::C_DATATYPE_TEXT32 );

		asrt( $writer->scanType( "2001-10-10", TRUE ), MySQL::C_DATATYPE_SPECIAL_DATE );

		asrt( $writer->scanType( "2001-10-10 10:00:00", TRUE ), MySQL::C_DATATYPE_SPECIAL_DATETIME );

		asrt( $writer->scanType( "2001-10-10" ), 4 );

		asrt( $writer->scanType( "2001-10-10 10:00:00" ), 4 );

		asrt( $writer->scanType( str_repeat( "lorem ipsum", 100 ) ), 5 );

		$writer->widenColumn( "testtable", "c1", 2 );

		$writer->addColumn( "testtable", "special", MySQL::C_DATATYPE_SPECIAL_DATE );
		
		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special'], TRUE ), MySQL::C_DATATYPE_SPECIAL_DATE );
		
		asrt( $writer->code( $cols['special'], FALSE ), MySQL::C_DATATYPE_SPECIFIED );
		
		$writer->addColumn( "testtable", "special2", MySQL::C_DATATYPE_SPECIAL_DATETIME );
		
		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special2'], TRUE ), MySQL::C_DATATYPE_SPECIAL_DATETIME );
		
		asrt( $writer->code( $cols['special'], FALSE ), MySQL::C_DATATYPE_SPECIFIED );
		
		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols["c1"] ), 2 );

		$writer->widenColumn( "testtable", "c1", 3 );

		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols["c1"] ), 3 );

		$writer->widenColumn( "testtable", "c1", 4 );

		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols["c1"] ), 4 );

		$writer->widenColumn( "testtable", "c1", 5 );

		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols["c1"] ), 5 );

		$id  = $writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "lorem ipsum" ) ) );

		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );

		asrt( $row[0]["c1"], "lorem ipsum" );

		$writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "ipsum lorem" ) ), $id );

		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );

		asrt( $row[0]["c1"], "ipsum lorem" );

		$writer->deleteRecord( "testtable", array( "id" => array( $id ) ) );

		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );

		asrt( empty( $row ), TRUE );

		$writer->addColumn( "testtable", "c2", 2 );

		try {
			$writer->addUniqueIndex( "testtable", array( "c1", "c2" ) );

			fail(); //should fail, no content length blob
		} catch ( SQL $e ) {
			pass();
		}

		$writer->addColumn( "testtable", "c3", 2 );

		try {
			$writer->addUniqueIndex( "testtable", array( "c2", "c3" ) );

			pass(); //should fail, no content length blob
		} catch ( SQL $e ) {
			fail();
		}

		$a = $adapter->get( "show index from testtable" );

		asrt( count( $a ), 3 );

		asrt( $a[1]["Key_name"], "UQ_64b283449b9c396053fe1724b4c685a80fd1a54d" );
		asrt( $a[2]["Key_name"], "UQ_64b283449b9c396053fe1724b4c685a80fd1a54d" );
	}

	/**
	 * (FALSE should be stored as 0 not as '')
	 * 
	 * @return voids
	 */
	public function testZeroIssue()
	{
		testpack( "Zero issue" );

		$toolbox = R::getToolBox();
		$redbean = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$pdo     = $adapter->getDatabase();

		$pdo->Execute( "DROP TABLE IF EXISTS `zero`" );

		$bean        = $redbean->dispense( "zero" );

		$bean->zero  = FALSE;
		$bean->title = "bla";

		$redbean->store( $bean );

		asrt( count( $redbean->find( "zero", array(), " zero = 0 " ) ), 1 );

		R::store( R::dispense( 'hack' ) );

		testpack( "Test RedBean Security - bean interface " );

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		$bean = $redbean->load( "page", "13; drop table hack" );

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );
		try {
			$bean = $redbean->load( "page where 1; drop table hack", 1 );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		$bean     = $redbean->dispense( "page" );

		$evil     = "; drop table hack";

		$bean->id = $evil;

		try {
			$redbean->store( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		unset( $bean->id );

		$bean->name = "\"" . $evil;

		try {
			$redbean->store( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		$bean->name = "'" . $evil;

		try {
			$redbean->store( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		$bean->$evil = 1;

		try {
			$redbean->store( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		unset( $bean->$evil );

		$bean->id   = 1;
		$bean->name = "\"" . $evil;

		try {
			$redbean->store( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		$bean->name = "'" . $evil;

		try {
			$redbean->store( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		$bean->$evil = 1;

		try {
			$redbean->store( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		try {
			$redbean->trash( $bean );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		try {
			$redbean->find( "::", array(), "" );
		} catch (\Exception $e ) {
			pass();
		}

		$adapter->exec( "drop table if exists sometable" );

		testpack( "Test RedBean Security - query writer" );

		try {
			$writer->createTable( "sometable` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , PRIMARY KEY ( `id` ) ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ; drop table hack; --" );
		} catch (\Exception $e ) {
		}

		asrt( in_array( "hack", $adapter->getCol( "show tables" ) ), TRUE );

		testpack( "Test ANSI92 issue in clearrelations" );

		$pdo->Execute( "DROP TABLE IF EXISTS book_group" );
		$pdo->Execute( "DROP TABLE IF EXISTS author_book" );
		$pdo->Execute( "DROP TABLE IF EXISTS book" );
		$pdo->Execute( "DROP TABLE IF EXISTS author" );

		$redbean       = $toolbox->getRedBean();

		$a             = new AssociationManager( $toolbox );

		$book          = $redbean->dispense( "book" );
		$author1       = $redbean->dispense( "author" );
		$author2       = $redbean->dispense( "author" );

		$book->title   = "My First Post";

		$author1->name = "Derek";
		$author2->name = "Whoever";

		set1toNAssoc( $a, $book, $author1 );
		set1toNAssoc( $a, $book, $author2 );

		pass();

		$pdo->Execute( "DROP TABLE IF EXISTS book_group" );
		$pdo->Execute( "DROP TABLE IF EXISTS book_author" );
		$pdo->Execute( "DROP TABLE IF EXISTS author_book" );
		$pdo->Execute( "DROP TABLE IF EXISTS book" );
		$pdo->Execute( "DROP TABLE IF EXISTS author" );

		$redbean       = $toolbox->getRedBean();

		$a             = new AssociationManager( $toolbox );

		$book          = $redbean->dispense( "book" );
		$author1       = $redbean->dispense( "author" );
		$author2       = $redbean->dispense( "author" );

		$book->title   = "My First Post";

		$author1->name = "Derek";
		$author2->name = "Whoever";

		$a->associate( $book, $author1 );
		$a->associate( $book, $author2 );

		pass();

		testpack( "Test Association Issue Group keyword (Issues 9 and 10)" );

		$pdo->Execute( "DROP TABLE IF EXISTS `book_group`" );
		$pdo->Execute( "DROP TABLE IF EXISTS `group`" );

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
			fail();
		}

		asrt( (int) $adapter->getCell( "select count(*) from book_group" ), 1 ); //just 1 rec!

		$pdo->Execute( "DROP TABLE IF EXISTS book_group" );
		$pdo->Execute( "DROP TABLE IF EXISTS author_book" );
		$pdo->Execute( "DROP TABLE IF EXISTS book" );
		$pdo->Execute( "DROP TABLE IF EXISTS author" );

		$redbean       = $toolbox->getRedBean();

		$a             = new AssociationManager( $toolbox );

		$book          = $redbean->dispense( "book" );
		$author1       = $redbean->dispense( "author" );
		$author2       = $redbean->dispense( "author" );

		$book->title   = "My First Post";

		$author1->name = "Derek";
		$author2->name = "Whoever";

		$a->unassociate( $book, $author1 );
		$a->unassociate( $book, $author2 );

		pass();

		$redbean->trash( $redbean->dispense( "bla" ) );

		pass();

		$bean       = $redbean->dispense( "bla" );

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
	public function testTypes()
	{
		testpack( 'Special data types' );

		$bean       = R::dispense( 'bean' );

		$bean->date = 'someday';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'varchar(255)' );

		$bean       = R::dispense( 'bean' );

		$bean->date = '2011-10-10';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'varchar(255)' );
	}

	/**
	 * Test date types.
	 * 
	 * @return void
	 */
	public function testTypesDates()
	{
		$bean       = R::dispense( 'bean' );

		$bean->date = '2011-10-10';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'date' );
	}

	/**
	 * Date-time
	 * 
	 * @return void
	 */
	public function testTypesDateTimes()
	{
		$bean       = R::dispense( 'bean' );

		$bean->date = '2011-10-10 10:00:00';

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['date'], 'datetime' );

		$bean = R::dispense( 'bean' );

		try {
			$bean        = R::dispense( 'bean' );

			$bean->title = 123;

			$bean->setMeta( 'cast.title', 'invalid' );

			R::store( $bean );

			fail();
		} catch ( RedException $e ) {
			pass();
		} catch (\Exception $e ) {
			fail();
		}

		$bean        = R::dispense( 'bean' );

		$bean->title = 123;

		$bean->setMeta( 'cast.title', 'text' );

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['title'], 'text' );

		R::nuke();

		$bean        = R::dispense( 'bean' );

		$bean->title = 123;

		$bean->setMeta( 'cast.title', 'string' );

		R::store( $bean );

		$cols = R::getColumns( 'bean' );

		asrt( $cols['title'], 'varchar(255)' );
	}
	
	/**
	 * Stored and reloads spatial data to see if the
	 * value is preserved correctly.
	 * 
	 * @return void
	 */
	protected function setGetSpatial( $data )
	{
		R::nuke();

		$place           = R::dispense( 'place' );

		$place->location = $data;

		R::store( $place );

		asrt( R::getCell( 'SELECT AsText(location) FROM place LIMIT 1' ), $data );
	}
}
