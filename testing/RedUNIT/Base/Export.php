<?php

class RedUNIT_Base_Export extends RedUNIT_Base {

	public function run() {
		
		
		$sheep = R::dispense('sheep');
		$sheep->aname = 'Shawn';
		R::store($sheep);
		$sheep =(R::findAndExport('sheep',' aname = ? ',array('Shawn')));
		asrt(count($sheep),1);
		$sheep = array_shift($sheep);
		asrt(count($sheep),2);
		asrt($sheep['aname'],'Shawn');
		
		$e = new RedBean_Plugin_BeanExport( R::$toolbox );
		
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
		$him->ownShoes = R::dispense('shoe',2);
		R::store($city);
		$id = $him->getID();
		
		$e = new RedBean_Plugin_BeanExport(R::$toolbox);
		$e->loadSchema();
		
		$data = $e->exportLimited($me,true);
		$arr = ( reset( $data) );
		
		asrt(is_array($arr['sharedCity']),true);
		asrt(count($arr['sharedCity']),1);
		$s = reset($arr['sharedCity']);
		asrt(count($s['sharedPerson']),0);
		
		
		$data = $e->exportLimited($me,false);
		$arr = ( reset( $data) );
		
		asrt(is_array($arr['sharedCity']),true);
		asrt(count($arr['sharedCity']),1);
		$s = reset($arr['sharedCity']);
		asrt(count($s['sharedPerson']),10);
		asrt(count($s['sharedPerson'][$id]['ownShoe']),2);
		asrt(count($s['sharedPerson'][$id]['suitcase']),1);
		
		$data = $e->exportLimited($me,false,4);
		$arr = ( reset( $data) );
		
		asrt(is_array($arr['sharedCity']),true);
		asrt(count($arr['sharedCity']),1);
		$s = reset($arr['sharedCity']);
		asrt(count($s['sharedPerson']),10);
		asrt(count($s['sharedPerson'][$id]['ownShoe']),2);
		asrt(count($s['sharedPerson'][$id]['suitcase']),1);
		
		$data = $e->exportLimited($me,false,3);
		$arr = ( reset( $data) );
		asrt(is_array($arr['sharedCity']),true);
		asrt(count($arr['sharedCity']),1);
		$s = reset($arr['sharedCity']);
		asrt(count($s['sharedPerson']),10);
		asrt(count($s['sharedPerson'][$id]['ownShoe']),0);
		asrt(count($s['sharedPerson'][$id]['suitcase']),0);
		
		
		$data = $e->exportLimited($me,false,2);
		$arr = ( reset( $data) );
		asrt(is_array($arr['sharedCity']),true);
		asrt(count($arr['sharedCity']),1);
		$s = reset($arr['sharedCity']);
		asrt(count($s['sharedPerson']),0);
		
		$data = $e->exportLimited($me,false,1);
		$arr = ( reset( $data) );
		asrt(is_array($arr['sharedCity']),true);
		asrt(count($arr['sharedCity']),0);
		
		$data = $e->exportLimited($me,false,0);
		$arr = ( reset( $data) );
		asrt(count($arr),1);
		
		$data = $e->exportLimited($me,true,0);
		$arr = ( reset( $data) );
		asrt(count($arr),1);

		R::nuke();

		list($v1,$v2,$v3) = R::dispense('village',3);
		list($b1,$b2,$b3,$b4,$b5,$b6) = R::dispense('building',6);
		$amulets = R::dispense('amulet',4);
		list($a1,$a2) = R::dispense('army',2);
		
		$v1->name = 'Ole Town';
		$v2->name = 'Sandy winds';
		$v3->name = 'Autumn Hill';
		$b1->kind = 'pub';
		$b2->kind = 'farm';
		$b3->kind = 'mill';
		$b4->kind = 'tower';
		$b5->kind = 'shed';
		$b6->kind = 'shop';
		$i=0;
		foreach($amulets as $k=>$a) $amulets[$k]->name = 'Amulet '+(++$i);
		$a1->name = 'Army 1';
		$a2->name = 'Army 2';
		$world = R::dispense('world');
		$world->name = 'Middle Earth';
		
		$v1->ownBuilding = array($b1,$b4);
		$v2->ownBuilding = array($b3,$b5,$b6);
		$v3->ownBuilding = array($b2);
		$b2->ownAmulet = array($amulets[0],$amulets[1]);
		$b6->ownAmulet = array($amulets[2]);
		$b1->ownAmulet[] = $amulets[3];
		$v2->sharedArmy = array($a1,$a2);
		$v3->sharedArmy = array($a2);
		$v1->world = $world;
		$v2->universe = $world;
		
		
		
		R::store($v1);
		R::store($v2);
		R::store($v3);
		
		$v1 = R::load('village',$v1->getID());
		$v2 = R::load('village',$v2->getID());
		$v3 = R::load('village',$v3->getID());
		
		
		
		R::$toolbox->getWriter()->setBeanFormatter( new ExportBeanFormatter );
		$e->loadSchema();
		//print_r($e->export($v2));
		
		$export = $e->export($v2);
		$out = json_encode($export);
		$expected = '{"2":{"id":"2","name":"Sandy winds","world_id":null,"universe_id":"1","universe":{"1":{"id":"1","name":"Middle Earth","ownVillage":{"1":{"id":"1","name":"Ole Town","world_id":"1","universe_id":null,"world":{"1":null},"ownBuilding":{"1":{"id":"1","kind":"pub","village_id":"1","village":{"1":null},"ownAmulet":{"1":{"id":"1","name":"4","building_id":"1","building":{"1":null}}}},"2":{"id":"2","kind":"tower","village_id":"1","village":{"1":null},"ownAmulet":[]}},"sharedArmy":[]}}}},"ownBuilding":{"3":{"id":"3","kind":"mill","village_id":"2","village":{"2":null},"ownAmulet":[]},"4":{"id":"4","kind":"shed","village_id":"2","village":{"2":null},"ownAmulet":[]},"5":{"id":"5","kind":"shop","village_id":"2","village":{"2":null},"ownAmulet":{"2":{"id":"2","name":"3","building_id":"5","building":{"5":null}}}}},"sharedArmy":{"1":{"id":"1","name":"Army 1","sharedVillage":{"2":null}},"2":{"id":"2","name":"Army 2","sharedVillage":{"2":null,"3":{"id":"3","name":"Autumn Hill","world_id":null,"universe_id":null,"ownBuilding":{"6":{"id":"6","kind":"farm","village_id":"3","village":{"3":null},"ownAmulet":{"3":{"id":"3","name":"1","building_id":"6","building":{"6":null}},"4":{"id":"4","name":"2","building_id":"6","building":{"6":null}}}}},"sharedArmy":{"2":null}}}}}}}';
		asrt(preg_replace("/\W/","",trim($out)),preg_replace("/\W/","",trim($expected)));
		$export=R::exportAll($v2,true);
		$out = json_encode($export);
		asrt(preg_replace("/\W/","",trim($out)),preg_replace("/\W/","",trim($expected)));
		
				
		testpack("Test Export All");
		list($p1,$p2) = R::dispense("page",2);
		$p1->name = '1';
		$p2->name = '2';
		$arr = ( R::exportAll(array($p1,$p2)) );
		asrt(count($arr),2);
		asrt($arr[0]["name"],"1");
		asrt($arr[1]["name"],"2");
		//ignore arrays
		$o = new stdClass();
		$arr = ( R::exportAll(array($p1,array($p1),$o,$p2)) );
		asrt(count($arr),2);
		asrt($arr[0]["name"],"1");
		asrt($arr[1]["name"],"2");
		
		
		testpack("Test Export to objects");
		list($b1,$b2) = R::dispense('book',2);
		$b1->title = 'Notes from a small island';
		$b2->title = 'Neither here nor there';
		R::store($b1);
		R::store($b2);
		$exportArray = $b1->export();
		asrt(count($exportArray),2);
		$exportObject = new stdClass;
		$b1->exportToObj($exportObject);
		asrt(is_object($exportObject),true);
		asrt($exportObject->title,$b1->title);
		$objs = R::exportAllToObj(array($b1,$b2));
		asrt(count($objs),2);
		foreach($objs as $o) asrt(is_object($o),true);
		
		testpack("Test Simple Facade Prefix");
		droptables();
		R::prefix('bla');
		$t = R::dispense('testje');
		R::store($t);
		$tables = R::$writer->getTables();
		asrt(true,in_array('blatestje',$tables));
	
	
		


	
			
	}

}