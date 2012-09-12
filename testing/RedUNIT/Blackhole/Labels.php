<?php
/**
 * RedUNIT_Blackhole_Import
 * 
 * @file 			RedUNIT/Blackhole/Labels.php
 * @description		Tests Facade Label functions.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole_Labels extends RedUNIT_Blackhole {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		$meals = R::dispenseLabels('meal',array('meat','fish','vegetarian'));
		asrt(is_array($meals),true);
		asrt(count($meals),3);
		foreach($meals as $m) {
			asrt(($m instanceof RedBean_OODBBean),true);
		}
		$listOfMeals = implode(',',R::gatherLabels($meals));
		asrt($listOfMeals,'fish,meat,vegetarian');
		
	}
}