<?php

class RedBean_QueryWriter_OCI extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {

    protected $adapter;
	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. 

	/**
	 * DATA TYPE
	 * Boolean Data type
	 * @var integer
	 */
	const C_DATATYPE_BOOL = 0;

	/**
	 *
	 * DATA TYPE
	 * Unsigned 8BIT Integer
	 * @var integer
	 */
	const C_DATATYPE_UINT8 = 1;

	/**
	 *
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
	 * 
	 * DATA TYPE
	 * 32BIT long textfield (number of characters can be as high as 32BIT) Data type
	 * This is the biggest column that RedBean supports. If possible you may write
	 * an implementation that stores even bigger values.
	 * @var integer
	 */
	const C_DATATYPE_TEXT32 = 6;

	/**
	 * Special type date for storing date values: YYYY-MM-DD
	 * @var integer
	 */	
	const C_DATATYPE_SPECIAL_DATE = 80;
	
	/**
	 * Special type datetime for store date-time values: YYYY-MM-DD HH:II:SS
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	

	/**
	 * 
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;

	/**
	 * Spatial types
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_POINT = 100;
	const C_DATATYPE_SPECIAL_LINESTRING = 101;
	const C_DATATYPE_SPECIAL_GEOMETRY = 102;
	const C_DATATYPE_SPECIAL_POLYGON = 103;
	const C_DATATYPE_SPECIAL_MULTIPOINT = 104;
	const C_DATATYPE_SPECIAL_MULTIPOLYGON = 105;
	const C_DATATYPE_SPECIAL_GEOMETRYCOLLECTION = 106;	

    public function __construct(RedBean_Adapter $a) {
		
        $this->adapter = $a;
		$this->typeno_sqltype = array(
			  RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL=>"  NUMBER(1,0)  ",
			  RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8=>' NUMBER(3,0)  ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32=>' NUMBER(11, 0) ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE=>' BINARY_FLOAT ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8=>' NVARCHAR2(255) ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16=>' NVARCHAR2(4000) ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32=>' CLOB ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE=>' DATE ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATETIME=>' DATE ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POINT=>' POINT ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_LINESTRING=>' LINESTRING  ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_GEOMETRY=>' GEOMETRY ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POLYGON=>' POLYGON ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_MULTIPOINT=>' MULTIPOINT ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_MULTIPOLYGON=>' MULTIPOLYGON ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_GEOMETRYCOLLECTION=>' GEOMETRYCOLLECTION ',
		);
		
		$this->sqltype_typeno = array();
		foreach($this->typeno_sqltype as $k=>$v)
		$this->sqltype_typeno[trim(strtolower($v))]=$k;		
    }

    public function addUniqueIndex($table, $columns) {
        
    }


    public function getTables() {
            return $this->adapter->getCol( 'SELECT LOWER(table_name) FROM user_tables' );
    }

	/**
	 * Creates an empty, column-less table for a bean based on it's type.
	 * This function creates an empty table for a bean. It uses the
	 * safeTable() function to convert the type name to a table name.
	 * For oracle we have to create a sequence and a trigger to get
	 * the autoincrement feature.
	 *
	 * @param string $table type of bean you want to create a table for
	 *
	 * @return void
	 */
    public function createTable($table) {
  
        $table = $this->safeTable($table);
        $sql = "    CREATE TABLE $table(
                        ID NUMBER(10) NOT NULL,  
                        CONSTRAINT ".$table."_PK PRIMARY KEY (ID)
                )";
        $this->adapter->exec($sql);
        
        $sql = "
            CREATE SEQUENCE ".$table."_SEQ
            START WITH 1 
            INCREMENT BY 1
            NOCACHE";
        $this->adapter->exec($sql);
        $sql = "
            CREATE OR REPLACE TRIGGER ".$table."_SEQ_TRI
            BEFORE INSERT ON $table
            FOR EACH ROW
            BEGIN
            SELECT ".$table."_SEQ.NEXTVAL
            INTO   :NEW.ID
            FROM   DUAL;
            END ".$table."_SEQ_TRI;";   
         $this->adapter->exec($sql); 
		 
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

		$suffix = $this->getInsertSuffix($table);
		$table = $this->safeTable($table);
		if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
				$insertcolumns[$k] = $this->safeColumn($v);
			}
			$insertSQL = "INSERT INTO $table (  ".implode(',',$insertcolumns)." ) VALUES 
			(  ". implode(',',array_fill(0,count($insertcolumns),' ? '))." ) $suffix";

			foreach($insertvalues as $i=>$insertvalue) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
			}
			$result = count($ids)===1 ? array_pop($ids) : $ids;
		}
		else {
			$result = $this->adapter->getCell( "INSERT INTO $table (id) VALUES($default) $suffix");
		}
		if ($suffix) return $result;
		$last_id = $this->adapter->getInsertID();
		return $last_id;
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
	 * @param boolean $all     IF TRUE suppress WHERE keyword, omitting WHERE clause
	 *
	 * @return array $records selected records
	 */
//	public function selectRecord( $type, $conditions, $addSql=null, $delete=null, $inverse=false, $all=false ) { 
//		$records = parent::selectRecord( $type, $conditions, $addSql, $delete, $inverse, $all );
//		//foreach($records as $record)
//			
//	}	
    public function getColumns($table) {
		$table = $this->safeTable($table);
		$columnsRaw = $this->adapter->get("SELECT LOWER(COLUMN_NAME) COLUMN_NAME, DATA_TYPE, DATA_LENGTH FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = UPPER('$table')");
		
		foreach($columnsRaw as $r) {
			$field = $r['column_name'];
			switch($r['data_type']) {
				case 'NUMBER':
					$columns[$field]=$r['data_type'].'('.$r['data_length'].',0)';
					break;
				case 'NVARCHAR2':
					$columns[$field]=$r['data_type'].'('.$r['data_length'].')';
					break;
				case 'BINARY_FLOAT':
				case 'CLOB':
				case 'DATE':
					$columns[$field]=$r['data_type'];
					break;

			}			
		}
		return $columns;
    }

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription, $includeSpecials = false ) {
		$r = ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED);
		if ($includeSpecials) return $r;
		if ($r > self::C_DATATYPE_SPECIFIED) return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	
    public function widenColumn($table, $column, $type) {
        throw new RedBean_Exception_SQL("This extension is for frozen mode only.");
    }



    public function deleteRecord($table, $id) {
        $this->deleteRecordArguments = array($table, "id", $id);
        return $this->returnDeleteRecord;
    }

    /*
      public function checkChanges($type, $id, $logid){
      $this->checkChangesArguments = array($type, $id, $logid);
      return $this->returnCheckChanges;
      }
      public function addUniqueIndex( $table,$columns ){
      $this->addUniqueIndexArguments=array($table,$columns);
      return $this->returnAddUniqueIndex;
      }
     */

    public function selectByCrit($select, $table, $column, $value, $withUnion = false) {
        $this->selectByCritArguments = array($select, $table, $column, $value, $withUnion);
        return $this->returnSelectByCrit;
    }

    public function deleteByCrit($table, $crits) {
        $this->deleteByCrit = array($table, $crits);
        return $this->returnDeleteByCrit;
    }

    public function getIDField($type) {
        return "id";
    }

    public function noKW($str) {
        return preg_replace("/\W/", "", $str);
    }

    public function sqlStateIn($state, $list) {
        return true;
    }

    /**
     * Returns the Column Type Code (integer) that corresponds
     * to the given value type. This method is used to determine the minimum
     * column type required to represent the given value.
     *
     * @param string $value value
     *
     * @return integer $type type
     */
    public function scanType($value, $flagSpecial=false) {
		$this->svalue = $value;
		
		if (is_null($value)) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
		}
		
		if ($flagSpecial) {
			if (strpos($value,'POINT(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POINT;
			}
			if (strpos($value,'LINESTRING(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_LINESTRING;
			}
			if (strpos($value,'POLYGON(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POLYGON;
			}
			if (strpos($value,'MULTIPOINT(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_MULTIPOINT;
			}
			
			
			if (preg_match('/^\d{4}\-\d\d-\d\d$/',$value)) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE;
			}
			if (preg_match('/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/',$value)) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATETIME;
			}
		}
		$value = strval($value);
		if (!$this->startsWithZeros($value)) {

			if ($value=='1' || $value=='') {
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
		if (strlen($value) <= 4000) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16;
		}
		return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32;        
    }

}

?>
