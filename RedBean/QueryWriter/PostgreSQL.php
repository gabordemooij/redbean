<?php
/**
 * RedBean PostgreSQL Query Writer
 * @file				RedBean/QueryWriter/PostgreSQL.php
 * @description	QueryWriter for the PostgreSQL database system.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_PostgreSQL extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {

	/**
	 * DATA TYPE
	 * Integer Data Type
	 * @var integer
	 */
	const C_DATATYPE_INTEGER = 0;

	/**
	 * DATA TYPE
	 * Double Precision Type
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 1;

	/**
	 * DATA TYPE
	 * String Data Type
	 * @var integer
	 */
	const C_DATATYPE_TEXT = 3;

	/**
	 * @var array
	 * Supported Column Types
	 */
	public $typeno_sqltype = array(
			  self::C_DATATYPE_INTEGER=>" integer ",
			  self::C_DATATYPE_DOUBLE=>" double precision ",
			  self::C_DATATYPE_TEXT=>" text "
	);

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
	public $sqltype_typeno = array(
			  "integer"=>self::C_DATATYPE_INTEGER,
			  "double precision" => self::C_DATATYPE_DOUBLE,
			  "text"=>self::C_DATATYPE_TEXT
	);

	/**
	 *
	 * @var RedBean_DBAdapter
	 * Holds Database Adapter
	 */
	protected $adapter;
	
	/**
	 * @var string
	 * character to escape keyword table/column names
	 */
  protected $quoteCharacter = '"';

  /**
   *
   * @var string
   * Default Value
   */
  protected $defaultValue = 'DEFAULT';

  /**
   * Returns the insert suffix SQL Snippet
   * 
   * @param string $table table
   *
   * @return  string $sql SQL Snippet
   */
  protected function getInsertSuffix($table) {
    return "RETURNING ".$this->getIDField($table);
  }  

	/**
	 * Constructor
	 * The Query Writer Constructor also sets up the database
	 *
	 * @param RedBean_DBAdapter $adapter adapter
	 */
	public function __construct( RedBean_Adapter_DBAdapter $adapter ) {
		$this->adapter = $adapter;
	}

	/**
	 * Returns all tables in the database
	 *
	 * @return array $tables tables
	 */
	public function getTables() {
		return $this->adapter->getCol( "select table_name from information_schema.tables
where table_schema = 'public'" );
	}

	/**
	 * Creates an empty, column-less table for a bean.
	 *
	 * @param string $table table to create
	 */
	public function createTable( $table ) {
		$idfield = $this->getIDfield($table);
		$table = $this->safeTable($table);
		$sql = " CREATE TABLE $table ($idfield SERIAL PRIMARY KEY); ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns an array containing the column names of the specified table.
	 *
	 * @param string $table table to get columns from
	 *
	 * @return array $columns array filled with column (name=>type)
	 */
	public function getColumns( $table ) {
		$table = $this->safeTable($table, true);
		$columnsRaw = $this->adapter->get("select column_name, data_type from information_schema.columns where table_name='$table'");
		foreach($columnsRaw as $r) {
			$columns[$r["column_name"]]=$r["data_type"];
		}
		return $columns;
	}

	/**
	 * Returns the pgSQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param string $value value to determine type of
	 *
	 * @return integer $type type code for this value
	 */
	public function scanType( $value ) {
		//echo " \n\n value = $value => ".strval(intval($value))." same? ".($value===strval(intval($value)));
		if (is_numeric($value)
				  && floor($value)==$value
				  && $value < 2147483648
				  && $value > -2147483648) {
			return self::C_DATATYPE_INTEGER;
		}
		elseif(is_numeric($value)) {
			return self::C_DATATYPE_DOUBLE;
		}
		else {
			return self::C_DATATYPE_TEXT;
		}
	}

	/**
	 * Returns the Type Code for a Column Description
	 *
	 * @param string $typedescription type description to get code for
	 *
	 * @return integer $typecode type code
	 */
	public function code( $typedescription ) {
		return ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
	}

	/**
	 * Change (Widen) the column to the give type.
	 *
	 * @param string  $table  table to widen
	 * @param string  $column column to widen
	 * @param integer $type   new column type
	 */
	public function widenColumn( $table, $column, $type ) {
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$newtype = $this->typeno_sqltype[$type];
		$changecolumnSQL = "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype ";
		try {
			$this->adapter->exec( $changecolumnSQL );
		}catch(Exception $e) {
			die($e->getMessage());
		}
	}

	/**
	 * Gets information about changed records using a type and id and a logid.
	 * RedBean Locking shields you from race conditions by comparing the latest
	 * cached insert id with a the highest insert id associated with a write action
	 * on the same table. If there is any id between these two the record has
	 * been changed and RedBean will throw an exception. This function checks for changes.
	 * If changes have occurred it will throw an exception. If no changes have occurred
	 * it will insert a new change record and return the new change id.
	 * This method locks the log table exclusively.
	 *
	 * @param  string  $type  type
	 * @param  integer $id    id
	 * @param  integer $logid log id
	 *
	 * @return integer $newchangeid new change id
	 */
	public function checkChanges($type, $id, $logid) {

		$table = $this->safeTable($type);
		$idfield = $this->getIDfield($type);
		$id = (int) $id;
		$logid = (int) $logid;
		$num = $this->adapter->getCell("
        SELECT count(*) FROM __log WHERE tbl=$table AND itemid=$id AND action=2 AND $idfield > $logid");
		if ($num) {
			throw new RedBean_Exception_FailedAccessBean("Locked, failed to access (type:$type, id:$id)");
		}
		$newid = $this->insertRecord("__log",array("action","tbl","itemid"),
				  array(array(2,  $type, $id)));
		if ($this->adapter->getCell("select id from __log where tbl=:tbl AND id < $newid and id > $logid and action=2 and itemid=$id ",
		array(":tbl"=>$type))) {
			throw new RedBean_Exception_FailedAccessBean("Locked, failed to access II (type:$type, id:$id)");
		}
		return $newid;
	}
	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table table to add index to
	 * @param string $col1  column to be part of index
	 * @param string $col2  column 2 to be part of index
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table, true);
		sort($columns); //else we get multiple indexes due to order-effects
		foreach($columns as $k=>$v) {
			$columns[$k]=$this->safeColumn($v);
		}
		$r = $this->adapter->get("SELECT
									i.relname as index_name
								FROM
									pg_class t,
									pg_class i,
									pg_index ix,
									pg_attribute a
								WHERE
									t.oid = ix.indrelid
									AND i.oid = ix.indexrelid
									AND a.attrelid = t.oid
									AND a.attnum = ANY(ix.indkey)
									AND t.relkind = 'r'
									AND t.relname = '$table'
								ORDER BY  t.relname,  i.relname;");

		/*
		 *
		 * ALTER TABLE testje ADD CONSTRAINT blabla UNIQUE (blaa, blaa2);
		*/

		$name = "UQ_".sha1($table.implode(',',$columns));
		if ($r) {
			foreach($r as $i) {
				if (strtolower( $i["index_name"] )== strtolower( $name )) {
					return;
				}
			}
		}

		$sql = "ALTER TABLE \"$table\"
                ADD CONSTRAINT $name UNIQUE (".implode(",",$columns).")";



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
		if ($state == "42P01") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE;
		if ($state == "42703") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN;
		return in_array($sqlState, $list);
	}

	/**
	 * Returns a snippet of SQL to filter records using SQL and a list of
	 * keys.
	 *
	 * @param string  $idfield ID Field to use for selecting primary key
	 * @param array   $keys		List of keys to use for filtering
	 * @param string  $sql		SQL to append, if any
	 * @param boolean $inverse Whether you want to inverse the selection
	 *
	 * @return string $snippet SQL Snippet crafted by function
	 */
	public function getSQLSnippetFilter( $idfield, $keys, $sql=null, $inverse=false ) {
		if (!$sql) $sql=" TRUE ";
		if (!$inverse && count($keys)===0) return " TRUE ";
		$idfield = $this->noKW($idfield);
		$sqlInverse = ($inverse) ? "NOT" : "";
		$sqlKeyFilter = ($keys) ? " $idfield $sqlInverse IN (".implode(",",$keys).") AND " : " ";
		$sqlSnippet = $sqlKeyFilter . $sql;
		return $sqlSnippet;
	}


}
