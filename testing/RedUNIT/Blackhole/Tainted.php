<?php
/**
 * RedUNIT_Blackhole_Tainted 
 * @file 			RedUNIT/Blackhole/Tainted.php
 * @description		Tests tainted flag for OODBBean objects.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_Tainted extends RedUNIT_Blackhole {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		$redbean = R::$redbean;
		$spoon = $redbean->dispense("spoon");
		asrt($spoon->getMeta("tainted"),true);
		$spoon->dirty = "yes";
		asrt($spoon->getMeta("tainted"),true);
	}
}