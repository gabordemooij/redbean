<?php

/**
 * RedUNIT Shared Test Classes / Mock Objects
 * This file contains a collection of test classes that can be used by
 * and shared by tests.
 */

/**
 * Observable Mock
 * This is just for testing
 */
class ObservableMock extends RedBean_Observable {
	public function test( $eventname, $info ) {
		$this->signal($eventname, $info);
	}
}
/**
 * Observer Mock
 * This is just for testing
 */
class ObserverMock implements RedBean_Observer {
	public $event = false;
	public $info = false;
	public function onEvent($event, $info) {
		$this->event = $event;
		$this->info = $info;
	}
}
/**
 * Shared helper class for tests.
 * A Basic Bean Formatter.
 */
class BF extends RedBean_DefaultBeanFormatter {
	public function formatBeanTable($t) {
		return '_'.$t;
	}
}
/**
 * Shared helper class for tests.
 * A Basic Bean Formatter.
 */
class Fm implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "prefix_$table";}
	public function formatBeanID( $table ) {return $table."__id";}
	public function getAlias($a){return $a;}
}
/**
 * Shared helper class for tests.
 * A Basic Bean Formatter.
 */
class Fm2 implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "prefix_$table";}
	public function formatBeanID( $table ) {return $table."_id";}
	public function getAlias($a){return $a;}
}
/**
 * Shared helper class for tests.
 * A Basic Bean Formatter.
 */
class TestFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xx_$table";}
	public function formatBeanID( $table ) {return "id";}
	public function getAlias($a){ return $a; }
}
/**
 * Shared helper class for tests.
 * A Basic Bean Formatter.
 */
class MyTableFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {
		return "xx_$table";
	}
	public function formatBeanID( $table ) {
		return "id";
	}
	public function getAlias($a){ return '__';}
}
/**
 * Shared helper class for tests.
 * A Basic Bean Formatter.
 */
class MyBeanFormatter implements RedBean_IBeanFormatter{
    public function formatBeanTable($table) {
        return "cms_$table";
    }
    public function formatBeanID( $table ) {
        return "{$table}_id"; // append table name to id. The table should not inclide the prefix.
    }
    public function getAlias($a){ return '__'.$a;  }
}
/**
 * Shared helper class for tests.
 * A Basic Model Formatter for FUSE tests.
 */
class mymodelformatter implements RedBean_IModelFormatter{
	public function formatModel($model){
		return "my_weird_".$model."_model";
	}
}
/**
 * Shared helper class for tests.
 * Default Model Formatter to reset model formatting in FUSE tests.
 */
class DefaultModelFormatter implements RedBean_IModelFormatter {
	public function formatModel($model) {
		return 'Model_'.ucfirst($model);
	}
}
/**
 * Shared helper class for tests.
 * A Basic Model Formatter for FUSE tests.
 */
class my_weird_weirdo_model extends RedBean_SimpleModel {
	public function blah(){ return "yes!"; }
}
/**
 * Shared helper class for tests.
 * Default Bean Formatter to reset bean formatting rules for Format tests.
 */
class DF extends RedBean_DefaultBeanFormatter {}
/**
 * Shared helper class for tests.
 * Bean Formatter to test aliasing of beans in N:1 relations. See Aliasing tests.
 */
class Aliaser extends RedBean_DefaultBeanFormatter {
		public function getAlias($a){
			if ($a=='cover') return 'page'; else return $a;
		}
}
/**
 * Shared helper class for tests.
 * A test model to test FUSE functions.
 */
class Model_Band extends RedBean_SimpleModel {
	public function after_update() {}
	public function update() {
		if (count($this->ownBandmember)>4) {
			throw new Exception('too many!');
		}
	}
}
/**
 * Shared helper class for tests.
 * Bean Formatter to test aliasing of beans in N:1 relations. See Aliasing tests.
 */
class N1AndFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xy_$table";}
	public function formatBeanID( $table ) {return "theid";}
	public function getAlias($a){ return $a; }
}
/**
 * Shared helper class for tests.
 * Bean Formatter to test aliasing of beans in N:1 relations. See Aliasing tests.
 */
class Aliaser2 implements RedBean_IBeanFormatter {
    public function formatBeanID($t){ return 'id'; }
    public function formatBeanTable($t){ return $t; }
    public function getAlias($a){
            if ($a=='creator' || $a=='recipient') return 'user';
            return $a;
    }
}
/**
 * Shared helper class for tests.
 * Bean Formatter to test aliasing of beans in N:1 relations. See Aliasing tests.
 */		
class Alias3 extends RedBean_DefaultBeanFormatter {
	public function getAlias($type) {
		if ($type=='familyman' || $type=='buddy') return 'person';
		return $type;
	}
}
/**
 * Shared helper class for tests.
 * Bean Formatter to test aliasing of beans in N:1 relations. See Aliasing tests.
 */
class ExportBeanFormatter extends RedBean_DefaultBeanFormatter{
   public function getAlias( $type ) {
   	if ($type == 'universe') return 'world'; else return $type;
   }
}
/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Box extends RedBean_SimpleModel {
        public function delete() { $a = $this->bean->ownBottle;}
}
/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_CandyBar extends RedBean_SimpleModel {
	public function customMethod($custom) { return $custom."!"; }
}
/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Cocoa extends RedBean_SimpleModel {
	public function update() {
		//print_r($this->sharedTaste);
	}
}
/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Taste extends RedBean_SimpleModel {
	public function after_update() {
		asrt(count($this->bean->ownCocoa),0);
	}
}

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Coffee extends RedBean_SimpleModel {
  public function update() {
   	while (count($this->bean->ownSugar)>3) {
   		array_pop($this->bean->ownSugar);
   	}
  }
}

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
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

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
global $lifeCycle;
class Model_Bandmember extends RedBean_SimpleModel {
	public function open() {
		global $lifeCycle;
		$lifeCycle .= "\n called open: ".$this->id;
	}
	public function dispense(){
		global $lifeCycle;
		$lifeCycle .= "\n called dispense() ".$this->bean;
	}
	public function update() {
		global $lifeCycle;
		$lifeCycle .= "\n called update() ".$this->bean;
	}
	public function after_update(){
		global $lifeCycle;
		$lifeCycle .= "\n called after_update() ".$this->bean;
	}
	public function delete() {
		global $lifeCycle;
		$lifeCycle .= "\n called delete() ".$this->bean;
	}
	public function after_delete() {
		global $lifeCycle;
		$lifeCycle .= "\n called after_delete() ".$this->bean;
	}

}
