<?php
/**
 * RedBean Association
 * @file 		RedBean/AssociationManager.php
 * @description		This is actually more like an example than
 *					a real part of RedBean. Since version 0.7 you can create
 *					your own ORM structures with RedBean. Association is a
 *					simplistic example of how you might manage associated beans.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_AssociationManager extends RedBean_CompatManager {

/**
 * Specify what database systems are supported by this class.
 * @var array $databaseSpecs
 */
	protected $supportedSystems = array(
	RedBean_CompatManager::C_SYSTEM_MYSQL => "5"
	);

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
	 * Constructor
	 * @param RedBean_ToolBox $tools
	 */
	public function __construct( RedBean_ToolBox $tools ) {
		$this->scanToolBox( $tools );
		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer = $tools->getWriter();
	}
	/**
	 * Creates a table name based on a types array.
	 * @param array $types
	 * @return string $table
	 */
	public function getTable( $types ) {
		sort($types);
		return implode("_", $types);
	}
	/**
	 * Associates two beans with eachother.
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 */
	public function associate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$table = $this->getTable( array($bean1->getMeta("type") , $bean2->getMeta("type")) );
		$idfield1 = $this->writer->getIDField($bean1->getMeta("type"));
		$idfield2 = $this->writer->getIDField($bean2->getMeta("type"));
		$bean = $this->oodb->dispense($table);
		$property1 = $bean1->getMeta("type") . "_id";
		$property2 = $bean2->getMeta("type") . "_id";
		if ($property1==$property2) $property2 = $bean2->getMeta("type")."2_id";
		$bean->setMeta( "buildcommand.unique" , array( array( $property1, $property2 )));
		$this->oodb->store($bean1);
		$this->oodb->store($bean2);
		$bean->$property1 = $bean1->$idfield1;
		$bean->$property2 = $bean2->$idfield2;
		try {
			return $this->oodb->store( $bean );
		}
		catch(RedBean_Exception_SQL $e)  {
		//If this is a SQLSTATE[23000]: Integrity constraint violation
		//Then just ignore the insert
			if ((int)$e->getSQLState()!==23000)  {
				throw $e;
			}
		}
	}

	/**
	 * Gets related beans of type $type for bean $bean
	 * @param RedBean_OODBBean $bean
	 * @param string $type
	 * @return array $ids
	 */
	public function related( RedBean_OODBBean $bean, $type, $getLinks=false ) {
		$table = $this->getTable( array($bean->getMeta("type") , $type) );
		$idfield = $this->writer->getIDField($bean->getMeta("type"));
		if ($type==$bean->getMeta("type")) {// echo "<b>CROSS</b>";
			$type .= "2";
			$cross = 1;
		}
		else $cross=0;
		if (!$getLinks) $targetproperty = $type."_id"; else $targetproperty="id";

		$property = $bean->getMeta("type")."_id";
		try {
			if ($cross) {
				$sqlFetchKeys = $this->writer->selectByCrit(
					$targetproperty,
					$table,
					$property,
					$bean->$idfield,
					true
				);
			}
			else {
				$sqlFetchKeys = $this->writer->selectByCrit(
					$targetproperty,
					$table,
					$property,
					$bean->$idfield
				);
			}
			return ( $sqlFetchKeys );
		}catch(RedBean_Exception_SQL $e ){
			if ($e->getSQLState()!="42S02" && $e->getSQLState()!="42S22") throw $e;
			return array();
		}
	}

	/**
	 * Breaks the association between two beans
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 */
	public function unassociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$this->oodb->store($bean1);
		$this->oodb->store($bean2);
		$table = $this->getTable( array($bean1->getMeta("type") , $bean2->getMeta("type")) );
		$idfield1 = $this->writer->getIDField($bean1->getMeta("type"));
		$idfield2 = $this->writer->getIDField($bean2->getMeta("type"));
		$type = $bean1->getMeta("type");
		if ($type==$bean2->getMeta("type")) { //echo "<b>CROSS</b>";
			$type .= "2";
			$cross = 1;
		}
		else $cross = 0;
		$property1 = $type."_id";
		$property2 = $bean2->getMeta("type")."_id";
		$value1 = (int) $bean1->$idfield1;
		$value2 = (int) $bean2->$idfield2;
		try {
			$this->writer->deleteByCrit($table,array($property1=>$value1,$property2=>$value2));
			if ($cross) {
				$this->writer->deleteByCrit($table,array($property2=>$value1,$property1=>$value2));
			}
		}catch(RedBean_Exception_SQL $e ){
			if ($e->getSQLState()!="42S02" && $e->getSQLState()!="42S22") throw $e;
		}
	}
	/**
	 * Removes all relations for a bean
	 * @param RedBean_OODBBean $bean
	 * @param string $type
	 */
	public function clearRelations(RedBean_OODBBean $bean, $type) {
		$this->oodb->store($bean);
		$table = $this->getTable( array($bean->getMeta("type") , $type) );
		$idfield = $this->writer->getIDField($bean->getMeta("type"));
		if ($type==$bean->getMeta("type")) {
			$property2 = $type."2_id";
			$cross = 1;
		}
		else $cross = 0;
		$property = $bean->getMeta("type")."_id";
		try {
			$this->writer->deleteByCrit($table,array($property=>$bean->$idfield));
			if ($cross) {
				$this->writer->deleteByCrit($table,array($property2=>$bean->$idfield));
			}
		}catch(RedBean_Exception_SQL $e ){
			if ($e->getSQLState()!="42S02" && $e->getSQLState()!="42S22") throw $e;
		}
	}
	/**
	 * Creates a 1 to Many Association
	 * If the association fails it throws an exception.
	 * @throws RedBean_Exception_SQL $failedToEnforce1toN
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 * @return RedBean_AssociationManager $chainable
	 */
	public function set1toNAssoc(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$type = $bean1->getMeta("type");
		$this->clearRelations($bean2, $type);
		$this->associate($bean1, $bean2);
		if (count( $this->related($bean2, $type) )===1) {
			return $this;
		}
		else {
			throw new RedBean_Exception_SQL("Failed to enforce 1toN Relation for $type ");
		}
	}

}