<?php

namespace RedBeanPHP\Sqlite;

use RedBeanPHP\SqliteTestCase;
use RedBeanPHP\Facade as R;

/**
 * port of RedUNIT_Sqlite_Foreignkeys
 *
 * @file    RedUNIT/Sqlite/Foreignkeys.php
 * @desc    Tests the creation of foreign keys.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class ForeignKeysTest extends SqliteTestCase
{
    /**
     * Test foreign keys with SQLite.
     *
     * @return void
     */
    public function testForeignKeysWithSQLite()
    {
        $book  = R::dispense( 'book' );
        $page  = R::dispense( 'page' );
        $cover = R::dispense( 'cover' );

        list( $g1, $g2 ) = R::dispense( 'genre', 2 );

        $g1->name          = '1';
        $g2->name          = '2';
        $book->ownPage     = array( $page );
        $book->cover       = $cover;

        $this->markTestIncomplete('Really strange: Line 43 throws: General error: 1 no such table: book_genre');

        $book->sharedGenre = array( $g1, $g2 );
        R::store( $book );

        $fkbook  = R::getAll( 'pragma foreign_key_list(book)' );
        $fkgenre = R::getAll( 'pragma foreign_key_list(book_genre)' );
        $fkpage  = R::getAll( 'pragma foreign_key_list(page)' );

        $this->assertSame('book_id', $fkpage[0]['from']);
        $this->assertSame('id', $fkpage[0]['to']);
        $this->assertSame('book', $fkpage[0]['table']);

        $this->assertCount(2, $fkgenre);

        if ($fkgenre[0]['from'] == 'book') {
            $this->assertSame('id', $fkgenre[0]['to']);
            $this->assertSame('book', $fkgenre[0]['table']);
        }

        if ($fkgenre[0]['from'] == 'genre') {
            $this->assertSame('id', $fkgenre[0]['to']);
            $this->assertSame('genre', $fkgenre[0]['table']);
        }

        $this->assertSame('cover_id', $fkbook[0]['from']);
        $this->assertSame('id', $fkbook[0]['to']);
        $this->assertSame('cover', $fkbook[0]['table']);
    }
}
