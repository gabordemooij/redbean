<?php
/**
 * RedUNIT_Oracle_Base
 * 
 * @file 			RedUNIT/Oracle/Base.php
 * @description		Basic tests for Oracle database.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Oracle_Base extends RedUNIT_Oracle {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		R::nuke();
		$village = R::dispense('village');
		$village->name = 'Lutry';
		$id =R::store($village);
		$village = R::load('village',$id);
		asrt($village->name,'Lutry');
		list($mill,$tavern) = R::dispense('building',2);
		$village->ownBuilding = array($mill,$tavern); //replaces entire list
		$id =R::store($village);    
		asrt($id,1);
		$village = R::load('village',$id);
		asrt(count($village->ownBuilding),2);
		
		$village2 = R::dispense('village');
		
		$army = R::dispense('army');
		$village->sharedArmy[] = $army;
		$village2->sharedArmy[] = $army;
		
		R::store($village);
		$id = R::store($village2);
		
		$village =R::load('village',$id);
		$army = $village->sharedArmy;
		$myVillages = R::related($army,'village');
		asrt(count($myVillages),2);
		
		
		
		
		echo PHP_EOL;
			
	}
}
