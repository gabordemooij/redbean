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
		$myAccount = $accounts[0];
		$myAccountID = $accounts[0]->id;
		$my = $me = $persons[1];
		$myID = $persons[1]->id;
		$otherAccountID = $accounts[1]->id;
		$otherAccount2ID = $accounts[2]->id;
		$myPartnerID = $persons[0]->id;
		$otherPersonID = $persons[2]->id;
		$otherBankID = $banks[1]->id;
		
		//People are allowed to open a new account.
		$defaultPolicies = array(
			array('types'=>'account','policy'=>'n'),
			array('beans'=>array($accounts[0]),'policy'=>'w'),
			array('beans'=>array($accounts[0]->sharedPerson),'policy'=>'r')
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
		
		//hacker behaves correctly, inspects own account
		$hacker = array(
			'account'=>array(
				'type'=>'account','id'=>(int)$myAccountID,
				'money'=>'0'
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			pass();
		}
		catch(RedBean_Exception_Security $e) {
			fail();
		}
		
		
		//hacker tries to access other account
		$hacker = array(
			'account'=>array(
				'type'=>'account','id'=>$otherAccountID,
				'money'=>'0'
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			//fail no write access for other account
			pass();
		}
		
		//hacker tries to move account to other bank
		$hacker = array(
			'account'=>array(
				'type'=>'account','id'=>$myAccountID,
				'bank_id'=>$otherBankID
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			//fail, no read access for other bank
			pass();
		}
		
		//hacker tries to change personal information
		$hacker = array(
			'account'=>array(
				'type'=>'account','id'=>$myAccountID,
				'sharedPerson'=>array(
					'0'=>array('type'=>'person','id'=>$myID,'name'=>'nobody')
				)
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			//fail, no write access for person
			pass();
		}
		
		
		//hacker is allowed to access own personal information
		$hacker = array(
			'account'=>array(
				'type'=>'account','id'=>$myAccountID,
				'me'=>array('type'=>'person','id'=>$myID)
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			pass();
		}
		catch(RedBean_Exception_Security $e) {
			//fail, no write access for person
			fail();
		}
		
		//hacker is allowed to access partner information
		$hacker = array(
			'partner'=>array(
				'type'=>'person','id'=>$myPartnerID,
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			pass();
		}
		catch(RedBean_Exception_Security $e) {
			//fail, no write access for person
			fail();
		}
		
		//hacker is not allowed to change partner information
		$hacker = array(
			'partner'=>array(
				'type'=>'person','id'=>$myPartnerID,
				'name'=>'me too!'
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			//fail, no write access for person
			pass();
		}
		
		//hacker is not access bank information
		$hacker = array(
			'bank'=>array(
				'type'=>'bank','id'=>$otherBankID,
			)
		);
		
		try {
			R::graph($hacker,false,$defaultPolicies);
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			pass();
		}
		
		//test policy system itself
		$cooker = new RedBean_Cooker;
		$cooker->setToolbox(R::$toolbox);
		
		try {
			$cooker->addPolicy($me,'r');
			pass();
		}
		catch(RedBean_Exception_Security $e) {
			fail();
		}
		
		try {
			$cooker->addPolicy($me,'w');
			pass();
		}
		catch(RedBean_Exception_Security $e) {
			fail();
		}
		
		try {
			$cooker->allowCreationOfTypes('person');
			pass();
		}
		catch(RedBean_Exception_Security $e) {
			fail();
		}
		
		
		
		//invalid policy code
		try {
			$cooker->addPolicy($me,'?');
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			pass();
		}
		
	}
}
