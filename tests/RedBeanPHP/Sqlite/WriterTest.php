<?php

namespace RedBeanPHP\Sqlite;

use RedBeanPHP\SqliteTestCase;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\QueryWriter\SQLiteT as SQLiteT;
use RedBeanPHP\RedException\SQL as SQL;

/**
 * WriterTest
 *
 * @file    test/Sqlite/WriterTest.php
 * @desc    Tests Sqlite writer specific functions.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class WriterTest extends SqliteTestCase
{

    /**
     * Test scanning and coding.
     *
     * @return void
     */
    public function testScanningAndCoding()
    {
        $toolbox = R::$toolbox;
        $adapter = $toolbox->getDatabaseAdapter();
        $writer  = $toolbox->getWriter();
        $redbean = $toolbox->getRedBean();
        $pdo     = $adapter->getDatabase();

        $a = new AssociationManager( $toolbox );

        $this->assertFalse(in_array("testtable", $writer->getTables()));

        $writer->createTable( "testtable" );

        $this->assertTrue (in_array("testtable", $writer->getTables()));
        $this->assertCount( 1, $writer->getColumns( "testtable" ));
        $this->assertTrue ( in_array( "id", array_keys( $writer->getColumns( "testtable" ) ) ));
        $this->assertFalse( in_array( "c1", array_keys( $writer->getColumns( "testtable" ) ) ));

        $writer->addColumn( "testtable", "c1", 1 );

        $this->assertCount( 2, $writer->getColumns( "testtable" ));
        $this->assertTrue ( in_array( "c1", array_keys( $writer->getColumns( "testtable" ) ) ));

        array_walk($writer->sqltype_typeno, function ($type, $key) use ($writer) {
            $this->assertSame($type, $writer->code( $key ));
        });

        $this->assertSame(99, $writer->code( "unknown" ));
        $this->assertSame(SQLiteT::C_DATATYPE_INTEGER,  $writer->scanType( FALSE ));
        $this->assertSame(SQLiteT::C_DATATYPE_INTEGER,  $writer->scanType( NULL ));
        $this->assertSame(SQLiteT::C_DATATYPE_INTEGER,  $writer->scanType( 2 )  );
        $this->assertSame(SQLiteT::C_DATATYPE_INTEGER,  $writer->scanType( 255 ));
        $this->assertSame(SQLiteT::C_DATATYPE_INTEGER,  $writer->scanType( 256 ));
        $this->assertSame(SQLiteT::C_DATATYPE_INTEGER,  $writer->scanType( -1 ) );
        $this->assertSame(SQLiteT::C_DATATYPE_NUMERIC,  $writer->scanType( 1.5 ));
        $this->assertSame(SQLiteT::C_DATATYPE_TEXT,     $writer->scanType( INF ));
        $this->assertSame(SQLiteT::C_DATATYPE_TEXT,     $writer->scanType( "abc" ));
        $this->assertSame(SQLiteT::C_DATATYPE_NUMERIC,  $writer->scanType( '2010-10-10' ));
        $this->assertSame(SQLiteT::C_DATATYPE_NUMERIC,  $writer->scanType( '2010-10-10 10:00:00' ));
        $this->assertSame(SQLiteT::C_DATATYPE_TEXT,     $writer->scanType( str_repeat( "lorem ipsum", 100 ) ));

        $writer->widenColumn( "testtable", "c1", 2 );
        $cols = $writer->getColumns( "testtable" );

        $this->assertSame( 2, $writer->code( $cols["c1"] ));

        // $id = $writer->insertRecord("testtable", array("c1"), array(array("lorem ipsum")));
        $id  = $writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "lorem ipsum" ) ) );
        $row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );
        $this->assertSame("lorem ipsum", $row[0]["c1"]);

        $writer->updateRecord( "testtable", array( array( "property" => "c1", "value" => "ipsum lorem" ) ), $id );
        $row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );
        $this->assertSame("ipsum lorem", $row[0]["c1"] );

        $writer->deleteRecord( "testtable", array( "id" => array( $id ) ) );
        $row = $writer->queryRecord( "testtable", array( "id" => array( $id ) ) );
        $this->assertEmpty($row);
    }

    /**
     * (FALSE should be stored as 0 not as '')
     */
    public function testZeroIssue()
    {

        $toolbox = R::$toolbox;
        $redbean = $toolbox->getRedBean();
        $bean = $redbean->dispense( "zero" );
        $bean->zero  = FALSE;
        $bean->title = "bla";
        $redbean->store( $bean );
        $this->assertCount(1, $redbean->find( "zero", array(), " zero = 0 " ));
    }

    /**
     * Test ANSI92 issue in clearrelations
     */
    public function testClearrelations_ANSI92_issue()
    {
        $this->markTestIncomplete('This test has not been ported yet.');

        $toolbox = R::$toolbox;
        $redbean = $toolbox->getRedBean();
        $a = new AssociationManager( $toolbox );

        $book    = $redbean->dispense( "book" );
        $author1 = $redbean->dispense( "author" );
        $author2 = $redbean->dispense( "author" );
        $book->title = "My First Post";
        $author1->name = "Derek";
        $author2->name = "Whoever";
        $this->assertOnetoManyAssociation( $a, $book, $author1 );
        $this->assertOnetoManyAssociation( $a, $book, $author2 );
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
        $this->markTestIncomplete('This test has not been ported yet.');

        $this->skip();
        $toolbox = R::$toolbox;
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
     * @todo  It seems this test was a black hole. Let's keep it failing for later investigation.
     */
    public function testUncommonScenarios()
    {
        $this->markTestIncomplete('This test has not been ported yet.');

        $toolbox = R::$toolbox;
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

        $redbean->trash( $redbean->dispense( "bla" ) );

        $bean = $redbean->dispense( "bla" );
        $bean->name = 1;
        $bean->id   = 2;

        $redbean->trash( $bean );
    }

    /**
     * Test special data types.
     * @dataProvider specialDataTypesProvider
     */
    public function testSpecialDataTypes($value, $type)
    {
        $bean = R::dispense( 'bean' );
        $bean->test_column = $value;
        R::store( $bean );
        $cols = R::getColumns( 'bean' );
        $this->assertSame($type, $cols['test_column']);
    }

    public function specialDataTypesProvider()
    {
        return [
            ['someday', 'TEXT'],
            ['2011-10-10', 'NUMERIC']
        ];
    }

    /**
     * Emulates legacy function for use with older tests.
     */
    public function assertOnetoManyAssociation($a, \RedBeanPHP\OODBBean $bean1, \RedBeanPHP\OODBBean $bean2)
    {
        $type = $bean1->getMeta( "type" );

        $a->clearRelations( $bean2, $type );
        $a->associate( $bean1, $bean2 );

        if ( count( $a->related( $bean2, $type ) ) === 1 ) {
            // return $this;
        } else {
            throw new RedBean_Exception_SQL( "Failed to enforce 1-N Relation for $type " );
        }
    }
}
