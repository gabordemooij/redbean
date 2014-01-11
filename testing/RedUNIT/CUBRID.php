<?php 
 
namespace RedUNIT;

/**
 * RedUNIT_CUBRID
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
	/*
	 * What drivers should be loaded for this test pack?
	 * 
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array( 'CUBRID' );
	}
}
