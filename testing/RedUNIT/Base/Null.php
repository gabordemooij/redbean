<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Null
 *
 * @file    RedUNIT/Base/Null.php
 * @desc    Tests handling of NULL values.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Null extends Base
{
    /**
     * Tests whether we can NULLify a parent bean
     * page->book if the parent (book) is already
     * NULL. (isset vs array_key_exists bug).
     *
     * @return void
     */
    public function testUnsetParent()
    {
        R::nuke();
        $book = R::dispense('book');
        $book->title = 'My Book';
        $page = R::dispense('page');
        $page->text = 'Lorem Ipsum';
        $book->ownPage[] = $page;
        R::store($book);
        $page = $page->fresh();
        R::freeze(true);
        asrt((int) $page->book->id, (int) $book->id);
        unset($page->book);
        R::store($page);
        $page = $page->fresh();
        asrt((int) $page->book->id, (int) $book->id);
        $page->book = null;
        R::store($page);
        $page = $page->fresh();
        asrt($page->book, null);
        asrt($page->book_id, null);
        asrt($page->bookID, null);
        asrt($page->bookId, null);
        $page = R::dispense('page');
        $page->text = 'Another Page';
        $page->book = null;
        try {
            R::store($page);
            fail();
        } catch (\Exception $exception) {
            pass();
        }
        unset($page->book);
        R::store($page);
        $page = $page->fresh();
        $page->book = null; //this must set field id to NULL not ADD column!
        try {
            R::store($page);
            pass();
        } catch (\Exception $exception) {
            fail();
        }
        $page = $page->fresh();
        $page->book = null;
        R::store($page);
        $page = $page->fresh();
        asrt(is_null($page->book_id), true);
        $page->book = $book;
        R::store($page);
        $page = $page->fresh();
        asrt((int) $page->book->id, (int) $book->id);
        $page->book = null;
        R::store($page);
        asrt(is_null($page->book_id), true);
        asrt(is_null($page->book), true);
        R::freeze(false);
    }

    /**
     * Test nullifying aliased parent.
     *
     * @return void
     */
    public function testUnsetAliasedParent()
    {
        R::nuke();
        $book = R::dispense('book');
        $author = R::dispense('author');
        $book->coauthor = $author;
        R::store($book);
        $book = $book->fresh();
        asrt(is_null($book->fetchAs('author')->coauthor), false);
        unset($book->coauthor);
        R::store($book);
        $book = $book->fresh();
        asrt(is_null($book->fetchAs('author')->coauthor), false);
        $book->coauthor = null;
        R::store($book);
        $book = $book->fresh();
        asrt(is_null($book->fetchAs('author')->coauthor), true);
        R::trash($book);
        R::trash($author);
        R::freeze(true);
        $book = R::dispense('book');
        $author = R::dispense('author');
        $book->coauthor = $author;
        R::store($book);
        $book = $book->fresh();
        asrt(is_null($book->fetchAs('author')->coauthor), false);
        unset($book->coauthor);
        R::store($book);
        $book = $book->fresh();
        asrt(is_null($book->fetchAs('author')->coauthor), false);
        $book->coauthor = null;
        R::store($book);
        $book = $book->fresh();
        asrt(is_null($book->fetchAs('author')->coauthor), true);
        R::trash($book);
        R::trash($author);
        R::freeze(false);
    }

    /**
     * Test NULL handling, setting a property to NULL must
     * cause a change.
     *
     * @return void
     */
    public function testBasicNullHandling()
    {
        // NULL can change bean
        $bean      = R::dispense('bean');
        $bean->bla = 'a';

        R::store($bean);

        $bean = $bean->fresh();

        asrt($bean->hasChanged('bla'), false);

        $bean->bla = null;

        asrt($bean->hasChanged('bla'), true);

        // NULL test
        $page = R::dispense('page');
        $book = R::dispense('book');

        $page->title = 'a NULL page';
        $page->book  = $book;
        $book->title = 'Why NUll is painful..';

        R::store($page);

        $bookid = $page->book->id;

        unset($page->book);

        $id = R::store($page);

        $page = R::load('page', $id);

        $page->title = 'another title';

        R::store($page);

        pass();

        $page = R::load('page', $id);

        $page->title   = 'another title';
        $page->book_id = null;

        R::store($page);

        pass();
    }

    /**
     * Here we test whether the column type is set correctly.
     * Normally if you store NULL, the smallest type (bool/set) will
     * be selected. However in case of a foreign key type INT should
     * be selected because fks columns require matching types.
     *
     * @return void
     */
    public function ColumnType()
    {
        $book = R::dispense('book');
        $page = R::dispense('page');

        $book->ownPage[] = $page;

        R::store($book);

        pass();

        asrt($page->getMeta('cast.book_id'), 'id');
    }

    /**
     * Test meta column type.
     *
     * @return void
     */
    public function TypeColumn()
    {
        $book = R::dispense('book');
        $page = R::dispense('page');

        $page->book = $book;

        R::store($page);

        pass();

        asrt($page->getMeta('cast.book_id'), 'id');
    }
}
