<?php
/**
 * RedBean Abstract Query Writer
 *
 * @file 			RedBean/QueryWriter/AQueryWriter.php
 * @description
 *					Represents an abstract Database to RedBean
 *					To write a driver for a different database for RedBean
 *					Contains a number of functions all implementors can
 *					inherit or override.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

abstract class RedBean_QueryWriter_AQueryWriter {


	/**
	 * @var array
	 * FK Cache
	 */
	protected $fcache = array();

	/**
	 *
	 * @var RedBean_IBeanFormatter
	 * Holds the bean formatter to be used for applying
	 * table schema.
	 */
	public $tableFormatter;


	/**
	 * @var array
	 * Supported Column Types.
	 */
	public $typeno_sqltype = array();

	/**
	 *
	 * @var RedBean_Adapter_DBAdapter
	 * Holds a reference to the database adapter to be used.
	 */
	protected $adapter;

	/**
	 * @var string
	 * Indicates the field name to be used for primary keys;
	 * default is 'id'.
	 */
	protected $idfield = "id";

	/**
	 * @var string
	 * default value to for blank field (passed to PK for auto-increment)
	 */
	protected $defaultValue = 'NULL';

	/**
	 * @var string
	 * character to escape keyword table/column names
	 */
	protected $quoteCharacter = '';


	/**
	 * Constructor
	 * Sets the default Bean Formatter, use parent::__construct() in
	 * subclass to achieve this.
	 */
	public function __construct() {
		$this->tableFormatter = new RedBean_DefaultBeanFormatter();
	}

	/**
	 * Do everything that needs to be done to format a table name.
	 *
	 * @param string $name of table
	 *
	 * @return string table name
	 */
	public function safeTable($name, $noQuotes = false) {
		$name = $this->getFormattedTableName($name);
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
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}

	/**
	 * Returns the sql that should follow an insert statement.
	 *
	 * @param string $table name
	 *
	 * @return string sql
	 */
  	protected function getInsertSuffix ($table) {
    	return "";
  	}

	/**
	 * Returns the string identifying a table for a given type.
	 *
	 * @param string $type
	 *
	 * @return string $table
	 */
	public function getFormattedTableName($type) {
		return $this->tableFormatter->formatBeanTable($type);
	}

	/**
	 * Returns an alias type based on a reference type. If the writer has
	 * a tableformatter this method will pass the type to the writer's alias
	 * function to get the alias of the type back.
	 *
	 * @param  string $type type you want an alias for
	 *
	 * @return
	 */
	public function getAlias($type) {
		return $this->tableFormatter->getAlias($type);
	}


	/**
	 * Sets the new bean formatter. A bean formatter is an instance
	 * of the class BeanFormatter that determines how a bean should be represented
	 * in the database.
	 *
	 * @param RedBean_IBeanFormatter $beanFormatter bean format
	 *
	 * @return void
	 */
	public function setBeanFormatter( RedBean_IBeanFormatter $beanFormatter ) {
		$this->tableFormatter = $beanFormatter;
	}

	/**
	 * Get sql column type.
	 *
	 * @param integer $type constant
	 *
	 * @return string sql type
	 */
	public function getFieldType( $type = "" ) {
		return array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : "";
	}

	/**
	 * Returns the column name that should be used
	 * to store and retrieve the primary key ID.
	 *
	 * @param string $type type of bean to get ID Field for
	 *
	 * @return string $idfieldtobeused ID field to be used for this type of bean
	 */
	public function getIDField( $type ) {
		$nArgs = func_num_args();
		if ($nArgs>1) throw new Exception("Deprecated parameter SAFE, use safeColumn() instead.");
		return $this->tableFormatter->formatBeanID($type);
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
		  throw new Redbean_Exception_Security("Illegal chars in table name");
	    }
		return $this->adapter->escape($table);
	}

	/**
	 * Puts keyword escaping symbols around string.
	 *
	 * @param string $str keyword
	 *
	 * @return string $keywordSafeString escaped keyword
	 */
	protected function noKW($str) {
		$q = $this->quoteCharacter;
		return $q.$str.$q;
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
		$type = $this->getFieldType($type);
		$sql = "ALTER TABLE $table ADD $column $type ";
		$this->adapter->exec( $sql );
	}

	/**
	 * This method updates (or inserts) a record, it takes
	 * a table name, a list of update values ( $field => $value ) and an
	 * primary key ID (optional). If no primary key ID is provided, an
	 * INSERT will take place.
	 * Returns the new ID.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type         name of the table to update
	 * @param array   $updatevalues list of update values
	 * @param integer $id			optional primary key ID value
	 *
	 * @return integer $id the primary key ID value of the new record
	 */
	public function updateRecord( $type, $updatevalues, $id=null) {
		$table = $type;
		if (!$id) {
			$insertcolumns =  $insertvalues = array();
			foreach($updatevalues as $pair) {
				$insertcolumns[] = $pair["property"];
				$insertvalues[] = $pair["value"];
			}
			return $this->insertRecord($table,$insertcolumns,array($insertvalues));
		}
		if ($id && !count($updatevalues)) return $id;
		$idfield = $this->safeColumn($this->getIDField($table));
		$table = $this->safeTable($table);
		$sql = "UPDATE $table SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " {$this->safeColumn($uv["property"])} = ? ";
			$v[]=$uv["value"];
		}
		$sql .= implode(",", $p ) ." WHERE $idfield = ".intval($id);
		$this->adapter->exec( $sql, $v );
		return $id;
	}

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 *
	 * @param string $table			  table to perform query on
	 * @param array  $insertcolumns columns to be inserted
	 * @param array  $insertvalues  values to be inserted
	 *
	 * @return integer $insertid	  insert id from driver, new record id
	 */
	protected function insertRecord( $table, $insertcolumns, $insertvalues ) {
		$default = $this->defaultValue;
		$idfield = $this->safeColumn($this->getIDField($table));
		$suffix = $this->getInsertSuffix($table);
		$table = $this->safeTable($table);
		if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
				$insertcolumns[$k] = $this->safeColumn($v);
			}
			$insertSQL = "INSERT INTO $table ( $idfield, ".implode(",",$insertcolumns)." ) VALUES ";
			$insertSQL .= "( $default, ". implode(",",array_fill(0,count($insertcolumns)," ? "))." ) $suffix";

			foreach($insertvalues as $i=>$insertvalue) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
			}
			$result = count($ids)===1 ? array_pop($ids) : $ids;
		}
		else {
			$result = $this->adapter->getCell( "INSERT INTO $table ($idfield) VALUES($default) $suffix");
		}
		if ($suffix) return $result;
	   $last_id = $this->adapter->getInsertID();
		return ($this->adapter->getErrorMsg()=="" ?  $last_id : 0);
	}


	/**
	 * This selects a record. You provide a
	 * collection of conditions using the following format:
	 * array( $field1 => array($possibleValue1, $possibleValue2,... $possibleValueN ),
	 * ...$fieldN=>array(...));
	 * Also, additional SQL can be provided. This SQL snippet will be appended to the
	 * query string. If the $delete parameter is set to TRUE instead of selecting the
	 * records they will be deleted.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @throws Exception
	 * @param string  $type    type of bean to select records from
	 * @param array   $cond    conditions using the specified format
	 * @param string  $asql    additional sql
	 * @param boolean $delete  IF TRUE delete records (optional)
	 * @param boolean $inverse IF TRUE inverse the selection (optional)
	 *
	 * @return array $records selected records
	 */
	public function selectRecord( $type, $conditions, $addSql=null, $delete=null, $inverse=false ) { 
		if (!is_array($conditions)) throw new Exception("Conditions must be an array");

		$table = $this->safeTable($type);
		$sqlConditions = array();
		$bindings=array();
		foreach($conditions as $column=>$values) {
			$sql = $this->safeColumn($column);
			$sql .= " ".($inverse ? " NOT ":"")." IN ( ";
			$sql .= implode(",",array_fill(0,count($values),"?")).") ";
			$sqlConditions[] = $sql;
			if (!is_array($values)) $values = array($values);
			foreach($values as $k=>$v) {
				$values[$k]=strval($v);
			}
			$bindings = array_merge($bindings,$values);
		}
		//$addSql can be either just a string or array($sql, $bindings)
		if (is_array($addSql)) {
			if (count($addSql)>1) {
				$bindings = array_merge($bindings,$addSql[1]);
			}
			else {
				$bindings = array();
			}
			$addSql = $addSql[0];

		}
		$sql="";
		if (count($sqlConditions)>0) {
			$sql = implode(" AND ",$sqlConditions);
			$sql = " WHERE ( $sql ) ";
			if ($addSql) $sql .= " AND $addSql ";
		}
		elseif ($addSql) {
			$sql = " WHERE ".$addSql;
		}
		$sql = (($delete) ? "DELETE FROM " : "SELECT * FROM ").$table.$sql;
		$rows = $this->adapter->get($sql,$bindings);
		return $rows;
	}

	/**
	 * This creates a view with name $viewID and
	 * based on the reference type. A list of types
	 * will be provided in the second argument. This method should create
	 * a view by joining each type in the list (using LEFT OUTER JOINS) to the
	 * reference type. If a type is mentioned multiple times it does not need
	 * to be re-joined but the next type should be joined to that type instead.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $referenceType reference type
	 * @param  array  $constraints   list of types
	 * @param  string $viewID		 name of the new view
	 *
	 * @return boolean $success whether a view has been generated
	 */
	public function createView($referenceType, $constraints, $viewID) {

		$referenceTable = $referenceType;
		$viewID = $this->safeTable($viewID,true);
		$safeReferenceTable = $this->safeTable($referenceTable);

		try{ $this->adapter->exec("DROP VIEW $viewID"); }catch(Exception $e){}

		$columns = array_keys( $this->getColumns( $referenceTable ) );

		$referenceTable = ($referenceTable);
		$joins = array();
		foreach($constraints as $table=>$constraint) {
			$safeTable = $this->safeTable($table);
			$addedColumns = array_keys($this->getColumns($table));
			foreach($addedColumns as $addedColumn) {
				$newColName = $addedColumn."_of_".$table;
				$newcolumns[] = $this->safeTable($table).".".$this->safeColumn($addedColumn) . " AS ".$this->safeColumn($newColName);
			}
			if (count($constraint)!==2) throw Exception("Invalid VIEW CONSTRAINT");
			$referenceColumn = $constraint[0];
			$compareColumn = $constraint[1];
			$join = $referenceColumn." = ".$compareColumn;
			$joins[] = " LEFT JOIN $safeTable ON $join ";
		}

		$joins = implode(" ", $joins);
		foreach($columns as $k=>$column) {
			$columns[$k]=$safeReferenceTable.".".$this->safeColumn($column)." as ".$this->safeColumn($column);
		}
		$columns = implode("\n,",array_merge($newcolumns,$columns));
		$sql = "CREATE VIEW $viewID AS SELECT $columns FROM $safeReferenceTable $joins ";

		$this->adapter->exec($sql);
		return true;
	}

	/**
	 * This method removes all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe($type) {
		$table = $type;
		$table = $this->safeTable($table);
		$sql = "TRUNCATE $table ";
		$this->adapter->exec($sql);
	}

	/**
	 * Counts rows in a table.
	 *
	 * @param string $beanType
	 *
	 * @return integer $numRowsFound
	 */
	public function count($beanType) {
		$table = $this->safeTable($beanType);
		$sql = "SELECT count(*) FROM $table ";
		return (int) $this->adapter->getCell($sql);
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
		$name = preg_replace("/\W/","",$name);
		$column = $this->safeColumn($column);
		try{ $this->adapter->exec("CREATE INDEX $name ON $table ($column) "); }catch(Exception $e){}
	}

	/**
	 * This is a utility service method publicly available.
	 * It allows you to check whether you can safely treat an certain value as an integer by
	 * comparing an int-valled string representation with a default string casted string representation and
	 * a ctype-digit check. It does not take into account numerical limitations (X-bit INT), just that it
	 * can be treated like an INT. This is useful for binding parameters to query statements like
	 * Query Writers and drivers can do.
	 *
	 * @static
	 *
	 * @param  string $value string representation of a certain value
	 *
	 * @return boolean $value boolean result of analysis
	 */
	public static function canBeTreatedAsInt( $value ) {
		return (boolean) (ctype_digit(strval($value)) && strval($value)===strval(intval($value)));
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
	public function addFK( $type, $targetType, $field, $targetField) {
		$table = $this->safeTable($type);
		$tableNoQ = $this->safeTable($type,true);
		$targetTable = $this->safeTable($targetType);
		$column = $this->safeColumn($field);
		$columnNoQ = $this->safeColumn($field,true);
		$targetColumn  = $this->safeColumn($targetField);
		$db = $this->adapter->getCell("select database()");
		$fks =  $this->adapter->getCell("
			SELECT count(*)
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA ='$db' AND TABLE_NAME = '$tableNoQ'  AND COLUMN_NAME = '$columnNoQ' AND
			CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME is not null
		");
		if ($fks==0) {
			try{
				$this->adapter->exec("ALTER TABLE  $table
				ADD FOREIGN KEY (  $column ) REFERENCES  $targetTable (
				$targetColumn) ON DELETE SET NULL ON UPDATE SET NULL ;");
			}
			catch(Exception $e) {
			}
		}

	}

	/**
	 * Returns the format for link tables.
	 * Given an array containing two type names this method returns the
	 * name of the link table to be used to store and retrieve
	 * association records.
	 *
	 * @param  array $types two types array($type1,$type2)
	 *
	 * @return string $linktable name of the link table
	 */
	public function getAssocTableFormat($types) {
		sort($types);
		return ( implode("_", $types) );
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
	public function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $dontCache = false ) {

		$table1 = $bean1->getMeta("type");
		$table2 = $bean2->getMeta("type");
		$writer = $this;
		$adapter = $this->adapter;
		$table = $this->getAssocTableFormat( array( $table1,$table2) );
		$idfield1 = $writer->getIDField($bean1->getMeta("type"));
		$idfield2 = $writer->getIDField($bean2->getMeta("type"));

		$property1 = $bean1->getMeta("type") . "_id";
		$property2 = $bean2->getMeta("type") . "_id";
		if ($property1==$property2) $property2 = $bean2->getMeta("type")."2_id";

		$table = $adapter->escape($table);
		$table1 = $adapter->escape($table1);
		$table2 = $adapter->escape($table2);
		$property1 = $adapter->escape($property1);
		$property2 = $adapter->escape($property2);

		//In Cache? Then we dont need to bother
		$fkCode = "fk".md5($table.$property1.$property2);
		if (isset($this->fkcache[$fkCode])) return false;
		//Dispatch to right method

		try {
			return $this->constrain($table, $table1, $table2, $property1, $property2, $dontCache);
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
	 * Abstract method. Needs to be implemented by 'fluid' driver.
	 * Add the constraints for a specific database driver.
	 * @abstract
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
	abstract protected function constrain($table, $table1, $table2, $p1, $p2, $cache);

	protected function startsWithZeros($value) {
		$value = strval($value);
		if (strlen($value)>1 && strpos($value,'0')===0 && strpos($value,'0.')!==0) {
			return true;
		}
		else {
			return false;
		}
	}

}
