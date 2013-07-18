<?php
/**
 * Base tests.
 *
 * @file    RedUNIT/Base.php
 * @desc    Base class for all drivers that support all database systems.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base extends RedUNIT {

	/**
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('mysql','pgsql','sqlite','CUBRID','oracle');
	}

}