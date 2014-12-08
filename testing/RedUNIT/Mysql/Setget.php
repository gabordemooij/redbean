<?php

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;

/**
 * Setget
 *
 * @file    RedUNIT/Mysql/Setget.php
 * @desc    Tests whether values are stored correctly.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Setget extends Mysql
{
    /**
     * Test numbers.
     *
     * @return void
     */
    public function testNumbers()
    {
        asrt(setget("-1"), "-1");
        asrt(setget(-1), "-1");

        asrt(setget("-0.25"), "-0.25");
        asrt(setget(-0.25), "-0.25");

        asrt(setget("0.12345678"), "0.12345678");
        asrt(setget(0.12345678), "0.12345678");

        asrt(setget("-0.12345678"), "-0.12345678");
        asrt(setget(-0.12345678), "-0.12345678");

        asrt(setget("2147483647"), "2147483647");
        asrt(setget(2147483647), "2147483647");

        asrt(setget(-2147483647), "-2147483647");
        asrt(setget("-2147483647"), "-2147483647");

        asrt(setget("2147483648"), "2147483648");
        asrt(setget("-2147483648"), "-2147483648");

        asrt(setget("199936710040730"), "199936710040730");
        asrt(setget("-199936710040730"), "-199936710040730");

        //Architecture dependent... only test this if you are sure what arch
        //asrt(setget("2147483647123456"),"2.14748364712346e+15");
        //asrt(setget(2147483647123456),"2.14748364712e+15");
    }

    /**
     * Test dates.
     *
     * @return void
     */
    public function testDates()
    {
        asrt(setget("2010-10-11"), "2010-10-11");

        asrt(setget("2010-10-11 12:10"), "2010-10-11 12:10");

        asrt(setget("2010-10-11 12:10:11"), "2010-10-11 12:10:11");

        asrt(setget("x2010-10-11 12:10:11"), "x2010-10-11 12:10:11");
    }

    /**
     * Test strings.
     *
     * @return void
     */
    public function testStrings()
    {
        asrt(setget("a"), "a");

        asrt(setget("."), ".");

        asrt(setget("\""), "\"");

        asrt(setget("just some text"), "just some text");
    }

    /**
     * Test booleans.
     *
     * @return void
     */
    public function testBool()
    {
        asrt(setget(true), "1");
        asrt(setget(false), "0");

        asrt(setget("TRUE"), "TRUE");
        asrt(setget("FALSE"), "FALSE");
    }

    /**
     * Test NULL.
     *
     * @return void
     */
    public function testNull()
    {
        asrt(setget("NULL"), "NULL");
        asrt(setget("NULL"), "NULL");

        asrt(setget("0123", 1), "0123");
        asrt(setget("0000123", 1), "0000123");

        asrt(setget(null), null);

        asrt((setget(0) == 0), true);
        asrt((setget(1) == 1), true);

        asrt((setget(true) == TRUE), true);
        asrt((setget(false) == FALSE), true);
    }
}
