<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\Facade as Facade;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Dispense
 *
 * @file    RedUNIT/Base/Dispense.php
 * @desc    Tests bean dispensing functionality.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Dispense extends Base
{
    /**
     * Test dispense.
     *
     * @return void
     */
    public function testBasicsDispense()
    {
        $redbean = R::getRedBean();

        // Can we dispense a bean?
        $page = $redbean->dispense("page");

        // Does it have a meta type?
        asrt(((bool) $page->getMeta("type")), true);

        // Does it have an ID?
        asrt(isset($page->id), true);

        // Type should be 'page'
        asrt(($page->getMeta("type")), "page");

        // ID should be 0 because bean does not exist in database yet.
        asrt(($page->id), 0);

        // Try some faulty dispense actions.
        foreach (array( "", ".", "-") as $value) {
            try {
                $redbean->dispense($value);

                fail();
            } catch (RedException $e) {
                pass();
            }
        }

        $bean = $redbean->dispense("testbean");

        $bean["property"] = 123;
        $bean["abc"]      = "def";

        asrt($bean["property"], 123);
        asrt($bean["abc"], "def");
        asrt($bean->abc, "def");

        asrt(isset($bean["abd"]), false);
        asrt(isset($bean["abc"]), true);
    }

    /**
     * Tests the facade-only dispenseAll method.
     *
     * @return void
     */
    public function testDispenseAll()
    {
        list($book, $page) = Facade::dispenseAll('book,page');
        asrt(($book instanceof OODBBean), true);
        asrt(($page instanceof OODBBean), true);
        asrt($book->getMeta('type'), 'book');
        asrt($page->getMeta('type'), 'page');

        list($book, $page, $texts, $mark) = R::dispenseAll('book,page,text*2,mark');
        asrt(($book instanceof OODBBean), true);
        asrt(($page instanceof OODBBean), true);
        asrt(is_array($texts), true);
        asrt(($mark instanceof OODBBean), true);
        asrt($book->getMeta('type'), 'book');
        asrt($page->getMeta('type'), 'page');
        asrt($mark->getMeta('type'), 'mark');
        asrt($texts[0]->getMeta('type'), 'text');
        asrt($texts[1]->getMeta('type'), 'text');

        list($eggs, $milk, $butter) = R::dispenseAll('eggs*3,milk*1,butter*9');
        asrt(count($eggs), 3);
        asrt(($milk instanceof OODBBean), true);
        asrt(count($butter), 9);

        list($eggs, $milk, $butter) = R::dispenseAll('eggs*3,milk*1,butter*9', true);
        asrt(count($eggs), 3);
        asrt(count($milk), 1);
        asrt(count($eggs), 3);

        list($beer) = R::dispenseAll('beer*0', true);
        asrt(is_array($beer), true);
        asrt(count($beer), 0);

        list($beer) = R::dispenseAll('beer*0', false);
        asrt(is_array($beer), false);
        asrt(is_null($beer), true);
        asrt(count($beer), 0);
    }

    /**
     * Tests different return values of dispense().
     *
     * @return void
     */
    public function testDispenseArray()
    {
        $oodb = R::getRedBean();
        $array = $oodb->dispense('book', 0, true);
        asrt(is_array($array), true);
        $array = $oodb->dispense('book', 1, true);
        asrt(is_array($array), true);
        $array = $oodb->dispense('book', 2, true);
        asrt(is_array($array), true);
        $array = R::dispense('book', 0, true);
        asrt(is_array($array), true);
        $array = R::dispense('book', 1, true);
        asrt(is_array($array), true);
        $array = R::dispense('book', 2, true);
        asrt(is_array($array), true);

        $array = $oodb->dispense('book', 0, false);
        asrt(is_array($array), false);
        asrt(is_null($array), true);
        $array = $oodb->dispense('book', 1, false);
        asrt(is_array($array), false);
        asrt(($array instanceof OODBBean), true);
        $array = $oodb->dispense('book', 2, false);
        asrt(is_array($array), true);
        $array = R::dispense('book', 0, false);
        asrt(is_array($array), false);
        $array = R::dispense('book', 1, false);
        asrt(is_array($array), false);
        $array = R::dispense('book', 2, false);
        asrt(is_array($array), true);

        $array = $oodb->dispense('book', 0);
        asrt(is_array($array), false);
        asrt(is_null($array), true);
        $array = $oodb->dispense('book', 1);
        asrt(is_array($array), false);
        asrt(($array instanceof OODBBean), true);
        $array = $oodb->dispense('book', 2);
        asrt(is_array($array), true);
        $array = R::dispense('book', 0);
        asrt(is_array($array), false);
        $array = R::dispense('book', 1);
        asrt(is_array($array), false);
        $array = R::dispense('book', 2);
        asrt(is_array($array), true);
    }
}
