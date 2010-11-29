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

	public $tableFormatter;
  
	/**
	 * @var string
	 * character to escape keyword table/column names
	 */
  protected $quoteCharacter = '';

	/**
	 * @var array
	 * Supported Column Types
	 */
	public $typeno_sqltype = array();
	
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
	// protected $idfield = "id";
	
	/**
	 * Returns the string identifying a table for a given type.
	 * @param string $type
	 * @return string $table
	 */
	public function getFormattedTableName($type) {
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanTable($type);
		return $type;
	}


	/**
	 * Sets the Bean Formatter to be used to handle
	 * custom/advanced DB<->Bean
	 * Mappings.
	 * @param RedBean_IBeanFormatter $beanFormatter
	 * @return void
	 */
	public function setBeanFormatter( RedBean_IBeanFormatter $beanFormatter ) {
		$this->tableFormatter = $beanFormatter;
	}
	
	/**
	 * Get sql column type
	 * @param int $type constant
	 * @return string sql type
	 */
	public function getFieldType( $type = null ) {
		if (array_key_exists($type, $this->typeno_sqltype)) { 
		  return $this->typeno_sqltype[$type];
	  }
	  else {
	    return "";
	  }
	}

  protected $idfield = "id";

	/**
	 * Returns the column name that should be used
	 * to store and retrieve the primary key ID.
	 * @param string $type
	 * @return string $idfieldtobeused
	 */
	public function getIDField( $type ) {
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanID($type);
		return $this->idfield;
	}
	
	/**
	 * Checks table name or column name
	 * @param string $table
	 * @return string $table
	 */
	public function check($table) {
		if (strpos($table,"`")!==false) throw new Redbean_Exception_Security("Illegal chars in table name");
		return $this->adapter->escape($table);
	}
	
// 	getTables()
// 	createTable()
// 	getColumns()
//  scanType()
	
	
	/**
	 * Adds a column of a given type to a table.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
	public function addColumn( $table, $column, $type ) {
		$q = $this->quoteCharacter;
		$table = $this->getFormattedTableName($table);
		$column = $this->check($column);
		$table = $this->check($table);
		$type = $this->getFieldType($type);
		$sql = "ALTER TABLE {$q}$table{$q} ADD {$q}$column{$q} $type ";
		$this->adapter->exec( $sql );
	}
	
	// code()
	// widenColumn()
	
	/**
	 * Update a record using a series of update values.
	 * @param string $table
	 * @param array $updatevalues
	 * @param integer $id
	 */
	public function updateRecord( $table, $updatevalues, $id) {
		$q = $this->quoteCharacter;
		$idfield = $this->getIDField($table);
		$table = $this->getFormattedTableName($table);
		$sql = "UPDATE ".$q.$this->adapter->escape($this->check($table)).$q." SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " ".$q.$uv["property"].$q." = ? ";
			//$v[]=strval( $uv["value"] );
			$v[]=$uv["value"];
		}
		$sql .= implode(",", $p ) ." WHERE $idfield = ".intval($id);
		$this->adapter->exec( $sql, $v );
	}
  
  protected $defaultValue = 'NULL';

  protected function getInsertSuffix ($table) {
    return "";
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
		$q = $this->quoteCharacter;
		$default = $this->defaultValue;
		$idfield = $this->getIDField($table);
		$suffix = $this->getInsertSuffix($table);
		$table = $this->getFormattedTableName($table);
		$table = $this->check($table);
		$table = $q.$table.$q;
		if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
				$insertcolumns[$k] = $q.$this->check($v).$q;
			}
			$insertSQL = "INSERT INTO $table ( $idfield, ".implode(",",$insertcolumns)." ) VALUES ";
			$insertSQL .= "( $default, ". implode(",",array_fill(0,count($insertcolumns)," ? "))." ) ".$suffix;
			$first=true;
			
			foreach($insertvalues as $i=>$insertvalue) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
			}
			$result = count($ids)===1 ? array_pop($ids) : $ids;
		}
		else {
			$result = $this->adapter->getCell( "INSERT INTO $table ($idfield) VALUES($default) ".$suffix );
		}
		if ($suffix) return $result;
	  $last_id = $this->adapter->getInsertID();
		return ($this->adapter->getErrorMsg()=="" ?  $last_id : 0);
	}
	
	/**
	 * Selects a record based on type and id.
	 * @param string $type
	 * @param integer $id
	 * @return array $row
	 */
	public function selectRecord($type, $ids) {
		$q = $this->quoteCharacter;
		$idfield = $this->getIDField($type);
		$type = $this->getFormattedTableName($type);
		$type=$this->check($type);
		$sql = "SELECT * FROM {$q}$type{$q} WHERE $idfield IN ( ".implode(',', array_fill(0, count($ids), " ? "))." )";
		$rows = $this->adapter->get($sql,$ids);
		return ($rows && is_array($rows) && count($rows)>0) ? $rows : NULL;
	}

	/**
	 * Deletes a record based on a table, column, value and operator
	 * @param string $table
	 * @param string $column
	 * @param mixed $value
	 * @param string $oper
	 * @todo validate arguments for security
	 */
	public function deleteRecord($table, $value) {
		$q = $this->quoteCharacter;
		$column = $this->getIDField($table);
		$table = $this->getFormattedTableName($table);
		$table = $this->check($table);
		
		$this->adapter->exec("DELETE FROM {$q}$table{$q} WHERE {$q}$column{$q} = ? ",array(strval($value)));
	}
	
	// addUniqueIndex()
	

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
		$table = $this->getFormattedTableName($table);
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
		$table = $this->getFormattedTableName($table);
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
		$q = $this->quoteCharacter;
		return $q.$str.$q;
	}
	
}
