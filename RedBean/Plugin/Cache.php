<?php


class RedBean_Plugin_Cache extends RedBean_Observable implements ObjectDatabase {


	private $oodb;
	private $writer;
	private $cache = array();


	public function __construct( RedBean_OODB $oodb, RedBean_ToolBox $toolBox ) {
		$this->oodb = $oodb;
		$this->writer = $toolBox->getWriter();
	}

	public function addEventListener($event, RedBean_Observer $o) {
		$this->oodb->addEventListener($event, $o);
	}
	
	public function load( $type, $id ) {

		if (!isset($this->cache[sha1($type."-".$id)])) {
			$this->cache[sha1($type."-".$id)] = $this->oodb->load($type,$id);
		}
		return $this->cache[sha1($type."-".$id)];

	}

	public function store( RedBean_OODBBean $bean ) {

		$type=$bean->getMeta("type");
		$id = $this->oodb->store($bean);
		$this->cache[sha1($type."-".$id)] = $bean;
		return $id;

	}

	public function trash( RedBean_OODBBean $bean ) {

		$type = $bean->getMeta("type");
		$id = $this->writer->getIDField($type);
		unset( $this->cache[sha1($type."-".$id)] );
		$this->oodb->trash($bean);

	}

	public function batch( $type, $ids ) {
		$idfield = $this->writer->getIDField($type);
		$collect = array();
		foreach($ids as $id) {
			if (isset($this->cache[sha1($type."-".$id)])) {
				$collect[$id] = $this->cache[sha1($type."-".$id)];
			}
		}
		if (count($collect) == count($ids)) {
			return $collect;
		}
		else {
			$beans = $this->oodb->batch($type, $ids);
			foreach($beans as $bean) {
				$this->cache[sha1($type."-".$bean->$idfield)] = $bean;
			}
			return $beans;
		}
	}

	public function dispense( $type ){
		return $this->oodb->dispense($type);
	}

}