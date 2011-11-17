<?php

class RedUNIT_Base_Misc extends RedUNIT_Base {

	public function run() {
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		R::nuke();
		$track = R::dispense('track');
		$album = R::dispense('cd');
		$track->name = 'a';
		$track->orderNum = 1;
		$track2 = R::dispense('track');
		$track2->orderNum = 2;
		$track2->name = 'b';
		R::associate( $album, $track );
		R::associate( $album, $track2 );
		$tracks = R::related( $album, 'track');
		$track = array_shift($tracks);
		$track2 = array_shift($tracks);
		$ab = $track->name.$track2->name;
		asrt(($ab=='ab' || $ab=='ba'),true);
		
		$t = R::dispense('person');
		$s = R::dispense('person');
		$s2 = R::dispense('person');
		$t->name = 'a';
		$t->role = 'teacher';
		$s->role = 'student';
		$s2->role = 'student';
		$s->name = 'a';
		$s2->name = 'b';
		R::associate($t, $s);
		R::associate($t, $s2);
		$students = R::related($t, 'person', ' role = ?  ',array("student"));
		$s = array_shift($students);
		$s2 = array_shift($students);
		asrt(($s->name=='a' || $s2->name=='a'),true);
		asrt(($s->name=='b' || $s2->name=='b'),true);
		$s= R::relatedOne($t, 'person', ' role = ?  ',array("student"));
		asrt($s->name,'a');
		//empty classroom
		R::clearRelations($t, 'person', $s2);
		$students = R::related($t, 'person', ' role = ?  ',array("student"));
		asrt(count($students),1);
		$s = reset($students);
		asrt($s->name, 'b');

		testpack('transactions');
		R::nuke();
		R::begin();
		$bean = R::dispense('bean');
		R::store($bean);
		R::commit();
		asrt(R::count('bean'),1);
		R::wipe('bean');
		R::freeze(1);
		R::begin();
		$bean = R::dispense('bean');
		R::store($bean);
		R::rollback();
		asrt(R::count('bean'),0);
		R::freeze(false);
		
		testpack("test old cooker");
		$post = array(
			"book"=>array("type"=>"book","title"=>"programming the C64"),
			"book2"=>array("type"=>"book","id"=>1,"title"=>"the art of doing nothing"),
			"book3"=>array("type"=>"book","id"=>1),
			"associations"=>array(
				array("book-book2"),array("page:2-book"),array("0")
			),
			"somethingelse"=>0
		);
		$beans = R::cooker($post);
		asrt(count($beans["can"]),3);
		asrt(count($beans["pairs"]),2);
		asrt($beans["can"]["book"]->getMeta("tainted"),true);
		asrt($beans["can"]["book2"]->getMeta("tainted"),true);
		asrt($beans["pairs"][0][0]->title,"programming the C64");
		
		testpack('genSlots');
		asrt(R::genSlots(array('a','b')),'?,?');				
		asrt(R::genSlots(array('a')),'?');
		asrt(R::genSlots(array()),'');
		
		
		
				
		testpack('FUSE models cant touch nested beans in update() - issue 106');
		R::nuke();
		
		$spoon = R::dispense('spoon');
		$spoon->name = 'spoon for test bean';
		$deep = R::dispense('deep');
		$deep->name = 'deepbean';
		$item = R::dispense('item');
		$item->val = 'Test';
		$item->deep = $deep;
		
		$test = R::dispense('test');
		$test->item = $item;
		$test->sharedSpoon[] = $spoon;
		
		
		$test->isNowTainted = true;
		$id=R::store($test); 
		$test = R::load('test',$id);
		asrt($test->item->val,'Test2');
		$can = reset($test->ownCan);
		$spoon = reset($test->sharedSpoon);
		asrt($can->name,'can for bean');
		asrt($spoon->name,'S2');
		asrt($test->item->deep->name,'123');
		asrt(count($test->ownCan),1);
		asrt(count($test->sharedSpoon),1);
		asrt(count($test->sharedPeas),10);
		asrt(count($test->ownChip),9);
		
		R::nuke();




	$coffee = R::dispense('coffee');
	$coffee->size = 'XL';
	$coffee->ownSugar = R::dispense('sugar',5);
	
	$id = R::store($coffee);
	
	
	$coffee=R::load('coffee',$id);
	asrt(count($coffee->ownSugar),3);
	$coffee->ownSugar = R::dispense('sugar',2);
	$id = R::store($coffee);
	$coffee=R::load('coffee',$id);
	asrt(count($coffee->ownSugar),2);
	
	
	
	$cocoa = R::dispense('cocoa');
	$cocoa->name = 'Fair Cocoa';
	list($taste1,$taste2) = R::dispense('taste',2);
	$taste1->name = 'sweet';
	$taste2->name = 'bitter';
	$cocoa->ownTaste = array($taste1, $taste2);
	R::store($cocoa);
	
	$cocoa->name = 'Koko';
	R::store($cocoa);
	}

}





class Model_Cocoa extends RedBean_SimpleModel {
	public function update() {
		//print_r($this->sharedTaste);
	}
}

class Model_Taste extends RedBean_SimpleModel {
	public function after_update() {
		asrt(count($this->bean->ownCocoa),0);
	}
}


class Model_Coffee extends RedBean_SimpleModel {

  public function update() {
  
  	
   	while (count($this->bean->ownSugar)>3) {
   		array_pop($this->bean->ownSugar);
   	}
  }

}


class Model_Test extends RedBean_SimpleModel {

  public function update() {
    if($this->bean->item->val) {
      $this->bean->item->val='Test2';
      $can = R::dispense('can');
      $can->name = 'can for bean';
      $s = reset($this->bean->sharedSpoon);
      $s->name = "S2";
      $this->bean->item->deep->name = '123';	      
      $this->bean->ownCan[] = $can;
      $this->bean->sharedPeas = R::dispense('peas',10);
      $this->bean->ownChip = R::dispense('chip',9);
      
    }
  }

}

