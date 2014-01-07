<?php 

use \RedBean\Setup as Setup;
use \RedBean\ToolBox as ToolBox;
use \RedBean\Driver\OCI as OCI;
use \RedBean\QueryWriter\Oracle as Oracle;
use \RedBean\QueryWriter\SQLiteT as SQLiteT; 
/**
 * RedUNIT_Base_Close
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
class RedUNIT_Base_Close extends RedUNIT_Base
{
	
	/**
	 * Test closing database connection.
	 * 
	 * @return void
	 */
	public function testClose()
	{
		asrt( R::$adapter->getDatabase()->isConnected(), TRUE );

		R::close();

		asrt( R::$adapter->getDatabase()->isConnected(), FALSE );

		// Can we create a database using empty setup?
		R::setup();

		$id = R::store( R::dispense( 'bean' ) );

		asrt( ( $id > 0 ), TRUE );

		// Test freeze via kickstart in setup
		$toolbox = Setup::kickstart( 'sqlite:/tmp/bla.txt', NULL, NULL, TRUE );

		asrt( $toolbox->getRedBean()->isFrozen(), TRUE );

		// Test Oracle setup
		$toolbox = Setup::kickstart( 'oracle:test-value', 'test', 'test', FALSE );

		asrt( ( $toolbox instanceof ToolBox ), TRUE );
	}
}

if ( !class_exists( 'OCI' ) ) {
	class OCI
	{
	}
}
if ( !class_exists( 'Oracle' ) ) {
	class Oracle extends SQLiteT
	{
	}
}


