<?php

namespace RedUNIT;

/**
 * Blackhole
 *
 * The Blackhole class is the parent class of all tests that do not require a
 * specific database connection.
 *
 * @file    RedUNIT/Blackhole.php
 * @desc    Tests that do not require a database or can just use the base SQLite driver.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Blackhole extends RedUNIT
{
	/**
	 * Returns a list of drivers for which this driver supports
	 * 'test rounds'. This class does not specify any drivers and
	 * can therefore be used as the base class of all tests not
	 * requiring a specific database connection.
	 *
	 * @see RedUNIT::getTargetDrivers() for details.
	 *
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array();
	}
}
