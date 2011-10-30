<?php
/**
 * RedBean Association
 * @file				RedBean/AssociationManager.php
 * @description	Manages simple bean associations.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_AssociationManager extends RedBean_Observable {



	/**
	 * Contains a reference to the Object Database OODB
	 * @var RedBean_OODB
	 */
	protected $oodb;

	/**
	 * Contains a reference to the Database Adapter
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * Contains a reference to the Query Writer
	 * @var RedBean_QueryWriter
	 */
	protected $writer;

	protected $flagUseConstraints = true;

	/**
	 * Constructor
	 *
	 * @param RedBean_ToolBox $tools toolbox
	 */
	public function __construct( RedBean_ToolBox $tools ) {
		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer = $tools->getWriter();
		$this->toolbox = $tools;
	}

	/**
	 * Creates a table name based on a types array.
	 *
	 * @param array $types types
	 *
	 * @return string $table table
	 */
	public function getTable( $types ) {
		return $this->writer->getAssocTableFormat($types);
	}
	/**
	 * Associates two beans with eachother.
	 *
	 * @param RedBean_OODBBean $bean1 bean1
	 * @param RedBean_OODBBean $bean2 bean2
	 */
	public function associate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$table = $this->getTable( array($bean1->getMeta("type") , $bean2->getMeta("type")) );
		$bean = $this->oodb->dispense($table);
		return $this->associateBeans( $bean1, $bean2, $bean );
	}

	/**
	 * Whether to use constraints
	 *
	 * @param  boolean $trueFalse yesno
	 *
	 * @return void
	 */
	public function setUseConstraints( $trueFalse ) {
		$this->flagUseConstraints = $trueFalse;
	}


	/**
	 * Associates a pair of beans. This method associates two beans, no matter
	 * what types.
	 *
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 * @param RedBean_OODBBean $bean  base bean
	 *
	 * @return mixed $id either the link ID or null
	 */
	protected function associateBeans(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $bean) {
		$idfield1 = $this->writer->getIDField($bean1->getMeta("type"));
		$idfield2 = $this->writer->getIDField($bean2->getMeta("type"));
		$property1 = $bean1->getMeta("type") . "_id";
		$property2 = $bean2->getMeta("type") . "_id";
		if ($property1==$property2) $property2 = $bean2->getMeta("type")."2_id";
		//add a build command for Unique Indexes
		$bean->setMeta("buildcommand.unique" , array(array($property1, $property2)));
		//add a build command for Single Column Index (to improve performance in case unqiue cant be used)
		$indexName1 = "index_for_".$bean->getMeta("type")."_".$property1;
		$indexName2 = "index_for_".$bean->getMeta("type")."_".$property2;
		$bean->setMeta("buildcommand.indexes", array($property1=>$indexName1,$property2=>$indexName2));
		$this->oodb->store($bean1);
		$this->oodb->store($bean2);
		$bean->setMeta("assoc.".$bean1->getMeta("type"),$bean1);
		$bean->setMeta("assoc.".$bean2->getMeta("type"),$bean2);
		$bean->setMeta("cast.$property1","id");
		$bean->setMeta("cast.$property2","id");
		$bean->$property1 = $bean1->$idfield1;
		$bean->$property2 = $bean2->$idfield2;
		try {
			$id = $this->oodb->store( $bean );
			//On creation, add constraints....
			if ($this->flagUseConstraints &&
				!$this->oodb->isFrozen() &&
				$bean->getMeta("buildreport.flags.created")){

				$bean->setMeta("buildreport.flags.created",0);
				if (!$this->oodb->isFrozen())
				$this->writer->addConstraint( $bean1, $bean2 );
			}
			return $id;
		}
		catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
			))) throw $e;
		}
	}

	/**
	 * Returns all ids of beans of type $type that are related to $bean. If the
	 * $getLinks parameter is set to boolean TRUE this method will return the ids
	 * of the association beans instead. You can also add additional SQL. This SQL
	 * will be appended to the original query string used by this method. Note that this
	 * method will not return beans, just keys. For a more convenient method see the R-facade
	 * method related(), that is in fact a wrapper for this method that offers a more
	 * convenient solution. If you want to make use of this method, consider the
	 * OODB batch() method to convert the ids to beans.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param RedBean_OODBBean $bean     reference bean
	 * @param string           $type     target type
	 * @param bool             $getLinks whether you are interested in the assoc records
	 * @param bool             $sql      room for additional SQL
	 *
	 * @return array $ids
	 */
	public function related( RedBean_OODBBean $bean, $type, $getLinks=false, $sql=false) {
	$table = $this->getTable( array($bean->getMeta("type") , $type) );
		$idfield = $this->writer->getIDField($bean->getMeta("type"));
		if ($type==$bean->getMeta("type")) {
			$type .= "2";
			$cross = 1;
		}
		else $cross=0;
		if (!$getLinks) $targetproperty = $type."_id"; else $targetproperty="id";

		$property = $bean->getMeta("type")."_id";
		try {
				$sqlFetchKeys = $this->writer->selectRecord(
					  $table,
					  array( $property => array( $bean->$idfield ) ),
					  $sql,
					  false
				);
				$sqlResult = array();
				foreach( $sqlFetchKeys as $row ) {
					$sqlResult[] = $row[$targetproperty];
				}
				if ($cross) {
					$sqlFetchKeys2 = $this->writer->selectRecord(
							  $table,
							  array( $targetproperty => array( $bean->$idfield ) ),
							  $sql,
							  false
					);
					foreach( $sqlFetchKeys2 as $row ) {
						$sqlResult[] = $row[$property];
					}
				}
			return $sqlResult; //or returns rows in case of $sql != empty
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
			return array();
		}
	}

	/**
	 * Breaks the association between two beans. This method unassociates two beans. If the
	 * method succeeds the beans will no longer form an association. In the database
	 * this means that the association record will be removed. This method uses the
	 * OODB trash() method to remove the association links, thus giving FUSE models the
	 * opportunity to hook-in additional business logic. If the $fast parameter is
	 * set to boolean TRUE this method will remove the beans without their consent,
	 * bypassing FUSE. This can be used to improve performance.
	 *
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 * @param boolean          $fast  If TRUE, removes the entries by query without FUSE
	 */
	public function unassociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $fast=null) {
		$this->oodb->store($bean1);
		$this->oodb->store($bean2);
		$table = $this->getTable( array($bean1->getMeta("type") , $bean2->getMeta("type")) );
		$idfield1 = $this->writer->getIDField($bean1->getMeta("type"));
		$idfield2 = $this->writer->getIDField($bean2->getMeta("type"));
		$type = $bean1->getMeta("type");
		if ($type==$bean2->getMeta("type")) {
			$type .= "2";
			$cross = 1;
		}
		else $cross = 0;
		$property1 = $type."_id";
		$property2 = $bean2->getMeta("type")."_id";
		$value1 = (int) $bean1->$idfield1;
		$value2 = (int) $bean2->$idfield2;
		try {
			$rows = $this->writer->selectRecord($table,array(
				$property1 => array($value1), $property2=>array($value2)),null,$fast
			);
			if ($cross) {
				$rows2 = $this->writer->selectRecord($table,array(
				$property2 => array($value1), $property1=>array($value2)),null,$fast
				);
				if ($fast) return;
				$rows = array_merge($rows,$rows2);
			}
			if ($fast) return;
			$beans = $this->oodb->convertToBeans($table,$rows);
			foreach($beans as $link) {
				$link->setMeta("assoc.".$bean1->getMeta("type"),$bean1);
				$link->setMeta("assoc.".$bean2->getMeta("type"),$bean2);

				$this->oodb->trash($link);
			}
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		return;
	}

	/**
	 * Removes all relations for a bean. This method breaks every connection between
	 * a certain bean $bean and every other bean of type $type. Warning: this method
	 * is really fast because it uses a direct SQL query however it does not inform the
	 * models about this. If you want to notify FUSE models about deletion use a foreach-loop
	 * with unassociate() instead. (that might be slower though)
	 *
	 * @param RedBean_OODBBean $bean reference bean
	 * @param string           $type type of beans that need to be unassociated
	 *
	 * @return void
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
			$this->writer->selectRecord( $table, array($property=>array($bean->$idfield)),null,true);

			if ($cross) {
				$this->writer->selectRecord( $table, array($property2=>array($bean->$idfield)),null,true);

			}

		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
	}

	/**
	 * Given two beans this function returns TRUE if they are associated using a
	 * many-to-many association, FALSE otherwise.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param RedBean_OODBBean $bean1 bean
	 * @param RedBean_OODBBean $bean2 bean
	 *
	 * @return bool $related whether they are associated N-M
	 */
	public function areRelated(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		if (!$bean1->getID() || !$bean2->getID()) return false;
		$table = $this->getTable( array($bean1->getMeta("type") , $bean2->getMeta("type")) );
		$idfield1 = $this->writer->getIDField($bean1->getMeta("type"));
		$idfield2 = $this->writer->getIDField($bean2->getMeta("type"));
		$type = $bean1->getMeta("type");
		if ($type==$bean2->getMeta("type")) {
			$type .= "2";
			$cross = 1;
		}
		else $cross = 0;
		$property1 = $type."_id";
		$property2 = $bean2->getMeta("type")."_id";
		$value1 = (int) $bean1->$idfield1;
		$value2 = (int) $bean2->$idfield2;
		try {
			$rows = $this->writer->selectRecord($table,array(
				$property1 => array($value1), $property2=>array($value2)),null
			);
			if ($cross) {
				$rows2 = $this->writer->selectRecord($table,array(
				$property2 => array($value1), $property1=>array($value2)),null
				);
				$rows = array_merge($rows,$rows2);
			}

		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
			return false;
		}
		return (count($rows)>0);
	}
}