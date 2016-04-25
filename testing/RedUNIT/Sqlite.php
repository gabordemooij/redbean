<?php

namespace RedUNIT;

/**
 * Sqlite
 *
 * The Sqlite class is the parent class of all SQLite3 specific test
 * classes.
 *
 * @file    RedUNIT/Sqlite.php
 * @desc    Base class for all SQLite specific tests.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Sqlite extends RedUNIT
{
	/**
	 * Returns a list of drivers for which this driver supports
	 * 'test rounds'. This class only supports the SQLite3 driver.
	 *
	 * @see RedUNIT::getTargetDrivers() for details.
	 *
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array( 'sqlite' );
	}
}
