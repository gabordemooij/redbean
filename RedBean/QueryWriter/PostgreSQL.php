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
        self::C_DATATYPE_BOOL=>" boolean ",
		self::C_DATATYPE_UINT8=>" smallint ",
        self::C_DATATYPE_UINT32=>" integer ",
		self::C_DATATYPE_DOUBLE=>" double precision ",
        self::C_DATATYPE_TEXT8=>" text ",
        self::C_DATATYPE_TEXT16=>" text ",
        self::C_DATATYPE_TEXT32=>" text "
    );

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
    public $sqltype_typeno = array(
	"boolean"=>self::C_DATATYPE_BOOL,
    "smallint"=>self::C_DATATYPE_UINT8,
    "integer"=>self::C_DATATYPE_UINT32,
    "double precision" => self::C_DATATYPE_DOUBLE,
    "text"=>self::C_DATATYPE_TEXT8,
    "text"=>self::C_DATATYPE_TEXT16,
    "text"=>self::C_DATATYPE_TEXT32
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
       

		
		$maxid = $this->adapter->getCell("SELECT MAX(id) FROM __log");
        $this->adapter->exec("DELETE FROM __log WHERE id < $maxid - 200 ");
    
	
		$addCastFunction = "
			create or replace function b2s( boolean ) 
			returns smallint as $$
			begin
				if $1=true then return 1;
				end if;
				return 0;
			end;
			$$ 
			language plpgsql
			immutable;";

		$addCastDefinition = "
			create cast ( boolean as smallint) with function b2s(boolean) AS IMPLICIT;
		";
		try {
			$this->adapter->exec($addCastFunction);
			$this->adapter->exec($addCastDefinition);

		}
		catch(Exception $e){}


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
			$v[]=strval( $this->adapter->escape( $uv["value"] ) );
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
        $r = $this->adapter->get("SHOW INDEX FROM \"$table\"");
        $name = "UQ_".sha1(implode(',',$columns));
        if ($r) {
            foreach($r as $i) {
                if ($i["Key_name"]==$name) {
                    return;
                }
            }
        }
		
        $sql = "ALTER IGNORE TABLE \"$table\"
                ADD UNIQUE INDEX $name (".implode(",",$columns).")";
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
		return "`".$str."`";
	}


}