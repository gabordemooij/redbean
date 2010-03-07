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
class RedBean_QueryWriter_SQLite implements RedBean_QueryWriter {


    /**
     *
     * @var RedBean_Adapter_DBAdapter
     */
    private $adapter;

	/**
	 * Indicates the field name to be used for primary keys;
	 * default is 'id'
	 * @var string
	 */
	private $idfield = "id";


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
	 * Checks table name or column name
	 * @param string $table
	 * @return string $table
	 */
	public function check($table) {
		if (strpos($table,"`")!==false) throw new RedBean_Exception_Security("Illegal chars in table name");
		return $this->adapter->escape($table);
	}

    /**
     * Constructor
     * The Query Writer Constructor also sets up the database
     * @param RedBean_Adapter_DBAdapter $adapter
     */
    public function __construct( RedBean_Adapter $adapter, $frozen = false ) {
		$this->adapter = $adapter;
    }


    /**
     * Returns all tables in the database
     * @return array $tables
     */
    public function getTables() {
        return $this->adapter->getCol( "SELECT name FROM sqlite_master
			WHERE type='table' AND name!='sqlite_sequence';" );
    }

	/**
	 * Creates an empty, column-less table for a bean.
	 * @param string $table
	 */
    public function createTable( $table ) {
		$idfield = $this->getIDfield($table);
		$table = $this->check($table);
		$sql = "
                     CREATE TABLE `$table` ( `$idfield` INTEGER PRIMARY KEY AUTOINCREMENT )
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
        $columnsRaw = $this->adapter->get("PRAGMA table_info('$table')");
		$columns = array();
        foreach($columnsRaw as $r) {
            $columns[$r["name"]]=1;
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
       return 1;
    }

	/**
	 * Adds a column of a given type to a table
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
    public function addColumn( $table, $column, $type ) {
		$column = $this->check($column);
		$table = $this->check($table);
        $sql = "ALTER TABLE `$table` ADD `$column` ";
        $this->adapter->exec( $sql );
    }

	/**
	 * Returns the Type Code for a Column Description
	 * @param string $typedescription
	 * @return integer $typecode
	 */
    public function code( $typedescription ) {
        return 1;
    }

	/**
	 * Change (Widen) the column to the give type.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
    public function widenColumn( $table, $column, $type ) {
       return true;
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
			$v[]=strval( $uv["value"] );
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
					$vs[] = strval( $v );
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
		
		$name = "UQ_".sha1(implode(',',$columns));
        $sql = "CREATE UNIQUE INDEX IF NOT EXISTS $name ON $table (".implode(",",$columns).")";
        $this->adapter->exec($sql);
    }


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


	public function deleteByCrit( $table, $crits ) {
		$table = $this->noKW($this->adapter->escape($table));
		$values = array();
		foreach($crits as $key=>$val) {
			$key = $this->noKW($this->adapter->escape($key));
			$values[] = $val;
			$conditions[] = $key ."= ? ";
		}
		$sql = "DELETE FROM $table WHERE ".implode(" AND ", $conditions);
		return $this->adapter->exec($sql, $values);
	}





	/**
	 * Puts keyword escaping symbols around string.
	 * @param string $str
	 * @return string $keywordSafeString
	 */
	public function noKW($str) {
		return "`".$str."`";
	}

	public function sqlStateIn($state, $list) {
		$sqlState = "0";
		if ($state == "HY000") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE;
		return in_array($sqlState, $list);
	}

}