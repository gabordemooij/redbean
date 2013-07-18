<?php
/**
 * Blackhole tests.
 *
 * @file    RedUNIT/Blackhole.php
 * @desc    Tests that do not require a database or can just use the base SQLite driver.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole extends RedUNIT {

	/**
	 * Returns the drivers this test suite applies to.
	 * 
	 * @return array
	 */
	public function getTargetDrivers() {
		return array();
	}
}