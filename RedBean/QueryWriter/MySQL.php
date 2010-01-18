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
class RedBean_QueryWriter_MySQL implements RedBean_QueryWriter {

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 * DATA TYPE
	 * Boolean Data type
	 * @var integer
	 */
	const C_DATATYPE_BOOL = 0;

	/**
	 * DATA TYPE
	 * Unsigned 8BIT Integer
	 * @var integer
	 */
	const C_DATATYPE_UINT8 = 1;

	/**
	 * DATA TYPE
	 * Unsigned 32BIT Integer
	 * @var integer
	 */
	const C_DATATYPE_UINT32 = 2;

	/**
	 * DATA TYPE
	 * Double precision floating point number and
	 * negative numbers.
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 3;

	/**
	 * DATA TYPE
	 * Standard Text column (like varchar255)
	 * At least 8BIT character support.
	 * @var integer
	 */
	const C_DATATYPE_TEXT8 = 4;

	/**
	 * DATA TYPE
	 * Long text column (16BIT)
	 * @var integer
	 */
	const C_DATATYPE_TEXT16 = 5;

	/**
	 * DATA TYPE
	 * 32BIT long textfield (number of characters can be as high as 32BIT) Data type
	 * This is the biggest column that RedBean supports. If possible you may write
	 * an implementation that stores even bigger values.
	 * @var integer
	 */
	const C_DATATYPE_TEXT32 = 6;

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
	RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL=>" SET('1') ",
	RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8=>" TINYINT(3) UNSIGNED ",
	RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32=>" INT(11) UNSIGNED ",
	RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE=>" DOUBLE ",
	RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8=>" VARCHAR(255) ",
	RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16=>" TEXT ",
	RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32=>" LONGTEXT "
	);

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
	public $sqltype_typeno = array(
	"set('1')"=>RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL,
	"tinyint(3) unsigned"=>RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8,
	"int(11) unsigned"=>RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32,
	"double" => RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE,
	"varchar(255)"=>RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8,
	"text"=>RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16,
	"longtext"=>RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32
	);

	/**
	 * @var array
	 * DTYPES code names of the supported types,
	 * these are used for the column names
	 */
	public $dtypes = array(
	"booleanset","tinyintus","intus","doubles","varchar255","text","ltext"
	);

	/**
	 *
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * Indicates the field name to be used for primary keys;
	 * default is 'id'
	 * @var string
	 */
	protected $idfield = "id";


	/**
	 * Returns the column name that should be used
	 * to store and retrieve the primary key ID.
	 * @param string $type
	 * @return string $idfieldtobeused
	 */
	public function getIDField( $type ) {
		return  $this->idfield;
	}


	/**
	 * Checks table name or column name.
	 * @param string $table
	 * @return string $table
	 */
	public function check($table) {
		if (strpos($table,"`")!==false) throw new RedBean_Exception_Security("Illegal chars in table name");
		return $this->adapter->escape($table);
	}

	/**
	 * Constructor.
	 * The Query Writer Constructor also sets up the database.
	 * @param RedBean_Adapter_DBAdapter $adapter
	 */
	public function __construct( RedBean_Adapter $adapter, $frozen = false ) {
		$this->adapter = $adapter;
	}


	/**
	 * Returns all tables in the database.
	 * @return array $tables
	 */
	public function getTables() {
		return $this->adapter->getCol( "show tables" );
	}

	/**
	 * Creates an empty, column-less table for a bean.
	 * @param string $table
	 */
	public function createTable( $table ) {
		$idfield = $this->getIDfield($table);
		$table = $this->check($table);
		$sql = "
                     CREATE TABLE `$table` (
                    `$idfield` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
                     PRIMARY KEY ( `$idfield` )
                     ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns an array containing the column names of the specified table.
	 * @param string $table
	 * @return array $columns
	 */
	public function getColumns( $table ) {
		$table = $this->check($table);
		$columnsRaw = $this->adapter->get("DESCRIBE `$table`");
		foreach($columnsRaw as $r) {
			$columns[$r["Field"]]=$r["Type"];
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

		if (is_null($value)) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
		}
		$orig = $value;
		$value = strval($value);
		if ($value=="1" || $value=="" || $value=="0") {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
		}
		if (is_numeric($value) && (floor($value)==$value) && $value >= 0 && $value <= 255 ) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8;
		}
		if (is_numeric($value) && (floor($value)==$value) && $value >= 0  && $value <= 4294967295 ) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32;
		}
		if (is_numeric($value)) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE;
		}
		if (strlen($value) <= 255) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8;
		}
		return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16;
	}

	/**
	 * Adds a column of a given type to a table.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
	public function addColumn( $table, $column, $type ) {
		$column = $this->check($column);
		$table = $this->check($table);
		$type=$this->typeno_sqltype[$type];
		$sql = "ALTER TABLE `$table` ADD `$column` $type ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns the Type Code for a Column Description
	 * @param string $typedescription
	 * @return integer $typecode
	 */
	public function code( $typedescription ) {
		return ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
	}

	/**
	 * Change (Widen) the column to the give type.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
	public function widenColumn( $table, $column, $type ) {
		$column = $this->check($column);
		$table = $this->check($table);
		$newtype = $this->typeno_sqltype[$type];
		$changecolumnSQL = "ALTER TABLE `$table` CHANGE `$column` `$column` $newtype ";
		$this->adapter->exec( $changecolumnSQL );
	}

	/**
	 * Update a record using a series of update values.
	 * @param string $table
	 * @param array $updatevalues
	 * @param integer $id
	 */
	public function updateRecord( $table, $updatevalues, $id) {
		$idfield = $this->getIDField($table);
		$sql = "UPDATE `".$this->check($table)."` SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " `".$uv["property"]."` = ? ";
			$v[]=( $uv["value"] );
		}
		$sql .= implode(",", $p ) ." WHERE $idfield = ".intval($id);
		$this->adapter->exec( $sql, $v );
	}

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 * @param string $table
	 * @param array $insertcolumns
	 * @param array $insertvalues
	 * @return integer $insertid
	 */
	public function insertRecord( $table, $insertcolumns, $insertvalues ) {
	//if ($table == "__log") $idfield="id"; else
		$idfield = $this->getIDField($table);
		$table = $this->check($table);
		if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
				$insertcolumns[$k] = "`".$this->check($v)."`";
			}
			$insertSQL = "INSERT INTO `$table` ( $idfield, ".implode(",",$insertcolumns)." ) VALUES ";
			$pat = "( NULL, ". implode(",",array_fill(0,count($insertcolumns)," ? "))." )";
			$insertSQL .= implode(",",array_fill(0,count($insertvalues),$pat));
			foreach($insertvalues as $insertvalue) {
				foreach($insertvalue as $v) {
					$vs[] = ( $v );
				}
			}
			$this->adapter->exec( $insertSQL, $vs );
			return ($this->adapter->getErrorMsg()=="" ?  $this->adapter->getInsertID() : 0);
		}
		else {
			$this->adapter->exec( "INSERT INTO `$table` ($idfield) VALUES(NULL) " );
			return ($this->adapter->getErrorMsg()=="" ?  $this->adapter->getInsertID() : 0);
		}
	}

	/**
	 * Selects a record based on type and id.
	 * @param string $type
	 * @param integer $id
	 * @return array $row
	 */
	public function selectRecord($type, $ids) {
		$idfield = $this->getIDField($type);
		$type=$this->check($type);
		$sql = "SELECT * FROM `$type` WHERE $idfield IN ( ".implode(',', array_fill(0, count($ids), " ? "))." )";
		$rows = $this->adapter->get($sql,$ids);
		return ($rows) ? $rows : NULL;

	}

	/**
	 * Deletes a record based on a table, column, value and operator
	 * @param string $table
	 * @param string $column
	 * @param mixed $value
	 * @param string $oper
	 * @todo validate arguments for security
	 */
	public function deleteRecord( $table, $id) {
		$table = $this->check($table);
		$this->adapter->exec("DELETE FROM `$table` WHERE `".$this->getIDField($table)."` = ? ",array(strval($id)));
	}

	/**
	 * Adds a Unique index constrain to the table.
	 * @param string $table
	 * @param string $col1
	 * @param string $col2
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		sort($columns); //else we get multiple indexes due to order-effects
		foreach($columns as $k=>$v) {
			$columns[$k]="`".$this->adapter->escape($v)."`";
		}
		$table = $this->check($table);
		$r = $this->adapter->get("SHOW INDEX FROM `$table`");
		$name = "UQ_".sha1(implode(',',$columns));
		if ($r) {
			foreach($r as $i) {
				if ($i["Key_name"]==$name) {
					return;
				}
			}
		}
		$sql = "ALTER IGNORE TABLE `$table`
                ADD UNIQUE INDEX `$name` (".implode(",",$columns).")";
		$this->adapter->exec($sql);
	}

	/**
	 * Selects a record using a criterium.
	 * Specify the select-column, the target table, the criterium column
	 * and the criterium value. This method scans the specified table for
	 * records having a criterium column with a value that matches the
	 * specified value. For each record the select-column value will be
	 * returned, most likely this will be a primary key column like ID.
	 * If $withUnion equals true the method will also return the $column
	 * values for each entry that has a matching select-column. This is
	 * handy for cross-link tables like page_page.
	 * @param string $select, the column to be selected
	 * @param string $table, the table to select from
	 * @param string $column, the column to compare the criteria value against
	 * @param string $value, the criterium value to match against
	 * @param boolean $withUnion (default is false)
	 * @return array $mixedColumns
	 */
	public function selectByCrit( $select, $table, $column, $value, $withUnion=false ) {
		$select = $this->noKW($this->adapter->escape($select));
		$table = $this->noKW($this->adapter->escape($table));
		$column = $this->noKW($this->adapter->escape($column));
		$value = $this->adapter->escape($value);
		$sql = "SELECT $select FROM $table WHERE $column = ? ";
		$values = array($value);
		if ($withUnion) {
			$sql .= " UNION SELECT $column FROM $table WHERE $select = ? ";
			$values[] = $value;
		}
		return $this->adapter->getCol($sql,$values);
	}

	/**
	 * This method takes an array with key=>value pairs.
	 * Each record that has a complete match with the array is
	 * deleted from the table.
	 * @param string $table
	 * @param array $crits
	 * @return integer $affectedRows
	 */
	public function deleteByCrit( $table, $crits ) {
		$table = $this->noKW($this->adapter->escape($table));
		$values = array();
		foreach($crits as $key=>$val) {
			$key = $this->noKW($this->adapter->escape($key));
			$values[] = $this->adapter->escape($val);
			$conditions[] = $key ."= ? ";
		}
		$sql = "DELETE FROM $table WHERE ".implode(" AND ", $conditions);
		return (int) $this->adapter->exec($sql, $values);
	}

	/**
	 * Puts keyword escaping symbols around string.
	 * @param string $str
	 * @return string $keywordSafeString
	 */
	public function noKW($str) {
		return "`".$str."`";
	}
}