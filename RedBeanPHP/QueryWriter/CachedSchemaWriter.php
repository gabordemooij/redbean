<?php

namespace RedBeanPHP\QueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;

/**
 * RedBeanPHP Cachable Schema Writer.
 * Allows you to cache the schema with all writers.
 * 
 * @file    RedBeanPHP/QueryWriter/CachableSchemaWriter.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class CachedSchemaWriter extends AQueryWriter implements QueryWriter {

	/**
	 * @var array
	 */
	protected $cachedTables = NULL;

	/**
	 * @var boolean
	 */
	protected $useCache = FALSE;

	/**
	 * Turns table cache ON or OFF.
	 * Toggles the table caching for fluid mode.
	 * This method activates or deactivates the schema cache.
	 * It will return the old value of the cache flag.
	 *
	 * @param boolean $toggle toggle
	 *
	 * @return boolean
	 */
	public function useSchemaCache( $toggle )
	{
		$old = $this->useCache;
		$this->useCache = $toggle;
		$this->cachedTables = NULL;
		return $old;
	}

	/**
	 * Gets the tables from the database.
	 * If caching is turned on these tables will be retrieved
	 * from table cache if possible. If no tables are in the cache
	 * the database will be queried for the table list. If caching
	 * is disabled then the tables will be queried with every call.
	 *
	 * @return array
	 */
	public function getTables()
	{
		if (!$this->useCache) return $this->loadTables();
		if (is_null($this->cachedTables)) $this->cachedTables = $this->loadTables();
		return $this->cachedTables;
	}

	/**
	 * @see QueryWriter::wipe( $type )
	 */
	public function wipe( $type )
	{
		$result = $this->truncate( $type );
		$this->cachedTables = NULL;
		return $result;
	}

	/**
	 * @see QueryWriter::wipeAll()
	 */
	public function wipeAll()
	{
		$result = $this->dropAll();
		$this->cachedTables = NULL;
		return $result;
	}

	/**
	 * @see QueryWriter::createTable( $type )
	 */
	public function createTable( $type )
	{
		$result = $this->addTable( $type );
		$this->cachedTables = NULL;
		return $result;
	}
}
