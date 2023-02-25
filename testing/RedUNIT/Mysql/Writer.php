<?php

namespace RedUNIT\Mysql;

use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\QueryWriter\MySQL as MySQL;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\RedException as RedException;

/**
 * Writer
 *
 * Tests for MySQL and MariaDB Query Writer.
 * This test class contains Query Writer specific tests.
 * Use this class to add tests to test Query Writer specific
 * behaviours, quirks and issues.
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
	 * for MySQL using NO conditions but only an
	 * additional SQL snippet.
	 *
	 * @return void
	 */
	public function testWriteDeleteQuery()
	{
		$queryWriter = R::getWriter();
		asrt( ( $queryWriter instanceof MySQL ), TRUE );
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
	 * for MySQL using conditions and an additional SQL snippet.
	 *
	 * @return void
	 */
	public function testWriteCountQuery()
	{
		$queryWriter = R::getWriter();
		asrt( ( $queryWriter instanceof MySQL ), TRUE );
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
	 * Tests whether we can write a MySQL join and
	 * whether the correct exception is thrown in case
	 * of an invalid join.
	 *
	 * @return void
	 */
	public function testWriteJoinSnippets()
	{
		$queryWriter = R::getWriter();
		asrt( ( $queryWriter instanceof MySQL ), TRUE );
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
	 * Test whether we can store JSON as a JSON column
	 * and whether this plays well with the other data types.
	 */
	public function testSetGetJSON()
	{
		/* a stub test in case full test cannot be performed, see below */
		R::useJSONFeatures( TRUE );
		asrt( R::getWriter()->scanType( '[1,2,3]', TRUE ), MySQL::C_DATATYPE_SPECIAL_JSON );
		R::useJSONFeatures( FALSE );
		global $travis;
		if ($travis) return;
		/* does not work on MariaDB */
		$version = strtolower( R::getCell('select version()') );
		if ( strpos( $version, 'mariadb' ) !== FALSE ) return;
		// Check if database platform is MariaDB < 10.2
		$selectVersion = R::getDatabaseAdapter()->getCol( 'SELECT VERSION()' );
		list ( $version, $dbPlatform ) = explode( '-', reset ( $selectVersion ) );
		list( $versionMajor, $versionMinor, $versionPatch ) = explode( '.', $version );
		if ( $dbPlatform == "MariaDB" && $versionMajor <= 10 && $versionMinor < 2 ) {
			// No support for JSON columns, abort test
			return;
		}
		R::nuke();
		$bean = R::dispense('bean');
		$message = json_encode( array( 'message' => 'hello', 'type' => 'greeting' ) );
		$bean->data = $message;
		R::store( $bean );
		$columns = R::inspect('bean');
		asrt( array_key_exists( 'data', $columns ), TRUE );
		asrt( ( $columns['data'] !== 'json' ), TRUE );
		R::useJSONFeatures( TRUE );
		R::nuke();
		$bean = R::dispense('bean');
		$message = array( 'message' => 'hello', 'type' => 'greeting' );
		$bean->data = $message;
		R::store( $bean );
		$columns = R::inspect('bean');
		asrt( array_key_exists( 'data', $columns ), TRUE );
		asrt( $columns['data'], 'json' );
		$bean = $bean->fresh();
		$message = json_decode( $bean->data, TRUE );
		asrt( $message['message'], 'hello' );
		asrt( $message['type'], 'greeting' );
		$message['message'] = 'hi';
		$bean->data = $message;
		R::store( $bean );
		pass();
		$bean = R::findOne( 'bean' );
		$message = json_decode( $bean->data );
		asrt( $message->message, 'hi' );
		$book = R::dispense( 'book' );
		$book->page = 'lorem ipsum';
		R::store( $book );
		$book = $book->fresh();
		asrt( $book->page, 'lorem ipsum' );
		$book2 = R::dispense( 'book' );
		$book2->page = array( 'chapter' => '1' );
		R::store( $book2 );
		pass(); //should not try to modify column and trigger exception
		$book = $book->fresh();
		asrt( $book->page, 'lorem ipsum' );
		$columns = R::inspect('book');
		asrt( ( $columns['page'] !== 'json' ), TRUE );
		$building = R::dispense( 'building' );
		$building->year = 'MLXXVIII';
		R::store( $building );
		$shop = R::dispense( 'building' );
		$shop->year = '2010-01-01';
		R::store( $shop );
		$building = R::load( 'building', $building->id );
		asrt( $building->year, 'MLXXVIII' );
		$columns = R::inspect( 'building' );
		asrt( strpos( strtolower( $columns['year'] ), 'date' ), FALSE );
		$shop->anno = '2010-01-01';
		R::store( $shop );
		$columns = R::inspect( 'building' );
		asrt( $columns['anno'], 'date' );
		R::useJSONFeatures( FALSE );
	}

	/**
	 * Test Facade bind function method.
	 * Test for MySQL WKT spatial format.
	 */
	public function testFunctionFilters()
	{
		if (strpos(R::getCell('select version()'), '10.3.9-MariaDB') === FALSE)
		return;
		R::nuke();
		R::bindFunc( 'read', 'location.point', 'asText' );
		R::bindFunc( 'write', 'location.point', 'GeomFromText' );
		R::store(R::dispense('location'));
		R::freeze( TRUE );
		try {
			R::find('location');
			fail();
		} catch( SQL $exception ) {
			pass();
		}
		R::freeze( FALSE );
		try {
			R::find('location');
			pass();
		} catch( SQL $exception ) {
			fail();
		}
		$location = R::dispense( 'location' );
		$location->point = 'POINT(14 6)';
		R::store($location);
		$columns = R::inspect( 'location' );
		asrt( $columns['point'], 'point' );
		$location = $location->fresh();
		asrt( $location->point, 'POINT(14 6)' );
		R::nuke();
		$location = R::dispense( 'location' );
		$location->point = 'LINESTRING(0 0,1 1,2 2)';
		R::store($location);
		$columns = R::inspect( 'location' );
		asrt( $columns['point'], 'linestring' );
		$location->bustcache = 2;
		R::store($location);
		$location = $location->fresh();
		asrt( $location->point, 'LINESTRING(0 0,1 1,2 2)' );
		R::nuke();
		$location = R::dispense( 'location' );
		$location->point = 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))';
		R::store($location);
		$columns = R::inspect( 'location' );
		asrt( $columns['point'], 'polygon' );
		$location->bustcache = 4;
		R::store($location);
		$location = $location->fresh();
		asrt( $location->point, 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))' );
		R::bindFunc( 'read', 'location.point', NULL );
		$location->bustcache = 1;
		R::store($location);
		$location = $location->fresh();
		asrt( ( $location->point === 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))' ), FALSE );
		$filters = AQueryWriter::getSQLFilters();
		asrt( is_array( $filters ), TRUE );
		asrt( count( $filters ), 2 );
		asrt( isset( $filters[ QueryWriter::C_SQLFILTER_READ] ), TRUE );
		asrt( isset( $filters[ QueryWriter::C_SQLFILTER_WRITE] ), TRUE );
		R::bindFunc( 'read', 'place.point', 'asText' );
		R::bindFunc( 'write', 'place.point', 'GeomFromText' );
		R::bindFunc( 'read', 'place.line', 'asText' );
		R::bindFunc( 'write', 'place.line', 'GeomFromText' );
		R::nuke();
		$place = R::dispense( 'place' );
		$place->point = 'POINT(13.2 666.6)';
		$place->line = 'LINESTRING(9.2 0,3 1.33)';
		R::store( $place );
		$columns = R::inspect( 'place' );
		asrt( $columns['point'], 'point' );
		asrt( $columns['line'], 'linestring' );
		$place = R::findOne('place');
		asrt( $place->point, 'POINT(13.2 666.6)' );
		asrt( $place->line, 'LINESTRING(9.2 0,3 1.33)' );
		R::bindFunc( 'read', 'place.point', NULL );
		R::bindFunc( 'write', 'place.point', NULL );
		R::bindFunc( 'read', 'place.line', NULL );
		R::bindFunc( 'write', 'place.line', NULL );
	}

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
				asrt( $writer->code( $key, TRUE ), MySQL::C_DATATYPE_SPECIFIED );
			}
		}
		asrt( $writer->code( MySQL::C_DATATYPE_SPECIAL_DATETIME ), MySQL::C_DATATYPE_SPECIFIED );
		asrt( $writer->code( "unknown" ), MySQL::C_DATATYPE_SPECIFIED );
		asrt( $writer->scanType( FALSE ), MySQL::C_DATATYPE_BOOL );
		asrt( $writer->scanType( TRUE ), MySQL::C_DATATYPE_BOOL );
		asrt( $writer->scanType( 0 ), MySQL::C_DATATYPE_BOOL );
		asrt( $writer->scanType( 1 ), MySQL::C_DATATYPE_BOOL );
		asrt( $writer->scanType( INF ), MySQL::C_DATATYPE_TEXT7 );
		asrt( $writer->scanType( NULL ), MySQL::C_DATATYPE_BOOL );
		asrt( $writer->scanType( 2 ), MySQL::C_DATATYPE_UINT32 );
		asrt( $writer->scanType( 255 ), MySQL::C_DATATYPE_UINT32 ); //no more uint8
		asrt( $writer->scanType( 256 ), MySQL::C_DATATYPE_UINT32 );
		asrt( $writer->scanType( -1 ), MySQL::C_DATATYPE_DOUBLE );
		asrt( $writer->scanType( 1.5 ), MySQL::C_DATATYPE_DOUBLE );
		asrt( $writer->scanType( "abc" ), MySQL::C_DATATYPE_TEXT7 );
		asrt( $writer->scanType( str_repeat( 'abcd', 100000 ) ), MySQL::C_DATATYPE_TEXT32 );
		asrt( $writer->scanType( "2001-10-10", TRUE ), MySQL::C_DATATYPE_SPECIAL_DATE );
		asrt( $writer->scanType( "2001-10-10 10:00:00", TRUE ), MySQL::C_DATATYPE_SPECIAL_DATETIME );
		asrt( $writer->scanType( "2001-10-10" ), MySQL::C_DATATYPE_TEXT7 );
		asrt( $writer->scanType( "2001-10-10 10:00:00" ), MySQL::C_DATATYPE_TEXT7 );
		asrt( $writer->scanType( "1.23", TRUE ), MySQL::C_DATATYPE_SPECIAL_MONEY );
		asrt( $writer->scanType( "12.23", TRUE ), MySQL::C_DATATYPE_SPECIAL_MONEY );
		asrt( $writer->scanType( "124.23", TRUE ), MySQL::C_DATATYPE_SPECIAL_MONEY );
		asrt( $writer->scanType( str_repeat( "lorem ipsum", 100 ) ), MySQL::C_DATATYPE_TEXT16 );
		$writer->widenColumn( "testtable", "c1", MySQL::C_DATATYPE_UINT32 );
		$writer->addColumn( "testtable", "special", MySQL::C_DATATYPE_SPECIAL_DATE );
		$cols = $writer->getColumns( "testtable" );
		asrt( $writer->code( $cols['special'], TRUE ), MySQL::C_DATATYPE_SPECIAL_DATE );
		asrt( $writer->code( $cols['special'], FALSE ), MySQL::C_DATATYPE_SPECIFIED );
		$writer->addColumn( "testtable", "special2", MySQL::C_DATATYPE_SPECIAL_DATETIME );
		$cols = $writer->getColumns( "testtable" );
		asrt( $writer->code( $cols['special2'], TRUE ), MySQL::C_DATATYPE_SPECIAL_DATETIME );
		asrt( $writer->code( $cols['special'], FALSE ), MySQL::C_DATATYPE_SPECIFIED );
		$cols = $writer->getColumns( "testtable" );
		asrt( $writer->code( $cols["c1"] ), MySQL::C_DATATYPE_UINT32 );
		$writer->widenColumn( "testtable", "c1", MySQL::C_DATATYPE_DOUBLE );
		$cols = $writer->getColumns( "testtable" );
		asrt( $writer->code( $cols["c1"] ), MySQL::C_DATATYPE_DOUBLE );
		$writer->widenColumn( "testtable", "c1", MySQL::C_DATATYPE_TEXT7 );
		$cols = $writer->getColumns( "testtable" );
		asrt( $writer->code( $cols["c1"] ), MySQL::C_DATATYPE_TEXT7 );
		$writer->widenColumn( "testtable", "c1", MySQL::C_DATATYPE_TEXT8 );
		$cols = $writer->getColumns( "testtable" );
		asrt( $writer->code( $cols["c1"] ), MySQL::C_DATATYPE_TEXT8 );
		$id  = $writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "lorem ipsum" ) ) );
		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );
		asrt( $row[0]["c1"], "lorem ipsum" );
		$writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "ipsum lorem" ) ), $id );
		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );
		asrt( $row[0]["c1"], "ipsum lorem" );
		$writer->deleteRecord( "testtable", array( "id" => array( $id ) ) );
		$row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );
		asrt( empty( $row ), TRUE );
		$writer->addColumn( "testtable", "c2", MySQL::C_DATATYPE_UINT32 );
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
		asrt( $cols['date'], 'varchar(191)' );
		$bean       = R::dispense( 'bean' );
		$bean->date = '2011-10-10';
		R::store( $bean );
		$cols = R::getColumns( 'bean' );
		asrt( $cols['date'], 'varchar(191)' );
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
	 * Test money types.
	 *
	 * @return void
	 */
	public function testTypesMon()
	{
		$bean       = R::dispense( 'bean' );
		$bean->amount = '22.99';
		R::store( $bean );
		$cols = R::getColumns( 'bean' );
		asrt( $cols['amount'], 'decimal(10,2)' );
		R::nuke();
		$bean       = R::dispense( 'bean' );
		$bean->amount = '-22.99';
		R::store( $bean );
		$cols = R::getColumns( 'bean' );
		asrt( $cols['amount'], 'decimal(10,2)' );
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
		asrt( $cols['title'], 'varchar(191)' );
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

	/**
	 * Can we manually add a MySQL time column?
	 *
	 * @return void
	 */
	public function testTime()
	{
		R::nuke();
		$clock = R::dispense('clock');
		$clock->time = '10:00:00';
		$clock->setMeta('cast.time', 'time');
		R::store( $clock );
		$columns = R::inspect('clock');
		asrt( $columns['time'], 'time' );
		$clock = R::findOne('clock');
		$clock->time = '12';
		R::store($clock);
		$clock = R::findOne('clock');
		$time = $clock->time;
		asrt( ( strpos( $time, ':' ) > 0 ), TRUE );
	}

	/**
	 * Can we use the 'ignoreDisplayWidth'-feature for MySQL 8
	 * compatibility?
	 *
	 * @return void
	 */
	public function testWriterFeature()
	{
		$adapter = R::getToolBox()->getDatabaseAdapter();
		$writer = new \RedBeanPHP\QueryWriter\MySQL( $adapter );
		$writer->useFeature('ignoreDisplayWidth');
		asrt($writer->typeno_sqltype[MySQL::C_DATATYPE_BOOL],' TINYINT UNSIGNED ');
		asrt($writer->typeno_sqltype[MySQL::C_DATATYPE_UINT32],' INT UNSIGNED ');
		asrt($writer->sqltype_typeno['tinyint unsigned'],MySQL::C_DATATYPE_BOOL);
		asrt($writer->sqltype_typeno['int unsigned'],MySQL::C_DATATYPE_UINT32);
		//Can we also pass invalid features without errors?
		$writer->useFeature('nonsense');
		pass();
	}

	/**
	 * Can we pass an options array to Writer Constructor?
	 *
	 * @return void
	 */
	public function testWriterOptions()
	{
		$adapter = R::getToolBox()->getDatabaseAdapter();
		$writer = new \RedBeanPHP\QueryWriter\MySQL( $adapter, array('noInitcode'=>TRUE) );
		pass();
	}
}
