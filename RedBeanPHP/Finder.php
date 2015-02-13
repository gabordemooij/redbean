<?php

namespace RedBeanPHP;

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\RedException\Security as Security;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * RedBeanPHP Finder
 *
 * @file    RedBeanPHP/Finder.php
 * @desc    Helper class to harmonize APIs.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Finder
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * @var OODB
	 */
	protected $redbean;

	/**
	 * Constructor.
	 * The Finder requires a toolbox.
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 *
	 * @throws Security
	 */
	public function find( $type, $sql = NULL, $bindings = array() )
	{
		if ( !is_array( $bindings ) ) {
			throw new RedException(
				'Expected array, ' . gettype( $bindings ) . ' given.'
			);
		}

		return $this->redbean->find( $type, array(), $sql, $bindings );
	}

	/**
	 * @see Finder::find
	 *      The variation also exports the beans (i.e. it returns arrays).
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function findAndExport( $type, $sql = NULL, $bindings = array() )
	{
		$arr = array();
		foreach ( $this->find( $type, $sql, $bindings ) as $key => $item ) {
			$arr[] = $item->export();
		}

		return $arr;
	}

	/**
	 * @see Finder::find
	 *      This variation returns the first bean only.
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public function findOne( $type, $sql = NULL, $bindings = array() )
	{
		$sql = $this->toolbox->getWriter()->glueLimitOne( $sql );
		
		$items = $this->find( $type, $sql, $bindings );

		if ( empty($items) ) {
			return NULL;
		}

		return reset( $items );
	}

	/**
	 * @see Finder::find
	 *      This variation returns the last bean only.
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public function findLast( $type, $sql = NULL, $bindings = array() )
	{
		$items = $this->find( $type, $sql, $bindings );

		if ( empty($items) ) {
			return NULL;
		}

		return end( $items );
	}

	/**
	 * @see Finder::find
	 *      Convience method. Tries to find beans of a certain type,
	 *      if no beans are found, it dispenses a bean of that type.
	 *
	 * @param  string $type     the type of bean you are looking for
	 * @param  string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param  array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function findOrDispense( $type, $sql = NULL, $bindings = array() )
	{
		$foundBeans = $this->find( $type, $sql, $bindings );

		if ( empty( $foundBeans ) ) {
			return array( $this->redbean->dispense( $type ) );
		} else {
			return $foundBeans;
		}
	}

	/**
	 * Finds a BeanCollection using the repository.
	 *
	 * @param  string $type     the type of bean you are looking for
	 * @param  string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param  array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return BeanCollection
	 */
	public function findCollection( $type, $sql, $bindings = array() )
	{
		return $this->redbean->findCollection( $type, $sql, $bindings );
	}

	/**
	 * Finds or creates a bean.
	 * Tries to find a bean with certain properties specified in the second
	 * parameter ($like). If the bean is found, it will be returned.
	 * If multiple beans are found, only the first will be returned.
	 * If no beans match the criteria, a new bean will be dispensed,
	 * the criteria will be imported as properties and this new bean
	 * will be stored and returned.
	 *
	 * Format of criteria set: property => value
	 * The criteria set also supports OR-conditions: property => array( value1, orValue2 )
	 *
	 * @param string $type type of bean to search for
	 * @param array  $like criteria set describing bean to search for
	 *
	 * @return OODBBean
	 */
	public function findOrCreate( $type, $like = array() )
	{
			$beans = $this->findLike( $type, $like );
			if ( count( $beans ) ) {
				$bean = reset( $beans );
				return $bean;
			}

			$bean = $this->redbean->dispense( $type );
			$bean->import( $like );
			$this->redbean->store( $bean );
			return $bean;
	}

	/**
	 * Finds beans by its type and a certain criteria set.
	 *
	 * Format of criteria set: property => value
	 * The criteria set also supports OR-conditions: property => array( value1, orValue2 )
	 *
	 * If the additional SQL is a condition, this condition will be glued to the rest
	 * of the query using an AND operator.
	 *
	 * @param string $type       type of bean to search for
	 * @param array  $conditions criteria set describing the bean to search for
	 * @param string $sql        additional SQL (for sorting)
	 *
	 * @return array
	 */
	public function findLike( $type, $conditions = array(), $sql = '' )
	{
		if ( count( $conditions ) > 0 ) {
			foreach( $conditions as $key => $condition ) {
				if ( !count( $condition ) ) unset( $conditions[$key] );
			}
		}

		return $this->redbean->find( $type, $conditions, $sql );
	}
}
