<?php
/**
 * RedBean Bean Constraint
 * @file			RedBean/Plugin/Constraint.php
 * @description		Adds Cascaded Delete functionality for a pair of beans
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Constraint {

	/**
	 *
	 * @var array
	 * Keeps track of foreign keys (only to improve fluid performance)
	 */
	private static $fkcache = array();

	/**
	 * Ensures that given an association between
	 * $bean1 and $bean2,
	 * if one of them gets trashed the association will be
	 * automatically removed.
	 *
	 * @param RedBean_OODBBean $bean1 bean
	 * @param RedBean_OODBBean $bean2 bean
	 *
	 * @return boolean $addedFKS whether we succeeded
	 */
	public static function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $dontCache = false ) {

		//Fetch the toolbox
		$toolbox = RedBean_Setup::getToolBox();

		RedBean_CompatManager::scanDirect($toolbox, array(
				  RedBean_CompatManager::C_SYSTEM_MYSQL => "5",
				  RedBean_CompatManager::C_SYSTEM_SQLITE => "3",
				  RedBean_CompatManager::C_SYSTEM_POSTGRESQL => "7",));


		//Create an association manager
		$association = new RedBean_AssociationManager( $toolbox );
		$writer = $toolbox->getWriter();
		$oodb = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();

		//Frozen? Then we may not alter the schema!
		if ($oodb->isFrozen()) return false;

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
		//if (isset(self::$fkcache[$table])) return false;
		$fkCode = "fk".md5($table.$property1.$property2);
		if (isset(self::$fkcache[$fkCode])) return false;
		//Dispatch to right method

		try {
			if ($writer instanceof RedBean_QueryWriter_PostgreSQL) {
				return self::constraintPostgreSQL($toolbox, $table, $table1, $table2, $property1, $property2, $dontCache);
			}
			if ($writer instanceof RedBean_QueryWriter_SQLite) {
				return self::constraintSQLite($toolbox, $table, $table1, $table2, $property1, $property2, $dontCache);
			}
			if ($writer instanceof RedBean_QueryWriter_MySQL) {
				return self::constraintMySQL($toolbox, $table, $table1, $table2, $property1, $property2, $dontCache);
			}
		}
		catch(RedBean_Exception_SQL $e) {
			if (!$writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}

		return false;

	}

	/**
	 * Add the constraints for a specific database driver: PostgreSQL.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param RedBean_ToolBox $toolbox   toolbox
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 * @param boolean			  $dontCache want to have cache?
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	private static function constraintPostgreSQL($toolbox, $table, $table1, $table2, $property1, $property2, $dontCache) {
		$writer = $toolbox->getWriter();
		$oodb = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();
		$fkCode = "fk".md5($table.$property1.$property2);
		$sql = "
					SELECT
							c.oid,
							n.nspname,
							c.relname,
							n2.nspname,
							c2.relname,
							cons.conname
					FROM pg_class c
					JOIN pg_namespace n ON n.oid = c.relnamespace
					LEFT OUTER JOIN pg_constraint cons ON cons.conrelid = c.oid
					LEFT OUTER JOIN pg_class c2 ON cons.confrelid = c2.oid
					LEFT OUTER JOIN pg_namespace n2 ON n2.oid = c2.relnamespace
					WHERE c.relkind = 'r'
					AND n.nspname IN ('public')
					AND (cons.contype = 'f' OR cons.contype IS NULL)
					AND
					(  cons.conname = '{$fkCode}a'	OR  cons.conname = '{$fkCode}b' )

				  ";

		$rows = $adapter->get( $sql );
		if (!count($rows)) {
			if (!$dontCache) self::$fkcache[ $fkCode ] = true;
			$sql1 = "ALTER TABLE $table ADD CONSTRAINT
					  {$fkCode}a FOREIGN KEY ($property1)
						REFERENCES $table1 (id) ON DELETE CASCADE ";
			$sql2 = "ALTER TABLE $table ADD CONSTRAINT
					  {$fkCode}b FOREIGN KEY ($property2)
						REFERENCES $table2 (id) ON DELETE CASCADE ";
			$adapter->exec($sql1);
			$adapter->exec($sql2);
		}
		return true;
	}

	/**
	 * Add the constraints for a specific database driver: MySQL.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param RedBean_ToolBox $toolbox   toolbox
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 * @param boolean			  $dontCache want to have cache?
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	private static function constraintMySQL($toolbox, $table, $table1, $table2, $property1, $property2, $dontCache) {
		$writer = $toolbox->getWriter();
		$oodb = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();
		$db = $adapter->getCell("select database()");
		$fkCode = "fk".md5($table.$property1.$property2);
		$fks =  $adapter->getCell("
			SELECT count(*)
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA ='$db' AND TABLE_NAME ='$table' AND
			CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME is not null
				  ");

		//already foreign keys added in this association table
		if ($fks>0) return false;

		//add the table to the cache, so we dont have to fire the fk query all the time.
		if (!$dontCache) self::$fkcache[ $fkCode ] = true;
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

	/**
	 * Add the constraints for a specific database driver: SQLite.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param RedBean_ToolBox $toolbox   toolbox
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 * @param boolean			  $dontCache want to have cache?
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	private static function constraintSQLite($toolbox, $table, $table1, $table2, $property1, $property2, $dontCache) {
		$writer = $toolbox->getWriter();
		$oodb = $toolbox->getRedBean();
		$adapter = $toolbox->getDatabaseAdapter();
		$fkCode = "fk".md5($table.$property1.$property2);
		$sql1 = "
			CREATE TRIGGER IF NOT EXISTS {$fkCode}a
				BEFORE DELETE ON $table1
				FOR EACH ROW BEGIN
					DELETE FROM $table WHERE  $table.$property1 = OLD.id;
				END;
				  ";

		$sql2 = "
			CREATE TRIGGER IF NOT EXISTS {$fkCode}b
				BEFORE DELETE ON $table2
				FOR EACH ROW BEGIN
					DELETE FROM $table WHERE $table.$property2 = OLD.id;
				END;

				  ";
		$adapter->exec($sql1);
		$adapter->exec($sql2);
		return true;
	}

}
