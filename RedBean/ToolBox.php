<?php
/**
 * ToolBox
 * 
 * @file			RedBean/ToolBox.php
 * @desc			A RedBeanPHP-wide service locator
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * The ToolBox acts as a resource locator for RedBean but can
 * be integrated in larger resource locators (nested).
 * It does not do anything more than just store the three most
 * important RedBeanPHP resources (tools): the database adapter,
 * the RedBeanPHP core class (oodb) and the query writer.
 * 
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ToolBox {
	/**
	 * Reference to the RedBeanPHP OODB Object Database instance
	 * @var RedBean_OODB
	 */
	protected $oodb;
	/**
	 * Reference to the Query Writer
	 * @var RedBean_QueryWriter
	 */
	protected $writer;
	/**
	 * Reference to the database adapter
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;
	/**
	 * Constructor.
	 * The Constructor of the ToolBox takes three arguments: a RedBean_OODB $redbean
	 * object database, a RedBean_Adapter $databaseAdapter and a
	 * RedBean_QueryWriter $writer. It stores these objects inside and acts as
	 * a micro service locator. You can pass the toolbox to any object that needs
	 * one of the RedBean core objects to interact with.
	 *
	 * @param RedBean_OODB              $oodb    Object Database
	 * @param RedBean_Adapter_DBAdapter $adapter Adapter
	 * @param RedBean_QueryWriter       $writer  Writer
	 *
	 * return RedBean_ToolBox $toolbox Toolbox
	 */
	public function __construct(RedBean_OODB $oodb, RedBean_Adapter $adapter, RedBean_QueryWriter $writer) {
		$this->oodb = $oodb;
		$this->adapter = $adapter;
		$this->writer = $writer;
		return $this;
	}
	/**
	 * Returns the query writer in this toolbox.
	 * 
	 * @return RedBean_QueryWriter $writer writer
	 */
	public function getWriter() {
		return $this->writer;
	}
	/**
	 * Returns the OODB instance in this toolbox.
	 * 
	 * @return RedBean_OODB $oodb Object Database
	 */
	public function getRedBean() {
		return $this->oodb;
	}
	/**
	 * Returns the database adapter in this toolbox.
	 * 
	 * @return RedBean_Adapter_DBAdapter $adapter Adapter
	 */
	public function getDatabaseAdapter() {
		return $this->adapter;
	}
}