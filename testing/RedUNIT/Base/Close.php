<?php
/**
 * RedUNIT_Base_Close 
 * @file 			RedUNIT/Base/Close.php
 * @description		Tests bean closing functionality.
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
		
		R::close();
		asrt(is_null(R::$adapter->getDatabase()->getPDO()),true);
		
	}
	
}