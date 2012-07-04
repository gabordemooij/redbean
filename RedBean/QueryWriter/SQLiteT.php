<?php
/**
 * RedBean SQLiteWriter with support for SQLite types
 *
 * @file				RedBean/QueryWriter/SQLiteT.php
 * @description			Represents a SQLite Database to RedBean
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author				Gabor de Mooij and the RedBeanPHP Community
 * @license				BSD/GPLv2
 * 
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
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
	 * Constructor
	 * The Query Writer Constructor also sets up the database
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 */
	public function __construct( RedBean_Adapter $adapter ) {
	
		$this->typeno_sqltype = array(
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_INTEGER=>'INTEGER',
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_NUMERIC=>'NUMERIC',
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_TEXT=>'TEXT',
		);
		
		$this->sqltype_typeno = array();
		foreach($this->typeno_sqltype as $k=>$v)
		$this->sqltype_typeno[$v]=$k;
		
				
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
	public function scanType( $value, $flagSpecial=false ) {
		$this->svalue=$value;
		if ($value===false) return self::C_DATATYPE_INTEGER;
		if ($value===null) return self::C_DATATYPE_INTEGER; //for fks
		if ($this->startsWithZeros($value)) return self::C_DATATYPE_TEXT;
		if (is_numeric($value) && (intval($value)==$value) && $value<2147483648) return self::C_DATATYPE_INTEGER;
		if ((is_numeric($value) && $value < 2147483648)
				  || preg_match('/\d{4}\-\d\d\-\d\d/',$value)
				  || preg_match('/\d{4}\-\d\d\-\d\d\s\d\d:\d\d:\d\d/',$value)
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
		$column = $this->check($column);
		$table = $this->check($table);
		$type=$this->typeno_sqltype[$type];
		$sql = "ALTER TABLE `$table` ADD `$column` $type ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription, $includeSpecials = false ) {
		$r =  ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
		if ($includeSpecials) return $r;
		if ($r > self::C_DATATYPE_SPECIFIED) return self::C_DATATYPE_SPECIFIED;
		return $r;
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
	 * Gets all information about a table (from a type).
	 * 
	 * Format:
	 * array(
	 *		name => name of the table
	 *		columns => array( name => datatype )
	 *		indexes => array() raw index information rows from PRAGMA query
	 *		keys => array() raw key information rows from PRAGMA query
	 * )
	 * 
	 * @param string $type type you want to get info of
	 * 
	 * @return array $info 
	 */
	protected function getTable($type) {
		$tableName = $this->safeTable($type,true);
		$columns = $this->getColumns($type);
		$indexes = $this->getIndexes($type);
		$keys = $this->getKeys($type);
		$table = array('columns'=>$columns,'indexes'=>$indexes,'keys'=>$keys,'name'=>$tableName);
		$this->tableArchive[$tableName] = $table;
		return $table;
	}
	
	/**
	 * Puts a table. Updates the table structure.
	 * In SQLite we can't change columns, drop columns, change or add foreign keys so we
	 * have a table-rebuild function. You simply load your table with getTable(), modify it and
	 * then store it with putTable()...
	 * 
	 * @param array $tableMap information array 
	 */
	protected function putTable($tableMap) {
		$table = $tableMap['name'];
		$q = array();
		$q[] = "DROP TABLE IF EXISTS tmp_backup;";
		$oldColumnNames = array_keys($this->getColumns($table));
		foreach($oldColumnNames as $k=>$v) $oldColumnNames[$k] = "`$v`";
		$q[] = "CREATE TEMPORARY TABLE tmp_backup(".implode(",",$oldColumnNames).");";
		$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
		$q[] = "PRAGMA foreign_keys = 0 ";
		$q[] = "DROP TABLE `$table`;";
		$newTableDefStr = '';
		foreach($tableMap['columns'] as $column=>$type) {
			if ($column != 'id') {
				$newTableDefStr .= ",`$column` $type";
			}
		}
		$fkDef = '';
		foreach($tableMap['keys'] as $key) {
			$fkDef .= ", FOREIGN KEY(`{$key['from']}`) 
						 REFERENCES `{$key['table']}`(`{$key['to']}`) 
						 ON DELETE {$key['on_delete']} ON UPDATE {$key['on_update']}";
		}
		$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  $fkDef );";
		foreach($tableMap['indexes'] as $name=>$index)  {
			if (strpos($name,'UQ_')===0) {
				if (strpos($name,'__')===false) continue; //old  index, forget.
				$cols = explode('__',substr($name,strlen('UQ_'.$table)));
				foreach($cols as $k=>$v) $cols[$k] = "`$v`";
				$q[] = "CREATE UNIQUE INDEX $name ON `$table` (".implode(',',$cols).")";
			}
			else $q[] = "CREATE INDEX $name ON `$table` ({$index['name']}) ";
		}
		$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
		$q[] = "DROP TABLE tmp_backup;";
		$q[] = "PRAGMA foreign_keys = 1 ";
		foreach($q as $sq) $this->adapter->exec($sq);
		
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
		$t = $this->getTable($type);
		$t['columns'][$column] = $this->typeno_sqltype[$datatype];
		$this->putTable($t);
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
		$table = $this->safeTable($table);
		$sql = "CREATE TABLE $table ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ";
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
			$columns[$r['name']]=$r['type'];
		}
		return $columns;
	}

	/**
	 * Returns the indexes for type $type.
	 * 
	 * @param string $type
	 * 
	 * @return array $indexInfo index information
	 */
	protected function getIndexes($type) {
		$table = $this->safeTable($type, true);
		$indexes = $this->adapter->get("PRAGMA index_list('$table')");
		$indexInfoList = array();
		foreach($indexes as $i) {
			$indexInfoList[$i['name']] = $this->adapter->getRow("PRAGMA index_info('{$i['name']}') ");
			$indexInfoList[$i['name']]['unique'] = $i['unique'];
		}
		return $indexInfoList;
	}
	
	/**
	 * Returns the keys for type $type.
	 * 
	 * @param string $type
	 * 
	 * @return array $keysInfo keys information
	 */
	protected function getKeys($type) {
		$table = $this->safeTable($type,true);
		$keys = $this->adapter->get("PRAGMA foreign_key_list('$table')");
		$keyInfoList = array();
		foreach($keys as $k) {
			$keyInfoList['from_'.$k['from'].'_to_table_'.$k['table'].'_col_'.$k['to']] = $k;
		}
		return $keyInfoList;
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
	public function addUniqueIndex( $type,$columns ) {
		$table = $this->safeTable($type,true);
		$name = 'UQ_'.$table.implode('__',$columns);
		$t = $this->getTable($type);
		if (isset($t['indexes'][$name])) return;
		$t['indexes'][$name] = array('name'=>$name);
		$this->putTable($t);
		
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
		$stateMap = array(
			'HY000'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'23000'=>RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);
		return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'),$list);
	}

	/**
	 * This method should add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  $type   type to add index to
	 * @param  $name   name of the new index
	 * @param  $column field to index
	 *
	 * @return void
	 */
	public function addIndex($type, $name, $column) {
		$table = $type;
		$table = $this->safeTable($table);
		$name = preg_replace('/\W/','',$name);
		$column = $this->safeColumn($column,true);
		foreach( $this->adapter->get("PRAGMA INDEX_LIST($table) ") as $ind) {
			if ($ind['name']===$name) return;
		}
		$t = $this->getTable($type);
		$t['indexes'][$name] = array('name'=>$column);
		return $this->putTable($t);
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
	 * @param string  $type        type you want to modify table of
	 * @param string  $targetType  target type
	 * @param string  $field       field of the type that needs to get the fk
	 * @param string  $targetField field where the fk needs to point to
	 * @param boolean $isDep       whether this field is dependent on it's referenced record
	 *
	 * @return bool $success whether an FK has been added
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDep=false) {
		return $this->buildFK($type, $targetType, $field, $targetField, $isDep);
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
	 * @return boolean $didIt
	 * 
	 * @note: cant put this in try-catch because that can hide the fact
	 * that database has been damaged. 
	 */

	protected function buildFK($type, $targetType, $field, $targetField,$constraint=false) {
		$consSQL = ($constraint ? 'CASCADE' : 'SET NULL');
		$t = $this->getTable($type);
		$label = 'from_'.$field.'_to_table_'.$targetType.'_col_'.$targetField;
		if (isset($t['keys'][$label]) 
				&& $t['keys'][$label]['table']===$targetType 
				&& $t['keys'][$label]['from']===$field
				&& $t['keys'][$label]['to']===$targetField
				&& $t['keys'][$label]['on_delete']===$consSQL
		) return false;
		
		$t['keys'][$label] = array(
			'table' => $targetType,
			'from' => $field,
			'to' => $targetField,
			'on_update' => 'SET NULL',
			'on_delete' => $consSQL
		);
		$this->putTable($t);
		return true;
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
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected  function constrain($table, $table1, $table2, $property1, $property2) {
		$writer = $this;
		$adapter = $this->adapter;
		$firstState = $this->buildFK($table,$table1,$property1,'id',true);
		$secondState = $this->buildFK($table,$table2,$property2,'id',true);
		return ($firstState && $secondState);
	}

	/**
	 * Removes all tables and views from the database.
	 * 
	 * @return void
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
