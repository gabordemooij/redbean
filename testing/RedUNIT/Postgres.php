<?php

namespace RedUNIT;

/**
 * Postgres
 *
 * The Postgres class is the parent class of all PostgreSQL specific
 * test classes.
 *
 * @file    RedUNIT/Postgres.php
 * @desc    Base class for all PostgreSQL specific tests.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Postgres extends RedUNIT
{
	/**
	 * Returns a list of drivers for which this driver supports
	 * 'test rounds'. This class only supports the PostgreSQL driver.
	 *
	 * @see RedUNIT::getTargetDrivers() for details.
	 *
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array( 'pgsql' );
	}
}
