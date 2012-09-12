<?php
/**
 * RedUNIT_Plugin
 * 
 * @file 			RedUNIT/Plugin.php
 * @description		Parent class for all tests that should be ran for all plugin tests.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin extends RedUNIT {

	/**
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('mysql','pgsql','sqlite','CUBRID','oracle');
	}

}