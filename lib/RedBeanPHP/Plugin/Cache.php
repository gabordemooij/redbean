<?php 

namespace RedBeanPHP\Plugin; 

use RedBeanPHP\OODB;
use RedBeanPHP\Plugin;
use RedBeanPHP\QueryWriter;
use RedBeanPHP\OODBBean; 

/**
 * RedBeanPHP Cache Plugin
 *
 * @file    RedBean/Plugin/Cache.php
 * @desc    Cache plugin, caches beans.
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * Provides a means to cache beans after loading or batch loading.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Cache extends OODB implements Plugin
{
	/**
	 * @var array
	 */
	protected $cache = array();

	/**
	 * @var integer
	 */
	protected $hits = 0;

	/**
	 * @var integer
	 */
	protected $misses = 0;

	/**
	 * Constructor.
	 * Cache decorates RedBeanPHP OODB class, so needs a writer.
	 *
	 * @param QueryWriter $writer
	 */
	public function __construct( QueryWriter $writer )
	{
		parent::__construct( $writer );
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
	 * @return OODBBean $bean the bean object found
	 */
	public function load( $type, $id )
	{
		if ( isset( $this->cache[$type][$id] ) ) {
			$this->hits++;
			$bean = $this->cache[$type][$id];
		} else {
			$this->misses++;

			$bean = parent::load( $type, $id );

			if ( $bean->id ) {
				if ( !isset( $this->cache[$type] ) ) {
					$this->cache[$type] = array();
				}

				$this->cache[$type][$id] = $bean;
			}
		}

		return $bean;
	}

	/**
	 * Stores a RedBean OODBBean and caches it.
	 *
	 * @param OODBBean $bean the bean you want to store
	 *
	 * @return mixed
	 */
	public function store( $bean )
	{
		$id   = parent::store( $bean );
		$type = $bean->getMeta( 'type' );

		if ( !isset( $this->cache[$type] ) ) {
			$this->cache[$type] = array();
		}

		$this->cache[$type][$id] = $bean;

		return $id;
	}

	/**
	 * Trashes a RedBean OODBBean and removes it from cache.
	 *
	 * @param OODBBean $bean bean
	 *
	 * @return mixed
	 */
	public function trash( $bean )
	{
		$type = $bean->getMeta( 'type' );
		$id   = $bean->id;

		if ( isset( $this->cache[$type][$id] ) ) {
			unset( $this->cache[$type][$id] );
		}

		parent::trash( $bean );
	}

	/**
	 * Flushes the cache for a given type.
	 *
	 * @param string $type
	 *
	 * @return Cache
	 */
	public function flush( $type )
	{
		if ( isset( $this->cache[$type] ) ) {
			$this->cache[$type] = array();
		}

		return $this;
	}

	/**
	 * Flushes the cache completely.
	 *
	 * @return Cache
	 */
	public function flushAll()
	{
		$this->cache = array();

		return $this;
	}

	/**
	 * Returns the number of hits. If a call to load() or
	 * batch() can use the cache this counts as a hit.
	 * Otherwise it's a miss.
	 *
	 * @return integer
	 */
	public function getHits()
	{
		return $this->hits;
	}

	/**
	 * Returns the number of hits. If a call to load() or
	 * batch() can use the cache this counts as a hit.
	 * Otherwise it's a miss.
	 *
	 * @return integer
	 */
	public function getMisses()
	{
		return $this->misses;
	}

	/**
	 * Resets hits counter to 0.
	 */
	public function resetHits()
	{
		$this->hits = 0;
	}

	/**
	 * Resets misses counter to 0.
	 */
	public function resetMisses()
	{
		$this->misses = 0;
	}
}
