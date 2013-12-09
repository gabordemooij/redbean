<?php

namespace RedBeanPHP\Sqlite;

use RedBeanPHP\SqliteTestCase;
use RedBeanPHP\Facade as R;

/**
 * Port of RedUNIT_Sqlite_Setget
 *
 * @file    RedUNIT/Sqlite/Setget.php
 * @desc    Tests whether values are stored correctly.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RebuildTest extends SqliteTestCase
{
    /**
     * Test SQLite table rebuilding.
     *
     * @return void
     */
    public function testRebuilder()
    {
        $toolbox = R::$toolbox;
        $adapter = $toolbox->getDatabaseAdapter();
        $writer  = $toolbox->getWriter();
        $redbean = $toolbox->getRedBean();
        $pdo     = $adapter->getDatabase();

        R::dependencies( array( 'page' => array( 'book' ) ) );

        $book = R::dispense( 'book' );
        $page = R::dispense( 'page' );
        $book->ownPage[] = $page;
        $id = R::store( $book );
        $book = R::load( 'book', $id );

        $this->assertCount(1, $book->ownPage);
        $this->assertSame(1, (int) R::getCell( 'SELECT COUNT(*) FROM page' ));

        R::trash( $book );
        $this->assertSame(0, (int) R::getCell( 'SELECT COUNT(*) FROM page' ));

        $book = R::dispense( 'book' );
        $page = R::dispense( 'page' );
        $book->ownPage[] = $page;

        $id = R::store( $book );
        $book = R::load( 'book', $id );
        $this->assertCount(1, $book->ownPage);
        $this->assertSame(1, (int) R::getCell( 'SELECT COUNT(*) FROM page' ));

        $book->added = 2;
        R::store( $book );
        $book->added = 'added';
        R::store( $book );
        R::trash( $book );

        $this->assertSame(0, (int) R::getCell( 'SELECT COUNT(*) FROM page' ));
    }
}
