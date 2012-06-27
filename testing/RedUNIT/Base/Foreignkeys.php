<?php

/**
 * RedUNIT_Base_Foreignkeys
 *
 * @file 			RedUNIT/Base/Foreignkeys.php
 * @description		Tests foreign key handling and dynamic foreign keys with
 * 					dependency list.
 *
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Foreignkeys extends RedUNIT_Base implements RedBean_Observer {

	/**
	 * To log the queries
	 * @var array
	 */
	private $queries= array();
	
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 *
	 * @return void
	 */
	public function run() {
		//Unit test for dependency
		R::nuke();
		$can = $this->createBeanInCan();
		asrt(1, R::count('bean'));
		R::trash($can);
		asrt(1, R::count('bean')); //bean stays.
		R::nuke();
		R::dependencies(array('bean' => array('can')));
		$can = $this->createBeanInCan();
		asrt(1, R::count('bean'));
		R::trash($can);
		asrt(0, R::count('bean')); //bean gone.
		R::dependencies(array());
		$can = $this->createBeanInCan();
		asrt(1, R::count('bean'));
		R::trash($can);
		asrt(1, R::count('bean')); //bean stays, constraint removed

		R::nuke();
		$can = $this->createCanForBean();
		asrt(1, R::count('bean'));
		R::trash($can);
		asrt(1, R::count('bean'));
		R::nuke();
		R::dependencies(array('bean' => array('can')));
		$can = $this->createCanForBean();
		asrt(1, R::count('bean'));
		R::trash($can);
		asrt(0, R::count('bean'));
		R::dependencies(array());
		$can = $this->createCanForBean();
		asrt(1, R::count('bean'));
		R::trash($can);
		asrt(1, R::count('bean'));
		
		/**
		 * Issue #171
		 * The index name argument is not unique in processEmbeddedBean etc.
		 */
		R::nuke();
		R::$adapter->addEventListener('sql_exec',$this);
		$account = R::dispense('account');
		$user = R::dispense('user');
		$player = R::dispense('player');
		$account->ownUser[] = $user;
		R::store($account);
		asrt(strpos(implode(',',$this->queries),'index_foreignkey_user_account')!==false,true);
		$this->queries = array();
		$account->ownPlayer[] = $player;
		R::store($account);
		asrt(strpos(implode(',',$this->queries),'index_foreignkey_player_account')!==false,true);
		R::nuke();
		$this->queries = array();
		$account = R::dispense('account');
		$user = R::dispense('user');
		$player = R::dispense('player');
		$user->account = $account;
		R::store($user);
		asrt(strpos(implode(',',$this->queries),'index_foreignkey_user_account')!==false,true);
		$this->queries = array();
		$player->account = $account;
		R::store($player);
		asrt(strpos(implode(',',$this->queries),'index_foreignkey_player_account')!==false,true);
		
		
	}

	/**
	 * Test helper method.
	 * Creates a bean in a can. The bean will get a reference
	 * to the can and can be made dependent.
	 * 
	 * @return RedBean_OODBBean $can 
	 */	
	private function createBeanInCan() {
		$can = R::dispense('can');
		$bean = R::dispense('bean');
		$can->name = 'bakedbeans';
		$bean->taste = 'salty';
		$can->ownBean[] = $bean;
		R::store($can);
		return $can;
	}

	/**
	 * Test helper method.
	 * Creates a bean in a can beginning with the bean. The bean will get a reference
	 * to the can and can be made dependent.
	 * 
	 * @return RedBean_OODBBean $can 
	 */	
	private function createCanForBean() {
		$can = R::dispense('can');
		$bean = R::dispense('bean');
		$bean->can = $can;
		R::store($bean);
		return $can;
	}

	/**
	 * Log queries
	 * 
	 * @param string $event
	 * @param RedBean_Adapter $info 
	 */
	public function onEvent($event,$info) {
		$this->queries[] = $info->getSQL();
	}
	
}