<?php
/**
 * RedUNIT_Blackhole_Policy
 *  
 * @file 			RedUNIT/Blackhole/policy.php
 * @description		Tests the Cooker Policies, tries to circumvent
 *					policies to hack into the database.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_Policy extends RedUNIT_Blackhole {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		testpack('Test Cooker Policies');
		R::nuke();
		$city = R::dispense('city');
		$banks = R::dispense('bank',2);
		$accounts = R::dispense('account',3);
		$persons = R::dispense('person',4);
		$city->ownBank = $banks;
		$banks[0]->ownAccount = array($accounts[0],$accounts[1]);
		$banks[1]->ownAccount = array($accounts[2]);
		$accounts[0]->sharedPerson = array($persons[0],$persons[1]);
		$accounts[1]->sharedPerson = array($persons[2],$persons[3]);
		R::store($city);
		
		//People are allowed to open a new account.
		$defaultPolicies = array(
			array('types'=>'account','policy'=>'n'),
		);
		
		//hacker tries to create a new city
		$hacker = array(
			'city'=>array(
				array('type'=>'city','name'=>'Banksville')
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			pass();
		}
		
		
		
		
	}
}
