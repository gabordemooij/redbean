<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\QueryWriter\SQLiteT as SQLiteT;

/**
 * Close
 *
 * Tests whether we can close the database connection.
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
		R::getDatabaseAdapter()->setOption( 'setInitQuery', NULL );
		asrt( R::getDatabaseAdapter()->getDatabase()->isConnected(), TRUE );
		R::close();
		asrt( R::getDatabaseAdapter()->getDatabase()->isConnected(), FALSE );
	}
}

