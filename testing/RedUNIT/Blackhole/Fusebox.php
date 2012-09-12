<?php
/**
 * RedUNIT_Blackhole_Fusebox
 * 
 * @file 			RedUNIT/Blackhole/Fusebox.php
 * @description		Tests Boxing/Unboxing of beans.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole_Fusebox extends RedUNIT_Blackhole {
	
	/**
	 * Test type hinting with boxed model
	 * 
	 * @param Model_Soup $soup 
	 */
	public function giveMeSoup(Model_Soup $soup) {
		asrt(($soup instanceof Model_Soup),true);
		asrt('A bit too salty',$soup->taste());
		asrt('tomato',$soup->flavour);
		
	}
	
	/**
	 * Test unboxing 
	 * 
	 * @param RedBean_OODBBean $bean 
	 */
	public function giveMeBean(RedBean_OODBBean $bean) {
		asrt(($bean instanceof RedBean_OODBBean),true);
		asrt('A bit too salty',$bean->taste());
		asrt('tomato',$bean->flavour);
	}
	
	/**
	 * Testing Fusebox
	 */
	public function run() {
		
		$soup = R::dispense('soup');
		$soup->flavour = 'tomato';
		$this->giveMeSoup($soup->box());
		$this->giveMeBean($soup->box()->unbox());
		$this->giveMeBean($soup);
		
	}
	
	
}


/**
 * A model to box soup models :)
 */
class Model_Soup extends RedBean_SimpleModel {
	
	public function taste() {
		return 'A bit too salty';
	}
}