<?php
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
		R::store($village);
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
		asrt(count($myVillages),2)
		
		
		
		
		echo PHP_EOL;
			
	}
}
