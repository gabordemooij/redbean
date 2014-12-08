<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Fusebox
 *
 * @file    RedUNIT/Blackhole/Fusebox.php
 * @desc    Tests Boxing/Unboxing of beans.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class Fusebox extends Blackhole
{
    /**
     * Test boxing.
     *
     * @return void
     */
    public function testBasicBox()
    {
        $soup          = R::dispense('soup');

        $soup->flavour = 'tomato';

        $this->giveMeSoup($soup->box());

        $this->giveMeBean($soup->box()->unbox());

        $this->giveMeBean($soup);
    }

    /**
     * Test type hinting with boxed model
     *
     * @param Model_Soup $soup
     */
    private function giveMeSoup(\Model_Soup $soup)
    {
        asrt(($soup instanceof \Model_Soup), true);

        asrt('A bit too salty', $soup->taste());

        asrt('tomato', $soup->flavour);
    }

    /**
     * Test unboxing
     *
     * @param OODBBean $bean
     */
    private function giveMeBean(OODBBean $bean)
    {
        asrt(($bean instanceof OODBBean), true);

        asrt('A bit too salty', $bean->taste());

        asrt('tomato', $bean->flavour);
    }
}
