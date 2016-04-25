<?php

namespace RedUNIT;

/**
 * CUBRID
 *
 * The CUBRID class is the parent class for all CUBRID specific tests.
 *
 * @file    RedUNIT/CUBRID.php
 * @desc    Base class for all test classes that aim to test the CUBRID database support.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class CUBRID extends RedUNIT
{
	/**
	 * Returns a list of drivers for which this driver supports
	 * 'test rounds'. This class only supports the CUBRID driver.
	 *
	 * @see RedUNIT::getTargetDrivers() for details.
	 *
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array( 'CUBRID' );
	}
}
