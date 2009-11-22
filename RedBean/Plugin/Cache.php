<?php
/**
 * RedBean Bean Cache
 * @file 		RedBean/Plugin/Cache.php
 * @description		Decorator for RedBean core class RedBean_OODB
 *					Adds primitive caching to RedBean.
 *					
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Plugin_Cache extends RedBean_Observable implements RedBean_Plugin, RedBean_ObjectDatabase {

	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var RedBean_QueryWriter
	 */
	private $writer;

	/**
	 * @var array
	 */
	private $cache = array();

	/**
	 * @var array
	 */
	private $originals = array();

	/**
	 * @var integer
	 */
	private $columnCounter = 0;


	/**
	 * Constructor.
	 * @param RedBean_OODB $oodb
	 * @param RedBean_ToolBox $toolBox
	 */
	public function __construct( RedBean_OODB $oodb, RedBean_ToolBox $toolBox ) {
		$this->oodb = $oodb;
		$this->writer = $toolBox->getWriter();
	}

	/**
	 * Adds event listener.
	 * @param <type> $event
	 * @param RedBean_Observer $o
	 */
	public function addEventListener($event, RedBean_Observer $o) {
		$this->oodb->addEventListener($event, $o);
	}


	/**
	 * Generates a key based on the ID and TYPE of a bean to
	 * identify the bean in the cache.
	 * @param RedBean_OODBBean $bean
	 * @return string $key
	 */
	private function generateKey( RedBean_OODBBean $bean ) {
		$type=$bean->getMeta("type");
		$idfield = $this->writer->getIDField($type);
		$id = $bean->$idfield;
		return sha1($type."-".$id);
	}


	/**
	 * Puts a bean in the cache and stores a copy of the bean in the
	 * cache archive.
	 * @param RedBean_OODBBean $bean
	 */
	private function putInCache( RedBean_OODBBean $bean ) {
		$key = $this->generateKey($bean);
		$this->cache[$key]=$bean;
		$copy = clone $bean;
		$copy->copyMetaFrom($bean);
		$this->originals[ $key ] = $copy;
		return $this;
	}


	/**
	 * Fetches a bean from the cache or returns NULL.
	 * @param RedBean_OODBBean $bean
	 * @return RedBean_OODBBean $bean
	 */
	private function fetchFromCache( RedBean_OODBBean $bean ) {
		$key = $this->generateKey($bean);
		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}
		else {
			return NULL;
		}
	}


	/**
	 * Fetches a bean from the cache or returns NULL.
	 * This function takes a TYPE and ID.
	 * @param string $type
	 * @param integer $id
	 * @return  RedBean_OODBBean $bean
	 */
	private function fetchFromCacheByTypeID( $type, $id ) {
		$bean = $this->oodb->dispense($type);
		$idfield = $this->writer->getIDField($type);
		$bean->$idfield = $id;
		return $this->fetchFromCache($bean);
	}


	/**
	 * Fetches the original bean as it was stored in the cache
	 * archive or NULL.
	 * @param RedBean_OODBBean $bean
	 * @return RedBean_OODBBean $bean
	 */
	private function fetchOriginal(RedBean_OODBBean $bean) {
		$key = $this->generateKey($bean);
		if (isset($this->originals[$key])) {
			return $this->originals[$key];
		}
		else {
			return NULL;
		}
	}

	/**
	 * Removes a bean from the cache and the archive.
	 * @param RedBean_OODBBean $bean
	 */
	private function removeFromCache( RedBean_OODBBean $bean ) {
		$key = $this->generateKey($bean);
		unset($this->cache[$key]);
		unset($this->originals[$key]);
		return $this;
	}

	/**
	 * Tries to load a bean from cache, if this fails, it asks
	 * the oodb object to load the bean from the database.
	 * @param string $type
	 * @param integer $id
	 * @return RedBean_OODB $bean
	 */
	public function load( $type, $id ) {

		$bean = $this->fetchFromCacheByTypeID($type, $id);
		if ($bean) {
			return $bean;
		}
		else {
			$bean = $this->oodb->load($type, $id);
			$this->putInCache($bean);
			return $bean;
		}

	}

	
	/**
	 * Stores a bean and updates cache.
	 * @param RedBean_OODBBean $bean
	 * @return integer $id
	 */
	public function store( RedBean_OODBBean $bean ) {

		$this->columnCounter = 0;
		$type=$bean->getMeta("type");
		$idfield = $this->writer->getIDField($type);
		$newbean = $this->oodb->dispense($type);
		$newbean->$idfield = $bean->$idfield;
		$oldBean = $this->fetchOriginal($bean);
		//Is there a cached version?
		if ($oldBean) {
			//Assume no differences.
			$dirty = false;
			//Check for differences.
			foreach($oldBean as $p=>$v) {
				if ($v !== $bean->$p && $p!=$idfield) {
					$newbean->$p = $bean->$p;
					//echo "ADDING CHANGED PROP: $p $v ->  ".$bean->$p;
					$this->columnCounter++; //for tests.
					//found a difference; mark as tainted.
					$dirty=true;
				}
			}
			//If the bean is dirty; send only differences for update.
			if ($dirty) {
				$newbean->copyMetaFrom($bean);
				$id = $this->oodb->store($newbean);
				$bean->copyMetaFrom($newbean);
				$this->putInCache($bean);
				return $id;
			}
			else {
				return $bean->$idfield;
			}
		}
		else {
			$id = $this->oodb->store($bean);
			$this->putInCache($bean);
			return $id;
		}
	}

	/**
	 * Trashes a bean and removes the bean from cache.
	 * @param RedBean_OODBBean $bean
	 */
	public function trash( RedBean_OODBBean $bean ) {
		$this->removeFromCache($bean);
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
			$bean = $this->fetchFromCacheByTypeID($type, $id);
			if ($bean) $collect[$id] = $bean;
		}
		if (count($collect) == count($ids)) { 
			return $collect;
		}
		else {
			$beans = $this->oodb->batch($type, $ids);
			foreach($beans as $bean) {
				$this->putInCache( $bean );
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

	/**
	 * For testing only; returns the number of properties that has
	 * been updated in the latest store action.
	 * @return integer $count
	 */
	public function test_getColCount() {
		return $this->columnCounter;
	}

}