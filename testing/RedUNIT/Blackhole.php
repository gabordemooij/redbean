<?php
/**
 * RedUNIT_Blackhole
 * 
 * @file 			RedUNIT/Blackhole.php
 * @description		Parent class for all tests that don't need a database connection.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole extends RedUNIT {

	public function getTargetDrivers() {
		return array();
	}

}