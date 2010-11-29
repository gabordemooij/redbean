<?php
/**
 * RedBean MySQLWriter
 * @file 		RedBean/QueryWriter/MySQL.php
 * @description		Represents a MySQL Database to RedBean
 *					To write a driver for a different database for RedBean
 *					you should only have to change this file.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_MySQL extends RedBean_AQueryWriter implements RedBean_QueryWriter {

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
		$table = $this->getFormattedTableName($table);
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
		$table = $this->getFormattedTableName($table);
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
			return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8;
		}
		$orig = $value;
		$value = strval($value);
		if ($value=="1" || $value=="" || $value=="0") {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8; //RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
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
		$table = $this->getFormattedTableName($table);
		$column = $this->check($column);
		$table = $this->check($table);
		$newtype = $this->typeno_sqltype[$type];
		$changecolumnSQL = "ALTER TABLE `$table` CHANGE `$column` `$column` $newtype ";
		$this->adapter->exec( $changecolumnSQL );
	}

	/**
	 * Adds a Unique index constrain to the table.
	 * @param string $table
	 * @param string $col1
	 * @param string $col2
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->getFormattedTableName($table);
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

	public function sqlStateIn($state, $list) {

		$sqlState = "0";
		if ($state == "42S02") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE;
		if ($state == "42S22") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN;
		if ($state == "23000") $sqlState = RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION;
		return in_array($sqlState, $list);
	}

}
