<?php

class RedBean_QueryWriter_Oracle extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {

	protected $adapter;

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. 

	  /**
	 * character to escape keyword table/column names
	 * @var string
	 */
	protected $quoteCharacter = '"';

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

	
	public function safeColumn($c,$q=false) {
			return parent::safeColumn((!$q) ? strtoupper($c):$c,$q);
	}

	public function safeTable($type,$q=false) {
			return parent::safeTable((!$q) ? strtoupper($type) : $type,$q);
	}

	
	public function __construct(RedBean_Adapter $a) {

		$this->adapter = $a;
		$this->typeno_sqltype = array(
			RedBean_QueryWriter_Oracle::C_DATATYPE_BOOL => 'NUMBER(1,0)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_UINT8 => 'NUMBER(3,0)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32 => 'NUMBER(11,0)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_DOUBLE => 'FLOAT',
			RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT8 => 'NVARCHAR2(255)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT16 => 'NVARCHAR2(2000)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT32 => 'CLOB',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_DATE => 'DATE',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_DATETIME => 'DATE',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_POINT => 'POINT',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_LINESTRING => 'LINESTRING',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_GEOMETRY => 'GEOMETRY',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_POLYGON => 'POLYGON',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_MULTIPOINT => 'MULTIPOINT',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_MULTIPOLYGON => 'MULTIPOLYGON',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_GEOMETRYCOLLECTION => 'GEOMETRYCOLLECTION',
		);

		$this->sqltype_typeno = array();
		foreach ($this->typeno_sqltype as $k => $v)
			$this->sqltype_typeno[$v] = $k;
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
	public function addUniqueIndex($table, $columns) {
		$tableNoQuote = strtoupper($this->safeTable($table, true));
		$tableWithQuote = strtoupper($this->safeTable($table));
		sort($columns); //else we get multiple indexes due to order-effects
		foreach ($columns as $k => $v) {
			$columns[$k] = strtoupper($this->safeColumn($v, true));
		}
		$r = $this->adapter->get("SELECT INDEX_NAME FROM USER_INDEXES WHERE TABLE_NAME='$tableNoQuote' AND UNIQUENESS='UNIQUE'");
		$name = strtoupper('UQ_' . substr(sha1(implode(',', $columns)), 0, 20));
		if ($r) {
			foreach ($r as $i) {
				if ($i['index_name'] == $name) {
					return;
				}
			}
		}
		$sql = "ALTER TABLE $tableWithQuote
                ADD CONSTRAINT  $name UNIQUE (" . implode(',', $columns) . ")";
		$this->adapter->exec($sql);
	}

	/**
	 * Add the constraints for a specific database driver: Oracle.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected function constrain($table, $table1, $table2, $property1, $property2) {
		try {

			$table = strtoupper($this->safeTable($table));
			$table1 = strtoupper($this->safeTable($table1));
			$table2 = strtoupper($this->safeTable($table2));
			$property1 = strtoupper($this->safeColumn($property1));
			$property2 = strtoupper($this->safeColumn($property2));

			$fks = $this->adapter->getCell("
				SELECT COUNT(*)
		        FROM ALL_CONS_COLUMNS A JOIN ALL_CONSTRAINTS C  ON A.CONSTRAINT_NAME = C.CONSTRAINT_NAME 
			    WHERE LOWER(C.TABLE_NAME) = ? AND C.CONSTRAINT_TYPE = 'R'	
					  ", array($table));
			//already foreign keys added in this association table
			if ($fks > 0)
				return false;
			$columns = $this->getColumns($table);
			if ($this->code($columns[$property1]) !== RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32) {
				$this->widenColumn($table, $property1, RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32);
			}
			if ($this->code($columns[$property2]) !== RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32) {
				$this->widenColumn($table, $property2, edBean_QueryWriter_Oracle::C_DATATYPE_UINT32);
			}


			$sql = "
				ALTER TABLE " . $table . "
				ADD FOREIGN KEY($property1) references $table1(id) ON DELETE CASCADE";
			$this->adapter->exec($sql);
			$sql = "
				ALTER TABLE " . $table . "
				ADD FOREIGN KEY($property2) references $table2(id) ON DELETE CASCADE";
			$this->adapter->exec($sql);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Counts rows in a table.
	 *
	 * @param string $beanType
	 *
	 * @return integer $numRowsFound
	 */
	public function count($beanType) {
		return parent::count(strtoupper($beanType));
	}

	public function getTables() {
		return $this->adapter->getCol('SELECT LOWER(table_name) FROM user_tables');
	}

	/**
	 * This method should add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  $type   type to add index to
	 * @param  $name   name of the new index
	 * @param  $column field to index
	 *
	 * @return void
	 */
	public function addIndex($type, $name, $column) {
		$table = $type;
		$table = strtoupper($this->safeTable($table));
		$name = $this->limitOracleIdentifierLength(preg_replace('/\W/', '', $name));
		$column = strtoupper($this->safeColumn($column));
		try {
			$this->adapter->exec("CREATE INDEX $name ON $table ($column) ");
		} catch (Exception $e) {}
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
		if (strtolower($table) != $table) {
			throw new Exception($table . ' is not lowercase. With ORACLE you MUST only use lowercase table in PHP, sorry!');
		}
		$table_with_quotes = strtoupper($this->safeTable($table));
		$safe_table_without_quotes = strtoupper($this->safeTable($table, true));
		$sql = "CREATE TABLE $table_with_quotes(
                ID NUMBER(11) NOT NULL,  
                CONSTRAINT " . $safe_table_without_quotes . "_PK PRIMARY KEY (ID)
                )";
		$this->adapter->exec($sql);

		$sql =
				"CREATE SEQUENCE " . $safe_table_without_quotes . "_SEQ
            START WITH 1 
            INCREMENT BY 1
            NOCACHE";
		$this->adapter->exec($sql);
		$sql =
				"CREATE OR REPLACE TRIGGER " . $safe_table_without_quotes . "_SEQ_TRI
            BEFORE INSERT ON $table_with_quotes
            FOR EACH ROW
            BEGIN
            SELECT " . $safe_table_without_quotes . "_SEQ.NEXTVAL
            INTO   :NEW.ID
            FROM   DUAL;
            END " . $safe_table_without_quotes . "_SEQ_TRI;";
		$this->adapter->exec($sql);
	}

	/**
	 * This method adds a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 *
	 */
	public function addColumn($type, $column, $field) {
		// 
		$columnTested = preg_replace('/^((own)|(shared))./', '', $column);
		if (strtolower($columnTested) != $columnTested) {
			throw new Exception($column . ' is not lowercase. With ORACLE you MUST only use lowercase properties in PHP, sorry!');
		}
		parent::addColumn(strtoupper($type), strtoupper($column), $field);
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
	protected function insertRecord($table, $insertcolumns, $insertvalues) {
		foreach ($insertcolumns as &$col) {
			$col = strtoupper($col);
		}

		return parent::insertRecord(strtoupper($table), $insertcolumns, $insertvalues);
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

	public function getColumns($table) {
		$table = $this->safeTable($table, true);
		$columnsRaw = $this->adapter->get("SELECT LOWER(COLUMN_NAME) COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = UPPER('$table')");

		foreach ($columnsRaw as $r) {
			$field = $r['column_name'];
			switch ($r['data_type']) {
				case 'NUMBER':
					$columns[$field] = $r['data_type'] . '(' . ((int) $r['data_precision']) . ',0)';
					break;
				case 'NVARCHAR2':
					$columns[$field] = $r['data_type'] . '(' . ($r['data_length'] / 2) . ')';
					break;
				case 'FLOAT':
				case 'CLOB':
				case 'DATE':
					$columns[$field] = $r['data_type'];
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
	public function code($typedescription, $includeSpecials = false) {
		$r = ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED);
		if ($includeSpecials)
			return $r;
		if ($r > self::C_DATATYPE_SPECIFIED)
			return self::C_DATATYPE_SPECIFIED;
		return $r;
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
	public function widenColumn($type, $column, $datatype) {
		$table = $type;
		$type = $datatype;
		$table = strtoupper($this->safeTable($table));
		$column = strtoupper($this->safeColumn($column));
		$newtype = array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : '';
		$addTempColumn = "ALTER TABLE $table ADD (HOPEFULLYNOTEXIST $newtype)";
		$this->adapter->exec($addTempColumn);
		$updateTempColumn = "UPDATE $table SET HOPEFULLYNOTEXIST = $column";
		$this->adapter->exec($updateTempColumn);
		$this->adapter->exec("ALTER TABLE $table DROP COLUMN $column");
		$this->adapter->exec("ALTER TABLE $table RENAME COLUMN HOPEFULLYNOTEXIST TO $column");
	}

	public function deleteRecord($table, $id) {
		throw new Exception('Not defined');
		$this->deleteRecordArguments = array($table, "id", $id);
		return $this->returnDeleteRecord;
	}

	public function selectByCrit($select, $table, $column, $value, $withUnion = false) {
		throw new Exception('Not defined');
		$this->selectByCritArguments = array($select, $table, $column, $value, $withUnion);
		return $this->returnSelectByCrit;
	}

	public function deleteByCrit($table, $crits) {
		throw new Exception('Not defined');
		$this->deleteByCrit = array($table, $crits);
		return $this->returnDeleteByCrit;
	}

	public function getIDField($type) {
		return "id";
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
		$stateMap = array(
			RedBean_Driver_OCI::OCI_NO_SUCH_TABLE => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			RedBean_Driver_OCI::OCI_NO_SUCH_COLUMN => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_Driver_OCI::OCI_INTEGRITY_CONSTRAINT_VIOLATION => RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);
		return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'), $list);
	}

	private function limitOracleIdentifierLength($id) {
		return substr($id, 0, 30);
	}

	/**
	 * This method updates (or inserts) a record, it takes
	 * a table name, a list of update values ( $field => $value ) and an
	 * primary key ID (optional). If no primary key ID is provided, an
	 * INSERT will take place.
	 * Returns the new ID.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type         name of the table to update
	 * @param array   $updatevalues list of update values
	 * @param integer $id			optional primary key ID value
	 *
	 * @return integer $id the primary key ID value of the new record
	 */
	public function updateRecord($type, $updatevalues, $id = null) {
		foreach ($updatevalues as &$updatevalue) {
			$updatevalue['property'] = strtoupper($updatevalue['property']);
		}
		return parent::updateRecord(strtoupper($type), $updatevalues, $id);
	}

	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	public function addFK($type, $targetType, $field, $targetField, $isDependent = false) {
		$table = strtoupper($this->safeTable($type));
		$tableNoQ = strtoupper($this->safeTable($type, true));
		$targetTable = strtoupper($this->safeTable($targetType));
		$column = strtoupper($this->safeColumn($field));
		$columnNoQ = strtoupper($this->safeColumn($field, true));
		$targetColumn = strtoupper($this->safeColumn($targetField));
		$targetColumnNoQ = strtoupper($this->safeColumn($targetField, true));
		//$db = $this->adapter->getCell('select database()');
		$fkName = 'FK_' . ($isDependent ? 'C_' : '') . $tableNoQ . '_' . $columnNoQ . '_' . $targetColumnNoQ;
		$fkName = $this->limitOracleIdentifierLength($fkName);

		$cfks = $this->adapter->getCell("
			SELECT A.CONSTRAINT_NAME
		    FROM ALL_CONS_COLUMNS A JOIN ALL_CONSTRAINTS C  ON A.CONSTRAINT_NAME = C.CONSTRAINT_NAME 
			WHERE C.TABLE_NAME = '$tableNoQ' AND C.CONSTRAINT_TYPE = 'R'	AND COLUMN_NAME='$columnNoQ'");

		$flagAddKey = false;

		try {
			//No keys
			if (!$cfks) {
				$flagAddKey = true; //go get a new key
			}
			//has fk, but different setting, --remove
			if ($cfks && $cfks != $fkName) {
				$this->adapter->exec("ALTER TABLE $table DROP CONSTRAINT $cfks ");
				$flagAddKey = true; //go get a new key.
			}
			if ($flagAddKey) {
				$sql = "ALTER TABLE  $table
				ADD CONSTRAINT $fkName FOREIGN KEY (  $column ) REFERENCES  $targetTable (
				$targetColumn) ON DELETE " . ($isDependent ? 'CASCADE' : 'SET NULL');

				$this->adapter->exec($sql);
			}
		} catch (Exception $e) {} //Failure of fk-constraints is not a problem
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
	public function selectRecord($type, $conditions, $addSql = null, $delete = null, $inverse = false, $all = false) {
		$upperCaseConditions = array();
		foreach ($conditions as $column => $value) {
			$upperCaseConditions[strtoupper($column)] = $value;
		}

		return parent::selectRecord(strtoupper($type), $upperCaseConditions, $addSql, $delete, $inverse, $all);
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
	public function scanType($value, $flagSpecial = false) {
		$this->svalue = $value;

		if (is_null($value)) {
			return RedBean_QueryWriter_Oracle::C_DATATYPE_BOOL;
		}

		if ($flagSpecial) {
			if (strpos($value, 'POINT(') === 0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)', array($value));
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_POINT;
			}
			if (strpos($value, 'LINESTRING(') === 0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)', array($value));
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_LINESTRING;
			}
			if (strpos($value, 'POLYGON(') === 0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)', array($value));
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_POLYGON;
			}
			if (strpos($value, 'MULTIPOINT(') === 0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)', array($value));
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_MULTIPOINT;
			}


			if (preg_match('/^\d{4}\-\d\d-\d\d$/', $value)) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_DATE;
			}
			if (preg_match('/^\d{4}\-\d\d-\d\d\s\d\d:\d\d(:\d\d)?$/', $value)) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_DATETIME;
			}
		}
		$value = strval($value);
		if (!$this->startsWithZeros($value)) {

			if ($value == '1' || $value == '') {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_BOOL;
			}
			if (is_numeric($value) && (floor($value) == $value) && $value >= 0 && $value <= 255) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_UINT8;
			}
			if (is_numeric($value) && (floor($value) == $value) && $value >= 0 && $value <= 4294967295) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32;
			}
			if (is_numeric($value)) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_DOUBLE;
			}
		}
		if (strlen($value) <= 255) {
			return RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT8;
		}
		if (strlen($value) <= 2000) {
			return RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT16;
		}
		return RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT32;
	}

	/**
	 * This method removes all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe($type) {
		$table = $type;
		$table = $this->safeTable($table);
		$sql = "TRUNCATE TABLE $table ";
		$this->adapter->exec($sql);
	}

	/**
	 * Drops all tables in database
	 */
	public function wipeAll() {
		$this->adapter->exec("
			BEGIN

			--Bye Sequences!
			FOR i IN (SELECT us.sequence_name
						FROM USER_SEQUENCES us) LOOP
				EXECUTE IMMEDIATE 'drop sequence \"'|| i.sequence_name ||'\"';
			END LOOP;

			--Bye Tables!
			FOR i IN (SELECT ut.table_name
						FROM USER_TABLES ut) LOOP
				EXECUTE IMMEDIATE 'drop table \"'|| i.table_name ||'\" CASCADE CONSTRAINTS ';
			END LOOP;

			END;");
	}

}

?>
