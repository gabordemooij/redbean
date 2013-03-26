<?php
/**
 * ToolBox
 * 
 * @file			RedBean/ToolBox.php
 * @desc			A RedBeanPHP-wide service locator
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ToolBox {
	/**
	 * @var RedBean_OODB
	 */
	protected $oodb;
	/**
	 * @var RedBean_QueryWriter
	 */
	protected $writer;
	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;
	/**
	 * Constructor.
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