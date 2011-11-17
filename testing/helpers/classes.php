<?php


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

//test optimizer icw table format
class BF extends RedBean_DefaultBeanFormatter {
	public function formatBeanTable($t) {
		return '_'.$t;
	}
}

class Fm implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "prefix_$table";}
	public function formatBeanID( $table ) {return $table."__id";}
	public function getAlias($a){return $a;}
}


class Fm2 implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "prefix_$table";}
	public function formatBeanID( $table ) {return $table."_id";}
	public function getAlias($a){return $a;}
}

class TestFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xx_$table";}
	public function formatBeanID( $table ) {return "id";}
	public function getAlias($a){ return $a; }
}

class MyTableFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {
		return "xx_$table";
	}
	public function formatBeanID( $table ) {
		return "id";
	}
	public function getAlias($a){ return '__';}
}

class MyBeanFormatter implements RedBean_IBeanFormatter{
    public function formatBeanTable($table) {
        return "cms_$table";
    }
    public function formatBeanID( $table ) {
        return "{$table}_id"; // append table name to id. The table should not inclide the prefix.
    }
    public function getAlias($a){ return '__'.$a;  }
}


class mymodelformatter implements RedBean_IModelFormatter{
	public function formatModel($model){
		return "my_weird_".$model."_model";
	}
}

class DefaultModelFormatter implements RedBean_IModelFormatter {
	public function formatModel($model) {
		return 'Model_'.ucfirst($model);
	}
}

class my_weird_weirdo_model extends RedBean_SimpleModel {
	public function blah(){ return "yes!"; }
}

class DF extends RedBean_DefaultBeanFormatter {}


class Aliaser extends RedBean_DefaultBeanFormatter {
		public function getAlias($a){
			if ($a=='cover') return 'page'; else return $a;
		}
}
class Model_Band extends RedBean_SimpleModel {

	public function after_update() {
	}

	public function update() {
		if (count($this->ownBandmember)>4) {
			throw new Exception('too many!');
		}
	}
}

class Aliaser2 implements RedBean_IBeanFormatter {
    public function formatBeanID($t){ return 'id'; }
    public function formatBeanTable($t){ return $t; }
    public function getAlias($a){
            if ($a=='creator' || $a=='recipient') return 'user';
            return $a;
    }
}
		
class Alias3 extends RedBean_DefaultBeanFormatter {
	public function getAlias($type) {
		if ($type=='familyman' || $type=='buddy') return 'person';
		return $type;
	}
}

class ExportBeanFormatter extends RedBean_DefaultBeanFormatter{
   public function getAlias( $type ) {
   	if ($type == 'universe') return 'world'; else return $type;
   }
}

class Model_Box extends RedBean_SimpleModel {
        public function delete() {
                $a = $this->bean->ownBottle;
        }
}


class Model_CandyBar extends RedBean_SimpleModel {

	public function customMethod($custom) {
		return $custom."!";
	}

}