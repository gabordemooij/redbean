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
		// Test whether we can select a specific feature set
		R::useFeatureSet('novice/latest');
		pass();
		R::useFeatureSet('latest');
		pass();
		R::useFeatureSet('5.3');
		pass();
		R::useFeatureSet('novice/5.3');
		pass();
		try {
			R::useFeatureSet('5.2');
			fail();
		} catch ( \Exception $e ) {
			asrt( $e->getMessage(), 'Unknown feature set label.' );
		}
		try {
			R::nuke();
			fail();
		} catch( \Exception $e ) {
			asrt( $e->getMessage(), 'The nuke() command has been disabled using noNuke() or R::feature(novice/...).' );
		}
		R::useFeatureSet('latest');

		//Close
		R::getDatabaseAdapter()->setOption( 'setInitQuery', NULL );
		asrt( R::getDatabaseAdapter()->getDatabase()->isConnected(), TRUE );
		R::close();
		asrt( R::getDatabaseAdapter()->getDatabase()->isConnected(), FALSE );
	}
}

