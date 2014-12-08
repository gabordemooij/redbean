<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Close
 *
 * @file    RedUNIT/Base/Close.php
 * @desc    Tests database closing functionality.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Close extends Base
{
    /**
     * Test closing database connection.
     *
     * @return void
     */
    public function testClose()
    {
        asrt(R::getDatabaseAdapter()->getDatabase()->isConnected(), true);

        R::close();

        asrt(R::getDatabaseAdapter()->getDatabase()->isConnected(), false);
    }
}
