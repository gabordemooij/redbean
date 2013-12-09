<?php

namespace RedBeanPHP\Sqlite;

use RedBeanPHP\SqliteTestCase;
use RedBeanPHP\Facade as R;

/**
 * Port of RedUNIT_Sqlite_Parambind
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
class ParameterBindTest extends SqliteTestCase
{
    /**
     * Test parameter binding with SQLite.
     *
     * @return void
     */
    public function testParamBindWithSQLite()
    {
        $toolbox = R::$toolbox;
        $adapter = $toolbox->getDatabaseAdapter();
        $writer  = $toolbox->getWriter();
        $redbean = $toolbox->getRedBean();
        $pdo     = $adapter->getDatabase();

        $this->assertSame(123, (int) $adapter->getCell( "SELECT 123" ));
        $this->assertSame(987, (int) $adapter->getCell( "SELECT ?", array( "987" ) ));
        $this->assertSame(989, (int) $adapter->getCell( "SELECT ?+?", array( "987", "2" ) ));
        $this->assertSame(92, (int) $adapter->getCell(
            "SELECT :numberOne+:numberTwo",
            array( ":numberOne" => 42, ":numberTwo" => 50 ))
        );

        $pair = $adapter->getAssoc( "SELECT 'thekey','thevalue' " );

        $this->assertTrue( is_array( $pair ));
        $this->assertCount(1, $pair);
        $this->assertArrayHasKey('thekey', $pair);
        $this->assertSame("thevalue", $pair["thekey"]);

        // testpack( 'Test whether we can properly bind and receive NULL values' );

        $this->assertSame('NULL', $adapter->getCell( 'SELECT :nil ', array( ':nil' => 'NULL' ) ));
        $this->assertNull( $adapter->getCell( 'SELECT :nil ', array( ':nil' => NULL ) ));

        $this->assertSame('NULL', $adapter->getCell( 'SELECT ? ', array( 'NULL' ) ) );
        $this->assertNull( $adapter->getCell( 'SELECT ? ', array( NULL ) ) );
    }
}
