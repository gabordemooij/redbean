<?php
/**
 * Created by JetBrains PhpStorm.
 * User: prive
 * Date: 03-04-11
 * Time: 15:17
 * To change this template use File | Settings | File Templates.
 */
 
class RedBean_ViewManager extends RedBean_CompatManager {

	/**
	 * Specify what database systems are supported by this class.
	 * @var array $databaseSpecs
	 */
	protected $supportedSystems = array(
			  RedBean_CompatManager::C_SYSTEM_MYSQL => "5",
			  RedBean_CompatManager::C_SYSTEM_SQLITE=>"3",
			  RedBean_CompatManager::C_SYSTEM_POSTGRESQL=>"8"
	);

	/**
	 * @var RedBean_OODB
	 */
	protected $oodb;

	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * @var RedBean_QueryWriter
	 */
	protected $writer;


	/**
	 * Constructor
	 *
	 * @param RedBean_ToolBox $tools toolbox
	 */
	public function __construct( RedBean_ToolBox $tools ) {
		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer = $tools->getWriter();
	}


	public function createView( $viewID, $refType, $types ) {

		if ($this->oodb->isFrozen()) return false;
		
		$tables = array_flip( $this->writer->getTables() );

		$refTable = $this->writer->getFormattedTableName($refType);
		$currentTable = $refTable;
		foreach($types as $t) {
			$connection = array($t,$currentTable);
			sort($connection);
			$connection = implode("_", $connection);
			if (isset($tables[$connection])) {
				//this connection exists
				$srcPoint = $connection.".".$currentTable."_id"; //i.e. partic_project.project_id
				$dstPoint = $currentTable.".".$this->writer->getIDField($currentTable); //i.e. project.id
				$joins[$connection] = array($srcPoint,$dstPoint);
				//now join the type
				$srcPoint = $connection.".".$t."_id";
				$dstPoint = $t.".".$this->writer->getIDField($t);
				$joins[$t] = array($srcPoint,$dstPoint);
				//now set the new refTable
				$currentTable=$refTable;
			}
		}
		return (boolean) $this->writer->createView($refTable,$joins,$viewID);
	}

}
