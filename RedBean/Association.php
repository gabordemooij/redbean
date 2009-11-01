<?php
/**
 * RedBean Association
 * @package 		RedBean/Association.php
 * @description		This is actually more like an example than
 *					a real part of RedBean. Since version 0.7 you can create
 *					your own ORM structures with RedBean. Association is a
 *					simplistic example of how you might manage associated beans.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_AssociationManager {

	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var RedBean_DBAdapter
	 */
	private $adapter;

	/**
	 * Constructor
	 * @param RedBean_ToolBox $tools
	 */
	public function __construct( RedBean_ToolBox $tools ) {
		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
	}
	/**
	 * Creates a table name based on a types array.
	 * @param array $types
	 * @return string $table
	 */
	private function getTable( $types ) {
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
		$bean = $this->oodb->dispense($table);
		$property1 = $bean1->getMeta("type") . "_id";
		$property2 = $bean2->getMeta("type") . "_id";
		if ($property1==$property2) $property2 = $bean2->getMeta("type")."2_id";
		$bean->setMeta( "buildcommand.unique" , array( array( $property1, $property2 )));
		$this->oodb->store($bean1);
		$this->oodb->store($bean2);
		$bean->$property1 = $bean1->id;
		$bean->$property2 = $bean2->id;
		$this->oodb->store( $bean );
	}

	/**
	 * Gets related beans of type $type for bean $bean
	 * @param RedBean_OODBBean $bean
	 * @param string $type
	 * @return array $ids
	 */
	public function related( RedBean_OODBBean $bean, $type ) {
		$table = $this->getTable( array($bean->getMeta("type") , $type) );
		if ($type==$bean->getMeta("type")) {// echo "<b>CROSS</b>";
			$type .= "2";
			$cross = 1;
		}
		else $cross=0;
		$targetproperty = $type."_id";
		$property = $bean->getMeta("type")."_id";
		$sqlFetchKeys = " SELECT ".$this->adapter->escape($targetproperty)." FROM `$table` WHERE ".$this->adapter->escape($property)."
			= ".$this->adapter->escape($bean->id);
		if ($cross) {
			$sqlFetchKeys .= " UNION SELECT ".$this->adapter->escape($property)." 
			FROM `$table`
			WHERE ".$this->adapter->escape($targetproperty)." = ".$this->adapter->escape($bean->id);;
		}
		try{
			return $this->adapter->getCol( $sqlFetchKeys );
		}catch(RedBean_Exception_SQL $e){
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
		$type = $bean1->getMeta("type");
		if ($type==$bean2->getMeta("type")) { //echo "<b>CROSS</b>";
			$type .= "2";
			$cross = 1;
		}
		else $cross = 0;
		$property1 = $type."_id";
		$property2 = $bean2->getMeta("type")."_id";
		$value1 = (int) $bean1->id;
		$value2 = (int) $bean2->id;
		$sqlDeleteAssoc = "DELETE FROM `$table`
		WHERE 
		( $property1 = $value1 AND $property2 = $value2 )	";
		if ($cross) {
			$sqlDeleteAssoc .= " OR ( $property2 = $value1 AND $property1 = $value2 ) ";
		}
		try{
		$this->adapter->exec( $sqlDeleteAssoc );
		}catch(RedBean_Exception_SQL $e){
			if ($e->getSQLState()!="42S02" && $e->getSQLState()!="42S22") throw $e;
		}
	}
	/**
	 * Removes all relations for a bean
	 * @param RedBean_OODBBean $bean
	 * @param <type> $type
	 */
	public function clearRelations(RedBean_OODBBean $bean, $type) {
		$this->oodb->store($bean);
		$table = $this->getTable( array($bean->getMeta("type") , $type) );
		if ($type==$bean->getMeta("type")) { //echo "<b>CROSS</b>";
			$property2 = $type."2_id";
			$cross = 1;
		}
		else $cross = 0;
		$property = $bean->getMeta("type")."_id";
		$sql = "DELETE FROM `$table`
		WHERE ".$this->adapter->escape($property)." = ".$this->adapter->escape($bean->id);
		if ($cross){
			$sql .= " OR  ".$this->adapter->escape($property2)." = ".$this->adapter->escape($bean->id);;
		}
		try{
		$this->adapter->exec($sql);
		}catch(RedBean_Exception_SQL $e){
			if ($e->getSQLState()!="42S02" && $e->getSQLState()!="42S22") throw $e;
		}
	}
	/**
	 * Creates a 1 to Many Association
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 */
	public function set1toNAssoc(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$this->clearRelations($bean2, $bean1->getMeta("type"));
		$this->associate($bean1, $bean2);
	}

}