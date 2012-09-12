<?php
/**
 * RedUNIT_Base_Typechecking 
 * 
 * @file 			RedUNIT/Base/Typechecking.php
 * @description		Tests basic bean validation rules; invalid bean handling.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Typechecking extends RedUNIT_Base {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
 		$redbean = R::$redbean;
		 $bean = $redbean->dispense("page");
		//Set some illegal values in the bean; this should trugger Security exceptions.
		//Arrays are not allowed.
		$bean->name = array("1");
		try {
			$redbean->store($bean);
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		try {
			$redbean->check($bean);
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		$bean->name = new RedBean_OODBBean;
		try {
			$redbean->check($bean);
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		//Property names should be alphanumeric
		$prop = ".";
		$bean->$prop = 1;
		try {
			$redbean->store($bean);
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		try {
			$redbean->check($bean);
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		//Really...
		$prop = "-";
		$bean->$prop = 1;
		try {
			$redbean->store($bean);
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
		try {
			$redbean->check($bean);
			fail();
		}catch(RedBean_Exception_Security $e) {
			pass();
		}
	}
}
 