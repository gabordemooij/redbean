<?php
/**
 * RedBean MySQLWriter
 *
 * @file				RedBean/QueryWriter/MySQL.php
 * @description	Represents a MySQL Database to RedBean
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_MySQL extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Boolean Data type
	 */
	const C_DATATYPE_BOOL = 0;

	/**
	 *
	 * @var integer
	 *
	 * DATA TYPE
	 * Unsigned 8BIT Integer
	 *
	 */
	const C_DATATYPE_UINT8 = 1;

	/**
	 *
	 * @var integer
	 *
	 * DATA TYPE
	 * Unsigned 32BIT Integer
	 *
	 */
	const C_DATATYPE_UINT32 = 2;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Double precision floating point number and
	 * negative numbers.
	 *
	 */
	const C_DATATYPE_DOUBLE = 3;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Standard Text column (like varchar255)
	 * At least 8BIT character support.
	 *
	 */
	const C_DATATYPE_TEXT8 = 4;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Long text column (16BIT)
	 *
	 */
	const C_DATATYPE_TEXT16 = 5;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * 32BIT long textfield (number of characters can be as high as 32BIT) Data type
	 * This is the biggest column that RedBean supports. If possible you may write
	 * an implementation that stores even bigger values.
	 *
	 */
	const C_DATATYPE_TEXT32 = 6;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 *
	 */
	const C_DATATYPE_SPECIFIED = 99;


	/**
	 * @var array
	 * Supported Column Types
	 */
	public $typeno_sqltype = array(
			  RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL=>"  SET('1')  ",
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
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 *
	 */
	public function __construct( RedBean_Adapter $adapter ) {
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
		return self::C_DATATYPE_UINT32;
	}

	/**
	 * Returns all tables in the database.
	 *
	 * @return array $tables tables
	 */
	public function getTables() {
		return $this->adapter->getCol( "show tables" );
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
		$idfield = $this->safeColumn($this->getIDfield($table));
		$table = $this->safeTable($table);
		$sql = "
                     CREATE TABLE $table (
                    $idfield INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
                     PRIMARY KEY ( $idfield )
                     ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
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
		$table = $this->safeTable($table);
		$columnsRaw = $this->adapter->get("DESCRIBE $table");
		foreach($columnsRaw as $r) {
			$columns[$r["Field"]]=$r["Type"];
		}
		return $columns;
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
		if (is_null($value)) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
		}
		$value = strval($value);
		if (!$this->startsWithZeros($value)) {

			if ($value=="1" || $value=="") {
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
		}
		if (strlen($value) <= 255) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8;
		}
		if (strlen($value) <= 65535) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16;
		}
		return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32;
	}

	/**
	 * Returns the Type Code for a Column Description.
	 *
	 * @param string $typedescription description
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription ) {
		return ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED);
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
		$newtype = $this->getFieldType($type);
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
		$name = "UQ_".sha1(implode(',',$columns));
		if ($r) {
			foreach($r as $i) {
				if ($i["Key_name"]== $name) {
					return;
				}
			}
		}
		$sql = "ALTER IGNORE TABLE $table
                ADD UNIQUE INDEX $name (".implode(",",$columns).")";
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
		$sqlState = "0";
		if ($state == "42S02") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE;
		if ($state == "42S22") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN;
		if ($state == "23000") $sqlState = RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION;
		return in_array($sqlState, $list);
	}


	/**
	 * Add the constraints for a specific database driver: MySQL.
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
	protected function constrain($table, $table1, $table2, $property1, $property2, $dontCache) {
		try{
			$writer = $this;
			$adapter = $this->adapter;
			$db = $adapter->getCell("select database()");
			$fkCode = "fk".md5($table.$property1.$property2);
			$fks =  $adapter->getCell("
				SELECT count(*)
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA ='$db' AND TABLE_NAME ='".$writer->getFormattedTableName($table)."' AND
				CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME is not null
					  ");

			//already foreign keys added in this association table
			if ($fks>0) return false;
			//add the table to the cache, so we dont have to fire the fk query all the time.
			if (!$dontCache) $this->fkcache[ $fkCode ] = true;
			$columns = $writer->getColumns($table);
			if ($writer->code($columns[$property1])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
				$writer->widenColumn($table, $property1, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
			}
			if ($writer->code($columns[$property2])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
				$writer->widenColumn($table, $property2, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
			}

			$idfield1 = $writer->getIDField($table1);
			$idfield2 = $writer->getIDField($table2);
			$table = $writer->getFormattedTableName($table);
			$table1 = $writer->getFormattedTableName($table1);
			$table2 = $writer->getFormattedTableName($table2);
			$sql = "
				ALTER TABLE ".$writer->noKW($table)."
				ADD FOREIGN KEY($property1) references `$table1`($idfield1) ON DELETE CASCADE;
					  ";
			$adapter->exec( $sql );
			$sql ="
				ALTER TABLE ".$writer->noKW($table)."
				ADD FOREIGN KEY($property2) references `$table2`($idfield2) ON DELETE CASCADE
					  ";
			$adapter->exec( $sql );
			return true;
		}
		catch(Exception $e){
			return false;
		}
	}

	/**
	 * Drops all tables in database
	 */
	public function wipeAll() {
		$this->adapter->exec('SET FOREIGN_KEY_CHECKS=0;');
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
		$this->adapter->exec('SET FOREIGN_KEY_CHECKS=1;');
	}


}
