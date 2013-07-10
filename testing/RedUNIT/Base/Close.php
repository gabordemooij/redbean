<?php
/**
 * RedUNIT_Base_Close 
 * 
 * @file 			RedUNIT/Base/Close.php
 * @description		Tests database closing functionality.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Close extends RedUNIT_Base {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		asrt(R::$adapter->getDatabase()->isConnected(),true);
		R::close();
		asrt(R::$adapter->getDatabase()->isConnected(),false);
		//can we create a database using empty setup?
		R::setup();
		$id = R::store(R::dispense('bean'));
		asrt(($id > 0), true); //yup, seems to work.
		//test freeze via kickstart in setup
		$toolbox = RedBean_Setup::kickstart('sqlite:/tmp/bla.txt', null, null, true);
		asrt($toolbox->getRedBean()->isFrozen(), true);
		
		
		//test Oracle setup
		$toolbox = RedBean_Setup::kickstart('oracle:test-value', 'test', 'test', false);
		asrt(($toolbox instanceof RedBean_ToolBox), true);
	}
	
}


if (!class_exists('RedBean_Driver_OCI')) {
	class RedBean_Driver_OCI {}
}
if (!class_exists('RedBean_QueryWriter_Oracle')) {
	class RedBean_QueryWriter_Oracle extends RedBean_QueryWriter_SQLiteT {}
}


