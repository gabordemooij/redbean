<?php
/**
 * RedBean SimpleStat
 * @file 		RedBean/SimpleStat.php
 * @description		Provides simple statistics for MySQL Databases
 * @author			Gabor de Mooij
 * @license			BSD
 */

class RedBean_SimpleStat extends RedBean_CompatManager implements RedBean_Plugin {

	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	private $adapter;

	/**
	 * @var RedBean_QueryWriter
	 */
	private $writer;

	/**
	 * Specify what database systems are supported by this class.
	 * @var array $databaseSpecs
	 */
	protected $supportedSystems = array(
		RedBean_CompatManager::C_SYSTEM_MYSQL => "5"
	);


	/**
	 * Constructor
	 * @param RedBean_ToolBox $tools
	 */
	public function __construct( RedBean_ToolBox $tools ) {

		//Do a compatibility check, using the Compatibility Management System
		$this->scanToolBox( $tools );


		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer = $tools->getWriter();
	}


	/**
	 * Counts the number of beans of a specific type
	 * @param RedBean_OODBBean $bean
	 * @return integer $count
	 */
	public function numberOf(RedBean_OODBBean $bean) {
		$type = $this->adapter->escape( $bean->getMeta("type") );
		return (int) $this->adapter->getCell("SELECT count(*) FROM `$type`");
	}

	


}