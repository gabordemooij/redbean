<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Arrays
 *
 * @file    RedUNIT/Base/Arrays.php
 * @desc    Tests the array interface of beans
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Arrays extends Base
{
    /**
     * Tests basic array access.
     *
     * @return void
     */
    public function testArrayAccess()
    {
        $bean = R::dispense('bean');
        $bean->name = 'bean';
        $bean->taste = 'salty';
        $properties = array();
        foreach ($bean as $key => $value) {
            $properties[ $key ] = $value;
        }
        asrt(count($bean), 3);
        asrt(count($properties), 3);
        asrt(isset($properties['id']), true);
        asrt(isset($properties['name']), true);
        asrt(isset($properties['taste']), true);

        $bean = R::dispense('bean');
        $bean['name'] = 'bean';
        $bean['taste'] = 'salty';
        $properties = array();
        foreach ($bean as $key => $value) {
            $properties[ $key ] = $value;
        }
        asrt(count($bean), 3);
        asrt(count($properties), 3);
        asrt(isset($properties['id']), true);
        asrt(isset($properties['name']), true);
        asrt(isset($properties['taste']), true);
    }

    /**
     * Tests array access with lists.
     *
     * @return void
     */
    public function testArrayAccessAndLists()
    {
        $book = R::dispense('book');
        $book['title'] = 'My Book';

        //Can we add a bean in list with array access?
        $book['ownPage'][] = R::dispense('page');
        $book['ownPage'][] = R::dispense('page');

        asrt(count($book), 3);

        $properties = array();
        foreach ($book as $key => $value) {
            $properties[ $key ] = $value;
        }

        asrt(count($properties), 3);

        //Dont reveal aliased x-own and -List in foreach-loop
        asrt(isset($properties['id']), true);
        asrt(isset($properties['title']), true);
        asrt(isset($properties['ownPage']), true);
        asrt(isset($properties['ownPageList']), false);
        asrt(isset($properties['xownPage']), false);
        asrt(isset($properties['xownPageList']), false);

        //But keep them countable
        asrt(count($book['ownPage']), 2);
        asrt(count($book['ownPageList']), 2);
        asrt(count($book['xownPage']), 2);
        asrt(count($book['xownPageList']), 2);

        //And reveal them with isset()
        asrt(isset($book['id']), true);
        asrt(isset($book['title']), true);
        asrt(isset($book['ownPage']), true);
        asrt(isset($book['ownPageList']), true);
        asrt(isset($book['xownPage']), true);
        asrt(isset($book['xownPageList']), true);

        //Can we add using the List alias?
        $book['ownPageList'][] = R::dispense('page');
        asrt(count($book['ownPage']), 3);
        asrt(count($book['ownPageList']), 3);
        asrt(count($book['xownPage']), 3);
        asrt(count($book['xownPageList']), 3);

        //Can we add using the x-mode alias?
        $book['ownPageList'][] = R::dispense('page');
        asrt(count($book['ownPage']), 4);
        asrt(count($book['ownPageList']), 4);
        asrt(count($book['xownPage']), 4);
        asrt(count($book['xownPageList']), 4);

        //Can we unset using array access?
        unset($book['ownPage']);
        asrt(isset($book['ownPage']), false);
        asrt(isset($book['ownPageList']), false);
        asrt(isset($book['xownPage']), false);
        asrt(isset($book['xownPageList']), false);
        $book['ownPage'] = array( R::dispense('page') );
        unset($book['xownPage']);
        asrt(isset($book['ownPage']), false);
        asrt(isset($book['ownPageList']), false);
        asrt(isset($book['xownPage']), false);
        asrt(isset($book['xownPageList']), false);
        $book['ownPage'] = array( R::dispense('page') );
        unset($book['ownPageList']);
        asrt(isset($book['ownPage']), false);
        asrt(isset($book['ownPageList']), false);
        asrt(isset($book['xownPage']), false);
        asrt(isset($book['xownPageList']), false);
        $book['ownPage'] = array( R::dispense('page') );
        unset($book['xownPageList']);
        asrt(isset($book['ownPage']), false);
        asrt(isset($book['ownPageList']), false);
        asrt(isset($book['xownPage']), false);
        asrt(isset($book['xownPageList']), false);

        //does it work with shared lists as well?
        $book['sharedCategory'] = array( R::dispense('category') );
        asrt(count($book), 3);

        $properties = array();
        foreach ($book as $key => $value) {
            $properties[ $key ] = $value;
        }

        asrt(isset($properties['sharedCategory']), true);
        asrt(isset($properties['sharedCategoryList']), false);

        asrt(isset($book['sharedCategory']), true);
        asrt(isset($book['sharedCategoryList']), true);

        asrt(count($book['sharedCategory']), 1);
        asrt(count($book['sharedCategoryList']), 1);

        $book['sharedCategory'][] = R::dispense('category');

        asrt(count($book['sharedCategory']), 2);
        asrt(count($book['sharedCategoryList']), 2);

        $book['sharedCategoryList'][] = R::dispense('category');

        asrt(count($book['sharedCategory']), 3);
        asrt(count($book['sharedCategoryList']), 3);
    }

    /**
     * Tests array access with parent beans.
     *
     * @return void
     */
    public function testArrayAccessWithBeans()
    {
        $book = R::dispense('bean');
        $book['author'] = R::dispense('author');
        asrt(isset($book['author']), true);
        asrt(count($book), 2);
        $book['author']['name'] = 'me';
        asrt($book['author']['name'], 'me');
        $book['author']['address'] = R::dispense('address');
        $book['author']['ownTagList'][] = R::dispense('tag');
        asrt(isset($book['author']['address']), true);
        asrt(isset($book['author']['ownTag']), true);
        asrt(count($book['author']['ownTag']), 1);
        asrt(isset($book['author']['xownTag']), true);
        asrt(count($book['author']['xownTag']), 1);
        asrt(isset($book['author']['ownTagList']), true);
        asrt(count($book['author']['ownTagList']), 1);
        asrt(isset($book['author']['xownTagList']), true);
        asrt(count($book['author']['xownTagList']), 1);
        unset($book['author']);
        asrt(isset($book['author']), false);
        asrt(count($book), 1);
    }

    /**
     * Tests array access with CRUD operations.
     *
     * @return void
     */
    public function testArrayAccessWithCRUD()
    {
        R::nuke();
        $book = R::dispense('book');
        $book['ownPageList'] = R::dispense('page', 3);
        R::store($book);

        $book = $book->fresh();
        //note that isset first returns false, so you can check if a list is loaded
        asrt(isset($book['ownPage']), false);
        asrt(isset($book['ownPageList']), false);
        asrt(isset($book['xownPage']), false);
        asrt(isset($book['xownPageList']), false);
        //count triggers load...
        asrt(count($book['ownPage']), 3);
        asrt(isset($book['ownPage']), true);
        asrt(isset($book['ownPageList']), true);
        asrt(isset($book['xownPage']), true);
        asrt(isset($book['xownPageList']), true);
        $book = $book->fresh();
        asrt(count($book['xownPage']), 3);
        $book = $book->fresh();
        asrt(count($book['ownPageList']), 3);
        $book = $book->fresh();
        asrt(count($book['xownPageList']), 3);

        $book['ownPage'][] = R::dispense('page');
        R::store($book);

        $book = $book->fresh();
        asrt(count($book['ownPage']), 4);
        $book = $book->fresh();
        asrt(count($book['xownPage']), 4);
        $book = $book->fresh();
        asrt(count($book['ownPageList']), 4);
        $book = $book->fresh();
        asrt(count($book['xownPageList']), 4);

        //does dependency still work?
        $book['xownPageList'] = array();
        R::store($book);
        $book = $book->fresh();
        asrt(count($book['ownPage']), 0);
        $book = $book->fresh();
        asrt(count($book['xownPage']), 0);
        $book = $book->fresh();
        asrt(count($book['ownPageList']), 0);
        $book = $book->fresh();
        asrt(count($book['xownPageList']), 0);

        //does shared list work as well?
        $book['sharedTag'] = R::dispense('tag', 2);
        R::store($book);

        $book = $book->fresh();
        //note that isset first returns false, so you can check if a list is loaded
        asrt(isset($book['sharedTagList']), false);
        asrt(isset($book['sharedTag']), false);
        //count triggers load...
        asrt(count($book['sharedTagList']), 2);
        asrt(count($book['sharedTag']), 2);
        asrt(isset($book['sharedTagList']), true);
        asrt(isset($book['sharedTag']), true);

        $book['sharedTag'][] = R::dispense('tag');
        R::store($book);

        $book = $book->fresh();
        asrt(count($book['sharedTagList']), 3);
        asrt(count($book['sharedTag']), 3);

        $book['sharedTagList'][] = R::dispense('tag');
        R::store($book);

        $book = $book->fresh();
        asrt(count($book['sharedTagList']), 4);
        asrt(count($book['sharedTag']), 4);

        //does it also work with cross-shared
        $book['sharedBookList'][] = R::dispense('book');
        R::store($book);
        $book = $book->fresh();
        asrt(isset($book['sharedBookList']), false);
        asrt(count($book['sharedBookList']), 1);
        $first = reset($book['sharedBookList']);
        $id = $first['id'];
        asrt(count($book['sharedBookList'][$id]['sharedBookList']), 1);

        $properties = array();
        foreach ($book as $key => $value) {
            $properties[ $key ] = $value;
        }

        asrt(count($properties), 2);
        $keys = array_keys($properties);
        sort($keys);
        asrt(implode(',', $keys), 'id,sharedBook');
    }
}
