<?php
/**
 * RedBean Cubrid Writer 
 *
 * @file				RedBean/QueryWriter/Cubrid.php
 * @description			Represents a Cubrid Database to RedBean
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author				Gabor de Mooij and the RedBeanPHP Community
 * @license				BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 
 */
class RedBean_QueryWriter_Cubrid extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {

	
	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 *
	 * DATA TYPE
	 * Signed 4 byte Integer
	 * @var integer
	 */
	const C_DATATYPE_INTEGER = 0;

	/**
	 * DATA TYPE
	 * Double precision floating point number
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 1;
	
	/**
	 *
	 * DATA TYPE
	 * Variable length text
	 * @var integer
	 */
	const C_DATATYPE_STRING = 2;

	
	/**
	 * Special type date for storing date values: YYYY-MM-DD
	 * @var integer
	 */	
	const C_DATATYPE_SPECIAL_DATE = 80;
	
	/**
	 * Special type datetime for store date-time values: YYYY-MM-DD HH:II:SS
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	

	/**
	 * 
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;

	
	
	/**
	 * Holds the RedBean Database Adapter.
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * character to escape keyword table/column names
	 * @var string
	 */
  	protected $quoteCharacter = '`';
	
	/**
	 * Do everything that needs to be done to format a table name.
	 *
	 * @param string $name of table
	 *
	 * @return string table name
	 */
	public function safeTable($name, $noQuotes = false) {
		$name = strtolower($name);
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}

	
	/**
	 * Do everything that needs to be done to format a column name.
	 *
	 * @param string $name of column
	 *
	 * @return string $column name
	 */
	public function safeColumn($name, $noQuotes = false) {
		$name = strtolower($name);
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}
	
	/**
	 * Constructor.
	 * The Query Writer Constructor also sets up the database.
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 *
	 */
	public function __construct( RedBean_Adapter $adapter ) {
		
		$this->typeno_sqltype = array(
			RedBean_QueryWriter_Cubrid::C_DATATYPE_INTEGER => ' INTEGER ',
			RedBean_QueryWriter_Cubrid::C_DATATYPE_DOUBLE => ' DOUBLE ',
			RedBean_QueryWriter_Cubrid::C_DATATYPE_STRING => ' STRING ',
			RedBean_QueryWriter_Cubrid::C_DATATYPE_SPECIAL_DATE => ' DATE ',
			RedBean_QueryWriter_Cubrid::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
		);
		
		$this->sqltype_typeno = array();
		foreach($this->typeno_sqltype as $k=>$v)
		$this->sqltype_typeno[trim(($v))]=$k;
		$this->sqltype_typeno['STRING(1073741823)'] = self::C_DATATYPE_STRING;
		
		$this->adapter = $adapter;
		parent::__construct();
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
	 * Returns all tables in the database.
	 *
	 * @return array $tables tables
	 */
	public function getTables() { 
		$rows = $this->adapter->getCol( "SELECT class_name FROM db_class WHERE is_system_class = 'NO';" );
		return $rows;
	}

	/**
	 * Creates an empty, column-less table for a bean based on it's type.
	 * This function creates an empty table for a bean. It uses the
	 * safeTable() function to convert the type name to a table name.
	 *
	 * @param string $table type of bean you want to create a table for
	 *
	 * @return void
	 */
	public function createTable( $table ) {
		$rawTable = $this->safeTable($table,true);
		$table = $this->safeTable($table);
		
		$sql = '     CREATE TABLE '.$table.' (
                     "id" integer AUTO_INCREMENT,
					 CONSTRAINT "pk_'.$rawTable.'_id" PRIMARY KEY("id")
		             )';
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
		$columns = array();
		$table = $this->safeTable($table);
		$columnsRaw = $this->adapter->get("SHOW COLUMNS FROM $table");
		foreach($columnsRaw as $r) {
			$columns[$r['Field']]=$r['Type'];
		}
		return $columns;
	}

	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType( $value, $flagSpecial=false ) {
		$this->svalue = $value;
		
		if (is_null($value)) {
			return self::C_DATATYPE_INTEGER;
		}
		
		if ($flagSpecial) {
			if (preg_match('/^\d{4}\-\d\d-\d\d$/',$value)) {
				return self::C_DATATYPE_SPECIAL_DATE;
			}
			if (preg_match('/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/',$value)) {
				return self::C_DATATYPE_SPECIAL_DATETIME;
			}
		}
		$value = strval($value);
		if (!$this->startsWithZeros($value)) {

			if (is_numeric($value) && (floor($value)==$value) && $value >= -2147483648  && $value <= 2147483648 ) {
				return self::C_DATATYPE_INTEGER;
			}
			if (is_numeric($value)) {
				return self::C_DATATYPE_DOUBLE;
			}
		}
		
		return self::C_DATATYPE_STRING;
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
		
		
		$r = ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED);
		
		if ($includeSpecials) return $r;
		if ($r > self::C_DATATYPE_SPECIFIED) return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	
	/**
	 * This method adds a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 *
	 */
	public function addColumn( $type, $column, $field ) {
		$table = $type;
		$type = $field;
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$type = array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : '';
		$sql = "ALTER TABLE $table ADD COLUMN $column $type ";
		$this->adapter->exec( $sql );
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
		$table = $type;
		$type = $datatype;
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$newtype = array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : '';
		$changecolumnSQL = "ALTER TABLE $table CHANGE $column $column $newtype ";
		$this->adapter->exec( $changecolumnSQL );
	}

	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table table
	 * @param string $col1  column
	 * @param string $col2  column
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table);
		sort($columns); //else we get multiple indexes due to order-effects
		foreach($columns as $k=>$v) {
			$columns[$k]= $this->safeColumn($v);
		}
		$r = $this->adapter->get("SHOW INDEX FROM $table");
		$name = 'UQ_'.sha1(implode(',',$columns));
		if ($r) {
			foreach($r as $i) { 
				if (strtoupper($i['Key_name'])== strtoupper($name)) {
					return;
				}
			}
		}
		$sql = "ALTER TABLE $table
                ADD CONSTRAINT UNIQUE $name (".implode(',',$columns).")";
		$this->adapter->exec($sql);
	}

	/**
	 * Tests whether a given SQL state is in the list of states.
	 *
	 * @param string $state code
	 * @param array  $list  array of sql states
	 *
	 * @return boolean $yesno occurs in list
	 */
	public function sqlStateIn($state, $list) {
		/*$stateMap = array(
			'HY000'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'HY000'=>RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);*/
		
		if ($state=='HY000') {
			if (in_array(RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION,$list)) return true;
			if (in_array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,$list)) return true;
			if (in_array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,$list)) return true;
		}
		return false;
		//return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'),$list); 
	}

	
	/**
	 * Adds a constraint. If one of the beans gets trashed
	 * the other, related bean should be removed as well.
	 *
	 * @param RedBean_OODBBean $bean1      first bean
	 * @param RedBean_OODBBean $bean2      second bean
	 * @param bool 			   $dontCache  by default we use a cache, TRUE = NO CACHING (optional)
	 *
	 * @return void
	 */
	public function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$table1 = $bean1->getMeta('type');
		$table2 = $bean2->getMeta('type');
		$writer = $this;
		$adapter = $this->adapter;
		$table = RedBean_QueryWriter_AQueryWriter::getAssocTableFormat( array( $table1,$table2) );
		$property1 = $bean1->getMeta('type') . '_id';
		$property2 = $bean2->getMeta('type') . '_id';
		if ($property1==$property2) $property2 = $bean2->getMeta('type').'2_id';
		//Dispatch to right method
		return $this->constrain($table, $table1, $table2, $property1, $property2);
	}

	
	/**
	 * Add the constraints for a specific database driver: Cubrid
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
	protected function constrain($table, $table1, $table2, $property1, $property2) {
		$writer = $this;
		$adapter = $this->adapter;
		$firstState = $this->buildFK($table,$table1,$property1,'id',true);
		$secondState = $this->buildFK($table,$table2,$property2,'id',true);
		return ($firstState && $secondState);
	}

	
	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDependent = false) {
		return $this->buildFK($type, $targetType, $field, $targetField, $isDependent);
	}
	
	
	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	protected function buildFK($type, $targetType, $field, $targetField,$isDep=false) {
		$table = $this->safeTable($type);
		$tableNoQ = $this->safeTable($type,true);
		$targetTable = $this->safeTable($targetType);
		$targetTableNoQ = $this->safeTable($targetType,true);
		$column = $this->safeColumn($field);
		$columnNoQ = $this->safeColumn($field,true);
		$targetColumn  = $this->safeColumn($targetField);
		$targetColumnNoQ  = $this->safeColumn($targetField,true);
		$keys = $this->getKeys($targetTableNoQ,$tableNoQ);
		$needsToAddFK = true;
		$needsToDropFK = false;
		foreach($keys as $key) {
			if ($key['FKTABLE_NAME']==$tableNoQ && $key['FKCOLUMN_NAME']==$columnNoQ) { 
				//already has an FK
				$needsToDropFK = true;
				if ((($isDep && $key['DELETE_RULE']==0) || (!$isDep && $key['DELETE_RULE']==3))) {
					return false;
				}
				
			}
		}
		
		if ($needsToDropFK) {
			$sql = "ALTER TABLE $table DROP FOREIGN KEY {$key['FK_NAME']} ";
			$this->adapter->exec($sql);
		}
		
		$casc = ($isDep ? 'CASCADE' : 'SET NULL');
		$sql = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc ";
		$this->adapter->exec($sql);
		
	}	
	
	
	/**
	 * Drops all tables in database
	 */
	public function wipeAll() {
		foreach($this->getTables() as $t) {
			foreach($this->getKeys($t) as $k) {
				$this->adapter->exec("ALTER TABLE \"{$k['FKTABLE_NAME']}\" DROP FOREIGN KEY \"{$k['FK_NAME']}\"");
			}
			$this->adapter->exec("DROP TABLE \"$t\"");
		}
		foreach($this->getTables() as $t) {
			$this->adapter->exec("DROP TABLE \"$t\"");
		}
	}
	
	
	/**
	 * Obtains the keys of a table using the PDO schema function.
	 * 
	 * @param type $table
	 * @return type 
	 */
	protected function getKeys($table,$table2=null) {
		$pdo = $this->adapter->getDatabase()->getPDO();
		$keys = $pdo->cubrid_schema(PDO::CUBRID_SCH_EXPORTED_KEYS,$table);//print_r($keys);
		if ($table2) $keys = array_merge($keys, $pdo->cubrid_schema(PDO::CUBRID_SCH_IMPORTED_KEYS,$table2) );//print_r($keys);
		
		return $keys;
	}
	
	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string $table escaped string
	 */
	protected function check($table) {
		if ($this->quoteCharacter && strpos($table, $this->quoteCharacter)!==false) {
		  throw new Redbean_Exception_Security('Illegal chars in table name');
	    }
		return $table;
	}
	
}