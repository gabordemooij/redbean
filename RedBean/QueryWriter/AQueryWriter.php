<?php
/**
 * RedBean Abstract Query Writer
 * @file 		RedBean/QueryWriter/AQueryWriter.php
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

abstract class RedBean_AQueryWriter {


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
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanTable($type);
		return $type;
	}

	/**
	 * Sets the Bean Formatter to be used to handle
	 * custom/advanced DB<->Bean
	 * Mappings. This method has no return value.
	 *
	 * @param RedBean_IBeanFormatter $beanFormatter the bean formatter
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
		if ($nArgs>1) $safe = func_get_arg(1); else $safe = false;
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanID($type);
		return $safe ? $this->safeColumn($this->idfield) : $this->idfield;
	}
	
	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string $table escaped string
	 */
	public function check($table) {
		// if (strpos($table, '`')!==false || strpos($table, '"')!==false) { // maybe this?
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
	public function noKW($str) {
		$q = $this->quoteCharacter;
		return $q.$str.$q;
	}
	
	/**
	 * Adds a column of a given type to a table.
	 *
	 * @param string  $table  name of the table
	 * @param string  $column name of the column
	 * @param integer $type   type
	 *
	 */
	public function addColumn( $table, $column, $type ) {
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$type = $this->getFieldType($type);
		$sql = "ALTER TABLE $table ADD $column $type ";
		$this->adapter->exec( $sql );
	}
	
	/**
	 * Update a record using a series of update values.
	 *
	 * @param string  $table		  table
	 * @param array   $updatevalues update values
	 * @param integer $id			  primary key for record
	 */
	public function updateRecord( $table, $updatevalues, $id) {
		$idfield = $this->getIDField($table, true);
		$table = $this->safeTable($table);
		$sql = "UPDATE $table SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " {$this->safeColumn($uv["property"])} = ? ";
			//$v[]=strval( $uv["value"] );
			$v[]=$uv["value"];
		}
		$sql .= implode(",", $p ) ." WHERE $idfield = ".intval($id);
		$this->adapter->exec( $sql, $v );
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
	public function insertRecord( $table, $insertcolumns, $insertvalues ) {
		$default = $this->defaultValue;
		$idfield = $this->getIDField($table, true);
		$suffix = $this->getInsertSuffix($table);
		$table = $this->safeTable($table);
		if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
				$insertcolumns[$k] = $this->safeColumn($v);
			}
			$insertSQL = "INSERT INTO $table ( $idfield, ".implode(",",$insertcolumns)." ) VALUES ";
			$insertSQL .= "( $default, ". implode(",",array_fill(0,count($insertcolumns)," ? "))." ) $suffix";
			$first=true;
			
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
	 * Selects a record based on type and id.
	 *
	 * @param string  $type type
	 * @param integer $id   id
	 *
	 * @return array $row	resulting row or NULL if none has been found
	 */
	public function selectRecord($type, $ids) {
		$idfield = $this->getIDField($type, true);
		$table = $this->safeTable($type);
		$sql = "SELECT * FROM $table WHERE $idfield IN ( ".implode(',', array_fill(0, count($ids), " ? "))." )";
		$rows = $this->adapter->get($sql,$ids);
		return ($rows && is_array($rows) && count($rows)>0) ? $rows : NULL;
	}

	/**
	 * Deletes a record based on a table, column, value and operator
	 *
	 * @param string  $table  table
	 * @param integer $value  primary key id
	 *
	 * @todo validate arguments for security
	 */
	public function deleteRecord($table, $value) {
		$column = $this->getIDField($table, true);
		$table = $this->safeTable($table);
		
		$this->adapter->exec("DELETE FROM $table WHERE $column = ? ",array(strval($value)));
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
	 *
	 * @param string $select the column to be selected
	 * @param string $table  the table to select from
	 * @param string $column the column to compare the criteria value against
	 * @param string $value  the criterium value to match against
	 * @param boolean $union with union (default is false)
	 *
	 * @return array $array selected column with values
	 */
	public function selectByCrit( $select, $table, $column, $value, $withUnion=false ) {
		$table = $this->safeTable($table);
		$select = $this->safeColumn($select);
		$column = $this->safeColumn($column);
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
	 *
	 * @param string $table table
	 * @param array  $crits criteria
	 *
	 * @return integer $affectedRows num. of affected rows.
	 */
	public function deleteByCrit( $table, $crits ) {
		$table = $this->safeTable($table);
		$values = array();
		foreach($crits as $key=>$val) {
			$values[] = $this->adapter->escape($val);
			$conditions[] = $this->safeColumn($key) ."= ? ";
		}
		$sql = "DELETE FROM $table WHERE ".implode(" AND ", $conditions);
		return (int) $this->adapter->exec($sql, $values);
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
		if (!$sql) $sql=" 1 ";
		if (!$inverse && count($keys)===0) return " 0 ";
		$idfield = $this->noKW($idfield);
		$sqlInverse = ($inverse) ? "NOT" : "";
		$sqlKeyFilter = ($keys) ? " $idfield $sqlInverse IN (".implode(",",$keys).") AND " : " ";
		$sqlSnippet = $sqlKeyFilter . $sql;
		return $sqlSnippet;
	}

}
