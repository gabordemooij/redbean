<?php
/**
 * RedUNIT_Blackhole_Version
 * 
 * @file    RedUNIT/Blackhole/Version.php
 * @desc    Tests identification features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
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