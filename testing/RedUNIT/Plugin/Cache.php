<?php

require_once('../../RedBean/Plugin/Cache.php');

/**
 * RedUNIT_Plugin_Cache
 * @file 			RedUNIT/Plugin/Cache.php
 * @description		Tests caching plugin.
 * 					
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Cache extends RedUNIT {

	
	/**
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('sqlite');
	}
	
	public function run() {
		
		R::nuke();
		R::$redbean = new RedBean_Plugin_Cache(R::$writer);
		$cachedOODB = R::$redbean;
		$cachedOODB->addListener(R::$adapter);
		
		
		$p = R::dispense('person');
		$p->name = 'Tom';
		$id = R::store($p);
		$p = R::load('person',$id);
		asrt($cachedOODB->getHits(),0);
		asrt($cachedOODB->getMisses(),1);
		asrt($p->name,'Tom');
		$p = R::load('person',$id);
		asrt($cachedOODB->getHits(),1);
		asrt($cachedOODB->getMisses(),1);
		asrt($p->name,'Tom');
		$p = R::load('person',$id);
		asrt($cachedOODB->getHits(),2);
		asrt($cachedOODB->getMisses(),1);
		asrt($p->name,'Tom');
		$cachedOODB->flushCache();
		$p = R::load('person',$id);
		asrt($cachedOODB->getHits(),2);
		asrt($cachedOODB->getMisses(),2);
		asrt($p->name,'Tom');
		
		$pizzas = R::dispense('pizza',4);
		$pizzas[0]->name = 'Funghi';
		$pizzas[1]->name = 'Quattro Fromaggi';
		$pizzas[2]->name = 'Tonno';
		$pizzas[3]->name = 'Caprese';
		R::storeAll($pizzas);
		$ids = array($pizzas[0]->id,$pizzas[1]->id,$pizzas[2]->id,$pizzas[3]->id);
		$pizzas = R::findAll('pizza');
		$cachedOODB->resetHits();
		$cachedOODB->resetMisses();
		asrt($cachedOODB->getHits(),0);
		asrt($cachedOODB->getMisses(),0);
		$pizzas = R::batch('pizza',$ids);
		asrt($cachedOODB->getHits(),4);
		asrt($cachedOODB->getMisses(),1);
		$pizzas = R::batch('pizza',$ids);
		asrt($cachedOODB->getHits(),5);
		asrt($cachedOODB->getMisses(),1);
		$id = array_pop($ids);
		$pizza = R::load('pizza',$id);
		asrt($cachedOODB->getHits(),6);
		asrt($cachedOODB->getMisses(),1);
		R::trash($pizza); //flushes cache
		$pizzas = R::batch('pizza',$ids);
		asrt($cachedOODB->getHits(),6);
		asrt($cachedOODB->getMisses(),5);
		$pizzas = R::batch('pizza',$ids);
		asrt($cachedOODB->getHits(),7);
		asrt($cachedOODB->getMisses(),5);
		$p = end($pizzas);
		$p->price = 7.00;
		$cachedOODB->keepCache();
		R::store($p);
		$pizzas = R::batch('pizza',$ids);
		asrt($cachedOODB->getHits(),8);
		asrt($cachedOODB->getMisses(),5);
		
		
	}	

}