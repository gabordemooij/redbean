<?php
/**
 * RedBean MySQLWriter
 * @file 		RedBean/QueryWriter/MySQL.php
 * @description		Represents a MySQL Database to RedBean
 *					To write a driver for a different database for RedBean
 *					you should only have to change this file.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_QueryWriter_SQLite extends RedBean_AQueryWriter implements RedBean_QueryWriter {


	/**
	 *
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;
	
	/**
	 * @var string
	 * character to escape keyword table/column names
	 */
  protected $quoteCharacter = '`';



	/**
	 * Constructor
	 * The Query Writer Constructor also sets up the database
	 * @param RedBean_Adapter_DBAdapter $adapter
	 */
	public function __construct( RedBean_Adapter $adapter, $frozen = false ) {
		$this->adapter = $adapter;
	}

	/**
	 * Returns all tables in the database
	 * @return array $tables
	 */
	public function getTables() {
		return $this->adapter->getCol( "SELECT name FROM sqlite_master
			WHERE type='table' AND name!='sqlite_sequence';" );
	}

	/**
	 * Creates an empty, column-less table for a bean.
	 * @param string $table
	 */
	public function createTable( $table ) {
		$idfield = $this->getIDfield($table, true);
		$table = $this->safeTable($table);
		$sql = "
                     CREATE TABLE $table ( $idfield INTEGER PRIMARY KEY AUTOINCREMENT )
				  ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns an array containing the column names of the specified table.
	 * @param string $table
	 * @return array $columns
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

	/**
	 * Returns the MySQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 * @param string $value
	 * @return integer $type
	 */
	public function scanType( $value ) {
		return 1;
	}

	/**
	 * Returns the Type Code for a Column Description
	 * @param string $typedescription
	 * @return integer $typecode
	 */
	public function code( $typedescription ) {
		return 1;
	}

	/**
	 * Change (Widen) the column to the give type.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
	public function widenColumn( $table, $column, $type ) {
		return true;
	}

	/**
	 * Adds a Unique index constrain to the table.
	 * @param string $table
	 * @param string $col1
	 * @param string $col2
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table);
		$name = "UQ_".sha1(implode(',',$columns));
		$sql = "CREATE UNIQUE INDEX IF NOT EXISTS $name ON $table (".implode(",",$columns).")";
		$this->adapter->exec($sql);
	}

	public function sqlStateIn($state, $list) {
		$sqlState = "0";
		if ($state == "HY000") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE;
		if ($state == "23000") $sqlState = RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION;
		return in_array($sqlState, $list);
	}

}
