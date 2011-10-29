<?php
/**
 * RedBean SQLiteWriter with support for SQLite types
 *
 * @file				RedBean/QueryWriter/SQLiteT.php
 * @description			Represents a SQLite Database to RedBean
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author				Gabor de Mooij
 * @license				BSD
 */
class RedBean_QueryWriter_SQLiteT extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {
	/**
	 *
	 * @var RedBean_Adapter_DBAdapter
	 * Holds database adapter
	 */
	protected $adapter;

	/**
	 * @var string
	 * character to escape keyword table/column names
	 */
  	protected $quoteCharacter = '`';

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 * DATA TYPE
	 * Integer Data type
	 * @var integer
	 */
	const C_DATATYPE_INTEGER = 0;

	/**
	 * DATA TYPE
	 * Numeric Data type (for REAL and date/time)
	 * @var integer
	 */
	const C_DATATYPE_NUMERIC = 1;

	/**
	 * DATA TYPE
	 * Text type
	 * @var integer
	 */
	const C_DATATYPE_TEXT = 2;

	/**
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;




	/**
	 * @var array
	 * Supported Column Types
	 */
	public $typeno_sqltype = array(
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_INTEGER=>"INTEGER",
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_NUMERIC=>"NUMERIC",
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_TEXT=>"TEXT",
	);

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
	public $sqltype_typeno = array(
			  "INTEGER"=>RedBean_QueryWriter_SQLiteT::C_DATATYPE_INTEGER,
			  "NUMERIC"=>RedBean_QueryWriter_SQLiteT::C_DATATYPE_NUMERIC,
			  "TEXT"=>RedBean_QueryWriter_SQLiteT::C_DATATYPE_TEXT,
	);


	/**
	 * Constructor
	 * The Query Writer Constructor also sets up the database
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 */
	public function __construct( RedBean_Adapter $adapter ) {
		$this->adapter = $adapter;
		parent::__construct($adapter);
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID() {
		return self::C_DATATYPE_INTEGER;
	}

	/**
	 * Returns the MySQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param  string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType( $value ) {
		$sz = ($this->startsWithZeros($value));
		if (!$sz && $value===null) return self::C_DATATYPE_INTEGER; //for fks
		if (!$sz && is_numeric($value) && (intval($value)==$value) && $value<2147483648) return self::C_DATATYPE_INTEGER;
		if (!$sz && (is_numeric($value) && $value < 2147483648)
				  || preg_match("/\d\d\d\d\-\d\d\-\d\d/",$value)
				  || preg_match("/\d\d\d\d\-\d\d\-\d\d\s\d\d:\d\d:\d\d/",$value)
		) {
			return self::C_DATATYPE_NUMERIC;
		}


		return self::C_DATATYPE_TEXT;
	}

	/**
	 * Adds a column of a given type to a table
	 *
	 * @param string  $table  table
	 * @param string  $column column
	 * @param integer $type	  type
	 */
	public function addColumn( $table, $column, $type) {
		$table = $this->getFormattedTableName($table);
		$column = $this->check($column);
		$table = $this->check($table);
		$type=$this->typeno_sqltype[$type];
		$sql = "ALTER TABLE `$table` ADD `$column` $type ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns the Type Code for a Column Description
	 *
	 * @param string $typedescription description
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription ) {
		return ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
	}

	/**
	 * Quote Items, to prevent issues with reserved words.
	 *
	 * @param array $items items to quote
	 *
	 * @return $quotedfItems quoted items
	 */
	private function quote( $items ) {
		foreach($items as $k=>$item) {
			$items[$k]=$this->noKW($item);
		}
		return $items;
	}

	/**
	 * This method upgrades the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype ) {
		$table = $this->safeTable($type,true);
		$column = $this->safeColumn($column,true);
		$idfield = $this->safeColumn($this->getIDfield($type),true);
		$newtype = $this->typeno_sqltype[$datatype];
		$oldColumns = $this->getColumns($type);
		$oldColumnNames = $this->quote(array_keys($oldColumns));
		$newTableDefStr="";
		foreach($oldColumns as $oldName=>$oldType) {
			if ($oldName != $idfield) {
				if ($oldName!=$column) {
					$newTableDefStr .= ",`$oldName` $oldType";
				}
				else {
					$newTableDefStr .= ",`$oldName` $newtype";
				}
			}
		}
		$q = array();
		$q[] = "DROP TABLE IF EXISTS tmp_backup;";
		$q[] = "CREATE TEMPORARY TABLE tmp_backup(".implode(",",$oldColumnNames).");";
		$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
		$q[] = "DROP TABLE `$table`;";
		$q[] = "CREATE TABLE `$table` ( `$idfield` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  );";
		$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
		$q[] = "DROP TABLE tmp_backup;";
		foreach($q as $sq) {
			$this->adapter->exec($sq);
		}
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
	 * Adds a foreign key to a type
	 *
	 * @param  string $type        type you want to modify table of
	 * @param  string $targetType  target type
	 * @param  string $field       field of the type that needs to get the fk
	 * @param  string $targetField field where the fk needs to point to
	 *
	 * @return bool $success whether an FK has been added
	 */
	public function addFK( $type, $targetType, $field, $targetField) {
		return $this->buildFK($type, $targetType, $field, $targetField);
	}

	/**
	 * Adds a foreign key to a type
	 *
	 * @param  string $type        type you want to modify table of
	 * @param  string $targetType  target type
	 * @param  string $field       field of the type that needs to get the fk
	 * @param  string $targetField field where the fk needs to point to
	 * @param  integer $buildopt   0 = NO ACTION, 1 = ON DELETE CASCADE
	 *
	 * @return bool $success whether an FK has been added
	 */

	protected function buildFK($type, $targetType, $field, $targetField,$constraint=false) {

			try{
				$table = $this->safeTable($type,true);
				$targetTable = $this->safeTable($targetType,true);
				$field = $this->safeColumn($field,true);
				$targetField = $this->safeColumn($targetField,true);
				$idfield = $this->safeColumn($this->getIDfield($type),true);
				$oldColumns = $this->getColumns($type);
				$oldColumnNames = $this->quote(array_keys($oldColumns));
				$newTableDefStr="";
				foreach($oldColumns as $oldName=>$oldType) {
					if ($oldName != $idfield) {
						$newTableDefStr .= ",`$oldName` $oldType";
					}
				}

				//retrieve old foreign keys
				$sqlGetOldFKS = "PRAGMA foreign_key_list('$table'); ";
				$oldFKs = $this->adapter->get($sqlGetOldFKS);

				$restoreFKSQLSnippets = "";
				foreach($oldFKs as $oldFKInfo) {
					if ($oldFKInfo['from']==$field) {
						//this field already has a FK.
						return false;
					}
					$oldTable = $table;
					$oldField = $oldFKInfo['from'];
					$oldTargetTable = $oldFKInfo['table'];
					$oldTargetField = $oldFKInfo['to'];
					$restoreFKSQLSnippets .= ", FOREIGN KEY(`$oldField`) REFERENCES `$oldTargetTable`(`$oldTargetField`) ON DELETE ".$oldFKInfo['on_delete'];
				}

				$fkDef = $restoreFKSQLSnippets;

				if ($constraint) {
					$fkDef .= ', FOREIGN KEY(`'.$field.'`) REFERENCES `'.$targetTable.'`(`'.$targetField.'`) ON DELETE CASCADE ';

				}
				else {
					$fkDef .= ', FOREIGN KEY(`'.$field.'`) REFERENCES `'.$targetTable.'`(`'.$targetField.'`) ON DELETE SET NULL ON UPDATE SET NULL';

				}

				$q = array();
				$q[] = "DROP TABLE IF EXISTS tmp_backup;";
				$q[] = "CREATE TEMPORARY TABLE tmp_backup(".implode(",",$oldColumnNames).");";
				$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
				$q[] = "PRAGMA foreign_keys = 0 ";
				$q[] = "DROP TABLE `$table`;";
				$q[] = "CREATE TABLE `$table` ( `$idfield` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr $fkDef );";
				$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
				$q[] = "DROP TABLE tmp_backup;";
				$q[] = "PRAGMA foreign_keys = 1 ";


				foreach($q as $sq) {
					$this->adapter->exec($sq);
				}
			}
			catch(Exception $e){

			}

	}


	/**
	 * Add the constraints for a specific database driver: SQLite.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 * @param boolean			  $dontCache want to have cache?
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected  function constrain($table, $table1, $table2, $property1, $property2, $dontCache) {


		try{
			$writer = $this;

			$adapter = $this->adapter;
			//$fkCode = "fk".md5($table.$property1.$property2);

			$idfield1 = $writer->getIDField($table1);
			$idfield2 = $writer->getIDField($table2);

			$this->buildFK($table,$table1,$property1,$idfield1,true);
			$this->buildFK($table,$table2,$property2,$idfield2,true);

			return true;
		}
		catch(Exception $e){

			return false;
		}
	}

	/**
	 * Removes all tables and views from the database.
	 */
	public function wipeAll() {
		$this->adapter->exec('PRAGMA foreign_keys = 0 ');
		foreach($this->getTables() as $t) {
	 		try{
	 			$this->adapter->exec("drop table if exists`$t`");
	 		}
	 		catch(Exception $e){}
	 		try{
	 			$this->adapter->exec("drop view if exists`$t`");
	 		}
	 		catch(Exception $e){}
		}
		$this->adapter->exec('PRAGMA foreign_keys = 1 ');

	}



}
