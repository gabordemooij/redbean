<?php
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
		asrt( R::$adapter->getDatabase()->isConnected(), true );

		R::close();

		asrt( R::$adapter->getDatabase()->isConnected(), false );

		// Can we create a database using empty setup?
		R::setup();

		$id = R::store( R::dispense( 'bean' ) );

		asrt( ( $id > 0 ), true );

		// Test freeze via kickstart in setup
		$toolbox = RedBean_Setup::kickstart( 'sqlite:/tmp/bla.txt', null, null, true );

		asrt( $toolbox->getRedBean()->isFrozen(), true );

		// Test Oracle setup
		$toolbox = RedBean_Setup::kickstart( 'oracle:test-value', 'test', 'test', false );

		asrt( ( $toolbox instanceof RedBean_ToolBox ), true );
	}
}

if ( !class_exists( 'RedBean_Driver_OCI' ) ) {
	class RedBean_Driver_OCI
	{
	}
}
if ( !class_exists( 'RedBean_QueryWriter_Oracle' ) ) {
	class RedBean_QueryWriter_Oracle extends RedBean_QueryWriter_SQLiteT
	{
	}
}


