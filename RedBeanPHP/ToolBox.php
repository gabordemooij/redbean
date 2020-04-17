<?php

namespace RedBeanPHP;

use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * ToolBox.
 *
 * The toolbox is an integral part of RedBeanPHP providing the basic
 * architectural building blocks to manager objects, helpers and additional tools
 * like plugins. A toolbox contains the three core components of RedBeanPHP:
 * the adapter, the query writer and the core functionality of RedBeanPHP in
 * OODB.
 *
 * @file      RedBeanPHP/ToolBox.php
 * @author    Gabor de Mooij and the RedBeanPHP community
 * @license   BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class ToolBox
{
	/**
	 * @var OODB
	 */
	protected $oodb;

	/**
	 * @var QueryWriter
	 */
	protected $writer;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * Constructor.
	 * The toolbox is an integral part of RedBeanPHP providing the basic
	 * architectural building blocks to manager objects, helpers and additional tools
	 * like plugins. A toolbox contains the three core components of RedBeanPHP:
	 * the adapter, the query writer and the core functionality of RedBeanPHP in
	 * OODB.
	 *
	 * Usage:
	 *
	 * <code>
	 * $toolbox = new ToolBox( $oodb, $adapter, $writer );
	 * $plugin  = new MyPlugin( $toolbox );
	 * </code>
	 *
	 * The example above illustrates how the toolbox is used.
	 * The core objects are passed to the ToolBox constructor to
	 * assemble a toolbox instance. The toolbox is then passed to
	 * the plugin, helper or manager object. Instances of
	 * TagManager, AssociationManager and so on are examples of
	 * this, they all require a toolbox. The toolbox can also
	 * be obtained from the facade using: R::getToolBox();
	 *
	 * @param OODB        $oodb    Object Database, OODB
	 * @param DBAdapter   $adapter Database Adapter
	 * @param QueryWriter $writer  Query Writer
	 */
	public function __construct( OODB $oodb, Adapter $adapter, QueryWriter $writer )
	{
		$this->oodb    = $oodb;
		$this->adapter = $adapter;
		$this->writer  = $writer;
		return $this;
	}

	/**
	 * Returns the query writer in this toolbox.
	 * The Query Writer is responsible for building the queries for a
	 * specific database and executing them through the adapter.
	 *
	 * Usage:
	 *
	 * <code>
	 * $toolbox = R::getToolBox();
	 * $redbean = $toolbox->getRedBean();
	 * $adapter = $toolbox->getDatabaseAdapter();
	 * $writer  = $toolbox->getWriter();
	 * </code>
	 *
	 * The example above illustrates how to obtain the core objects
	 * from a toolbox instance. If you are working with the R-object
	 * only, the following shortcuts exist as well:
	 *
	 * - R::getRedBean()
	 * - R::getDatabaseAdapter()
	 * - R::getWriter()
	 *
	 * @return QueryWriter
	 */
	public function getWriter()
	{
		return $this->writer;
	}

	/**
	 * Returns the OODB instance in this toolbox.
	 * OODB is responsible for creating, storing, retrieving and deleting
	 * single beans. Other components rely
	 * on OODB for their basic functionality.
	 *
	 * Usage:
	 *
	 * <code>
	 * $toolbox = R::getToolBox();
	 * $redbean = $toolbox->getRedBean();
	 * $adapter = $toolbox->getDatabaseAdapter();
	 * $writer  = $toolbox->getWriter();
	 * </code>
	 *
	 * The example above illustrates how to obtain the core objects
	 * from a toolbox instance. If you are working with the R-object
	 * only, the following shortcuts exist as well:
	 *
	 * - R::getRedBean()
	 * - R::getDatabaseAdapter()
	 * - R::getWriter()
	 *
	 * @return OODB
	 */
	public function getRedBean()
	{
		return $this->oodb;
	}

	/**
	 * Returns the database adapter in this toolbox.
	 * The adapter is responsible for executing the query and binding the values.
	 * The adapter also takes care of transaction handling.
	 *
	 * Usage:
	 *
	 * <code>
	 * $toolbox = R::getToolBox();
	 * $redbean = $toolbox->getRedBean();
	 * $adapter = $toolbox->getDatabaseAdapter();
	 * $writer  = $toolbox->getWriter();
	 * </code>
	 *
	 * The example above illustrates how to obtain the core objects
	 * from a toolbox instance. If you are working with the R-object
	 * only, the following shortcuts exist as well:
	 *
	 * - R::getRedBean()
	 * - R::getDatabaseAdapter()
	 * - R::getWriter()
	 *
	 * @return DBAdapter
	 */
	public function getDatabaseAdapter()
	{
		return $this->adapter;
  }

  /**
	 * Query function, executes the desired query.
	 *
	 * @param string    $method   desired query method (i.e. 'cell', 'col', 'exec' etc..)
	 * @param string    $sql      the sql you want to execute
	 * @param array     $bindings array of values to be bound to query statement
	 *
	 * @return array
	 */
  public function query($method, $sql, $bindings )
	{
		if ( !$this->oodb->isFrozen() ) {
			try {
				$rs = $this->adapter->$method( $sql, $bindings );
			} catch ( SQLException $exception ) {
				if ( $this->writer->sqlStateIn( $exception->getSQLState(),
					array(
						QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
						QueryWriter::C_SQLSTATE_NO_SUCH_TABLE )
					,$exception->getDriverDetails()
					)
				) {
					return ( $method === 'getCell' ) ? NULL : array();
				} else {
					throw $exception;
				}
			}

			return $rs;
		} else {
			return $this->adapter->$method( $sql, $bindings );
		}
  }

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql       SQL query to execute
	 * @param array  $bindings  a list of values to be bound to query parameters
	 *
	 * @return integer
	 */
	public function exec( $sql, $bindings = array() )
	{
		return $this->query( 'exec', $sql, $bindings );
	}

	/**
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns all rows
	 * and all columns.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public function getAll( $sql, $bindings = array() )
	{
		return $this->query( 'get', $sql, $bindings );
	}

	/**
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a single cell.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return string
	 */
	public function getCell( $sql, $bindings = array() )
	{
		return $this->query( 'getCell', $sql, $bindings );
	}

	/**
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a PDOCursor instance.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return RedBeanPHP\Cursor\PDOCursor
	 */
	public function getCursor( $sql, $bindings = array() )
	{
		return $this->query( 'getCursor', $sql, $bindings );
	}

	/**
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a single row.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public function getRow( $sql, $bindings = array() )
	{
		return $this->query( 'getRow', $sql, $bindings );
	}

	/**
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a single column.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public function getCol( $sql, $bindings = array() )
	{
		return $this->query( 'getCol', $sql, $bindings );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 * Results will be returned as an associative array. The first
	 * column in the select clause will be used for the keys in this array and
	 * the second column will be used for the values. If only one column is
	 * selected in the query, both key and value of the array will have the
	 * value of this field for each row.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public function getAssoc( $sql, $bindings = array() )
	{
		return $this->query( 'getAssoc', $sql, $bindings );
	}

	/**
	 *Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns an associative array.
	 * Results will be returned as an associative array indexed by the first
	 * column in the select.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public function getAssocRow( $sql, $bindings = array() )
	{
		return $this->query( 'getAssocRow', $sql, $bindings );
	}

}
