<?php

class RedUNIT_Base_Typechecking extends RedUNIT_Base {
	

	
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
 