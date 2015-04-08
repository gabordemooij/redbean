<?php 

namespace RedBeanPHP;

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
	 * @var IQueryWriter
	 */
	protected $writer;

	/**
	 * @var IAdapter
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
	 * @param OODB              $oodb    Object Database
	 * @param IAdapter $adapter Adapter
	 * @param IQueryWriter       $writer  Writer
	 *
	 * @return ToolBox
	 */
	public function __construct( OODB $oodb, IAdapter $adapter, IQueryWriter $writer )
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
	 * @return IQueryWriter
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
	 * @return IAdapter
	 */
	public function getDatabaseAdapter()
	{
		return $this->adapter;
	}
}
