<?php
/**
 * RedUNIT_Base_Export 
 * @file 			RedUNIT/Base/Export.php
 * @description		Tests export functions for beans.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Export extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		//Export with parents / embedded objects
		R::nuke();
		$wines = R::dispense('wine',3);
		$wines[0]->name = 'Cabernet Franc';
		$wines[1]->name = 'Chardonnay';
		$wines[2]->name = 'Malbec';
		$shelves = R::dispense('shelf',2);
		$shelves[0]->number = 1;
		$shelves[1]->number = 2;
		$cellar = R::dispense('cellar');
		$cellar->name = 'My Cellar';
		$cellar->ownShelf = $shelves;
		$shelves[0]->ownWine = array($wines[0],$wines[1]);
		$shelves[1]->ownWine[] = $wines[2];
		$id = R::store($cellar);
		$wine = R::load('wine',$wines[1]->id);
		$list1 = R::exportAll(array($wine,$shelves[1]));
		$list2 = R::exportAll(array($wine,$shelves[1]),true);
		
		asrt($list1[0]['name'],'Chardonnay');
		asrt(isset($list1[0]['shelf']),false);
		asrt(isset($list1[0]['shelf_id']),true);
		asrt(isset($list1[0]['shelf']['cellar']),false);
		
		asrt($list2[0]['name'],'Chardonnay');
		asrt(isset($list2[0]['shelf']),true);
		asrt(intval($list2[0]['shelf']['number']),1);
		asrt(isset($list2[0]['shelf']['ownWine']),false);
		asrt(isset($list2[0]['shelf']['cellar']),true);
		asrt(isset($list2[0]['shelf']['cellar']['name']),true);
		asrt(isset($list2[0]['shelf_id']),true);
		
		asrt(intval($list1[1]['number']),2);
		asrt(isset($list1[1]['ownWine']),true);
		asrt(isset($list1[1]['cellar']),false);
		asrt(isset($list1[1]['cellar']['name']),false);
		
		asrt(intval($list2[1]['number']),2);
		asrt(isset($list2[1]['ownWine']),true);
		asrt(isset($list2[1]['cellar']),true);
		asrt(isset($list2[1]['cellar']['name']),true);
		
		
		
		R::nuke();
		$sheep = R::dispense('sheep');
		$sheep->aname = 'Shawn';
		R::store($sheep);
		$sheep =(R::findAndExport('sheep',' aname = ? ',array('Shawn')));
		asrt(count($sheep),1);
		$sheep = array_shift($sheep);
		asrt(count($sheep),2);
		asrt($sheep['aname'],'Shawn');
		
		R::nuke();
		testpack('Extended export algorithm, feature issue 105');
		
		$city = R::dispense('city');
		$people = R::dispense('person',10);
		$me = reset($people);
		$him = end($people);
		$city->sharedPeople = $people;
		$me->name = 'me';
		$suitcase = R::dispense('suitcase');
		$him->suitcase = $suitcase;
		$him->ownShoe = R::dispense('shoe',2);
		R::store($city);
		$id = $him->getID();
		
		$data = R::exportAll($city);
		$data = reset($data);
		asrt(isset($data['sharedPerson']),true);
		asrt(count($data['sharedPerson']),10);
		$last = end($data['sharedPerson']);
		asrt(($last['suitcase_id']>0),true);
		
		$data = R::exportAll($him);
		$data = reset($data);
		asrt(isset($data['ownShoe']),true);
		asrt(count($data['ownShoe']),2);
		asrt(count($data['sharedCity']),1);
		
		
		
				
	}

}