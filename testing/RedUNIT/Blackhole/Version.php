<?php
/**
 * RedUNIT_Blackhole_Version 
 * 
 * @file 			RedUNIT/Blackhole/Version.php
 * @description		Tests identification features.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_Version extends RedUNIT_Blackhole {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */	
	public function run() {
		$version = R::getVersion();
		asrt(is_string($version),true);
	}
	
}