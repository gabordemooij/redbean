<?php
/**
 * RedBean Bean Cache
 * @package 		RedBean/Plugin/Cache.php
 * @description		Decorator for RedBean core class RedBean_OODB
 *					Adds caching to RedBean
 *					
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Plugin_Cache extends RedBean_Observable implements ObjectDatabase {

	/**
	 *
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 *
	 * @var RedBean_QueryWriter
	 */
	private $writer;

	/**
	 *
	 * @var array
	 */
	private $cache = array();


	/**
	 * Constructor
	 * @param RedBean_OODB $oodb
	 * @param RedBean_ToolBox $toolBox
	 */
	public function __construct( RedBean_OODB $oodb, RedBean_ToolBox $toolBox ) {
		$this->oodb = $oodb;
		$this->writer = $toolBox->getWriter();
	}

	/**
	 * Adds event listener
	 * @param <type> $event
	 * @param RedBean_Observer $o
	 */
	public function addEventListener($event, RedBean_Observer $o) {
		$this->oodb->addEventListener($event, $o);
	}

	/**
	 * Tries to load a bean from cache, if this fails, it asks
	 * the oodb object to load the bean from the database.
	 * @param string $type
	 * @param integer $id
	 * @return RedBean_OODB $bean
	 */
	public function load( $type, $id ) {

		if (!isset($this->cache[sha1($type."-".$id)])) {
			$this->cache[sha1($type."-".$id)] = $this->oodb->load($type,$id);
		}
		return $this->cache[sha1($type."-".$id)];

	}

	/**
	 * Stores a bean and updates cache
	 * @param RedBean_OODBBean $bean
	 * @return integer $id
	 */
	public function store( RedBean_OODBBean $bean ) {

		$type=$bean->getMeta("type");
		$id = $this->oodb->store($bean);
		$this->cache[sha1($type."-".$id)] = $bean;
		return $id;

	}

	/**
	 * Trashes a bean and removes the bean from cache.
	 * @param RedBean_OODBBean $bean
	 */
	public function trash( RedBean_OODBBean $bean ) {
		$type = $bean->getMeta("type");
		$id = $this->writer->getIDField($type);
		unset( $this->cache[sha1($type."-".$id)] );
		return $this->oodb->trash($bean);
	}

	/**
	 * Loads a batch of beans all at once.
	 * This function first inspects the cache; if every element in the batch
	 * is available in the cache, the function will return the collected beans
	 * from the cache. If one or more beans cannot be found, the function will
	 * ask oodb for the beans and update the cache.	
	 * @param string $type
	 * @param integer $ids
	 * @return array $beans 
	 */
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

	/**
	 * Dispenses a bean, just like oodb does
	 * @param string $type
	 * @return RedBean_OODBBean $bean
	 */
	public function dispense( $type ){
		return $this->oodb->dispense($type);
	}

}