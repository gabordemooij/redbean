<?php
/**
 * RedBean MySQLWriter
 * @package 		RedBean/QueryWriter/PostgreSQL.php
 * @description		Represents a PostgreSQL Database to RedBean
 *			To write a driver for a different database for RedBean
 *			you should only have to change this file.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_QueryWriter_PostgreSQL implements RedBean_QueryWriter {



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
     */
    private $adapter;


	/**
	 * Checks table name or column name
	 * @param string $table
	 * @return string $table
	 */
	public function check($table) {
		if (strpos($table,"`")!==false) throw new Redbean_Exception_Security("Illegal chars in table name");
		return $this->adapter->escape($table);
	}

    /**
     * Constructor
     * The Query Writer Constructor also sets up the database
     * @param RedBean_DBAdapter $adapter
     */
    public function __construct( RedBean_Adapter_DBAdapter $adapter ) {
        $this->adapter = $adapter;
    }



    /**
     * Returns all tables in the database
     * @return array $tables
     */
    public function getTables() {
        return $this->adapter->getCol( "select table_name from information_schema.tables
where table_schema = 'public'" );
    }

	/**
	 * Creates an empty, column-less table for a bean.
	 * @param string $table
	 */
    public function createTable( $table ) {
		$table = $this->check($table);
		$sql = "
                     CREATE TABLE \"$table\" (
						id SERIAL PRIMARY KEY
                     );
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
        $columnsRaw = $this->adapter->get("select column_name, data_type from information_schema.columns where table_name='$table'");
        

		foreach($columnsRaw as $r) {
            $columns[$r["column_name"]]=$r["data_type"];
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
		if (is_integer($value) && $value < 2147483648 && $value > -2147483648) {
			return self::C_DATATYPE_INTEGER;
		}
		elseif( is_double($value) ) {
			return self::C_DATATYPE_DOUBLE;
		}
		else {
			return self::C_DATATYPE_TEXT;
		}
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
        $type=$this->typeno_sqltype[$type];
        $sql = "ALTER TABLE \"$table\" ADD $column $type ";
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
        $changecolumnSQL = "ALTER TABLE \"$table\" \n\t ALTER COLUMN $column TYPE $newtype ";
        try { $this->adapter->exec( $changecolumnSQL ); }catch(Exception $e){ die($e->getMessage()); }
    }

	/**
	 * Update a record using a series of update values.
	 * @param string $table
	 * @param array $updatevalues
	 * @param integer $id
	 */
    public function updateRecord( $table, $updatevalues, $id) {
		$sql = "UPDATE \"".$this->adapter->escape($this->check($table))."\" SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " \"".$uv["property"]."\" = ? ";
			$v[]=strval( ( $uv["value"] ) );
		}
		$sql .= implode(",", $p ) ." WHERE id = ".intval($id);
		
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
		$table = $this->check($table);
        if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
                $insertcolumns[$k] = "".$this->check($v)."";
            }
			$insertSQL = "INSERT INTO \"$table\" ( id, ".implode(",",$insertcolumns)." ) VALUES ";
			$insertSQL .= "( DEFAULT, ". implode(",",array_fill(0,count($insertcolumns)," ? "))." ) RETURNING id";
			
			$ids = array();
			foreach($insertvalues as $insertvalue) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue );
			}
			if (count($ids)===1) return array_pop($ids); else	return $ids;
			
        }
        else {
			return $this->adapter->getCell( "INSERT INTO \"$table\" (id) VALUES(DEFAULT) RETURNING id " );
        }
    }



	/**
	 * Selects a record based on type and id.
	 * @param string $type
	 * @param integer $id
	 * @return array $row
	 */
    public function selectRecord($type, $ids) {
		$type=$this->check($type);
		$sql = "SELECT * FROM $type WHERE id IN ( ".implode(',', array_fill(0, count($ids), " ? "))." )";
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
    public function deleteRecord( $table, $value) {
		$table = $this->check($table);
		$column = "id";
	    $this->adapter->exec("DELETE FROM $table WHERE $column = ? ",array(strval($value)));
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
	 * @param  string $type
	 * @param  integer $id
	 * @param  integer $logid
	 * @return integer $newchangeid
	 */
    public function checkChanges($type, $id, $logid) {
		$type = $this->check($type);
		$id = (int) $id;
		$logid = (int) $logid;
		$num = $this->adapter->getCell("
        SELECT count(*) FROM __log WHERE tbl=\"$type\" AND itemid=$id AND action=2 AND id > $logid");
        if ($num) {
			throw new RedBean_Exception_FailedAccessBean("Locked, failed to access (type:$type, id:$id)");
		}
		$newid = $this->insertRecord("__log",array("action","tbl","itemid"),
		   array(array(2,  $type, $id)));
	    if ($this->adapter->getCell("select id from __log where tbl=:tbl AND id < $newid and id > $logid and action=2 and itemid=$id ",
			array(":tbl"=>$type))){
			throw new RedBean_Exception_FailedAccessBean("Locked, failed to access II (type:$type, id:$id)");
		}
		return $newid;
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
		foreach($columns as $k=>$v){
			$columns[$k]="".$this->adapter->escape($v)."";
		}
		$table = $this->adapter->escape( $this->check($table) );
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

        $name = "UQ_".sha1(implode(',',$columns));
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
	 * Returns the column name that should be used
	 * to store and retrieve the primary key ID.
	 * @param string $type
	 * @return string $idfieldtobeused
	 */
	public function getIDField( $type ) {
		return  "id";
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
		return "\"".$str."\"";
	}



	/**
	 * Given an Database Specific SQLState and a list of QueryWriter
	 * Standard SQL States this function converts the raw SQL state to a
	 * database agnostic ANSI-92 SQL states and checks if the given state
	 * is in the list of agnostic states.
	 * @param string $state
	 * @param array $list
	 * @return boolean $isInArray
	 */
	public function sqlStateIn($state, $list) {

		$sqlState = "0";
		if ($state == "42P01") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE;
		if ($state == "42703") $sqlState = RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN;
		return in_array($sqlState, $list);
	}


}