<?php
/**
 * RedBean Bean Constraint
 * @file			RedBean/Plugin/Constraint.php
 * @description		Adds Cascaded Delete functionality for a pair of beans
 *
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Plugin_Constraint {

	//Keeps track of foreign keys (only to improve fluid performance)
	private static $fkcache = array();

	/**
	 * Ensures that given an association between
	 * $bean1 and $bean2,
	 * if one of them gets trashed the association will be
	 * automatically removed.
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 * @return boolean $addedFKS
	 */
	public static function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $dontCache = false ) {

		//Fetch the toolbox
		$toolbox = RedBean_Setup::getToolBox();

		RedBean_CompatManager::scanDirect($toolbox, array(RedBean_CompatManager::C_SYSTEM_MYSQL => "5"));


		//Create an association manager
		$association = new RedBean_AssociationManager( $toolbox );
		$writer = $toolbox->getWriter();
		$oodb = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();

		//Frozen? Then we may not alter the schema!
		if ($oodb->isFrozen()) return false;

				
		//$adapter->getDatabase()->setDebugMode(1);

		$table1 = $bean1->getMeta("type");
		$table2 = $bean2->getMeta("type");
		$table = $association->getTable( array( $table1,$table2) );
		$idfield1 = $writer->getIDField($bean1->getMeta("type"));
		$idfield2 = $writer->getIDField($bean2->getMeta("type"));
		$bean = $oodb->dispense($table);
		$property1 = $bean1->getMeta("type") . "_id";
		$property2 = $bean2->getMeta("type") . "_id";
		if ($property1==$property2) $property2 = $bean2->getMeta("type")."2_id";

		$table = $adapter->escape($table);
		$table1 = $adapter->escape($table1);
		$table2 = $adapter->escape($table2);
		$property1 = $adapter->escape($property1);
		$property2 = $adapter->escape($property2);

		//In Cache? Then we dont need to bother
		if (isset(self::$fkcache[$table])) return false;

		$db = $adapter->getCell("select database()");
		$fks =  $adapter->getCell("
			SELECT count(*)
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA ='$db' AND TABLE_NAME ='$table' AND
			CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME is not null
		");

		//already foreign keys added in this association table
		if ($fks>0) return false;

		//add the table to the cache, so we dont have to fire the fk query all the time.
		if (!$dontCache) self::$fkcache[ $table ] = true;

		$columns = $writer->getColumns($table);

		if ($writer->code($columns[$property1])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
			$writer->widenColumn($table, $property1, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
		}
		if ($writer->code($columns[$property2])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
			$writer->widenColumn($table, $property2, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
		}



		$sql = "
			ALTER TABLE ".$writer->noKW($table)."
			ADD FOREIGN KEY($property1) references $table1(id) ON DELETE CASCADE;

		";
		$adapter->exec( $sql );
		$sql ="
			ALTER TABLE ".$writer->noKW($table)."
			ADD FOREIGN KEY($property2) references $table2(id) ON DELETE CASCADE
		";
		$adapter->exec( $sql );
		return true;

	}

}
