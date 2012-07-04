<?php
/**
 * RedBeanPHP Cache Plugin
 * 
 * @file			RedBean/Plugin/Cache.php
 * @description 	Cache plugin, caches beans.
 * 
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * Provides a means to cache beans after loading or batch loading.
 * Any other action will flush the cache unless keepCache() has been
 * called.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedBean_Plugin_Cache extends RedBean_OODB implements RedBean_Observer,RedBean_Plugin {
	
	/**
	 * Bean cache, contains the cached beans identified by
	 * label keys containing the type id and the fetch method;
	 * i.e. single load or batch load.
	 *  
	 * @var array 
	 */
	private $cache = array();
	
	/**
	 * Flag, indicating the current cache handling mode.
	 * TRUE means to flush the cache after the next SQL query, 
	 * FALSE means keep the cache whatever happens.
	 * By default the flag is set to TRUE to avoid caching issues.
	 * 
	 * @var boolean
	 */
	private $flushCache = true;
	
	/**
	 * Number of hits (beans/calls being served from cache). 
	 * Can be used to monitor cache performance.
	 *  
	 * @var integer 
	 */
	private $hits = 0;
	
	/**
	 * Number of misses (beans not being served from cache), can be
	 * used to monitor cache performance.
	 * 
	 * @var integer 
	 */
	private $misses = 0;
	
	/**
	 * Constructor.
	 * Cache decorates RedBeanPHP OODB class, so needs a writer.
	 * 
	 * @param RedBean_QueryWriter $writer 
	 */
	public function __construct(RedBean_QueryWriter $writer) {
		parent::__construct($writer);
	}
	
	/**
	 * In order for the cache to work properly you need to attach
	 * a listener to the database adapter. This enables the cache to be flushed
	 * whenever a possible destructive SQL query gets executed.
	 * 
	 * @param RedBean_Adapter $adapter 
	 */
	public function addListener(RedBean_Adapter $adapter) {
		$adapter->addEventListener('sql_exec',$this);
	}
	
	/**
	 * Loads a bean by type and id. If the bean cannot be found an
	 * empty bean will be returned instead. This is a cached version
	 * of the loader, if the bean has been cached it will be served
	 * from cache, otherwise the bean will be retrieved from the database
	 * as usual an a new cache entry will be added..
	 * 
	 * @param string  $type type of bean you are looking for
	 * @param integer $id   identifier of the bean
	 * 
	 * @return RedBean_OODBBean $bean the bean object found
	 */
	public function load($type,$id) {
		$this->flushCache = false;
		$label = $type.'-'.$id;
		if (isset($this->cache[$label])) {
			$this->hits ++;
			$bean = $this->cache[$label];
		}
		else {
			$this->misses ++;
			$bean = parent::load($type,$id);
			$this->cache[$label] = $bean;
		}
		$this->flushCache = true;
		return $bean;
		
	}
	
	/**
	 * Method to update cache from lists of beans.
	 * This provides us with the means to cache the results from batch
	 * and find operations.
	 * 
	 * @param array $beans beans
	 * 
	 * @return void
	 */
	private function updateCache($beans = array()) {
		if (!count($beans)) return;
		$bean = reset($beans);
		if (!($bean instanceof RedBean_OODBBean)) return;
		$type = $bean->getMeta('type');
		foreach($beans as $bean) {
			$label = $type.'-'.$bean->id;
			if (!isset($this->cache[$label])) {
				$this->cache[$label] = $bean;
			}
		}
	}
	
	
	/**
	 * Loads a batch of beans from the database. Loads beans by type and id
	 * collection. If the beans are in the cache they will be served from cache.
	 * 
	 * @param string $type type of bean you are looking for
	 * @param array  $ids  list of identifiers to look for
	 * 
	 * @return array $beans beans 
	 */
	public function batch($type,$ids) {
		$this->flushCache = false;
		$label = 'batch-'.$type.'-'.implode(',',$ids);
		if (isset($this->cache[$label])) {
			$this->hits ++;
			$beans = $this->cache[$label];
		}
		else {
			$beans = parent::batch($type,$ids);
			$this->misses ++;
			$this->cache[$label] = $beans;
			$this->updateCache($beans);
		}
		$this->flushCache = true;
		return $beans;
	}
	
	/**
	 * Cached version of OODB::find.
	 * Retrieves as much beans from the cache as possible and updates cache.
	 * 
	 * Searches the database for a bean that matches conditions $conditions and sql $addSQL
	 * and returns an array containing all the beans that have been found.
	 * 
	 * Conditions need to take form:
	 * 
	 * array(
	 * 		'PROPERTY' => array( POSSIBLE VALUES... 'John','Steve' )
	 * 		'PROPERTY' => array( POSSIBLE VALUES... )
	 * );
	 * 
	 * All conditions are glued together using the AND-operator, while all value lists
	 * are glued using IN-operators thus acting as OR-conditions.
	 * 
	 * Note that you can use property names; the columns will be extracted using the
	 * appropriate bean formatter.
	 * 
	 * @throws RedBean_Exception_SQL 
	 * 
	 * @param string  $type       type of beans you are looking for
	 * @param array   $conditions list of conditions
	 * @param string  $addSQL	  SQL to be used in query
	 * @param boolean $all        whether you prefer to use a WHERE clause or not (TRUE = not)
	 * 
	 * @return array $beans		  resulting beans
	 */
	public function find($type, $conditions=array(),$addSQL=null,$all=false) {
		/**
		 * If $addSQL is filled in the query might update the database,
		 * in this case dont use the cache but clear cache instead.
		 */
		if ($this->flushCache && (is_array($addSQL) && strlen($addSQL[0])>0)) {
			$this->cache = array();
			return parent::find($type, $conditions, $addSQL, $all);
		
		}
		$this->flushCache = false;
		$label = 'find-'.$type.'-'.sha1(serialize(array($conditions,$addSQL,$all)));
		if (isset($this->cache[$label])) {
			$this->hits ++;
			$beans = $this->cache[$label];
		}
		else {
			$beans = parent::find($type,$conditions,$addSQL,$all);
			$this->misses ++;
			$this->cache[$label] = $beans;
			$this->updateCache($beans);
		}
		$this->flushCache = true;
		return $beans;
	}
	
	/**
	 * Instructs cache to not flush the cache after the
	 * upcoming SQL queries.
	 */
	public function keepCache() {
		$this->flushCache = false;
	}
	
	/**
	 * Instructs the cache to flush itself after the next SQL
	 * Query and flushes the cache directly as well.
	 */
	public function flushCache() {
		$this->cache = array();
		$this->flushCache = true;
	}
	
	/**
	 * Event handler.
	 * If an SQL query is executed and the flush-cache flag has been set
	 * the cache will be flushed.
	 * 
	 * @param string $eventname identifier string of the event
	 * @param mixed  $bean      info 
	 */
	public function onEvent($eventname, $bean) {
		if ($eventname=='sql_exec') {
			if ($this->flushCache) { 
				$this->cache = array();
			}
		}
	}
	
	/**
	 * Returns the number of hits. If a call to load() or
	 * batch() can use the cache this counts as a hit.
	 * Otherwise it's a miss.
	 * 
	 * @return integer 
	 */
	public function getHits() {
		return $this->hits;
	}
	
	/**
	 * Returns the number of hits. If a call to load() or
	 * batch() can use the cache this counts as a hit.
	 * Otherwise it's a miss.
	 * 
	 * @return integer 
	 */
	public function getMisses() {
		return $this->misses;
	}
	
	/**
	 * Resets hits counter to 0.
	 */
	public function resetHits() {
		$this->hits = 0;
	}
	
	/**
	 * Resets misses counter to 0.
	 */
	public function resetMisses() {
		$this->misses = 0;
	}
	
}