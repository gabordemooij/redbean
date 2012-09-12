<?php
/**
 * RedUNIT_Base_Boxing 
 * 
 * @file 			RedUNIT/Base/Boxing.php
 * @description		Tests bean boxing and unboxing functionality.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Boxing extends RedUNIT_Base {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		R::nuke();
		
		$bean = R::dispense('boxedbean')->box();
		R::trash($bean);
		pass();
		
		
		$bean = R::dispense('boxedbean');
		$bean->sharedBoxbean = R::dispense('boxedbean')->box();
		R::store($bean);
		pass();
		
		
		$bean = R::dispense('boxedbean');
		$bean->ownBoxedbean = R::dispense('boxedbean')->box();
		R::store($bean);
		pass();
		
		
		$bean = R::dispense('boxedbean');
		$bean->other = R::dispense('boxedbean')->box();
		R::store($bean);
		pass();
		
		
		$bean = R::dispense('boxedbean');
		$bean->title = 'MyBean';
		$box = $bean->box();
		asrt(($box instanceof Model_Boxedbean),true);
		R::store($box);
		
		
	}
	
}



class Model_Boxedbean extends RedBean_SimpleModel { }