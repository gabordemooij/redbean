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
		
		$sheep = R::dispense('sheep');
		$sheep->aname = 'Shawn';
		R::store($sheep);
		$sheep =(R::findAndExport('sheep',' aname = ? ',array('Shawn')));
		asrt(count($sheep),1);
		$sheep = array_shift($sheep);
		asrt(count($sheep),2);
		asrt($sheep['aname'],'Shawn');
		
		//$e = new RedBean_Plugin_BeanExport( R::$toolbox );
		
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