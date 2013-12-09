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
class SetGetTest extends SqliteTestCase
{

    /**
     * @dataProvider dataProvider
     */
    public function testGetSet($value, $expected)
    {
        $bean = R::dispense( "page" );
        $bean->prop = $value;
        $id = R::store( $bean );
        $bean = R::load( "page", $id );
        $this->assertEquals($expected, $bean->prop);
    }

    public function dataProvider()
    {
        return [

            // numbers
            ["-1", "-1" ],
            [-1, "-1" ],

            ["-0.25", "-0.25" ],
            [-0.25, "-0.25" ],

            ["0.12345678", "0.12345678" ],
            [0.12345678, "0.12345678" ],

            ["-0.12345678", "-0.12345678" ],
            [-0.12345678, "-0.12345678" ],

            ["2147483647", "2147483647" ],
            [2147483647, "2147483647" ],

            [-2147483647, "-2147483647" ],
            ["-2147483647", "-2147483647" ],

            ["2147483648", "2147483648" ],
            ["-2147483648", "-2147483648" ],

            ["199936710040730", "199936710040730" ],
            ["-199936710040730", "-199936710040730" ],

            // dates
            ["2010-10-11", "2010-10-11"],
            ["2010-10-11 12:10", "2010-10-11 12:10"],
            ["2010-10-11 12:10:11", "2010-10-11 12:10:11"],
            ["x2010-10-11 12:10:11", "x2010-10-11 12:10:11"],

            // booleans
            [TRUE, "1"],
            [FALSE, "0"],

            ["TRUE", "TRUE"],
            ["FALSE", "FALSE"],

            // strings
            ["a", "a" ],
            [".", "." ],
            ["\"", "\"" ],
            ["just some text", "just some text" ],
        ];
    }

    /**
     * @dataProvider nullDataProvider
     */
    public function testNull($value, $expected)
    {
        $bean = R::dispense( "page" );
        $bean->prop = $value;
        $id = R::store( $bean );
        $bean = R::load( "page", $id );
        $this->assertTrue(($expected == $bean->prop));
    }

    public function nullDataProvider()
    {
        return [
            ["NULL", "NULL" ],
            ["NULL", "NULL" ],
            [NULL, NULL],

            [0, FALSE ],
            [1, TRUE ],

            [TRUE, TRUE ],
            [FALSE, FALSE ]
        ];
    }

}
