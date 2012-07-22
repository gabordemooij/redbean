<?php
/**
 * RedUNIT_Mysql
 * @file 			RedUNIT/Mysql.php
 * @description		Parent class for all MySQL tests.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_CUBRID extends RedUNIT {
	/*
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('CUBRID');
	}
}