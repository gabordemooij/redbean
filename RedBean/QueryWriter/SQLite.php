<?php
/**
 * RedBean SQLiteWriter
 *
 * @file				RedBean/QueryWriter/SQLite.php
 * @description			Represents a SQLite Database to RedBean
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author				Gabor de Mooij
 * @license				BSD
 */
class RedBean_QueryWriter_SQLite extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {


	/**
	 * Constructor
	 * The Query Writer Constructor also sets up the database
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 */
	public function __construct( RedBean_Adapter $adapter ) {
		$this->adapter = $adapter;
		parent::__construct();
	}

	/**
	 * Returns all tables in the database
	 *
	 * @return array $tables tables
	 */
	public function getTables() {
		return $this->adapter->getCol( "SELECT name FROM sqlite_master
			WHERE type='table' AND name!='sqlite_sequence';" );
	}

	/**
	 * Creates an empty, column-less table for a bean.
	 *
	 * @param string $table table
	 */
	public function createTable( $table ) {
		$idfield = $this->safeColumn($this->getIDfield($table));
		$table = $this->safeTable($table);
		$sql = "
                     CREATE TABLE $table ( $idfield INTEGER PRIMARY KEY AUTOINCREMENT )
				  ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns an array containing the column names of the specified table.
	 *
	 * @param string $table table
	 *
	 * @return array $columns columns
	 */
	public function getColumns( $table ) {
		$table = $this->safeTable($table, true);
		$columnsRaw = $this->adapter->get("PRAGMA table_info('$table')");
		$columns = array();
		foreach($columnsRaw as $r) {
			$columns[$r["name"]]=$r["type"];
		}
		return $columns;
	}

	public function getTypeForID() {
		return 1;
	}

	/**
	 * Returns the MySQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType( $value ) {
		return 1;
	}

	/**
	 * Returns the Type Code for a Column Description
	 *
	 * @param string $typedescription type description
	 *
	 * @return integer $typecode type code
	 */
	public function code( $typedescription ) {
		return 1;
	}

	/**
	 * Change (Widen) the column to the give type.
	 *
	 * @param string $table  table
	 * @param string $column column
	 *
	 * @param integer $type type
	 */
	public function widenColumn( $table, $column, $type ) {
		return true;
	}

	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table   table
	 * @param string $column1 first column
	 * @param string $column2 second column
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table);
		$name = "UQ_".sha1(implode(',',$columns));
		$sql = "CREATE UNIQUE INDEX IF NOT EXISTS $name ON $table (".implode(",",$columns).")";
		$this->adapter->exec($sql);
	}

	/**
	 * Given an Database Specific SQLState and a list of QueryWriter
	 * Standard SQL States this function converts the raw SQL state to a
	 * database agnostic ANSI-92 SQL states and checks if the given state
	 * is in the list of agnostic states.
	 *
	 * @param string $state state
	 * @param array  $list  list of states
	 *
	 * @return boolean $isInArray whether state is in list
	 */
	public function sqlStateIn($state, $list) {
		$sqlState = "0";
		if ($state == "HY000") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE;
		if ($state == "23000") $sqlState = RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION;
		return in_array($sqlState, $list);
	}




	/**
	 * Counts rows in a table.
	 * Uses SQLite optimization for deleting all records (i.e. no WHERE)
	 *
	 * @param string $beanType
	 *
	 * @return integer $numRowsFound
	 */
	public function wipe($type) {
		$table = $this->safeTable($type);
		$this->adapter->exec("DELETE FROM $table");
	}



	/**
	 * Not implemented for SQLite, uses triggers.
	 *
	 *
	 * @param  string $type        type you want to modify table of
	 * @param  string $targetType  target type
	 * @param  string $field       field of the type that needs to get the fk
	 * @param  string $targetField field where the fk needs to point to
	 *
	 * @return bool $success whether an FK has been added
	 */
	public function addFK( $type, $targetType, $field, $targetField) {
		//not supported yet
	}
}
