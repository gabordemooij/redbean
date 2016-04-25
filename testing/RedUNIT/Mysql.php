<?php

namespace RedUNIT;

/**
 * Mysql
 *
 * The Mysql class is the parent class of all MySQL and MariaDB
 * specific test classes.
 *
 * @file    RedUNIT/Mysql.php
 * @desc    Base class for all tests that test support for MySQL/MariaDB database.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Mysql extends RedUNIT
{
	/**
	 * Returns a list of drivers for which this driver supports
	 * 'test rounds'. This class only supports
	 * the MySQL/MariaDB driver.
	 *
	 * @see RedUNIT::getTargetDrivers() for details.
	 *
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql' );
	}
}
