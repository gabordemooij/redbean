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

	/**
	 * Ensures that given an association between
	 * $bean1 and $bean2,
	 * if one of them gets trashed the association will be
	 * automatically removed.
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 */
	public static function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {

		//Fetch the toolbox
		$toolbox = RedBean_Setup::getToolBox();

		//Create an association manager
		$association = new RedBean_AssociationManager( $toolbox );
		$writer = $toolbox->getWriter();
		$oodb = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();
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

		$columns = $writer->getColumns($table);
		if ($writer->code($columns[$property1])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
			$writer->widenColumn($table, $property1, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
		}
		if ($writer->code($columns[$property2])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
			$writer->widenColumn($table, $property2, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
		}
		$sql = "
			ALTER TABLE `$table`
			ADD FOREIGN KEY($property1) references $table1(id) ON DELETE CASCADE;

		";
		$adapter->exec( $sql );
		$sql ="
			ALTER TABLE `$table`
			ADD FOREIGN KEY($property2) references $table2(id) ON DELETE CASCADE
		";
		$adapter->exec( $sql );


	}

}
