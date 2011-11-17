<?php
/**
 * Unit Test: Dispense
 * @file			RedUNIT/Testpacks/RedUNIT.php
 * @description		Provides the basic logic for any unit test in RedUNIT.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Dispense extends RedUNIT_Base {
	

	
	public function run() {
		
		$redbean = R::$redbean;
		
		//Can we dispense a bean?
		$page = $redbean->dispense("page");
		//Does it have a meta type?
		asrt(((bool)$page->getMeta("type")),true);
		//Does it have an ID?
		asrt(isset($page->id),true);
		//Type should be 'page'
		asrt(($page->getMeta("type")),"page");
		//ID should be 0 because bean does not exist in database yet.
		asrt(($page->id),0);
		//Try some faulty dispense actions.
		try {
			$redbean->dispense("");
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		try {
			$redbean->dispense(".");
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		try {
			$redbean->dispense("-");
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		
		$bean = $redbean->dispense("testbean");
		$bean["property"] = 123;
		$bean["abc"] = "def";
		asrt($bean["property"],123);
		asrt($bean["abc"],"def");
		asrt($bean->abc,"def");
		asrt(isset($bean["abd"]),false);
		asrt(isset($bean["abc"]),true);
		
	}
	
}