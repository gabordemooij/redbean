<?php
/**
 * MySQL tests.
 *
 * @file    RedUNIT/Mysql.php
 * @desc    Base class for all tests that test support for MySQL/MariaDB database.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Mysql extends RedUNIT {
	/*
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('mysql');
	}
}